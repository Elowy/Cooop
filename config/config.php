<?php

/**
 * Alkalmazás konfiguráció.
 *
 * Az értékek környezeti változókból olvashatók, így a beállítások
 * nem kerülnek be a verziókezelésbe. Lokálisan a lenti alapértékek lépnek életbe.
 */

return [
    'app' => [
        'name'     => getenv('APP_NAME') ?: 'Net-Trade Hungary',
        'tagline'  => 'Ipari csomagolás · Faipari termékek · Fuvarozás',
        'url'      => getenv('APP_URL') ?: 'http://localhost:8000',
        'env'      => getenv('APP_ENV') ?: 'local',
        'debug'    => filter_var(getenv('APP_DEBUG') ?: 'true', FILTER_VALIDATE_BOOL),
        'currency' => 'Ft',
    ],

    'contact' => [
        'email'   => 'info@net-trade.hu',
        'phone'   => '+36 20 415 2695',
        'address' => '2660 Balassagyarmat, Mártírok útja 72.',
        'hours'   => 'H–P: 8:00–16:00',
    ],

    'database' => [
        'driver'   => getenv('DB_DRIVER') ?: 'mysql',
        'host'     => getenv('DB_HOST') ?: '127.0.0.1',
        'port'     => getenv('DB_PORT') ?: '3306',
        'name'     => getenv('DB_NAME') ?: 'net_trade',
        'user'     => getenv('DB_USER') ?: 'root',
        'password' => getenv('DB_PASSWORD') ?: '',
        'charset'  => 'utf8mb4',
    ],
];
