<?php

/**
 * Alkalmazás-konfiguráció.
 *
 * Az értékek környezeti változókkal felülírhatók, VAGY – ami Windows/IIS
 * alatt kényelmesebb – egy config/config.local.php fájllal, ami ezt a tömböt
 * rekurzívan felülírja. (Lásd config/config.local.php.example.)
 * A config.local.php nem kerül a verziókövetésbe.
 */

$config = [
    'app' => [
        'name'    => getenv('APP_NAME') ?: 'Net-Trade Hungary',
        'short'   => 'NT',
        'tagline' => 'Ipari csomagolás · Faipari gyártás · Logisztika',
        'url'     => getenv('APP_URL') ?: 'http://localhost:8000',
        // Élesben maradjon false! Lokális fejlesztéshez: APP_DEBUG=true.
        'debug'   => filter_var(getenv('APP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOL),
    ],

    'contact' => [
        'email'        => 'info@net-trade.hu',
        'phone'        => '+36 20 387 1450',
        'person'       => 'Nagy László (projektvezető)',
        'address'      => '2660 Balassagyarmat, Mártírok útja 72.',
        'messages_dir' => getenv('MESSAGES_DIR') ?: dirname(__DIR__) . '/storage/messages',
    ],

    // Vezérlőpult belépés. Élesben adj meg erős jelszót (ADMIN_PASSWORD vagy config.local.php)!
    'admin' => [
        'password'  => getenv('ADMIN_PASSWORD') ?: 'admin',
        'low_stock' => 10, // ennyi alatt figyelmeztet a készletre
    ],

    // Axel Pro integráció. Ugyanazon a gépen (VPS) futó Axelhez helyi mappás
    // adatcsere. A 'gateway' később 'xml'-re vált, ha kész az XmlAxelGateway.
    'axel' => [
        'gateway'      => getenv('AXEL_GATEWAY') ?: 'mock',     // mock | xml | rest
        'exchange_dir' => getenv('AXEL_DIR') ?: dirname(__DIR__) . '/storage/axel',
    ],

    // Webshop / pénztár.
    'shop' => [
        'payment'    => getenv('PAYMENT_GATEWAY') ?: 'mock',    // mock | simplepay | barion | stripe
        'currency'   => 'HUF',
        'orders_dir' => getenv('ORDERS_DIR') ?: dirname(__DIR__) . '/storage/orders',
    ],

    // Adatbázis. A telepítő (/telepito) írja felül a config/db.php fájlban.
    'installed' => false,
    'db' => [
        'driver' => 'mysql',  // mysql | sqlite
        'host'   => 'localhost',
        'port'   => '3306',
        'name'   => '',
        'user'   => '',
        'pass'   => '',
    ],
];

// Helyi felülírás (titkok, éles útvonalak) – ha létezik.
$localFile = __DIR__ . '/config.local.php';
if (is_file($localFile)) {
    $override = require $localFile;
    if (is_array($override)) {
        $config = array_replace_recursive($config, $override);
    }
}

// Adatbázis-kapcsolat a telepítőtől (config/db.php). Ha létezik → telepítve van.
$dbFile = __DIR__ . '/db.php';
if (is_file($dbFile)) {
    $db = require $dbFile;
    if (is_array($db)) {
        $config['db'] = array_merge($config['db'], $db);
        $config['installed'] = true;
    }
}

return $config;
