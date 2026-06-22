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

/** Átirányítás + futás leállítása (POST műveletek után). */
$redirect = static function (string $to): string {
    header('Location: ' . $to, true, 303);
    exit;
};

$router = new Router();

$router->get('/', static fn (): string => View::render('home', [
    'title' => null,
    'featured' => array_slice($axel->products(), 0, 3),
]));

$router->get('/webshop', static function () use ($axel): string {
    $cat = isset($_GET['kat']) ? (string) $_GET['kat'] : '';
    $products = $axel->products();
    if ($cat !== '') {
        $products = array_values(array_filter($products, static fn ($p) => $p->category === $cat));
    }
    return View::render('shop/index', [
        'title' => 'Webshop',
        'products' => $products,
        'activeCat' => $cat,
    ]);
});

$router->get('/termek/{slug}', static function (array $params) use ($axel): string {
    $product = $axel->findProduct($params['slug'] ?? '');
    if ($product === null) {
        http_response_code(404);
        return View::render('errors/404', ['title' => 'A termék nem található']);
    }
    return View::render('shop/show', ['title' => $product->name, 'product' => $product]);
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

echo $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
