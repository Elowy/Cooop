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

use App\Core\Router;
use App\Core\View;

$config = require dirname(__DIR__) . '/config/config.php';

if ($config['app']['debug']) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

$router = new Router();

$router->get('/', static fn (): string => View::render('home', ['title' => null]));

echo $router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
