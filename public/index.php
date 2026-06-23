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
use App\Db\Importer;
use App\Db\Schema;
use App\User\UserRepository;
use App\Integration\MockAxelGateway;
use App\Leader\LeaderStore;
use App\Map\PoiStore;
use App\Message\MessageStore;
use App\Newsletter\SubscriberStore;
use App\Newsletter\TemplateStore;
use App\Order\OrderStore;
use App\Payment\MockPaymentGateway;
use App\Reference\ReferenceStore;
use App\Seo\ProductSeoStore;
use App\Settings\SettingsStore;

$config = require dirname(__DIR__) . '/config/config.php';

if ($config['app']['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// Biztonságosabb session-süti: JS nem olvashatja, csak HTTPS-en megy, SameSite véd.
$secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
    || (($_SERVER['SERVER_PORT'] ?? '') == '443');
session_set_cookie_params([
    'httponly' => true,
    'secure' => $secureCookie,
    'samesite' => 'Lax',
]);
session_start();

// Az Axel-kapu. A config 'gateway' alapján választunk megvalósítást; éles
// bekötéskor az 'xml' (helyi mappás) vagy 'rest' adapter kerül a mock helyére.
$axel = match ($config['axel']['gateway'] ?? 'mock') {
    // 'xml' => new App\Integration\XmlAxelGateway($config['axel']['exchange_dir']),
    default => new MockAxelGateway(),
};

// Kategóriafa (config/categories.php).
$cats = new Categories();

// Adatbázis-kapcsolat, ha a telepítő már lefutott (különben minden fájl-alapú).
$pdo = null;
if ($config['installed']) {
    try {
        $pdo = Database::instance($config['db']);
        // Hiányzó táblák pótlása (pl. új funkció utáni deploy után), idempotens.
        Schema::create($pdo, (string) ($config['db']['driver'] ?? 'mysql'));
        // Az újonnan létrejött táblák egyszeri feltöltése a meglévő tartalomból.
        $boot = new SettingsStore($pdo);
        if (!$boot->has('seed_done')) {
            Importer::run($pdo);
            $boot->saveMany(['seed_done' => '1']);
        }
    } catch (\Throwable $e) {
        $pdo = null; // DB nem elérhető vagy nincs jog – fájl-módra esünk vissza
    }
}

// Adattárak (DB ha telepítve, különben fájl) és felhasználók.
$orders = new OrderStore($pdo, $config['shop']['orders_dir']);
$messages = new MessageStore($pdo, $config['contact']['messages_dir']);
$settings = new SettingsStore($pdo);
$references = new ReferenceStore($pdo);
$pois = new PoiStore($pdo);
$leaders = new LeaderStore($pdo);
$productSeo = new ProductSeoStore($pdo);
$subscribers = new SubscriberStore($pdo);
$templates = new TemplateStore($pdo);
$users = $pdo ? new UserRepository($pdo) : null;

// Általános képfeltöltő (referencia-logó, vezető-fotó) a megadott mappába.
$uploadImage = static function (array $file, string $dir): ?string {
    if (($file['error'] ?? 1) !== UPLOAD_ERR_OK || empty($file['tmp_name'])) {
        return null;
    }
    if (($file['size'] ?? 0) > 16 * 1024 * 1024) {
        return null; // max 16 MB
    }
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $info = @getimagesize($file['tmp_name']);
    $mime = $info['mime'] ?? '';
    if (!isset($allowed[$mime])) {
        return null;
    }
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $name = bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    return move_uploaded_file($file['tmp_name'], $dir . '/' . $name) ? $name : null;
};

// Csak http/https sémájú URL-t engedünk át (tárolt „javascript:" linkek ellen).
$safeUrl = static function (string $u): string {
    $u = trim($u);
    return preg_match('#^https?://#i', $u) === 1 ? $u : '';
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

$router->get('/', static function () use ($cats, $references, $leaders, $pois): string {
    $flash = $_SESSION['_flash_contact'] ?? [];
    unset($_SESSION['_flash_contact']);
    return View::render('home', [
        'title' => null,
        'topCats' => $cats->topLevel(),
        'references' => $references->all(),
        'leaders' => $leaders->all(),
        'pois' => $pois->all(),
        'contactSent' => !empty($flash['sent']),
        'contactErrors' => $flash['errors'] ?? [],
        'contactOld' => $flash['old'] ?? [],
    ]);
});

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
            Importer::run($pdo); // meglévő fájl-adatok átemelése a DB-be
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

$router->get('/terkep', static function () use ($redirect): string {
    return $redirect('/#terkep');
});

$router->get('/aszf', static fn (): string => View::render('legal', [
    'title' => 'ÁSZF', 'heading' => 'Általános Szerződési Feltételek', 'mdFile' => 'aszf.txt',
]));
$router->get('/adatkezeles', static fn (): string => View::render('legal', [
    'title' => 'Adatkezelési tájékoztató', 'heading' => 'Adatkezelési tájékoztató', 'mdFile' => 'adatkezeles.txt',
]));

$router->get('/kapcsolat', static function () use ($redirect): string {
    return $redirect('/#kapcsolat');
});

$router->post('/kapcsolat', static function () use ($config, $messages, $redirect): string {
    if (!Csrf::check($_POST['_csrf'] ?? null)) {
        return $redirect('/#kapcsolat');
    }
    $val = static fn (string $k): string => trim((string) ($_POST[$k] ?? ''));
    $errors = [];

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
        $_SESSION['_flash_contact'] = ['errors' => $errors, 'old' => $_POST];
        return $redirect('/#kapcsolat');
    }

    $company = $val('company');
    $message = $val('message');
    $stored = ($company !== '' ? "Cég: {$company}\n\n" : '') . $message;
    $msg = [
        'created' => date('c'),
        'name' => $val('name'), 'email' => $val('email'), 'phone' => $val('phone'),
        'subject' => $company, 'message' => $stored,
    ];
    $messages->save($msg);

    // E-mail értesítés (ha a szerver tudja küldeni; az üzenet ettől függetlenül tárolódik).
    $subject = mb_encode_mimeheader('Új üzenet a weboldalról' . ($company !== '' ? ' – ' . $company : ''), 'UTF-8');
    $body = "Név: {$msg['name']}\nCég: {$company}\nE-mail: {$msg['email']}\nTelefon: {$msg['phone']}\n\nÜzenet:\n{$message}\n";
    $fromHost = parse_url((string) $config['app']['url'], PHP_URL_HOST) ?: 'localhost';
    $headers = "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\n"
        . 'From: weboldal@' . $fromHost . "\r\nReply-To: {$msg['email']}";
    @mail($config['contact']['email'], $subject, $body, $headers);

    $_SESSION['_flash_contact'] = ['sent' => true];
    return $redirect('/#kapcsolat');
});

/* ------------------------------------------------------------------ */
/* Hírlevél (publikus)                                                 */
/* ------------------------------------------------------------------ */

$router->post('/hirlevel', static function () use ($subscribers, $redirect): string {
    // Vissza a feliratkozási sávhoz – csak a saját oldalra engedünk.
    $back = (string) ($_SERVER['HTTP_REFERER'] ?? '/');
    $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
    // Csak a saját hostra engedünk vissza (nyílt átirányítás ellen, pontos host-egyezés).
    if ($host === '' || parse_url($back, PHP_URL_HOST) !== $host) {
        $back = '/';
    }
    $back = strtok($back, '#') . '#hirlevel';

    if (!Csrf::check($_POST['_csrf'] ?? null)) {
        return $redirect($back);
    }
    $email = trim((string) ($_POST['email'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['_flash_newsletter'] = ['type' => 'error', 'text' => 'Adj meg egy érvényes e-mail címet.'];
        return $redirect($back);
    }
    if (!isset($_POST['privacy'])) {
        $_SESSION['_flash_newsletter'] = ['type' => 'error', 'text' => 'A feliratkozáshoz fogadd el az adatkezelési tájékoztatót.'];
        return $redirect($back);
    }
    $subscribers->subscribe($email, trim((string) ($_POST['name'] ?? '')));
    $_SESSION['_flash_newsletter'] = ['type' => 'ok', 'text' => 'Köszönjük! Sikeresen feliratkoztál a hírlevelünkre.'];
    return $redirect($back);
});

$router->get('/hirlevel/leiratkozas', static function () use ($subscribers): string {
    $token = (string) ($_GET['t'] ?? '');
    $sub = $token !== '' ? $subscribers->unsubscribeByToken($token) : null;
    return View::render('newsletter-unsub', [
        'title' => 'Leiratkozás',
        'ok' => $sub !== null,
        'email' => (string) ($sub['email'] ?? ''),
    ]);
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

$router->get('/termek/{slug}', static function (array $params) use ($axel, $cats, $productSeo): string {
    $product = $axel->findProduct($params['slug'] ?? '');
    if ($product === null) {
        http_response_code(404);
        return View::render('errors/404', ['title' => 'A termék nem található']);
    }
    $pseo = $productSeo->find($product->sku) ?? [];
    $meta = [
        'title' => (string) ($pseo['title'] ?? ''),
        'description' => trim((string) ($pseo['description'] ?? '')) !== '' ? (string) $pseo['description'] : $product->short,
        'keywords' => (string) ($pseo['keywords'] ?? ''),
        'og_image' => (string) ($pseo['og_image'] ?? ''),
    ];
    return View::render('shop/show', [
        'title' => $product->name,
        'product' => $product,
        'catPath' => $cats->path($product->category),
        'cats' => $cats,
        'meta' => $meta,
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

/** Admin nézet a közös adatokkal + admin layouttal. */
$adminView = static function (string $tpl, string $active, array $extra = []): string {
    return View::render($tpl, array_merge(['active' => $active], $extra), 'admin');
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

$router->get('/admin/integracio', static function () use ($adminView, $guard, $settings, $config): string {
    $guard();
    $s = $settings->all();
    return $adminView('admin/integration', 'integration', [
        'title' => 'Axel integráció',
        'values' => [
            'gateway' => (string) ($s['axel_gateway'] ?? ($config['axel']['gateway'] ?? 'mock')),
            'exchange_dir' => (string) ($s['axel_exchange_dir'] ?? ($config['axel']['exchange_dir'] ?? '')),
            'api_url' => (string) ($s['axel_api_url'] ?? ''),
            'api_key' => (string) ($s['axel_api_key'] ?? ''),
        ],
    ]);
});

$router->post('/admin/integracio', static function () use ($guard, $settings, $redirect): string {
    $guard();
    if (Csrf::check($_POST['_csrf'] ?? null)) {
        $gateway = in_array($_POST['gateway'] ?? 'mock', ['mock', 'xml', 'rest'], true) ? (string) $_POST['gateway'] : 'mock';
        $settings->saveMany([
            'axel_gateway' => $gateway,
            'axel_exchange_dir' => trim((string) ($_POST['exchange_dir'] ?? '')),
            'axel_api_url' => trim((string) ($_POST['api_url'] ?? '')),
            'axel_api_key' => trim((string) ($_POST['api_key'] ?? '')),
        ]);
        $_SESSION['_flash_admin'] = ['type' => 'ok', 'text' => 'Axel beállítások mentve.'];
    }
    return $redirect('/admin/integracio');
});

$router->get('/admin/uzenetek', static function () use ($adminView, $guard, $messages): string {
    $guard();
    return $adminView('admin/messages', 'messages', ['title' => 'Üzenetek', 'messages' => $messages->all()]);
});

/* ------------------------------------------------------------------ */
/* Hírlevél (admin)                                                    */
/* ------------------------------------------------------------------ */

$router->get('/admin/hirlevel', static function () use ($adminView, $guard, $subscribers, $templates, $settings, $config): string {
    $guard();
    $s = $settings->all();
    return $adminView('admin/newsletter', 'newsletter', [
        'title' => 'Hírlevél',
        'subscribers' => $subscribers->all(),
        'activeCount' => $subscribers->activeCount(),
        'templates' => $templates->all(),
        'values' => [
            'from' => (string) ($s['newsletter_from'] ?? $config['contact']['email']),
            'from_name' => (string) ($s['newsletter_from_name'] ?? $config['app']['name']),
        ],
        'lastSent' => (string) ($s['newsletter_last_sent'] ?? ''),
    ]);
});

$router->post('/admin/hirlevel/beallitasok', static function () use ($guard, $settings, $redirect): string {
    $guard();
    if (Csrf::check($_POST['_csrf'] ?? null)) {
        $settings->saveMany([
            'newsletter_from' => trim((string) ($_POST['from'] ?? '')),
            'newsletter_from_name' => trim((string) ($_POST['from_name'] ?? '')),
        ]);
        $_SESSION['_flash_admin'] = ['type' => 'ok', 'text' => 'Hírlevél-beállítások mentve.'];
    }
    return $redirect('/admin/hirlevel');
});

$router->get('/admin/hirlevel/sablon', static function () use ($adminView, $guard, $templates): string {
    $guard();
    $id = (int) ($_GET['id'] ?? 0);
    $tpl = $id > 0 ? $templates->find($id) : null;
    return $adminView('admin/newsletter-template', 'newsletter', [
        'title' => $tpl ? 'Sablon szerkesztése' : 'Új sablon',
        'tpl' => $tpl,
    ]);
});

$router->post('/admin/hirlevel/sablon/mentes', static function () use ($guard, $templates, $redirect): string {
    $guard();
    if (!Csrf::check($_POST['_csrf'] ?? null)) {
        return $redirect('/admin/hirlevel');
    }
    $id = (int) ($_POST['id'] ?? 0);
    $tpl = [
        'name' => trim((string) ($_POST['name'] ?? '')),
        'subject' => trim((string) ($_POST['subject'] ?? '')),
        'body' => (string) ($_POST['body'] ?? ''),
    ];
    if ($id > 0) {
        $tpl['id'] = $id;
    }
    if ($tpl['name'] === '') {
        $_SESSION['_flash_admin'] = ['type' => 'error', 'text' => 'A sablon nevét add meg.'];
        return $redirect('/admin/hirlevel/sablon' . ($id ? '?id=' . $id : ''));
    }
    $templates->save($tpl);
    $_SESSION['_flash_admin'] = ['type' => 'ok', 'text' => 'Sablon mentve.'];
    return $redirect('/admin/hirlevel');
});

$router->post('/admin/hirlevel/sablon/torles', static function () use ($guard, $templates, $redirect): string {
    $guard();
    if (Csrf::check($_POST['_csrf'] ?? null)) {
        $templates->delete((int) ($_POST['id'] ?? 0));
        $_SESSION['_flash_admin'] = ['type' => 'ok', 'text' => 'Sablon törölve.'];
    }
    return $redirect('/admin/hirlevel');
});

$router->post('/admin/hirlevel/feliratkozo/torles', static function () use ($guard, $subscribers, $redirect): string {
    $guard();
    if (Csrf::check($_POST['_csrf'] ?? null)) {
        $subscribers->delete((int) ($_POST['id'] ?? 0));
        $_SESSION['_flash_admin'] = ['type' => 'ok', 'text' => 'Feliratkozó törölve.'];
    }
    return $redirect('/admin/hirlevel');
});

$router->post('/admin/hirlevel/kuldes', static function () use ($guard, $subscribers, $templates, $settings, $config, $redirect): string {
    $guard();
    if (!Csrf::check($_POST['_csrf'] ?? null)) {
        return $redirect('/admin/hirlevel');
    }
    $s = $settings->all();
    $fromEmail = trim((string) ($s['newsletter_from'] ?? $config['contact']['email']));
    $fromName = trim((string) ($s['newsletter_from_name'] ?? $config['app']['name']));

    // Tárgy/törzs: közvetlenül megadva, vagy sablonból feltöltve.
    $subject = trim((string) ($_POST['subject'] ?? ''));
    $body = (string) ($_POST['body'] ?? '');
    $tplId = (int) ($_POST['template_id'] ?? 0);
    if ($tplId > 0 && ($tpl = $templates->find($tplId)) !== null) {
        if ($subject === '') {
            $subject = (string) $tpl['subject'];
        }
        if (trim($body) === '') {
            $body = (string) $tpl['body'];
        }
    }

    if ($subject === '' || trim($body) === '') {
        $_SESSION['_flash_admin'] = ['type' => 'error', 'text' => 'Add meg a tárgyat és a tartalmat (vagy válassz sablont).'];
        return $redirect('/admin/hirlevel');
    }
    if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['_flash_admin'] = ['type' => 'error', 'text' => 'Állíts be érvényes feladó e-mail címet a küldéshez.'];
        return $redirect('/admin/hirlevel');
    }

    $recipients = $subscribers->active();
    $base = rtrim((string) $config['app']['url'], '/');
    $subjectEnc = mb_encode_mimeheader($subject, 'UTF-8');
    $fromHeader = mb_encode_mimeheader($fromName, 'UTF-8') . ' <' . $fromEmail . '>';
    $sent = 0;

    foreach ($recipients as $r) {
        $unsub = $base . '/hirlevel/leiratkozas?t=' . urlencode((string) $r['token']);
        $html = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:#222">'
            . $body
            . '<hr style="border:0;border-top:1px solid #e0e0e0;margin:28px 0 14px">'
            . '<p style="font-size:12px;color:#888">Ezt az üzenetet azért kapod, mert feliratkoztál a(z) '
            . htmlspecialchars($fromName, ENT_QUOTES) . ' hírlevelére.<br>'
            . '<a href="' . htmlspecialchars($unsub, ENT_QUOTES) . '" style="color:#888">Leiratkozás</a></p></div>';
        $headers = "MIME-Version: 1.0\r\n"
            . "Content-Type: text/html; charset=UTF-8\r\n"
            . 'From: ' . $fromHeader . "\r\n"
            . 'List-Unsubscribe: <' . $unsub . '>';
        if (@mail((string) $r['email'], $subjectEnc, $html, $headers)) {
            $sent++;
        }
    }

    $settings->saveMany(['newsletter_last_sent' => date('c') . '|' . $sent . '/' . count($recipients)]);
    $_SESSION['_flash_admin'] = ['type' => 'ok', 'text' => "Hírlevél elküldve: {$sent}/" . count($recipients) . ' címzettnek.'];
    return $redirect('/admin/hirlevel');
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

$router->post('/admin/beallitasok', static function () use ($guard, $settings, $redirect, $safeUrl): string {
    $guard();
    if (Csrf::check($_POST['_csrf'] ?? null)) {
        $settings->saveMany([
            'contact_messenger' => $safeUrl((string) ($_POST['contact_messenger'] ?? '')),
            'contact_viber' => trim((string) ($_POST['contact_viber'] ?? '')),
            'contact_email' => trim((string) ($_POST['contact_email'] ?? '')),
            'contact_phone' => trim((string) ($_POST['contact_phone'] ?? '')),
        ]);
    }
    return $redirect('/admin/beallitasok?mentve=1');
});

$router->get('/admin/seo', static function () use ($adminView, $guard, $settings, $config): string {
    $guard();
    $s = $settings->all();
    return $adminView('admin/seo', 'seo', [
        'title' => 'SEO',
        'values' => [
            'seo_title' => array_key_exists('seo_title', $s) ? (string) $s['seo_title'] : ($config['app']['name'] . ' — ' . $config['app']['tagline']),
            'seo_description' => array_key_exists('seo_description', $s) ? (string) $s['seo_description'] : 'Net-Trade Hungary Kft. – egyedi raklapgyártás, ipari csomagolás, nemzetközi árufuvarozás és fűrészáru-nagykereskedelem.',
            'seo_keywords' => (string) ($s['seo_keywords'] ?? ''),
            'seo_og_image' => (string) ($s['seo_og_image'] ?? ''),
            'ga_id' => (string) ($s['ga_id'] ?? ''),
        ],
    ]);
});

$router->post('/admin/seo', static function () use ($guard, $settings, $redirect): string {
    $guard();
    if (Csrf::check($_POST['_csrf'] ?? null)) {
        $settings->saveMany([
            'seo_title' => trim((string) ($_POST['seo_title'] ?? '')),
            'seo_description' => trim((string) ($_POST['seo_description'] ?? '')),
            'seo_keywords' => trim((string) ($_POST['seo_keywords'] ?? '')),
            'seo_og_image' => trim((string) ($_POST['seo_og_image'] ?? '')),
            'ga_id' => trim((string) ($_POST['ga_id'] ?? '')),
        ]);
        $_SESSION['_flash_admin'] = ['type' => 'ok', 'text' => 'SEO beállítások mentve.'];
    }
    return $redirect('/admin/seo');
});

$router->get('/admin/termek-seo', static function () use ($adminView, $guard, $axel, $productSeo): string {
    $guard();
    $sku = (string) ($_GET['sku'] ?? '');
    $product = null;
    foreach ($axel->products() as $p) {
        if ($p->sku === $sku) {
            $product = $p;
            break;
        }
    }
    if ($product === null) {
        http_response_code(404);
        return $adminView('admin/product-seo', 'products', ['title' => 'Termék SEO', 'product' => null, 'values' => []]);
    }
    $pseo = $productSeo->find($sku) ?? [];
    return $adminView('admin/product-seo', 'products', [
        'title' => 'SEO · ' . $product->name,
        'product' => $product,
        'values' => [
            'title' => (string) ($pseo['title'] ?? ''),
            'description' => (string) ($pseo['description'] ?? ''),
            'keywords' => (string) ($pseo['keywords'] ?? ''),
            'og_image' => (string) ($pseo['og_image'] ?? ''),
        ],
    ]);
});

$router->post('/admin/termek-seo', static function () use ($guard, $productSeo, $redirect): string {
    $guard();
    $sku = (string) ($_POST['sku'] ?? '');
    if (Csrf::check($_POST['_csrf'] ?? null) && $sku !== '') {
        $productSeo->save($sku, [
            'title' => trim((string) ($_POST['title'] ?? '')),
            'description' => trim((string) ($_POST['description'] ?? '')),
            'keywords' => trim((string) ($_POST['keywords'] ?? '')),
            'og_image' => trim((string) ($_POST['og_image'] ?? '')),
        ]);
        $_SESSION['_flash_admin'] = ['type' => 'ok', 'text' => 'Termék SEO mentve.'];
    }
    return $redirect('/admin/termekek');
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

$router->post('/admin/referenciak/mentes', static function () use ($guard, $references, $uploadImage, $redirect, $safeUrl): string {
    $guard();
    if (empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $_SESSION['_flash_admin'] = ['type' => 'error', 'text' => 'A feltöltött fájl túl nagy a szerver korlátjához képest – tölts fel kisebb képet. (A módosítások nem mentődtek.)'];
        return $redirect('/admin/referenciak');
    }
    if (!Csrf::check($_POST['_csrf'] ?? null)) {
        return $redirect('/admin/referenciak');
    }
    $id = (int) ($_POST['id'] ?? 0);
    $existing = $id > 0 ? $references->find($id) : null;
    $ref = [
        'name' => trim((string) ($_POST['name'] ?? '')),
        'short' => trim((string) ($_POST['short'] ?? '')),
        'long' => trim((string) ($_POST['long'] ?? '')),
        'url' => $safeUrl((string) ($_POST['url'] ?? '')),
        'featured' => isset($_POST['featured']) ? 1 : 0,
        'logo' => $existing['logo'] ?? '',
    ];
    if ($id > 0) {
        $ref['id'] = $id;
    }

    $photoError = null;
    $fileErr = $_FILES['logo']['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($fileErr !== UPLOAD_ERR_NO_FILE) {
        if ($fileErr === UPLOAD_ERR_INI_SIZE || $fileErr === UPLOAD_ERR_FORM_SIZE) {
            $photoError = 'A logó túl nagy – tölts fel kisebbet (max 16 MB).';
        } elseif ($fileErr !== UPLOAD_ERR_OK) {
            $photoError = 'A logó feltöltése megszakadt, próbáld újra.';
        } else {
            $uploaded = $uploadImage($_FILES['logo'], dirname(__DIR__) . '/public/uploads/references');
            if ($uploaded === null) {
                $photoError = 'A logó nem menthető – JPG/PNG/WEBP, max 16 MB legyen.';
            } else {
                $ref['logo'] = $uploaded;
            }
        }
    }

    if ($ref['name'] !== '') {
        $references->save($ref);
        $_SESSION['_flash_admin'] = $photoError !== null
            ? ['type' => 'error', 'text' => 'Adatok mentve, de: ' . $photoError]
            : ['type' => 'ok', 'text' => 'Mentve.'];
    }
    return $redirect('/admin/referenciak');
});

$router->get('/admin/vezetok', static function () use ($adminView, $guard, $leaders): string {
    $guard();
    return $adminView('admin/leaders', 'leaders', ['title' => 'Vezetők', 'leaders' => $leaders->all()]);
});

$router->get('/admin/vezetok/szerkesztes', static function () use ($adminView, $guard, $leaders): string {
    $guard();
    $id = (int) ($_GET['id'] ?? 0);
    $leader = $id > 0 ? $leaders->find($id) : null;
    return $adminView('admin/leader-edit', 'leaders', [
        'title' => $leader ? 'Vezető szerkesztése' : 'Új vezető',
        'leader' => $leader,
    ]);
});

$router->post('/admin/vezetok/mentes', static function () use ($guard, $leaders, $uploadImage, $redirect): string {
    $guard();
    // Túl nagy feltöltésnél a PHP eldobja a teljes $_POST-ot (post_max_size).
    if (empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $_SESSION['_flash_admin'] = ['type' => 'error', 'text' => 'A feltöltött fájl túl nagy a szerver korlátjához képest – tölts fel kisebb képet. (A módosítások nem mentődtek.)'];
        return $redirect('/admin/vezetok');
    }
    if (!Csrf::check($_POST['_csrf'] ?? null)) {
        return $redirect('/admin/vezetok');
    }
    $id = (int) ($_POST['id'] ?? 0);
    $existing = $id > 0 ? $leaders->find($id) : null;
    $leader = [
        'name' => trim((string) ($_POST['name'] ?? '')),
        'role' => trim((string) ($_POST['role'] ?? '')),
        'phone' => trim((string) ($_POST['phone'] ?? '')),
        'email' => trim((string) ($_POST['email'] ?? '')),
        'photo' => $existing['photo'] ?? '',
    ];
    if ($id > 0) {
        $leader['id'] = $id;
    }

    $photoError = null;
    $fileErr = $_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($fileErr !== UPLOAD_ERR_NO_FILE) {
        if ($fileErr === UPLOAD_ERR_INI_SIZE || $fileErr === UPLOAD_ERR_FORM_SIZE) {
            $photoError = 'A kép túl nagy – tölts fel kisebbet (max 16 MB).';
        } elseif ($fileErr !== UPLOAD_ERR_OK) {
            $photoError = 'A kép feltöltése megszakadt, próbáld újra.';
        } else {
            $uploaded = $uploadImage($_FILES['photo'], dirname(__DIR__) . '/public/uploads/team');
            if ($uploaded === null) {
                $photoError = 'A kép nem menthető – JPG/PNG/WEBP, max 16 MB legyen.';
            } else {
                $leader['photo'] = $uploaded;
            }
        }
    }

    if ($leader['name'] !== '') {
        $leaders->save($leader);
        $_SESSION['_flash_admin'] = $photoError !== null
            ? ['type' => 'error', 'text' => 'Adatok mentve, de: ' . $photoError]
            : ['type' => 'ok', 'text' => 'Mentve.'];
    } else {
        $_SESSION['_flash_admin'] = ['type' => 'error', 'text' => 'A név megadása kötelező.'];
    }
    return $redirect('/admin/vezetok');
});

$router->post('/admin/vezetok/torles', static function () use ($guard, $leaders, $redirect): string {
    $guard();
    if (Csrf::check($_POST['_csrf'] ?? null)) {
        $leaders->delete((int) ($_POST['id'] ?? 0));
    }
    return $redirect('/admin/vezetok');
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

$router->post('/admin/terkep/mentes', static function () use ($guard, $pois, $redirect, $safeUrl): string {
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
            'link' => $safeUrl((string) ($_POST['link'] ?? '')),
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
