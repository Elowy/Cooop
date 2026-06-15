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

use App\Core\Auth;
use App\Core\Cart;
use App\Core\Csrf;
use App\Core\Router;
use App\Core\Upload;
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
    return View::render('about', ['title' => 'Bemutatkozás']);
});

/* ------------------------------------------------------------------ *
 *  Admin – belépés
 * ------------------------------------------------------------------ */

$router->get('/admin/bejelentkezes', static function () use ($config): string {
    if (Auth::check()) {
        redirect('/admin');
        return '';
    }
    return View::render('admin/login', ['title' => 'Belépés', 'error' => null], 'admin');
});

$router->post('/admin/bejelentkezes', static function () use ($config): string {
    $user = (string) ($_POST['user'] ?? '');
    $pass = (string) ($_POST['password'] ?? '');
    if (!Csrf::check($_POST['_token'] ?? null)) {
        return View::render('admin/login', ['title' => 'Belépés', 'error' => 'Érvénytelen munkamenet, próbáld újra.'], 'admin');
    }
    if (Auth::attempt($user, $pass, $config)) {
        redirect('/admin');
        return '';
    }
    return View::render('admin/login', ['title' => 'Belépés', 'error' => 'Hibás felhasználónév vagy jelszó.'], 'admin');
});

$router->post('/admin/kijelentkezes', static function (): string {
    Auth::logout();
    redirect('/admin/bejelentkezes');
    return '';
});

/* ------------------------------------------------------------------ *
 *  Admin – vezérlőpult
 * ------------------------------------------------------------------ */

$router->get('/admin', static function (): string {
    if (adminGuard()) {
        return '';
    }
    return View::render('admin/dashboard', [
        'title'      => 'Vezérlőpult',
        'products'   => Product::adminAll(),
        'categories' => Category::withCounts(),
    ], 'admin');
});

/* ------------------------------------------------------------------ *
 *  Admin – kategóriák
 * ------------------------------------------------------------------ */

$router->get('/admin/kategoriak', static function (): string {
    if (adminGuard()) {
        return '';
    }
    return View::render('admin/categories/index', [
        'title'      => 'Kategóriák',
        'categories' => Category::withCounts(),
        'flash'      => flash(),
    ], 'admin');
});

$router->get('/admin/kategoriak/uj', static function (): string {
    if (adminGuard()) {
        return '';
    }
    return View::render('admin/categories/form', [
        'title'    => 'Új kategória',
        'category' => null,
        'error'    => null,
    ], 'admin');
});

$router->post('/admin/kategoriak/uj', static function (): string {
    if (adminGuard()) {
        return '';
    }
    if (!Csrf::check($_POST['_token'] ?? null)) {
        return adminError('Érvénytelen munkamenet.');
    }
    $id = Category::create($_POST);
    if ($id === false) {
        return View::render('admin/categories/form', [
            'title' => 'Új kategória', 'category' => $_POST,
            'error' => 'A mentés nem sikerült. A név megadása kötelező.',
        ], 'admin');
    }
    setFlash('Kategória létrehozva.');
    redirect('/admin/kategoriak');
    return '';
});

$router->get('/admin/kategoriak/{id}/szerkesztes', static function (array $p): string {
    if (adminGuard()) {
        return '';
    }
    $category = Category::find((int) $p['id']);
    if ($category === null) {
        return adminError('A kategória nem található.', 404);
    }
    return View::render('admin/categories/form', [
        'title' => 'Kategória szerkesztése', 'category' => $category, 'error' => null,
    ], 'admin');
});

$router->post('/admin/kategoriak/{id}/szerkesztes', static function (array $p): string {
    if (adminGuard()) {
        return '';
    }
    if (!Csrf::check($_POST['_token'] ?? null)) {
        return adminError('Érvénytelen munkamenet.');
    }
    $id = (int) $p['id'];
    if (!Category::update($id, $_POST)) {
        return View::render('admin/categories/form', [
            'title' => 'Kategória szerkesztése', 'category' => ['id' => $id] + $_POST,
            'error' => 'A mentés nem sikerült. A név megadása kötelező.',
        ], 'admin');
    }
    setFlash('Kategória frissítve.');
    redirect('/admin/kategoriak');
    return '';
});

$router->post('/admin/kategoriak/{id}/torles', static function (array $p): string {
    if (adminGuard()) {
        return '';
    }
    if (!Csrf::check($_POST['_token'] ?? null)) {
        return adminError('Érvénytelen munkamenet.');
    }
    Category::delete((int) $p['id']);
    setFlash('Kategória törölve.');
    redirect('/admin/kategoriak');
    return '';
});

/* ------------------------------------------------------------------ *
 *  Admin – termékek
 * ------------------------------------------------------------------ */

$router->get('/admin/termekek', static function (): string {
    if (adminGuard()) {
        return '';
    }
    return View::render('admin/products/index', [
        'title'    => 'Termékek',
        'products' => Product::adminAll(),
        'flash'    => flash(),
    ], 'admin');
});

$router->get('/admin/termekek/uj', static function (): string {
    if (adminGuard()) {
        return '';
    }
    return View::render('admin/products/form', [
        'title'      => 'Új termék',
        'product'    => null,
        'categories' => Category::all(),
        'error'      => null,
    ], 'admin');
});

$router->post('/admin/termekek/uj', static function (): string {
    if (adminGuard()) {
        return '';
    }
    if (!Csrf::check($_POST['_token'] ?? null)) {
        return adminError('Érvénytelen munkamenet.');
    }
    return saveProduct(null);
});

$router->get('/admin/termekek/{id}/szerkesztes', static function (array $p): string {
    if (adminGuard()) {
        return '';
    }
    $product = Product::find((int) $p['id']);
    if ($product === null) {
        return adminError('A termék nem található.', 404);
    }
    return View::render('admin/products/form', [
        'title'      => 'Termék szerkesztése',
        'product'    => $product,
        'categories' => Category::all(),
        'error'      => null,
    ], 'admin');
});

$router->post('/admin/termekek/{id}/szerkesztes', static function (array $p): string {
    if (adminGuard()) {
        return '';
    }
    if (!Csrf::check($_POST['_token'] ?? null)) {
        return adminError('Érvénytelen munkamenet.');
    }
    return saveProduct((int) $p['id']);
});

$router->post('/admin/termekek/{id}/torles', static function (array $p): string {
    if (adminGuard()) {
        return '';
    }
    if (!Csrf::check($_POST['_token'] ?? null)) {
        return adminError('Érvénytelen munkamenet.');
    }
    Product::delete((int) $p['id']);
    setFlash('Termék törölve.');
    redirect('/admin/termekek');
    return '';
});

/* ------------------------------------------------------------------ *
 *  Segédfüggvények
 * ------------------------------------------------------------------ */

function redirect(string $path): void
{
    header('Location: ' . $path, true, 302);
}

/** Admin védelem: ha nincs belépve, átirányít a belépéshez és true-t ad. */
function adminGuard(): bool
{
    if (!Auth::check()) {
        redirect('/admin/bejelentkezes');
        return true;
    }
    return false;
}

function adminError(string $message, int $code = 400): string
{
    http_response_code($code);
    return View::render('admin/message', ['title' => 'Hiba', 'message' => $message], 'admin');
}

function setFlash(string $message): void
{
    $_SESSION['flash'] = $message;
}

function flash(): ?string
{
    $msg = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $msg;
}

/**
 * Termék mentése (létrehozás vagy frissítés) képfeltöltéssel együtt.
 */
function saveProduct(?int $id): string
{
    $existing = $id !== null ? Product::find($id) : null;
    $current = $existing['image'] ?? null;

    try {
        $image = Upload::image($_FILES['image'] ?? null, $current);
    } catch (\RuntimeException $e) {
        return View::render('admin/products/form', [
            'title'      => $id === null ? 'Új termék' : 'Termék szerkesztése',
            'product'    => ($id !== null ? ['id' => $id] : []) + $_POST,
            'categories' => Category::all(),
            'error'      => $e->getMessage(),
        ], 'admin');
    }

    $data = $_POST;
    $data['image'] = $image;
    $data['featured'] = isset($_POST['featured']) ? 1 : 0;
    $data['active'] = isset($_POST['active']) ? 1 : 0;

    $ok = $id === null ? Product::create($data) !== false : Product::update($id, $data);
    if (!$ok) {
        return View::render('admin/products/form', [
            'title'      => $id === null ? 'Új termék' : 'Termék szerkesztése',
            'product'    => ($id !== null ? ['id' => $id] : []) + $data,
            'categories' => Category::all(),
            'error'      => 'A mentés nem sikerült. A név megadása kötelező.',
        ], 'admin');
    }

    setFlash($id === null ? 'Termék létrehozva.' : 'Termék frissítve.');
    redirect('/admin/termekek');
    return '';
}

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
