<?php

/**
 * Alkalmazás-konfiguráció. Az értékek környezeti változókkal felülírhatók.
 */

return [
    'app' => [
        'name'    => getenv('APP_NAME') ?: 'Net-Trade Hungary',
        'short'   => 'NT',
        'tagline' => 'Ipari csomagolás · Faipari gyártás · Logisztika',
        'url'     => getenv('APP_URL') ?: 'http://localhost:8000',
        'debug'   => filter_var(getenv('APP_DEBUG') ?: 'true', FILTER_VALIDATE_BOOL),
    ],

    'contact' => [
        'email'   => 'info@net-trade.hu',
        'phone'   => '+36 20 415 2695',
        'address' => '2660 Balassagyarmat, Mártírok útja 72.',
    ],

    // Vezérlőpult belépés. Élesben az ADMIN_PASSWORD környezeti változóval add meg!
    'admin' => [
        'password'  => getenv('ADMIN_PASSWORD') ?: 'admin',
        'low_stock' => 10, // ennyi alatt figyelmeztet a készletre
    ],
];
