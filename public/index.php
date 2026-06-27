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

use App\Blog\BlogStore;
use App\Catalog\Categories;
use App\Catalog\ProductImageStore;
use App\Core\Auth;
use App\Core\Cart;
use App\Core\Csrf;
use App\Core\LoginThrottle;
use App\Core\Mailer;
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
use App\Service\ServiceStore;
use App\Settings\SettingsStore;

$config = require dirname(__DIR__) . '/config/config.php';

if ($config['app']['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
}

// Végzetes (nem elkapható) hibák biztonsági hálója: éles üzemben barátságos
// üzenet a fehér oldal / kiszivárgó hibaüzenet helyett.
register_shutdown_function(static function () use ($config): void {
    $err = error_get_last();
    if ($err === null || !in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        return;
    }
    error_log('[NetTrade] Végzetes hiba: ' . $err['message'] . ' @ ' . ($err['file'] ?? '?') . ':' . ($err['line'] ?? 0));
    if (!empty($config['app']['debug'])) {
        return; // fejlesztéskor a PHP mutassa a részleteket
    }
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=UTF-8');
    }
    echo '<!doctype html><meta charset="utf-8"><title>Hiba</title>'
        . '<p style="font-family:sans-serif;max-width:40rem;margin:4rem auto;text-align:center;color:#333">'
        . 'Váratlan hiba történt. Kérjük, próbáld újra később.</p>';
});

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

// Nyelv (i18n): a választott nyelv a 'lang' cookie-ban; alapértelmezett a magyar.
// A Lang::init betölti a globális t() helpert is, így minden nézetben elérhető.
\App\Core\Lang::init((string) ($_COOKIE['lang'] ?? 'hu'), dirname(__DIR__) . '/config/lang');

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
$productImages = new ProductImageStore($pdo);
$blog = new BlogStore($pdo);
$services = new ServiceStore($pdo);
$throttle = new LoginThrottle();
$clientIp = static fn (): string => (string) ($_SERVER['REMOTE_ADDR'] ?? 'cli');

// Az Axel-kapu. Elsődlegesen az adminban mentett 'axel_gateway' dönt (Axel
// integráció oldal), különben a config alapértelmezése. A konkrét adapter
// (mock | XML fájlcsere | REST API) a shop többi része számára átlátszó.
$axelCfg = $config['axel'] ?? [];
$axel = match ((string) $settings->get('axel_gateway', (string) ($axelCfg['gateway'] ?? 'mock'))) {
    'xml' => new App\Integration\XmlAxelGateway(
        (string) ($settings->get('axel_exchange_dir', '') ?: ($axelCfg['exchange_dir'] ?? ''))
    ),
    'rest' => new App\Integration\RestAxelGateway(
        (string) $settings->get('axel_api_url', ''),
        (string) $settings->get('axel_api_key', '')
    ),
    default => new MockAxelGateway(),
};

// E-mail küldő (SMTP, ha konfigurált; különben PHP mail()). Feladó-alapértékek.
$mailFromHost = parse_url((string) $config['app']['url'], PHP_URL_HOST) ?: 'localhost';
if (($config['mail']['from_email'] ?? '') === '') {
    $config['mail']['from_email'] = 'no-reply@' . $mailFromHost;
}
if (($config['mail']['from_name'] ?? '') === '') {
    $config['mail']['from_name'] = (string) $config['app']['name'];
}
$mailer = Mailer::fromConfig($config);
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
// Fizetési kapu: ha be van állítva a Barion POSKey + kifizetett (Payee) e-mail,
// éles/sandbox Barion-fizetés; különben a belső teszt-kapu.
$barionSettings = $settings->all();
$barionPoskey = trim((string) ($barionSettings['barion_poskey'] ?? ''));
$barionPayee = trim((string) ($barionSettings['barion_payee'] ?? ''));
$barionEnv = ($barionSettings['barion_env'] ?? 'test') === 'prod' ? 'prod' : 'test';
$barionEnabled = $barionPoskey !== '' && $barionPayee !== '';
$barionClient = $barionEnabled ? new App\Payment\BarionClient($barionPoskey, $barionEnv) : null;
$payment = ($barionEnabled && $barionClient !== null)
    ? new App\Payment\BarionPaymentGateway($barionClient, $orders, [
        'base_url' => rtrim((string) $config['app']['url'], '/'),
        'payee' => $barionPayee,
        'currency' => (string) ($config['shop']['currency'] ?? 'HUF'),
    ])
    : new MockPaymentGateway();

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
                    'qty' => $qty, 'price_net' => $p->priceNet, 'vat' => $p->vat,
                    'price_gross' => $p->priceGross(), 'subtotal' => $sub,
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

/** Igaz, ha a kérés fetch/XHR (JSON-választ várunk, nem átirányítást). */
$wantsJson = static function (): bool {
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
        || str_contains((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json');
};

$router = new Router();

// Nyelvváltás: a választott nyelvet cookie-ba menti, majd vissza az előző oldalra.
$router->get('/nyelv/{lang}', static function (array $params) use ($redirect): string {
    $lang = \App\Core\Lang::normalize((string) ($params['lang'] ?? 'hu'));
    setcookie('lang', $lang, ['expires' => time() + 31536000, 'path' => '/', 'samesite' => 'Lax']);
    // Biztonságos visszairányítás: csak a referer helyi útvonalát használjuk (nincs open redirect).
    $ref = (string) ($_SERVER['HTTP_REFERER'] ?? '');
    $path = (string) (parse_url($ref, PHP_URL_PATH) ?: '/');
    $query = parse_url($ref, PHP_URL_QUERY);
    if ($path === '' || $path[0] !== '/') { $path = '/'; }
    return $redirect($path . ($query ? '?' . $query : ''));
});

$router->get('/', static function () use ($cats, $references, $leaders, $pois, $services): string {
    $flash = $_SESSION['_flash_contact'] ?? [];
    unset($_SESSION['_flash_contact']);
    return View::render('home', [
        'title' => null,
        'topCats' => $cats->topLevel(),
        'services' => $services->all(true),
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

/* ------------------------------------------------------------------ */
/* Tevékenységek / szolgáltatások                                       */
/* ------------------------------------------------------------------ */

$router->get('/szolgaltatasok', static function () use ($services): string {
    return View::render('services/index', [
        'title' => 'Tevékenységek',
        'services' => $services->all(true),
    ]);
});

$router->get('/szolgaltatasok/{slug}', static function (array $params) use ($services): string {
    $service = $services->findBySlug((string) ($params['slug'] ?? ''), true);
    if ($service === null) {
        http_response_code(404);
        return View::render('errors/404', ['title' => 'A tevékenység nem található']);
    }
    return View::render('services/show', [
        'title' => (string) $service['title'],
        'service' => $service,
        'others' => array_values(array_filter(
            $services->all(true),
            static fn ($s) => (int) $s['id'] !== (int) $service['id']
        )),
        'meta' => [
            'description' => trim((string) ($service['summary'] ?? '')),
        ],
    ]);
});

/* ------------------------------------------------------------------ */
/* Blog                                                                */
/* ------------------------------------------------------------------ */

$router->get('/blog', static function () use ($blog): string {
    return View::render('blog/index', [
        'title' => 'Blog',
        'posts' => $blog->all(true),
    ]);
});

$router->get('/blog/{slug}', static function (array $params) use ($blog, $config): string {
    $post = $blog->findBySlug((string) ($params['slug'] ?? ''), true);
    if ($post === null) {
        http_response_code(404);
        return View::render('errors/404', ['title' => 'A bejegyzés nem található']);
    }
    $base = rtrim((string) ($config['app']['url'] ?? ''), '/');
    $cover = trim((string) ($post['cover'] ?? ''));
    $meta = [
        'description' => trim((string) ($post['excerpt'] ?? '')),
        'og_type' => 'article',
        'og_image' => $cover !== '' ? $base . '/uploads/blog/' . $cover : '',
    ];
    return View::render('blog/show', [
        'title' => (string) $post['title'],
        'post' => $post,
        'recent' => array_slice(array_values(array_filter(
            $blog->all(true),
            static fn ($p) => (int) $p['id'] !== (int) $post['id']
        )), 0, 3),
        'meta' => $meta,
    ]);
});

$router->get('/aszf', static fn (): string => View::render('legal', [
    'title' => 'ÁSZF', 'heading' => 'Általános Szerződési Feltételek', 'mdFile' => 'aszf.txt',
]));
$router->get('/adatkezeles', static fn (): string => View::render('legal', [
    'title' => 'Adatkezelési tájékoztató', 'heading' => 'Adatkezelési tájékoztató', 'mdFile' => 'adatkezeles.txt',
]));

/* ------------------------------------------------------------------ */
/* Statikus oldalak (bemutatkozás, szállítás) + HTML oldaltérkép        */
/* ------------------------------------------------------------------ */

$router->get('/bemutatkozas', static fn (): string => View::render('page', [
    'title' => 'Bemutatkozás',
    'eyebrow' => 'Rólunk',
    'heading' => 'Net-Trade Hungary Kft.',
    'lead' => 'Több évtizedes tapasztalat: egyedi raklapgyártás, ipari csomagolás, nemzetközi fuvarozás.',
    'mdFile' => 'bemutatkozas.md',
]));

$router->get('/szallitas', static fn (): string => View::render('page', [
    'title' => 'Szállítási információk',
    'eyebrow' => 'Szállítás',
    'heading' => 'Szállítási információk',
    'lead' => 'Hogyan jut el hozzád a megrendelt áru – módok, díjak és határidők.',
    'mdFile' => 'szallitas.md',
]));

// Pályázati közzététel (kötelező nyilvánosság) – adminból szerkeszthető, csak akkor
// érhető el, ha legalább az infoblokk-kép, a cím vagy a leírás ki van töltve.
$router->get('/palyazat', static function () use ($settings): string {
    $s = $settings->all();
    $grant = [
        'title' => (string) ($s['grant_title'] ?? ''),
        'id' => (string) ($s['grant_id'] ?? ''),
        'fund' => (string) ($s['grant_fund'] ?? ''),
        'amount' => (string) ($s['grant_amount'] ?? ''),
        'intensity' => (string) ($s['grant_intensity'] ?? ''),
        'from' => (string) ($s['grant_from'] ?? ''),
        'to' => (string) ($s['grant_to'] ?? ''),
        'body' => (string) ($s['grant_body'] ?? ''),
        'image' => (string) ($s['grant_image'] ?? ''),
    ];
    if ($grant['image'] === '' && trim($grant['title']) === '' && trim($grant['body']) === '') {
        http_response_code(404);
        return View::render('errors/404', ['title' => 'Nincs pályázati közzététel']);
    }
    return View::render('grant', ['title' => 'Pályázati közzététel', 'grant' => $grant]);
});

$router->get('/oldalterkep', static function () use ($cats, $services, $blog, $settings): string {
    $pages = [
        ['url' => '/', 'label' => 'Főoldal'],
        ['url' => '/webshop', 'label' => 'Webshop'],
        ['url' => '/szolgaltatasok', 'label' => 'Tevékenységek'],
        ['url' => '/blog', 'label' => 'Blog'],
        ['url' => '/bemutatkozas', 'label' => 'Bemutatkozás'],
        ['url' => '/szallitas', 'label' => 'Szállítási információk'],
        ['url' => '/#kapcsolat', 'label' => 'Kapcsolat'],
    ];
    $serviceLinks = [];
    foreach ($services->all(true) as $s) {
        $serviceLinks[] = ['url' => '/szolgaltatasok/' . (string) $s['slug'], 'label' => (string) $s['title']];
    }
    $catLinks = [];
    foreach ($cats->all() as $key => $node) {
        if ($node['children'] === []) {
            $catLinks[] = ['url' => '/webshop?kat=' . urlencode((string) $key), 'label' => $cats->name((string) $key)];
        }
    }
    $blogLinks = [];
    foreach ($blog->all(true) as $post) {
        $blogLinks[] = ['url' => '/blog/' . (string) $post['slug'], 'label' => (string) $post['title']];
    }
    $legal = [
        ['url' => '/aszf', 'label' => 'ÁSZF'],
        ['url' => '/adatkezeles', 'label' => 'Adatkezelési tájékoztató'],
    ];
    $gs = $settings->all();
    if (trim((string) ($gs['grant_image'] ?? '')) !== '' || trim((string) ($gs['grant_title'] ?? '')) !== '' || trim((string) ($gs['grant_body'] ?? '')) !== '') {
        $legal[] = ['url' => '/palyazat', 'label' => 'Pályázati közzététel'];
    }
    return View::render('sitemap-page', [
        'title' => 'Oldaltérkép',
        'groups' => [
            ['title' => 'Fő oldalak', 'links' => $pages],
            ['title' => 'Tevékenységek', 'links' => $serviceLinks],
            ['title' => 'Webshop kategóriák', 'links' => $catLinks],
            ['title' => 'Blog', 'links' => $blogLinks],
            ['title' => 'Jogi', 'links' => $legal],
        ],
    ]);
});

/* ------------------------------------------------------------------ */
/* SEO – sitemap és robots (dinamikus, a katalógusból)                  */
/* ------------------------------------------------------------------ */

$router->get('/sitemap.xml', static function () use ($axel, $cats, $blog, $services, $config): string {
    header('Content-Type: application/xml; charset=UTF-8');
    $base = rtrim((string) $config['app']['url'], '/');
    $esc = static fn (string $u): string => htmlspecialchars($u, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    $rows = [['/', '1.0'], ['/webshop', '0.9'], ['/szolgaltatasok', '0.7'], ['/blog', '0.6']];
    foreach ($services->all(true) as $service) {
        $rows[] = ['/szolgaltatasok/' . rawurlencode((string) $service['slug']), '0.6'];
    }
    foreach ($cats->all() as $key => $node) {
        if ($node['children'] === []) { // csak levél-kategóriák
            $rows[] = ['/webshop?kat=' . urlencode((string) $key), '0.5'];
        }
    }
    foreach ($axel->products() as $p) {
        $rows[] = ['/termek/' . rawurlencode($p->slug), '0.7'];
    }
    foreach ($blog->all(true) as $post) {
        $rows[] = ['/blog/' . rawurlencode((string) $post['slug']), '0.5'];
    }
    $rows[] = ['/bemutatkozas', '0.5'];
    $rows[] = ['/szallitas', '0.4'];
    $rows[] = ['/aszf', '0.3'];
    $rows[] = ['/adatkezeles', '0.3'];

    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
        . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($rows as [$loc, $priority]) {
        $xml .= '  <url><loc>' . $esc($base . $loc) . '</loc>'
            . '<changefreq>weekly</changefreq><priority>' . $priority . '</priority></url>' . "\n";
    }
    return $xml . '</urlset>' . "\n";
});

$router->get('/robots.txt', static function () use ($config): string {
    header('Content-Type: text/plain; charset=UTF-8');
    $base = rtrim((string) $config['app']['url'], '/');
    return "User-agent: *\n"
        . "Allow: /\n"
        . "Disallow: /admin\n"
        . "Disallow: /telepito\n"
        . "Disallow: /penztar\n"
        . "Disallow: /fizetes\n"
        . "Disallow: /barion\n"
        . "Disallow: /kosar\n\n"
        . "Sitemap: {$base}/sitemap.xml\n";
});

$router->get('/kapcsolat', static function () use ($redirect): string {
    return $redirect('/#kapcsolat');
});

$router->post('/kapcsolat', static function () use ($config, $messages, $mailer, $redirect): string {
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
    $subject = 'Új üzenet a weboldalról' . ($company !== '' ? ' – ' . $company : '');
    $body = "Név: {$msg['name']}\nCég: {$company}\nE-mail: {$msg['email']}\nTelefon: {$msg['phone']}\n\nÜzenet:\n{$message}\n";
    $mailer->send((string) $config['contact']['email'], $subject, $body, ['reply_to' => (string) $msg['email']]);

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

/* ------------------------------------------------------------------ */
/* Vásárlói fiók (regisztráció / belépés / rendeléstörténet)           */
/* ------------------------------------------------------------------ */

$router->get('/belepes', static function () use ($redirect): string {
    if (Auth::user() !== null) {
        return $redirect('/fiokom');
    }
    return View::render('auth/login', ['title' => 'Belépés', 'error' => isset($_GET['hiba']), 'locked' => isset($_GET['zar'])]);
});

$router->post('/belepes', static function () use ($users, $throttle, $clientIp, $redirect): string {
    if (!Csrf::check($_POST['_csrf'] ?? null)) {
        return $redirect('/belepes');
    }
    $key = 'cust:' . $clientIp();
    if ($throttle->blocked($key)) {
        return $redirect('/belepes?zar=1');
    }
    if ($users !== null) {
        $user = $users->findByEmail(trim((string) ($_POST['email'] ?? '')));
        if ($user !== null && password_verify((string) ($_POST['password'] ?? ''), $user['password'])) {
            $throttle->clear($key);
            Auth::loginUser($user);
            return $redirect('/fiokom');
        }
    }
    $throttle->registerFailure($key);
    return $redirect('/belepes?hiba=1');
});

$router->get('/regisztracio', static function () use ($redirect): string {
    if (Auth::user() !== null) {
        return $redirect('/fiokom');
    }
    return View::render('auth/register', ['title' => 'Regisztráció', 'errors' => [], 'old' => []]);
});

$router->post('/regisztracio', static function () use ($users, $redirect): string {
    if (!Csrf::check($_POST['_csrf'] ?? null)) {
        return $redirect('/regisztracio');
    }
    $val = static fn (string $k): string => trim((string) ($_POST[$k] ?? ''));
    $name = $val('name');
    $email = $val('email');
    $password = (string) ($_POST['password'] ?? '');
    $errors = [];
    if ($name === '') {
        $errors['name'] = 'A név megadása kötelező.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Érvényes e-mail cím szükséges.';
    }
    if (strlen($password) < 6) {
        $errors['password'] = 'A jelszó legalább 6 karakter legyen.';
    }
    if (!isset($_POST['privacy'])) {
        $errors['privacy'] = 'Az adatkezelési tájékoztató elfogadása kötelező.';
    }
    if (!$errors && $users === null) {
        $errors['email'] = 'A regisztráció jelenleg nem elérhető.';
    }
    if (!$errors && $users !== null && $users->findByEmail($email) !== null) {
        $errors['email'] = 'Ezzel az e-mail címmel már van fiók – jelentkezz be.';
    }
    if ($errors) {
        return View::render('auth/register', ['title' => 'Regisztráció', 'errors' => $errors, 'old' => $_POST]);
    }
    $id = $users->create($name, $email, password_hash($password, PASSWORD_DEFAULT), 'customer');
    $user = $users->find($id);
    if ($user !== null) {
        Auth::loginUser($user);
    }
    return $redirect('/fiokom');
});

$router->post('/kilepes', static function () use ($redirect): string {
    if (Csrf::check($_POST['_csrf'] ?? null)) {
        Auth::logout();
    }
    return $redirect('/');
});

$router->get('/fiokom', static function () use ($orders, $redirect): string {
    $me = Auth::user();
    if ($me === null) {
        return $redirect('/belepes');
    }
    return View::render('account/index', [
        'title' => 'Fiókom',
        'me' => $me,
        'orders' => $orders->forCustomer((int) $me['id'], (string) $me['email']),
    ]);
});

$router->get('/webshop', static function () use ($axel, $cats, $productImages): string {
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

    // Kereső + szűrők (név/cikkszám, ár-tartomány bruttóban, csak raktáron).
    $q = trim((string) ($_GET['q'] ?? ''));
    $min = ($_GET['min'] ?? '') !== '' ? max(0, (int) $_GET['min']) : null;
    $max = ($_GET['max'] ?? '') !== '' ? max(0, (int) $_GET['max']) : null;
    $inStockOnly = isset($_GET['keszlet']);
    if ($q !== '') {
        $products = array_filter($products, static fn ($p) => mb_stripos($p->name, $q) !== false || stripos($p->sku, $q) !== false);
    }
    if ($min !== null) {
        $products = array_filter($products, static fn ($p) => $p->priceGross() >= $min);
    }
    if ($max !== null) {
        $products = array_filter($products, static fn ($p) => $p->priceGross() <= $max);
    }
    if ($inStockOnly) {
        $products = array_filter($products, static fn ($p) => $p->inStock());
    }

    // Rendezés (raktáron lévők előre, azon belül a választott szempont szerint).
    $sort = (string) ($_GET['rendezes'] ?? '');
    $products = array_values($products);
    $byStock = static fn ($a, $b): int => ($b->inStock() <=> $a->inStock());
    switch ($sort) {
        case 'ar-fel':
            usort($products, static fn ($a, $b): int => $byStock($a, $b) ?: ($a->priceGross() <=> $b->priceGross()));
            break;
        case 'ar-le':
            usort($products, static fn ($a, $b): int => $byStock($a, $b) ?: ($b->priceGross() <=> $a->priceGross()));
            break;
        case 'nev':
            usort($products, static fn ($a, $b): int => $byStock($a, $b) ?: strnatcasecmp($a->name, $b->name));
            break;
        default:
            $sort = '';
    }

    return View::render('shop/index', [
        'title' => $activeCat !== '' ? $cats->name($activeCat) : 'Webshop',
        'products' => $products,
        'catsTree' => $cats->tree(),
        'activeCat' => $activeCat,
        'activePath' => $path,
        'sort' => $sort,
        'q' => $q,
        'min' => $min,
        'max' => $max,
        'inStockOnly' => $inStockOnly,
        'cats' => $cats,
        'images' => $productImages->all(),
    ]);
});

$router->get('/termek/{slug}', static function (array $params) use ($axel, $cats, $productSeo, $productImages, $config): string {
    $product = $axel->findProduct($params['slug'] ?? '');
    if ($product === null) {
        http_response_code(404);
        return View::render('errors/404', ['title' => 'A termék nem található']);
    }
    $pseo = $productSeo->find($product->sku) ?? [];
    $images = $productImages->find($product->sku);
    // OG-kép: a kézi SEO-felülírás elsőbbséget élvez, különben az első termékkép.
    $base = rtrim((string) ($config['app']['url'] ?? ''), '/');
    $ogImage = trim((string) ($pseo['og_image'] ?? ''));
    if ($ogImage === '' && $images) {
        $ogImage = $base . '/uploads/products/' . $images[0];
    }
    $meta = [
        'title' => (string) ($pseo['title'] ?? ''),
        'description' => trim((string) ($pseo['description'] ?? '')) !== '' ? (string) $pseo['description'] : $product->short,
        'keywords' => (string) ($pseo['keywords'] ?? ''),
        'og_image' => $ogImage,
        'og_type' => 'product',
        'product_price' => (string) $product->priceGross(),
        'product_currency' => (string) ($config['shop']['currency'] ?? 'HUF'),
        'product_availability' => $product->inStock() ? 'in stock' : 'out of stock',
    ];

    // Kapcsolódó termékek: előbb azonos kategóriából, majd a szülő-ágból, max 4.
    $all = $axel->products();
    $related = [];
    $collect = static function (array $catKeys) use ($all, $product, &$related): void {
        foreach ($all as $p) {
            if (count($related) >= 4) {
                break;
            }
            if ($p->sku === $product->sku || in_array($p, $related, true)) {
                continue;
            }
            if (in_array($p->category, $catKeys, true)) {
                $related[] = $p;
            }
        }
    };
    $collect([$product->category]);
    if (count($related) < 4) {
        $path = $cats->path($product->category);
        $parentKey = count($path) >= 2 ? $path[count($path) - 2] : ($path[0] ?? $product->category);
        $collect($cats->branch($parentKey));
    }

    return View::render('shop/show', [
        'title' => $product->name,
        'product' => $product,
        'catPath' => $cats->path($product->category),
        'cats' => $cats,
        'related' => $related,
        'images' => $images,
        'meta' => $meta,
    ]);
});

$router->get('/kosar', static function () use ($axel, $productImages): string {
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
    return View::render('cart/index', ['title' => 'Kosár', 'lines' => $lines, 'total' => $total, 'images' => $productImages->all()]);
});

$router->post('/kosar/hozzaad', static function () use ($redirect, $wantsJson, $axel): string {
    $ok = false;
    $name = '';
    if (Csrf::check($_POST['_csrf'] ?? null) && isset($_POST['sku'])) {
        $sku = (string) $_POST['sku'];
        Cart::add($sku, max(1, (int) ($_POST['qty'] ?? 1)));
        $ok = true;
        foreach ($axel->products() as $p) {
            if ($p->sku === $sku) {
                $name = $p->name;
                break;
            }
        }
    }
    // Fetch/XHR esetén marad az oldalon a vásárló: JSON-t adunk (kosár-jelvény + buborék).
    if ($wantsJson()) {
        header('Content-Type: application/json; charset=UTF-8');
        return (string) json_encode(['ok' => $ok, 'count' => Cart::count(), 'name' => $name], JSON_UNESCAPED_UNICODE);
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

/** Rendelés-visszaigazoló e-mail a vevőnek, és értesítés a shopnak. */
$orderEmail = static function (array $order) use ($config, $mailer): void {
    $email = (string) ($order['customer']['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return;
    }
    $appName = (string) $config['app']['name'];
    $number = (string) ($order['number'] ?? '');
    $method = (string) ($order['payment']['method'] ?? '');
    $money = static fn (int $n): string => number_format($n, 0, ',', ' ') . ' Ft';
    $grossFmt = $money((int) ($order['totals']['gross'] ?? 0));

    $lines = '';
    foreach ($order['items'] ?? [] as $it) {
        $lines .= '- ' . (string) $it['name'] . ' x ' . (int) $it['qty'] . ' ' . (string) $it['unit']
            . ' = ' . $money((int) $it['subtotal']) . "\n";
    }

    $body = 'Kedves ' . (string) ($order['customer']['name'] ?? '') . "!\n\n"
        . "Köszönjük a rendelésed a(z) {$appName} webáruházban.\n\n"
        . "Rendelésszám: {$number}\n\nTételek:\n{$lines}\nVégösszeg (bruttó): {$grossFmt}\n\n";
    $body .= $method === 'transfer'
        ? "Fizetési mód: banki átutalás. Kérjük, utald a {$grossFmt} összeget a közleményben "
            . "a(z) {$number} rendelésszámmal; a számlaszámot külön jelezzük.\n\n"
        : "Fizetési mód: bankkártya - a fizetésed rögzítettük.\n\n";
    $body .= "Hamarosan felvesszük veled a kapcsolatot a szállítás egyeztetéséhez.\n\n"
        . "Üdvözlettel:\n{$appName}\n";

    $contactEmail = (string) ($config['contact']['email'] ?? '');
    $mailer->send($email, "Rendelés visszaigazolása - {$number}", $body, ['reply_to' => $contactEmail]);

    // Értesítés a shopnak (ha van érvényes cím).
    if (filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
        $adminBody = "Új rendelés érkezett.\n\nRendelésszám: {$number}\n"
            . 'Vevő: ' . (string) ($order['customer']['name'] ?? '') . " <{$email}>\n"
            . 'Telefon: ' . (string) ($order['customer']['phone'] ?? '') . "\n"
            . "Fizetési mód: {$method}\nVégösszeg: {$grossFmt}\n\nTételek:\n{$lines}";
        $mailer->send($contactEmail, "Új rendelés - {$number}", $adminBody, ['reply_to' => $email]);
    }
};

// Barion fizetési állapot egyeztetése a rendeléssel (a vásárló visszatérése és
// a szerver-szerver IPN is ezt hívja). Idempotens: a pending → paid átmenetet
// (számla + e-mail) csak egyszer végzi el.
$barionReconcile = static function (array $order) use ($orders, $barionClient, $finalizeInvoice, $orderEmail): array {
    if ($barionClient === null) {
        return $order;
    }
    $token = (string) ($order['token'] ?? '');
    if (($order['status'] ?? '') !== 'pending') {
        return $order;
    }
    $paymentId = (string) ($order['payment']['payment_id'] ?? '');
    if ($paymentId === '') {
        return $order;
    }
    try {
        $state = $barionClient->getPaymentState($paymentId);
    } catch (\Throwable $e) {
        error_log('Barion állapot-lekérés hiba: ' . $e->getMessage());
        return $order;
    }
    $status = (string) ($state['Status'] ?? '');
    if (App\Payment\BarionPaymentGateway::isPaid($status)) {
        $paid = $orders->update($token, [
            'status' => 'paid',
            'payment' => ['status' => 'paid', 'paid_at' => date('c'), 'barion_status' => $status],
        ]);
        $finalizeInvoice($token);
        if ($paid !== null) {
            $orderEmail($paid);
        }
        return $paid ?? $order;
    }
    if (App\Payment\BarionPaymentGateway::isFinalFailure($status)) {
        return $orders->update($token, ['payment' => ['status' => 'failed', 'barion_status' => $status]]) ?? $order;
    }
    return $order;
};

$router->get('/penztar', static function () use ($buildCart, $payment, $redirect): string {
    $cart = $buildCart();
    if (!$cart['items']) {
        return $redirect('/kosar');
    }
    $me = Auth::user();
    return View::render('shop/checkout', [
        'title' => 'Pénztár',
        'cart' => $cart,
        'paymentLabel' => $payment->label(),
        'errors' => [],
        'payError' => isset($_GET['fizetes']) && $_GET['fizetes'] === 'hiba',
        'old' => $me !== null ? ['name' => $me['name'], 'email' => $me['email']] : [],
    ]);
});

$router->post('/penztar', static function () use ($buildCart, $orders, $payment, $finalizeInvoice, $orderEmail, $redirect): string {
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
            'user_id' => (int) (Auth::user()['id'] ?? 0),
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
        $orderEmail($order);
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

$router->post('/fizetes/{token}', static function (array $params) use ($orders, $finalizeInvoice, $orderEmail, $redirect): string {
    $token = $params['token'] ?? '';
    $order = $orders->find($token);
    if ($order === null || !Csrf::check($_POST['_csrf'] ?? null)) {
        return $redirect('/kosar');
    }
    if (($order['status'] ?? '') !== 'pending') {
        return $redirect('/rendeles/' . $token);
    }
    if (($_POST['result'] ?? '') === 'success') {
        $paid = $orders->update($token, [
            'status' => 'paid',
            'payment' => ['status' => 'paid', 'paid_at' => date('c')],
        ]);
        $finalizeInvoice($token);
        if ($paid !== null) {
            $orderEmail($paid);
        }
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
/* Barion fizetés – visszatérés (vásárló) és callback (IPN)            */
/* ------------------------------------------------------------------ */

// A vásárló visszatér a Barion fizetőoldaláról: egyeztetjük az állapotot,
// sikeres fizetésnél ürítjük a kosarat, majd a visszaigazoló oldalra megyünk.
$router->get('/barion/vissza', static function () use ($orders, $barionReconcile, $redirect): string {
    $token = (string) ($_GET['token'] ?? '');
    $order = $orders->find($token);
    if ($order === null) {
        http_response_code(404);
        return View::render('errors/404', ['title' => 'Ismeretlen rendelés']);
    }
    $order = $barionReconcile($order);
    if (($order['status'] ?? '') === 'paid') {
        Cart::clear();
    }
    return $redirect('/rendeles/' . $token);
});

// Szerver-szerver értesítés (IPN): a Barion a paymentId-t küldi. A rendelést
// a tárolt PaymentId alapján keressük meg, és egyeztetjük az állapotát.
$barionCallback = static function () use ($orders, $barionReconcile): string {
    header('Content-Type: text/plain; charset=utf-8');
    $paymentId = (string) ($_GET['paymentId'] ?? $_POST['paymentId'] ?? '');
    $order = $orders->findByPaymentId($paymentId);
    if ($order !== null) {
        $barionReconcile($order);
    }
    http_response_code(200);
    return 'OK';
};
$router->get('/barion/callback', $barionCallback);
$router->post('/barion/callback', $barionCallback);

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
        'locked' => isset($_GET['zar']),
        'installed' => (bool) $config['installed'],
    ], '');
});

$router->post('/admin/login', static function () use ($config, $adminPw, $users, $throttle, $clientIp, $redirect): string {
    if (!Csrf::check($_POST['_csrf'] ?? null)) {
        return $redirect('/admin/login');
    }
    $key = 'admin:' . $clientIp();
    if ($throttle->blocked($key)) {
        return $redirect('/admin/login?zar=1');
    }
    if ($config['installed'] && $users !== null) {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $user = $users->findByEmail($email);
        if ($user !== null && password_verify($password, $user['password'])
            && in_array($user['role'], ['admin', 'editor'], true)) {
            $throttle->clear($key);
            Auth::loginUser($user);
            return $redirect('/admin');
        }
        $throttle->registerFailure($key);
        return $redirect('/admin/login?hiba=1');
    }
    // Telepítés előtti, egyszerű jelszavas belépés.
    if (Auth::attemptLegacy((string) ($_POST['password'] ?? ''), $adminPw)) {
        $throttle->clear($key);
        return $redirect('/admin');
    }
    $throttle->registerFailure($key);
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

$router->post('/admin/hirlevel/kuldes', static function () use ($guard, $subscribers, $templates, $settings, $config, $mailer, $redirect): string {
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
    $sent = 0;

    foreach ($recipients as $r) {
        $unsub = $base . '/hirlevel/leiratkozas?t=' . urlencode((string) $r['token']);
        $html = '<div style="font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:1.6;color:#222">'
            . $body
            . '<hr style="border:0;border-top:1px solid #e0e0e0;margin:28px 0 14px">'
            . '<p style="font-size:12px;color:#888">Ezt az üzenetet azért kapod, mert feliratkoztál a(z) '
            . htmlspecialchars($fromName, ENT_QUOTES) . ' hírlevelére.<br>'
            . '<a href="' . htmlspecialchars($unsub, ENT_QUOTES) . '" style="color:#888">Leiratkozás</a></p></div>';
        $okSent = $mailer->send((string) $r['email'], $subject, $html, [
            'html' => true,
            'from_email' => $fromEmail,
            'from_name' => $fromName,
            'list_unsubscribe' => $unsub,
        ]);
        if ($okSent) {
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
            'social_facebook' => $s['social_facebook'] ?? '',
            'social_youtube' => $s['social_youtube'] ?? '',
            'barion_poskey' => $s['barion_poskey'] ?? '',
            'barion_payee' => $s['barion_payee'] ?? '',
            'barion_env' => ($s['barion_env'] ?? 'test') === 'prod' ? 'prod' : 'test',
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
            'social_facebook' => $safeUrl((string) ($_POST['social_facebook'] ?? '')),
            'social_youtube' => $safeUrl((string) ($_POST['social_youtube'] ?? '')),
            'barion_poskey' => trim((string) ($_POST['barion_poskey'] ?? '')),
            'barion_payee' => trim((string) ($_POST['barion_payee'] ?? '')),
            'barion_env' => ($_POST['barion_env'] ?? 'test') === 'prod' ? 'prod' : 'test',
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
            'fb_pixel' => (string) ($s['fb_pixel'] ?? ''),
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
            'fb_pixel' => trim((string) ($_POST['fb_pixel'] ?? '')),
        ]);
        $_SESSION['_flash_admin'] = ['type' => 'ok', 'text' => 'SEO beállítások mentve.'];
    }
    return $redirect('/admin/seo');
});

$router->get('/admin/palyazat', static function () use ($adminView, $guard, $settings): string {
    $guard();
    $s = $settings->all();
    return $adminView('admin/grant', 'grant', [
        'title' => 'Pályázati közzététel',
        'values' => [
            'grant_title' => (string) ($s['grant_title'] ?? ''),
            'grant_id' => (string) ($s['grant_id'] ?? ''),
            'grant_fund' => (string) ($s['grant_fund'] ?? ''),
            'grant_amount' => (string) ($s['grant_amount'] ?? ''),
            'grant_intensity' => (string) ($s['grant_intensity'] ?? ''),
            'grant_from' => (string) ($s['grant_from'] ?? ''),
            'grant_to' => (string) ($s['grant_to'] ?? ''),
            'grant_body' => (string) ($s['grant_body'] ?? ''),
            'grant_image' => (string) ($s['grant_image'] ?? ''),
        ],
    ]);
});

$router->post('/admin/palyazat', static function () use ($guard, $settings, $uploadImage, $redirect): string {
    $guard();
    if (empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $_SESSION['_flash_admin'] = ['type' => 'error', 'text' => 'A feltöltött infoblokk-kép túl nagy a szerver korlátjához képest – tölts fel kisebbet. (A módosítások nem mentődtek.)'];
        return $redirect('/admin/palyazat');
    }
    if (!Csrf::check($_POST['_csrf'] ?? null)) {
        return $redirect('/admin/palyazat');
    }
    // Infoblokk-kép: opcionális eltávolítás; a feltöltött fájl (ha van) felülírja.
    $image = (string) $settings->get('grant_image', '');
    if (isset($_POST['grant_image_remove'])) {
        $image = '';
    }
    $imageError = null;
    $fileErr = $_FILES['grant_image_file']['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($fileErr !== UPLOAD_ERR_NO_FILE) {
        if ($fileErr === UPLOAD_ERR_INI_SIZE || $fileErr === UPLOAD_ERR_FORM_SIZE) {
            $imageError = 'Az infoblokk-kép túl nagy – tölts fel kisebbet (max 16 MB).';
        } elseif ($fileErr !== UPLOAD_ERR_OK) {
            $imageError = 'A kép feltöltése megszakadt, próbáld újra.';
        } else {
            $uploaded = $uploadImage($_FILES['grant_image_file'], dirname(__DIR__) . '/public/uploads/grant');
            if ($uploaded === null) {
                $imageError = 'A kép nem menthető – JPG/PNG/WEBP, max 16 MB legyen.';
            } else {
                $image = '/uploads/grant/' . $uploaded;
            }
        }
    }
    $settings->saveMany([
        'grant_title' => trim((string) ($_POST['grant_title'] ?? '')),
        'grant_id' => trim((string) ($_POST['grant_id'] ?? '')),
        'grant_fund' => trim((string) ($_POST['grant_fund'] ?? '')),
        'grant_amount' => trim((string) ($_POST['grant_amount'] ?? '')),
        'grant_intensity' => trim((string) ($_POST['grant_intensity'] ?? '')),
        'grant_from' => trim((string) ($_POST['grant_from'] ?? '')),
        'grant_to' => trim((string) ($_POST['grant_to'] ?? '')),
        'grant_body' => (string) ($_POST['grant_body'] ?? ''),
        'grant_image' => $image,
    ]);
    $_SESSION['_flash_admin'] = $imageError !== null
        ? ['type' => 'error', 'text' => 'Adatok mentve, de: ' . $imageError]
        : ['type' => 'ok', 'text' => 'Pályázati adatok mentve.'];
    return $redirect('/admin/palyazat');
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

$router->get('/admin/termek-kepek', static function () use ($adminView, $guard, $axel, $productImages): string {
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
        return $adminView('admin/product-images', 'products', ['title' => 'Termékképek', 'product' => null, 'images' => []]);
    }
    return $adminView('admin/product-images', 'products', [
        'title' => 'Képek · ' . $product->name,
        'product' => $product,
        'images' => $productImages->find($sku),
    ]);
});

$router->post('/admin/termek-kepek/feltoltes', static function () use ($guard, $productImages, $uploadImage, $redirect): string {
    $guard();
    $sku = (string) ($_POST['sku'] ?? '');
    $back = '/admin/termek-kepek?sku=' . urlencode($sku);
    // Túl nagy feltöltésnél a PHP eldobja a teljes $_POST-ot (post_max_size).
    if (empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $_SESSION['_flash_admin'] = ['type' => 'error', 'text' => 'A feltöltött fájl(ok) túl nagy(ok) a szerver korlátjához képest – tölts fel kisebb képet.'];
        return $redirect($sku !== '' ? $back : '/admin/termekek');
    }
    if (!Csrf::check($_POST['_csrf'] ?? null) || $sku === '') {
        return $redirect('/admin/termekek');
    }
    $uploaded = 0;
    $errored = false;
    $files = $_FILES['images'] ?? null;
    if (is_array($files) && isset($files['name']) && is_array($files['name'])) {
        for ($i = 0, $n = count($files['name']); $i < $n; $i++) {
            if (((int) ($files['error'][$i] ?? UPLOAD_ERR_NO_FILE)) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $one = [
                'name' => $files['name'][$i] ?? '', 'type' => $files['type'][$i] ?? '',
                'tmp_name' => $files['tmp_name'][$i] ?? '', 'error' => $files['error'][$i] ?? 1,
                'size' => $files['size'][$i] ?? 0,
            ];
            $name = $uploadImage($one, dirname(__DIR__) . '/public/uploads/products');
            if ($name !== null) {
                $productImages->add($sku, $name);
                $uploaded++;
            } else {
                $errored = true;
            }
        }
    }
    $_SESSION['_flash_admin'] = $uploaded > 0
        ? ['type' => 'ok', 'text' => "{$uploaded} kép feltöltve." . ($errored ? ' (Néhány kimaradt – csak JPG/PNG/WEBP, max 16 MB.)' : '')]
        : ['type' => 'error', 'text' => 'Nem sikerült kép feltöltése – JPG/PNG/WEBP, max 16 MB legyen.'];
    return $redirect($back);
});

$router->post('/admin/termek-kepek/torles', static function () use ($guard, $productImages, $redirect): string {
    $guard();
    $sku = (string) ($_POST['sku'] ?? '');
    if (Csrf::check($_POST['_csrf'] ?? null) && $sku !== '') {
        $file = basename((string) ($_POST['file'] ?? ''));
        if ($file !== '' && in_array($file, $productImages->find($sku), true)) {
            $productImages->remove($sku, $file);
            @unlink(dirname(__DIR__) . '/public/uploads/products/' . $file);
        }
    }
    return $redirect('/admin/termek-kepek?sku=' . urlencode($sku));
});

$router->post('/admin/termek-kepek/elsodleges', static function () use ($guard, $productImages, $redirect): string {
    $guard();
    $sku = (string) ($_POST['sku'] ?? '');
    if (Csrf::check($_POST['_csrf'] ?? null) && $sku !== '') {
        $productImages->makePrimary($sku, basename((string) ($_POST['file'] ?? '')));
    }
    return $redirect('/admin/termek-kepek?sku=' . urlencode($sku));
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

/* ------------------------------------------------------------------ */
/* Blog (admin)                                                        */
/* ------------------------------------------------------------------ */

$router->get('/admin/blog', static function () use ($adminView, $guard, $blog): string {
    $guard();
    return $adminView('admin/blog', 'blog', ['title' => 'Blog', 'posts' => $blog->all()]);
});

$router->get('/admin/blog/szerkesztes', static function () use ($adminView, $guard, $blog): string {
    $guard();
    $id = (int) ($_GET['id'] ?? 0);
    $post = $id > 0 ? $blog->find($id) : null;
    return $adminView('admin/blog-edit', 'blog', [
        'title' => $post ? 'Bejegyzés szerkesztése' : 'Új bejegyzés',
        'post' => $post,
    ]);
});

$router->post('/admin/blog/mentes', static function () use ($guard, $blog, $uploadImage, $redirect): string {
    $guard();
    // Túl nagy feltöltésnél a PHP eldobja a teljes $_POST-ot (post_max_size).
    if (empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $_SESSION['_flash_admin'] = ['type' => 'error', 'text' => 'A feltöltött kép túl nagy a szerver korlátjához képest – tölts fel kisebbet. (A módosítások nem mentődtek.)'];
        return $redirect('/admin/blog');
    }
    if (!Csrf::check($_POST['_csrf'] ?? null)) {
        return $redirect('/admin/blog');
    }
    $id = (int) ($_POST['id'] ?? 0);
    $existing = $id > 0 ? $blog->find($id) : null;
    $post = [
        'title' => trim((string) ($_POST['title'] ?? '')),
        'slug' => trim((string) ($_POST['slug'] ?? '')),
        'excerpt' => trim((string) ($_POST['excerpt'] ?? '')),
        'body' => (string) ($_POST['body'] ?? ''),
        'author' => trim((string) ($_POST['author'] ?? '')),
        'published' => isset($_POST['published']) ? 1 : 0,
        'cover' => (string) ($existing['cover'] ?? ''),
    ];
    if ($id > 0) {
        $post['id'] = $id;
    }

    if ($post['title'] === '') {
        $_SESSION['_flash_admin'] = ['type' => 'error', 'text' => 'A cím megadása kötelező.'];
        return $redirect('/admin/blog/szerkesztes' . ($id ? '?id=' . $id : ''));
    }

    $coverError = null;
    $fileErr = $_FILES['cover']['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($fileErr !== UPLOAD_ERR_NO_FILE) {
        if ($fileErr === UPLOAD_ERR_INI_SIZE || $fileErr === UPLOAD_ERR_FORM_SIZE) {
            $coverError = 'A borítókép túl nagy – tölts fel kisebbet (max 16 MB).';
        } elseif ($fileErr !== UPLOAD_ERR_OK) {
            $coverError = 'A borítókép feltöltése megszakadt, próbáld újra.';
        } else {
            $uploaded = $uploadImage($_FILES['cover'], dirname(__DIR__) . '/public/uploads/blog');
            if ($uploaded === null) {
                $coverError = 'A borítókép nem menthető – JPG/PNG/WEBP, max 16 MB legyen.';
            } else {
                if (($existing['cover'] ?? '') !== '') {
                    @unlink(dirname(__DIR__) . '/public/uploads/blog/' . basename((string) $existing['cover']));
                }
                $post['cover'] = $uploaded;
            }
        }
    }

    $blog->save($post);
    $_SESSION['_flash_admin'] = $coverError !== null
        ? ['type' => 'error', 'text' => 'Adatok mentve, de: ' . $coverError]
        : ['type' => 'ok', 'text' => 'Bejegyzés mentve.'];
    return $redirect('/admin/blog');
});

$router->post('/admin/blog/torles', static function () use ($guard, $blog, $redirect): string {
    $guard();
    if (Csrf::check($_POST['_csrf'] ?? null)) {
        $id = (int) ($_POST['id'] ?? 0);
        $post = $id > 0 ? $blog->find($id) : null;
        if ($post !== null && ($post['cover'] ?? '') !== '') {
            @unlink(dirname(__DIR__) . '/public/uploads/blog/' . basename((string) $post['cover']));
        }
        $blog->delete($id);
    }
    return $redirect('/admin/blog');
});

/* ------------------------------------------------------------------ */
/* Tevékenységek (admin)                                                */
/* ------------------------------------------------------------------ */

$router->get('/admin/szolgaltatasok', static function () use ($adminView, $guard, $services): string {
    $guard();
    return $adminView('admin/services', 'services_admin', ['title' => 'Tevékenységek', 'services' => $services->all(false, true)]);
});

$router->get('/admin/szolgaltatasok/szerkesztes', static function () use ($adminView, $guard, $services): string {
    $guard();
    $id = (int) ($_GET['id'] ?? 0);
    $service = $id > 0 ? $services->find($id, true) : null;
    return $adminView('admin/service-edit', 'services_admin', [
        'title' => $service ? 'Tevékenység szerkesztése' : 'Új tevékenység',
        'service' => $service,
    ]);
});

$router->post('/admin/szolgaltatasok/mentes', static function () use ($guard, $services, $uploadImage, $redirect): string {
    $guard();
    // Üres $_POST, de volt törzs → a feltöltött kép meghaladta a szerver korlátját.
    if (empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        $_SESSION['_flash_admin'] = ['type' => 'error', 'text' => 'A feltöltött kép túl nagy a szerver korlátjához képest – tölts fel kisebbet. (A módosítások nem mentődtek.)'];
        return $redirect('/admin/szolgaltatasok');
    }
    if (!Csrf::check($_POST['_csrf'] ?? null)) {
        return $redirect('/admin/szolgaltatasok');
    }
    $id = (int) ($_POST['id'] ?? 0);

    // Kép: URL/útvonal mező, opcionális eltávolítás; a feltöltött fájl (ha van) felülírja.
    $image = trim((string) ($_POST['image'] ?? ''));
    if (isset($_POST['image_remove'])) {
        $image = '';
    }
    $imageError = null;
    $fileErr = $_FILES['image_file']['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($fileErr !== UPLOAD_ERR_NO_FILE) {
        if ($fileErr === UPLOAD_ERR_INI_SIZE || $fileErr === UPLOAD_ERR_FORM_SIZE) {
            $imageError = 'A kép túl nagy – tölts fel kisebbet (max 16 MB).';
        } elseif ($fileErr !== UPLOAD_ERR_OK) {
            $imageError = 'A kép feltöltése megszakadt, próbáld újra.';
        } else {
            $uploaded = $uploadImage($_FILES['image_file'], dirname(__DIR__) . '/public/uploads/services');
            if ($uploaded === null) {
                $imageError = 'A kép nem menthető – JPG/PNG/WEBP, max 16 MB legyen.';
            } else {
                $image = '/uploads/services/' . $uploaded;
            }
        }
    }

    $service = [
        'title' => trim((string) ($_POST['title'] ?? '')),
        'slug' => trim((string) ($_POST['slug'] ?? '')),
        'icon' => trim((string) ($_POST['icon'] ?? '')),
        'image' => $image,
        'summary' => trim((string) ($_POST['summary'] ?? '')),
        'body' => (string) ($_POST['body'] ?? ''),
        'sort' => (int) ($_POST['sort'] ?? 0),
        'published' => isset($_POST['published']) ? 1 : 0,
        'i18n' => is_array($_POST['i18n'] ?? null) ? $_POST['i18n'] : [],
    ];
    if ($id > 0) {
        $service['id'] = $id;
    }
    if ($service['title'] === '') {
        $_SESSION['_flash_admin'] = ['type' => 'error', 'text' => 'A cím megadása kötelező.'];
        return $redirect('/admin/szolgaltatasok/szerkesztes' . ($id ? '?id=' . $id : ''));
    }
    $services->save($service);
    $_SESSION['_flash_admin'] = $imageError !== null
        ? ['type' => 'error', 'text' => 'Adatok mentve, de: ' . $imageError]
        : ['type' => 'ok', 'text' => 'Tevékenység mentve.'];
    return $redirect('/admin/szolgaltatasok');
});

$router->post('/admin/szolgaltatasok/torles', static function () use ($guard, $services, $redirect): string {
    $guard();
    if (Csrf::check($_POST['_csrf'] ?? null)) {
        $services->delete((int) ($_POST['id'] ?? 0));
        $_SESSION['_flash_admin'] = ['type' => 'ok', 'text' => 'Tevékenység törölve.'];
    }
    return $redirect('/admin/szolgaltatasok');
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

try {
    echo $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
} catch (\Throwable $e) {
    if (!empty($config['app']['debug'])) {
        throw $e; // fejlesztéskor lássuk a hibát
    }
    error_log('[NetTrade] Kezeletlen hiba: ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo View::render('errors/500', ['title' => 'Hiba történt']);
}
