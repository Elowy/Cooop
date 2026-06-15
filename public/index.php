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
use App\Core\Lang;
use App\Core\Router;
use App\Core\View;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;

$config = require dirname(__DIR__) . '/config/config.php';

if ($config['app']['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

Lang::boot();

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
    return View::render('about', ['title' => Lang::t('nav.about')]);
});

$router->get('/galeria', static function (): string {
    return View::render('gallery', ['title' => Lang::t('gallery.title')]);
});

$router->get('/kalkulator', static function (): string {
    return View::render('calculator', [
        'title'    => Lang::t('calc.title'),
        'products' => Product::all(),
    ]);
});

/* ------------------------------------------------------------------ *
 *  Autentikáció: regisztráció, belépés, kilépés
 * ------------------------------------------------------------------ */

$router->get('/regisztracio', static function (): string {
    if (Auth::check()) {
        redirect('/');
        return '';
    }
    return View::render('auth/register', ['title' => Lang::t('auth.register_title')]);
});

$router->post('/regisztracio', static function (): string {
    if (Auth::check()) {
        redirect('/');
        return '';
    }
    $name  = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $pass  = (string) ($_POST['password'] ?? '');
    $pass2 = (string) ($_POST['password2'] ?? '');

    $error = null;
    if (!Csrf::check($_POST['_csrf'] ?? null)) {
        $error = 'auth.err_csrf';
    } elseif (App\Core\Database::getConnection() === null) {
        $error = 'auth.err_db';
    } elseif ($name === '' || $email === '' || $pass === '') {
        $error = 'auth.err_required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'auth.err_email';
    } elseif (strlen($pass) < 6) {
        $error = 'auth.err_short';
    } elseif ($pass !== $pass2) {
        $error = 'auth.err_match';
    } elseif (User::findByEmail($email) !== null) {
        $error = 'auth.err_taken';
    }

    if ($error !== null) {
        return View::render('auth/register', [
            'title' => Lang::t('auth.register_title'),
            'error' => $error,
            'old'   => ['name' => $name, 'email' => $email],
        ]);
    }

    // Az első felhasználó automatikusan admin.
    $role = User::count() === 0 ? 'admin' : 'user';
    $id   = User::create($name, $email, password_hash($pass, PASSWORD_DEFAULT), $role);
    Auth::login(User::find($id) ?? []);
    redirect($role === 'admin' ? '/admin' : '/');
    return '';
});

$router->get('/belepes', static function (): string {
    if (Auth::check()) {
        redirect('/');
        return '';
    }
    return View::render('auth/login', ['title' => Lang::t('auth.login_title')]);
});

$router->post('/belepes', static function (): string {
    $email = trim((string) ($_POST['email'] ?? ''));
    $pass  = (string) ($_POST['password'] ?? '');

    $error = null;
    if (!Csrf::check($_POST['_csrf'] ?? null)) {
        $error = 'auth.err_csrf';
    } elseif (App\Core\Database::getConnection() === null) {
        $error = 'auth.err_db';
    } elseif (!Auth::attempt($email, $pass)) {
        $error = 'auth.err_login';
    }

    if ($error !== null) {
        return View::render('auth/login', [
            'title' => Lang::t('auth.login_title'),
            'error' => $error,
            'old'   => ['email' => $email],
        ]);
    }
    redirect(Auth::isAdmin() ? '/admin' : '/');
    return '';
});

$router->post('/kilepes', static function (): string {
    if (Csrf::check($_POST['_csrf'] ?? null)) {
        Auth::logout();
    }
    redirect('/');
    return '';
});

/* ------------------------------------------------------------------ *
 *  Admin: termékek kezelése (csak admin)
 * ------------------------------------------------------------------ */

$router->get('/admin', static function (): string {
    if ($r = requireAdmin()) {
        return $r;
    }
    return View::render('admin/index', [
        'title'    => Lang::t('admin.title'),
        'products' => Product::allForAdmin(),
        'flash'    => $_GET['uzenet'] ?? null,
    ]);
});

$router->get('/admin/termek/uj', static function (): string {
    if ($r = requireAdmin()) {
        return $r;
    }
    return View::render('admin/product-form', [
        'title'      => Lang::t('admin.new_product'),
        'product'    => null,
        'categories' => Category::all(),
        'action'     => '/admin/termek',
    ]);
});

$router->post('/admin/termek', static function (): string {
    if ($r = requireAdmin()) {
        return $r;
    }
    if (!Csrf::check($_POST['_csrf'] ?? null)) {
        redirect('/admin');
        return '';
    }
    $data = productInput();
    $data['image'] = handleImageUpload() ?? 'placeholder.svg';
    Product::create($data);
    redirect('/admin?uzenet=created');
    return '';
});

$router->get('/admin/termek/{id}/szerkeszt', static function (array $p): string {
    if ($r = requireAdmin()) {
        return $r;
    }
    $product = Product::find((int) $p['id']);
    if ($product === null) {
        http_response_code(404);
        return View::render('errors/404', ['title' => Lang::t('e404.title')]);
    }
    return View::render('admin/product-form', [
        'title'      => Lang::t('admin.edit'),
        'product'    => $product,
        'categories' => Category::all(),
        'action'     => '/admin/termek/' . (int) $product['id'],
    ]);
});

$router->post('/admin/termek/{id}', static function (array $p): string {
    if ($r = requireAdmin()) {
        return $r;
    }
    $id = (int) $p['id'];
    $existing = Product::find($id);
    if ($existing === null || !Csrf::check($_POST['_csrf'] ?? null)) {
        redirect('/admin');
        return '';
    }
    $data = productInput();
    $data['image'] = handleImageUpload() ?? ($existing['image'] ?? 'placeholder.svg');
    Product::update($id, $data);
    redirect('/admin?uzenet=updated');
    return '';
});

$router->post('/admin/termek/{id}/torles', static function (array $p): string {
    if ($r = requireAdmin()) {
        return $r;
    }
    if (Csrf::check($_POST['_csrf'] ?? null)) {
        Product::delete((int) $p['id']);
    }
    redirect('/admin?uzenet=deleted');
    return '';
});

/* ------------------------------------------------------------------ *
 *  Segédfüggvények
 * ------------------------------------------------------------------ */

function redirect(string $path): void
{
    header('Location: ' . $path, true, 302);
}

/** Admin őr: átirányít, ha nincs jogosultság. Visszaad egy stringet, ha meg kell állni. */
function requireAdmin(): ?string
{
    if (!Auth::isAdmin()) {
        redirect(Auth::check() ? '/' : '/belepes');
        return '';
    }
    return null;
}

/**
 * Termék űrlap adatainak kinyerése és normalizálása.
 *
 * @return array<string, mixed>
 */
function productInput(): array
{
    $name = trim((string) ($_POST['name'] ?? ''));
    $slug = trim((string) ($_POST['slug'] ?? ''));
    if ($slug === '') {
        $slug = Product::slugify($name);
    }
    return [
        'category_id' => (int) ($_POST['category_id'] ?? 0) ?: null,
        'slug'        => Product::slugify($slug),
        'name'        => $name,
        'short'       => trim((string) ($_POST['short'] ?? '')),
        'description' => trim((string) ($_POST['description'] ?? '')),
        'price'       => (float) ($_POST['price'] ?? 0),
        'unit'        => trim((string) ($_POST['unit'] ?? 'db')) ?: 'db',
        'stock'       => (int) ($_POST['stock'] ?? 0),
        'featured'    => !empty($_POST['featured']),
        'active'      => !empty($_POST['active']),
    ];
}

/**
 * Feltöltött termékkép kezelése. Sikeres feltöltéskor a fájlnevet adja vissza,
 * egyébként null-t (a hívó a meglévő / alapértelmezett képet használja).
 */
function handleImageUpload(): ?string
{
    if (empty($_FILES['image']['tmp_name']) || ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }
    $allowed = ['svg' => 'image/svg+xml', 'png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'webp' => 'image/webp'];
    $ext = strtolower(pathinfo((string) $_FILES['image']['name'], PATHINFO_EXTENSION));
    if (!isset($allowed[$ext]) || $_FILES['image']['size'] > 3 * 1024 * 1024) {
        return null;
    }
    $dir = dirname(__DIR__) . '/public/assets/img/products';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $filename = 'p-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
    if (move_uploaded_file($_FILES['image']['tmp_name'], $dir . '/' . $filename)) {
        return $filename;
    }
    return null;
}

/** Fordítás (escape-elt kontextusban használandó). */
function t(string $key): string
{
    return Lang::t($key);
}

/** Aktuális nyelvkód. */
function lang(): string
{
    return Lang::code();
}

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
