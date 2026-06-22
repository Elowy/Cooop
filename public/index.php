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
use App\Integration\MockAxelGateway;

$config = require dirname(__DIR__) . '/config/config.php';

if ($config['app']['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

session_start();

// Az Axel-kapu egyetlen példánya. Éles bekötéskor itt cseréljük a megvalósítást.
$axel = new MockAxelGateway();

// Kategóriafa (config/categories.php).
$cats = new Categories();

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
]));

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
/* Vezérlőpult (admin)                                                 */
/* ------------------------------------------------------------------ */

$adminPw = (string) ($config['admin']['password'] ?? '');
$lowStock = (int) ($config['admin']['low_stock'] ?? 10);
$pwWeak = $adminPw === '' || $adminPw === 'admin';

/** Admin nézet a közös adatokkal + admin layouttal. */
$adminView = static function (string $tpl, string $active, array $extra = []) use ($pwWeak): string {
    return View::render($tpl, array_merge(['active' => $active, 'pwWeak' => $pwWeak], $extra), 'admin');
};

/** Belépés-ellenőrzés; ha nincs jogosultság, átirányít. */
$guard = static function () use ($redirect): void {
    if (!Auth::check()) {
        $redirect('/admin/login');
    }
};

$router->get('/admin/login', static function () use ($redirect): string {
    if (Auth::check()) {
        return $redirect('/admin');
    }
    return View::render('admin/login', ['title' => 'Belépés', 'error' => isset($_GET['hiba'])], '');
});

$router->post('/admin/login', static function () use ($adminPw, $redirect): string {
    if (Csrf::check($_POST['_csrf'] ?? null) && Auth::attempt((string) ($_POST['password'] ?? ''), $adminPw)) {
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

$router->get('/admin/rendelesek', static function () use ($adminView, $guard): string {
    $guard();
    return $adminView('admin/orders', 'orders', ['title' => 'Rendelések']);
});

$router->get('/admin/integracio', static function () use ($adminView, $guard): string {
    $guard();
    return $adminView('admin/integration', 'integration', ['title' => 'Axel integráció']);
});

echo $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
