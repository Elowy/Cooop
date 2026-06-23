<?php

declare(strict_types=1);

/**
 * Front controller – minden kérés ide fut be a .htaccess átírás miatt.
 */

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $file = dirname(__DIR__) . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

use App\Catalog\Categories;
use App\Core\Auth;
use App\Core\Cart;
use App\Core\Csrf;
use App\Core\Router;
use App\Core\View;
use App\Db\Database;
use App\Db\Schema;
use App\User\UserRepository;
use App\Integration\MockAxelGateway;
use App\Map\PoiStore;
use App\Message\MessageStore;
use App\Order\OrderStore;
use App\Payment\MockPaymentGateway;
use App\Reference\ReferenceStore;
use App\Settings\SettingsStore;

$config = require dirname(__DIR__) . '/config/config.php';

if ($config['app']['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

session_start();

// Az Axel-kapu. A config 'gateway' alapján választunk megvalósítást; éles
// bekötéskor az 'xml' (helyi mappás) vagy 'rest' adapter kerül a mock helyére.
$axel = match ($config['axel']['gateway'] ?? 'mock') {
    // 'xml' => new App\Integration\XmlAxelGateway($config['axel']['exchange_dir']),
    default => new MockAxelGateway(),
};

// Kategóriafa (config/categories.php).
$cats = new Categories();

// Rendeléstár, üzenettár és fizetési szolgáltató (utóbbi a config alapján választva).
$orders = new OrderStore($config['shop']['orders_dir']);
$messages = new MessageStore($config['contact']['messages_dir']);

// Oldal-beállítások, referenciák és térkép-pontok.
$settings = new SettingsStore();
$references = new ReferenceStore();
$pois = new PoiStore();

// Adatbázis és felhasználók – csak ha a telepítő már lefutott.
$users = null;
if ($config['installed']) {
    try {
        $users = new UserRepository(Database::instance($config['db']));
    } catch (\Throwable $e) {
        $users = null; // DB nem elérhető; a belépés ilyenkor nem működik
    }
}

// Referencia-logó feltöltése a public/uploads/references mappába.
$uploadsDir = dirname(__DIR__) . '/public/uploads/references';
$uploadLogo = static function (array $file) use ($uploadsDir): ?string {
    if (($file['error'] ?? 1) !== UPLOAD_ERR_OK || empty($file['tmp_name'])) {
        return null;
    }
    if (($file['size'] ?? 0) > 3 * 1024 * 1024) {
        return null; // max 3 MB
    }
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $info = @getimagesize($file['tmp_name']);
    $mime = $info['mime'] ?? '';
    if (!isset($allowed[$mime])) {
        return null;
    }
    if (!is_dir($uploadsDir)) {
        @mkdir($uploadsDir, 0775, true);
    }
    $name = bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    return move_uploaded_file($file['tmp_name'], $uploadsDir . '/' . $name) ? $name : null;
};
$payment = match ($config['shop']['payment'] ?? 'mock') {
    // 'simplepay' => new App\Payment\SimplePayGateway(...),  // éles bekötéskor
    default => new MockPaymentGateway(),
};

/**
 * A kosár tartalmát feloldja rendelési tételekké az aktuális (Axel) árakkal.
 * @return array{items: array<int, array<string, mixed>>, total: int}
 */
$buildCart = static function () use ($axel): array {
    $items = [];
    $total = 0;
    foreach (Cart::items() as $sku => $qty) {
        foreach ($axel->products() as $p) {
            if ($p->sku === $sku) {
                $sub = $p->priceGross() * $qty;
                $total += $sub;
                $items[] = [
                    'sku' => $p->sku, 'name' => $p->name, 'unit' => $p->unit,
                    'qty' => $qty, 'price_gross' => $p->priceGross(), 'subtotal' => $sub,
                    'stock' => $p->stock,
                ];
                break;
            }
        }
    }
    return ['items' => $items, 'total' => $total];
};

/** Átirányítás + futás leállítása (POST műveletek után). */
$redirect = static function (string $to): string {
    header('Location: ' . $to, true, 303);
    exit;
};

$router = new Router();

$router->get('/', static fn (): string => View::render('home', [
    'title' => null,
    'featured' => array_slice($axel->products(), 0, 3),
    'topCats' => $cats->topLevel(),
    'references' => $references->all(),
]));

/* ------------------------------------------------------------------ */
/* Telepítő                                                            */
/* ------------------------------------------------------------------ */

$router->get('/telepito', static function () use ($config): string {
    return View::render('install', [
        'title' => 'Telepítő',
        'done' => (bool) $config['installed'],
        'errors' => [],
        'old' => [],
    ], '');
});

$router->post('/telepito', static function () use ($config, $redirect): string {
    if ($config['installed']) {
        return $redirect('/');
    }
    if (!Csrf::check($_POST['_csrf'] ?? null)) {
        return $redirect('/telepito');
    }

    $driver = in_array($_POST['driver'] ?? 'mysql', ['mysql', 'sqlite'], true) ? (string) $_POST['driver'] : 'mysql';
    $dbcfg = [
        'driver' => $driver,
        'host' => trim((string) ($_POST['host'] ?? 'localhost')),
        'port' => trim((string) ($_POST['port'] ?? '3306')),
        'name' => trim((string) ($_POST['name'] ?? '')),
        'user' => trim((string) ($_POST['user'] ?? '')),
        'pass' => (string) ($_POST['pass'] ?? ''),
    ];
    $adminName = trim((string) ($_POST['admin_name'] ?? ''));
    $adminEmail = trim((string) ($_POST['admin_email'] ?? ''));
    $adminPass = (string) ($_POST['admin_password'] ?? '');

    $errors = [];
    if ($driver === 'mysql' && $dbcfg['name'] === '') {
        $errors[] = 'Add meg az adatbázis nevét.';
    }
    if ($adminName === '') {
        $errors[] = 'Add meg az admin nevét.';
    }
    if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Érvényes admin e-mail cím szükséges.';
    }
    if (strlen($adminPass) < 6) {
        $errors[] = 'Az admin jelszó legalább 6 karakter legyen.';
    }

    if (!$errors) {
        try {
            $pdo = Database::make($dbcfg);
            Schema::create($pdo, $driver);
            $repo = new UserRepository($pdo);
            if ($repo->findByEmail($adminEmail) === null) {
                $repo->create($adminName, $adminEmail, password_hash($adminPass, PASSWORD_DEFAULT), 'admin');
            }
            file_put_contents(
                dirname(__DIR__) . '/config/db.php',
                "<?php\n\nreturn " . var_export($dbcfg, true) . ";\n"
            );
            $user = $repo->findByEmail($adminEmail);
            if ($user !== null) {
                Auth::loginUser($user);
            }
            return $redirect('/admin');
        } catch (\Throwable $e) {
            $errors[] = 'Adatbázis hiba: ' . $e->getMessage();
        }
    }

    return View::render('install', ['title' => 'Telepítő', 'done' => false, 'errors' => $errors, 'old' => $_POST], '');
});

$router->get('/referencia/{id}', static function (array $params) use ($references): string {
    $ref = $references->find((int) ($params['id'] ?? 0));
    if ($ref === null) {
        http_response_code(404);
        return View::render('errors/404', ['title' => 'A referencia nem található']);
    }
    return View::render('reference', ['title' => (string) $ref['name'], 'ref' => $ref]);
});

$router->get('/terkep', static function () use ($pois): string {
    return View::render('map', ['title' => 'Térkép', 'pois' => $pois->all()]);
});

$router->get('/kapcsolat', static function (): string {
    return View::render('contact', [
        'title' => 'Kapcsolat',
        'sent' => isset($_GET['elkuldve']),
        'errors' => [],
        'old' => [],
    ]);
});

$router->post('/kapcsolat', static function () use ($config, $messages, $redirect): string {
    if (!Csrf::check($_POST['_csrf'] ?? null)) {
        return $redirect('/kapcsolat');
    }
    $val = static fn (string $k): string => trim((string) ($_POST[$k] ?? ''));
    $errors = [];
    $old = $_POST;

    if ($val('name') === '') {
        $errors['name'] = 'A név megadása kötelező.';
    }
    if ($val('email') === '' || !filter_var($val('email'), FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Érvényes e-mail cím szükséges.';
    }
    if ($val('message') === '') {
        $errors['message'] = 'Az üzenet megadása kötelező.';
    }
    if (!isset($_POST['privacy'])) {
        $errors['privacy'] = 'Az adatkezelési tájékoztató elfogadása kötelező.';
    }

    if ($errors) {
        return View::render('contact', ['title' => 'Kapcsolat', 'sent' => false, 'errors' => $errors, 'old' => $old]);
    }

    $msg = [
        'created' => date('c'),
        'name' => $val('name'), 'email' => $val('email'), 'phone' => $val('phone'),
        'subject' => $val('subject'), 'message' => $val('message'),
    ];
    $messages->save($msg);

    // E-mail értesítés (ha a szerver tudja küldeni; az üzenet ettől függetlenül tárolódik).
    $subject = mb_encode_mimeheader('Új üzenet a weboldalról' . ($msg['subject'] !== '' ? ': ' . $msg['subject'] : ''), 'UTF-8');
    $body = "Név: {$msg['name']}\nE-mail: {$msg['email']}\nTelefon: {$msg['phone']}\n"
        . "Tárgy: {$msg['subject']}\n\nÜzenet:\n{$msg['message']}\n";
    $headers = "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n"
        . 'From: weboldal@' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\nReply-To: {$msg['email']}";
    @mail($config['contact']['email'], $subject, $body, $headers);

    return $redirect('/kapcsolat?elkuldve=1');
});

$router->get('/webshop', static function () use ($axel, $cats): string {
    $activeCat = isset($_GET['kat']) ? (string) $_GET['kat'] : '';
    $products = $axel->products();
    $path = [];

    if ($activeCat !== '' && $cats->find($activeCat) !== null) {
        $branch = $cats->branch($activeCat);
        $products = array_values(array_filter($products, static fn ($p) => in_array($p->category, $branch, true)));
        $path = $cats->path($activeCat);
    } else {
        $activeCat = '';
    }

    return View::render('shop/index', [
        'title' => $activeCat !== '' ? $cats->name($activeCat) : 'Webshop',
        'products' => $products,
        'catsTree' => $cats->tree(),
        'activeCat' => $activeCat,
        'activePath' => $path,
        'cats' => $cats,
    ]);
});

$router->get('/termek/{slug}', static function (array $params) use ($axel, $cats): string {
    $product = $axel->findProduct($params['slug'] ?? '');
    if ($product === null) {
        http_response_code(404);
        return View::render('errors/404', ['title' => 'A termék nem található']);
    }
    return View::render('shop/show', [
        'title' => $product->name,
        'product' => $product,
        'catPath' => $cats->path($product->category),
        'cats' => $cats,
    ]);
});

$router->get('/kosar', static function () use ($axel): string {
    $lines = [];
    $total = 0;
    foreach (Cart::items() as $sku => $qty) {
        foreach ($axel->products() as $p) {
            if ($p->sku === $sku) {
                $sub = $p->priceGross() * $qty;
                $total += $sub;
                $lines[] = ['product' => $p, 'qty' => $qty, 'subtotal' => $sub];
                break;
            }
        }
    }
    return View::render('cart/index', ['title' => 'Kosár', 'lines' => $lines, 'total' => $total]);
});

$router->post('/kosar/hozzaad', static function () use ($redirect): string {
    if (Csrf::check($_POST['_csrf'] ?? null) && isset($_POST['sku'])) {
        Cart::add((string) $_POST['sku'], max(1, (int) ($_POST['qty'] ?? 1)));
    }
    return $redirect('/kosar');
});

$router->post('/kosar/frissit', static function () use ($redirect): string {
    if (Csrf::check($_POST['_csrf'] ?? null) && isset($_POST['sku'])) {
        Cart::set((string) $_POST['sku'], (int) ($_POST['qty'] ?? 0));
    }
    return $redirect('/kosar');
});

$router->post('/kosar/torol', static function () use ($redirect): string {
    if (Csrf::check($_POST['_csrf'] ?? null) && isset($_POST['sku'])) {
        Cart::remove((string) $_POST['sku']);
    }
    return $redirect('/kosar');
});

/* ------------------------------------------------------------------ */
/* Pénztár                                                             */
/* ------------------------------------------------------------------ */

/** Rendelés átadása az Axelnek számlázásra; az eredményt elmenti. */
$finalizeInvoice = static function (string $token) use ($axel, $orders): void {
    $order = $orders->find($token);
    if ($order === null) {
        return;
    }
    $result = $axel->createInvoice($order);
    $orders->update($token, ['invoice' => [
        'ok' => $result->ok, 'number' => $result->invoiceNumber, 'message' => $result->message,
    ]]);
};

$router->get('/penztar', static function () use ($buildCart, $payment, $redirect): string {
    $cart = $buildCart();
    if (!$cart['items']) {
        return $redirect('/kosar');
    }
    return View::render('shop/checkout', [
        'title' => 'Pénztár',
        'cart' => $cart,
        'paymentLabel' => $payment->label(),
        'errors' => [],
        'old' => [],
    ]);
});

$router->post('/penztar', static function () use ($buildCart, $orders, $payment, $finalizeInvoice, $redirect): string {
    $cart = $buildCart();
    if (!$cart['items']) {
        return $redirect('/kosar');
    }
    if (!Csrf::check($_POST['_csrf'] ?? null)) {
        return $redirect('/penztar');
    }

    $val = static fn (string $k): string => trim((string) ($_POST[$k] ?? ''));
    $old = $_POST;
    $errors = [];

    foreach (['name' => 'Név', 'email' => 'E-mail', 'phone' => 'Telefon',
              'billing_zip' => 'Irányítószám', 'billing_city' => 'Város', 'billing_address' => 'Cím'] as $f => $label) {
        if ($val($f) === '') {
            $errors[$f] = "A(z) „{$label}” megadása kötelező.";
        }
    }
    if ($val('email') !== '' && !filter_var($val('email'), FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Érvénytelen e-mail cím.';
    }
    $method = in_array($val('payment_method'), ['card', 'transfer'], true) ? $val('payment_method') : '';
    if ($method === '') {
        $errors['payment_method'] = 'Válassz fizetési módot.';
    }
    if (!isset($_POST['terms'])) {
        $errors['terms'] = 'Az ÁSZF elfogadása kötelező.';
    }
    $shipDiff = isset($_POST['shipping_diff']);
    if ($shipDiff) {
        foreach (['shipping_zip' => 'Irányítószám', 'shipping_city' => 'Város', 'shipping_address' => 'Cím'] as $f => $label) {
            if ($val($f) === '') {
                $errors[$f] = "A szállítási „{$label}” megadása kötelező.";
            }
        }
    }
    foreach ($cart['items'] as $it) {
        if ($it['qty'] > $it['stock']) {
            $errors['stock'] = "Nincs elég készlet: {$it['name']} (elérhető: {$it['stock']} {$it['unit']}).";
        }
    }

    if ($errors) {
        return View::render('shop/checkout', [
            'title' => 'Pénztár', 'cart' => $cart, 'paymentLabel' => $payment->label(),
            'errors' => $errors, 'old' => $old,
        ]);
    }

    $token = bin2hex(random_bytes(16));
    $order = [
        'token' => $token,
        'number' => $orders->nextNumber(),
        'created' => date('c'),
        'status' => $method === 'card' ? 'pending' : 'placed',
        'payment' => [
            'method' => $method,
            'status' => $method === 'card' ? 'pending' : 'awaiting_transfer',
            'paid_at' => null,
        ],
        'customer' => [
            'name' => $val('name'), 'email' => $val('email'), 'phone' => $val('phone'),
            'company' => $val('company'), 'tax_number' => $val('tax_number'), 'note' => $val('note'),
        ],
        'billing' => ['zip' => $val('billing_zip'), 'city' => $val('billing_city'), 'address' => $val('billing_address')],
        'shipping' => $shipDiff
            ? ['zip' => $val('shipping_zip'), 'city' => $val('shipping_city'), 'address' => $val('shipping_address')]
            : null,
        'items' => $cart['items'],
        'totals' => ['gross' => $cart['total']],
        'invoice' => ['ok' => false, 'number' => null, 'message' => null],
    ];
    $orders->save($order);

    if ($method === 'transfer') {
        $finalizeInvoice($token);
        Cart::clear();
        return $redirect('/rendeles/' . $token);
    }
    return $redirect($payment->start($order));
});

$router->get('/fizetes/{token}', static function (array $params) use ($orders, $redirect): string {
    $order = $orders->find($params['token'] ?? '');
    if ($order === null) {
        http_response_code(404);
        return View::render('errors/404', ['title' => 'Ismeretlen fizetés']);
    }
    if (($order['status'] ?? '') !== 'pending') {
        return $redirect('/rendeles/' . $order['token']);
    }
    return View::render('shop/payment', ['title' => 'Fizetés', 'order' => $order, 'failed' => isset($_GET['hiba'])]);
});

$router->post('/fizetes/{token}', static function (array $params) use ($orders, $finalizeInvoice, $redirect): string {
    $token = $params['token'] ?? '';
    $order = $orders->find($token);
    if ($order === null || !Csrf::check($_POST['_csrf'] ?? null)) {
        return $redirect('/kosar');
    }
    if (($order['status'] ?? '') !== 'pending') {
        return $redirect('/rendeles/' . $token);
    }
    if (($_POST['result'] ?? '') === 'success') {
        $orders->update($token, [
            'status' => 'paid',
            'payment' => ['status' => 'paid', 'paid_at' => date('c')],
        ]);
        $finalizeInvoice($token);
        Cart::clear();
        return $redirect('/rendeles/' . $token);
    }
    $orders->update($token, ['payment' => ['status' => 'failed']]);
    return $redirect('/fizetes/' . $token . '?hiba=1');
});

$router->get('/rendeles/{token}', static function (array $params) use ($orders): string {
    $order = $orders->find($params['token'] ?? '');
    if ($order === null) {
        http_response_code(404);
        return View::render('errors/404', ['title' => 'A rendelés nem található']);
    }
    return View::render('shop/confirmation', ['title' => 'Rendelés visszaigazolása', 'order' => $order]);
});

/* ------------------------------------------------------------------ */
/* Vezérlőpult (admin)                                                 */
/* ------------------------------------------------------------------ */

$adminPw = (string) ($config['admin']['password'] ?? '');
$lowStock = (int) ($config['admin']['low_stock'] ?? 10);
$pwWeak = $adminPw === '' || $adminPw === 'admin';

/** Admin nézet a közös adatokkal + admin layouttal. */
$adminView = static function (string $tpl, string $active, array $extra = []) use ($pwWeak): string {
    return View::render($tpl, array_merge(['active' => $active, 'pwWeak' => $pwWeak], $extra), 'admin');
};

/** Vezérlőpult-hozzáférés (admin vagy szerkesztő); különben átirányít. */
$guard = static function () use ($redirect): void {
    if (!Auth::isStaff()) {
        $redirect('/admin/login');
    }
};
/** Csak adminnak engedélyezett művelet. */
$guardAdmin = static function () use ($redirect): void {
    if (!Auth::isAdmin()) {
        $redirect('/admin');
    }
};

$router->get('/admin/login', static function () use ($config, $redirect): string {
    if (Auth::isStaff()) {
        return $redirect('/admin');
    }
    return View::render('admin/login', [
        'title' => 'Belépés',
        'error' => isset($_GET['hiba']),
        'installed' => (bool) $config['installed'],
    ], '');
});

$router->post('/admin/login', static function () use ($config, $adminPw, $users, $redirect): string {
    if (!Csrf::check($_POST['_csrf'] ?? null)) {
        return $redirect('/admin/login');
    }
    if ($config['installed'] && $users !== null) {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $user = $users->findByEmail($email);
        if ($user !== null && password_verify($password, $user['password'])
            && in_array($user['role'], ['admin', 'editor'], true)) {
            Auth::loginUser($user);
            return $redirect('/admin');
        }
        return $redirect('/admin/login?hiba=1');
    }
    // Telepítés előtti, egyszerű jelszavas belépés.
    if (Auth::attemptLegacy((string) ($_POST['password'] ?? ''), $adminPw)) {
        return $redirect('/admin');
    }
    return $redirect('/admin/login?hiba=1');
});

$router->post('/admin/logout', static function () use ($redirect): string {
    Auth::logout();
    return $redirect('/admin/login');
});

$router->get('/admin', static function () use ($axel, $cats, $adminView, $guard, $lowStock): string {
    $guard();
    $products = $axel->products();
    $inStock = array_filter($products, static fn ($p) => $p->inStock());
    $low = array_filter($products, static fn ($p) => $p->stock > 0 && $p->stock < $lowStock);
    $stockValue = array_sum(array_map(static fn ($p) => $p->priceGross() * $p->stock, $products));

    return $adminView('admin/dashboard', 'dashboard', [
        'title' => 'Áttekintés',
        'cats' => $cats,
        'lowStockItems' => array_values($low),
        'lowStock' => $lowStock,
        'kpi' => [
            'products' => count($products),
            'categories' => $cats->leafCount(),
            'inStock' => count($inStock),
            'out' => count($products) - count($inStock),
            'stockValue' => $stockValue,
        ],
    ]);
});

$router->get('/admin/termekek', static function () use ($axel, $cats, $adminView, $guard, $lowStock): string {
    $guard();
    $products = $axel->products();
    $kat = isset($_GET['kat']) ? (string) $_GET['kat'] : '';
    $q = trim((string) ($_GET['q'] ?? ''));

    if ($kat !== '' && $cats->find($kat) !== null) {
        $branch = $cats->branch($kat);
        $products = array_filter($products, static fn ($p) => in_array($p->category, $branch, true));
    }
    if ($q !== '') {
        $products = array_filter($products, static fn ($p) => mb_stripos($p->name, $q) !== false || stripos($p->sku, $q) !== false);
    }

    return $adminView('admin/products', 'products', [
        'title' => 'Termékek és készlet',
        'products' => array_values($products),
        'cats' => $cats,
        'kat' => $kat,
        'q' => $q,
        'lowStock' => $lowStock,
    ]);
});

$router->get('/admin/kategoriak', static function () use ($axel, $cats, $adminView, $guard): string {
    $guard();
    $products = $axel->products();
    $counts = [];
    foreach ($cats->all() as $key => $node) {
        $branch = $cats->branch($key);
        $counts[$key] = count(array_filter($products, static fn ($p) => in_array($p->category, $branch, true)));
    }
    return $adminView('admin/categories', 'categories', [
        'title' => 'Kategóriák',
        'catsTree' => $cats->tree(),
        'counts' => $counts,
    ]);
});

$router->get('/admin/rendelesek', static function () use ($adminView, $guard, $orders): string {
    $guard();
    return $adminView('admin/orders', 'orders', ['title' => 'Rendelések', 'orders' => $orders->all()]);
});

$router->get('/admin/rendeles/{token}', static function (array $params) use ($adminView, $guard, $orders): string {
    $guard();
    $order = $orders->find($params['token'] ?? '');
    if ($order === null) {
        http_response_code(404);
        return $adminView('admin/order', 'orders', ['title' => 'Rendelés', 'order' => null]);
    }
    return $adminView('admin/order', 'orders', ['title' => 'Rendelés · ' . ($order['number'] ?? ''), 'order' => $order]);
});

$router->get('/admin/integracio', static function () use ($adminView, $guard): string {
    $guard();
    return $adminView('admin/integration', 'integration', ['title' => 'Axel integráció']);
});

$router->get('/admin/uzenetek', static function () use ($adminView, $guard, $messages): string {
    $guard();
    return $adminView('admin/messages', 'messages', ['title' => 'Üzenetek', 'messages' => $messages->all()]);
});

$router->get('/admin/felhasznalok', static function () use ($adminView, $guard, $guardAdmin, $users): string {
    $guard();
    $guardAdmin();
    return $adminView('admin/users', 'users', ['title' => 'Felhasználók', 'users' => $users ? $users->all() : []]);
});

$router->get('/admin/felhasznalok/szerkesztes', static function () use ($adminView, $guard, $guardAdmin, $users): string {
    $guard();
    $guardAdmin();
    $id = (int) ($_GET['id'] ?? 0);
    $user = ($users && $id > 0) ? $users->find($id) : null;
    return $adminView('admin/user-edit', 'users', [
        'title' => $user ? 'Felhasználó szerkesztése' : 'Új felhasználó',
        'user' => $user,
    ]);
});

$router->post('/admin/felhasznalok/mentes', static function () use ($guard, $guardAdmin, $users, $redirect): string {
    $guard();
    $guardAdmin();
    if (!Csrf::check($_POST['_csrf'] ?? null) || $users === null) {
        return $redirect('/admin/felhasznalok');
    }
    $id = (int) ($_POST['id'] ?? 0);
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $role = in_array($_POST['role'] ?? 'customer', array_keys(UserRepository::ROLES), true) ? (string) $_POST['role'] : 'customer';
    $password = (string) ($_POST['password'] ?? '');

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $redirect('/admin/felhasznalok/szerkesztes' . ($id ? '?id=' . $id : ''));
    }
    try {
        if ($id > 0) {
            $fields = ['name' => $name, 'email' => $email, 'role' => $role];
            if ($password !== '') {
                $fields['password'] = password_hash($password, PASSWORD_DEFAULT);
            }
            $users->update($id, $fields);
        } else {
            if (strlen($password) < 6) {
                return $redirect('/admin/felhasznalok/szerkesztes');
            }
            $users->create($name, $email, password_hash($password, PASSWORD_DEFAULT), $role);
        }
    } catch (\Throwable $e) {
        // pl. duplikált e-mail – csendben visszairányít
    }
    return $redirect('/admin/felhasznalok');
});

$router->post('/admin/felhasznalok/torles', static function () use ($guard, $guardAdmin, $users, $redirect): string {
    $guard();
    $guardAdmin();
    if (Csrf::check($_POST['_csrf'] ?? null) && $users !== null) {
        $id = (int) ($_POST['id'] ?? 0);
        $me = Auth::user();
        if ($me && (int) $me['id'] !== $id) {
            $users->delete($id);
        }
    }
    return $redirect('/admin/felhasznalok');
});

$router->get('/admin/beallitasok', static function () use ($adminView, $guard, $settings, $config): string {
    $guard();
    $s = $settings->all();
    return $adminView('admin/settings', 'settings', [
        'title' => 'Beállítások',
        'saved' => isset($_GET['mentve']),
        'values' => [
            'contact_messenger' => $s['contact_messenger'] ?? '',
            'contact_viber' => $s['contact_viber'] ?? '',
            'contact_email' => array_key_exists('contact_email', $s) ? $s['contact_email'] : $config['contact']['email'],
            'contact_phone' => array_key_exists('contact_phone', $s) ? $s['contact_phone'] : $config['contact']['phone'],
        ],
    ]);
});

$router->post('/admin/beallitasok', static function () use ($guard, $settings, $redirect): string {
    $guard();
    if (Csrf::check($_POST['_csrf'] ?? null)) {
        $settings->saveMany([
            'contact_messenger' => trim((string) ($_POST['contact_messenger'] ?? '')),
            'contact_viber' => trim((string) ($_POST['contact_viber'] ?? '')),
            'contact_email' => trim((string) ($_POST['contact_email'] ?? '')),
            'contact_phone' => trim((string) ($_POST['contact_phone'] ?? '')),
        ]);
    }
    return $redirect('/admin/beallitasok?mentve=1');
});

$router->get('/admin/referenciak', static function () use ($adminView, $guard, $references): string {
    $guard();
    return $adminView('admin/references', 'references', ['title' => 'Referenciák', 'references' => $references->all()]);
});

$router->get('/admin/referenciak/szerkesztes', static function () use ($adminView, $guard, $references): string {
    $guard();
    $id = (int) ($_GET['id'] ?? 0);
    $ref = $id > 0 ? $references->find($id) : null;
    return $adminView('admin/reference-edit', 'references', [
        'title' => $ref ? 'Referencia szerkesztése' : 'Új referencia',
        'ref' => $ref,
    ]);
});

$router->post('/admin/referenciak/mentes', static function () use ($guard, $references, $uploadLogo, $redirect): string {
    $guard();
    if (!Csrf::check($_POST['_csrf'] ?? null)) {
        return $redirect('/admin/referenciak');
    }
    $id = (int) ($_POST['id'] ?? 0);
    $existing = $id > 0 ? $references->find($id) : null;
    $ref = [
        'name' => trim((string) ($_POST['name'] ?? '')),
        'short' => trim((string) ($_POST['short'] ?? '')),
        'long' => trim((string) ($_POST['long'] ?? '')),
        'logo' => $existing['logo'] ?? '',
    ];
    if ($id > 0) {
        $ref['id'] = $id;
    }
    if (isset($_FILES['logo']) && ($_FILES['logo']['error'] ?? 4) === UPLOAD_ERR_OK) {
        $uploaded = $uploadLogo($_FILES['logo']);
        if ($uploaded !== null) {
            $ref['logo'] = $uploaded;
        }
    }
    if ($ref['name'] !== '') {
        $references->save($ref);
    }
    return $redirect('/admin/referenciak');
});

$router->post('/admin/referenciak/torles', static function () use ($guard, $references, $redirect): string {
    $guard();
    if (Csrf::check($_POST['_csrf'] ?? null)) {
        $references->delete((int) ($_POST['id'] ?? 0));
    }
    return $redirect('/admin/referenciak');
});

$router->get('/admin/terkep', static function () use ($adminView, $guard, $pois): string {
    $guard();
    $id = (int) ($_GET['id'] ?? 0);
    return $adminView('admin/map', 'map', [
        'title' => 'Térkép',
        'pois' => $pois->all(),
        'edit' => $id > 0 ? $pois->find($id) : null,
    ]);
});

$router->post('/admin/terkep/mentes', static function () use ($guard, $pois, $redirect): string {
    $guard();
    if (!Csrf::check($_POST['_csrf'] ?? null)) {
        return $redirect('/admin/terkep');
    }
    $title = trim((string) ($_POST['title'] ?? ''));
    $lat = (float) ($_POST['lat'] ?? 0);
    $lng = (float) ($_POST['lng'] ?? 0);
    if ($title !== '' && $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180 && ($lat !== 0.0 || $lng !== 0.0)) {
        $poi = [
            'title' => $title,
            'lat' => $lat,
            'lng' => $lng,
            'description' => trim((string) ($_POST['description'] ?? '')),
            'link' => trim((string) ($_POST['link'] ?? '')),
        ];
        if (!empty($_POST['id'])) {
            $poi['id'] = (int) $_POST['id'];
        }
        $pois->save($poi);
    }
    return $redirect('/admin/terkep');
});

$router->post('/admin/terkep/torles', static function () use ($guard, $pois, $redirect): string {
    $guard();
    if (Csrf::check($_POST['_csrf'] ?? null)) {
        $pois->delete((int) ($_POST['id'] ?? 0));
    }
    return $redirect('/admin/terkep');
});

echo $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
