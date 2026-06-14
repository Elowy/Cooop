<?php

declare(strict_types=1);

session_start();

/**
 * Front controller – minden kérés ide fut be a .htaccess átírás miatt.
 */

// Egyszerű PSR-4 jellegű autoloader az App\ névtérhez.
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $file = dirname(__DIR__) . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

use App\Core\Cart;
use App\Core\Router;
use App\Core\View;
use App\Models\Category;
use App\Models\Product;

$config = require dirname(__DIR__) . '/config/config.php';

if ($config['app']['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

$router = new Router();

/* ------------------------------------------------------------------ *
 *  Útvonalak
 * ------------------------------------------------------------------ */

$router->get('/', static function (): string {
    return View::render('home', [
        'title'      => null,
        'categories' => Category::all(),
        'featured'   => Product::featured(),
    ]);
});

$router->get('/termekek', static function (): string {
    $cat = isset($_GET['kategoria']) ? (string) $_GET['kategoria'] : null;
    $q   = isset($_GET['kereses']) ? trim((string) $_GET['kereses']) : null;

    return View::render('products/index', [
        'title'      => 'Termékek',
        'categories' => Category::all(),
        'products'   => Product::all($cat, $q),
        'activeCat'  => $cat,
        'search'     => $q,
    ]);
});

$router->get('/termek/{slug}', static function (array $params): string {
    $product = Product::findBySlug($params['slug']);
    if ($product === null) {
        http_response_code(404);
        return View::render('errors/404', ['title' => 'A termék nem található']);
    }
    return View::render('products/show', [
        'title'   => $product['name'],
        'product' => $product,
    ]);
});

$router->get('/kosar', static function (): string {
    return View::render('cart/index', [
        'title' => 'Kosár',
        'cart'  => Cart::detailed(),
    ]);
});

$router->post('/kosar/hozzaad', static function (): string {
    $id  = (int) ($_POST['product_id'] ?? 0);
    $qty = (int) ($_POST['qty'] ?? 1);
    if ($id > 0) {
        Cart::add($id, $qty);
    }
    redirect('/kosar');
    return '';
});

$router->post('/kosar/frissit', static function (): string {
    foreach (($_POST['qty'] ?? []) as $id => $qty) {
        Cart::update((int) $id, (int) $qty);
    }
    redirect('/kosar');
    return '';
});

$router->post('/kosar/torol', static function (): string {
    Cart::remove((int) ($_POST['product_id'] ?? 0));
    redirect('/kosar');
    return '';
});

$router->get('/penztar', static function (): string {
    return View::render('cart/checkout', [
        'title' => 'Pénztár',
        'cart'  => Cart::detailed(),
    ]);
});

$router->post('/penztar', static function (): string {
    $cart = Cart::detailed();
    if (empty($cart['lines'])) {
        redirect('/kosar');
        return '';
    }
    // Itt jönne a rendelés mentése / fizetés indítása.
    Cart::clear();
    return View::render('cart/success', [
        'title' => 'Sikeres rendelés',
        'name'  => trim((string) ($_POST['name'] ?? '')),
    ]);
});

$router->get('/kapcsolat', static function (): string {
    return View::render('contact', ['title' => 'Kapcsolat']);
});

$router->post('/kapcsolat', static function (): string {
    // Demó: itt küldenénk e-mailt. Most csak visszajelzünk.
    return View::render('contact', [
        'title' => 'Kapcsolat',
        'sent'  => true,
        'name'  => trim((string) ($_POST['name'] ?? '')),
    ]);
});

$router->get('/rolunk', static function (): string {
    return View::render('about', ['title' => 'Rólunk']);
});

/* ------------------------------------------------------------------ *
 *  Segédfüggvény
 * ------------------------------------------------------------------ */

function redirect(string $path): void
{
    header('Location: ' . $path, true, 302);
}

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
