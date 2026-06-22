<?php

/**
 * Webáruház kategóriafa (a megadott termékstruktúra alapján).
 * Háromszintű hierarchia; minden csomópont: key (URL-azonosító), name, children.
 * A termékek (config/catalog.php) a levél-kategóriák key-ére hivatkoznak.
 */

return [
    ['key' => 'epitoanyagok', 'name' => 'Építőanyagok', 'children' => [
        ['key' => 'falazo-anyagok', 'name' => 'Falazó anyagok', 'children' => [
            ['key' => 'tegla', 'name' => 'Tégla'],
            ['key' => 'athidalo', 'name' => 'Áthidaló'],
        ]],
        ['key' => 'homok', 'name' => 'Homok'],
        ['key' => 'faanyagok', 'name' => 'Faanyagok'],
    ]],

    ['key' => 'kert-szabadido', 'name' => 'Kert és szabadidő', 'children' => [
        ['key' => 'haziallat', 'name' => 'Háziállat', 'children' => [
            ['key' => 'kutya', 'name' => 'Kutya'],
            ['key' => 'macska', 'name' => 'Macska'],
            ['key' => 'ragcsalo', 'name' => 'Rágcsáló'],
            ['key' => 'madar', 'name' => 'Madár'],
        ]],
        ['key' => 'vadmadar', 'name' => 'Vadmadár', 'children' => [
            ['key' => 'madaretetes', 'name' => 'Madáretetés'],
            ['key' => 'madarodu', 'name' => 'Madárodú'],
        ]],
        ['key' => 'kerti-jatszoter', 'name' => 'Kerti játszótér'],
        ['key' => 'kerti-butor', 'name' => 'Kerti bútor'],
        ['key' => 'novenytermesztes', 'name' => 'Növénytermesztés'],
        ['key' => 'kerti-dekoracio', 'name' => 'Kerti dekoráció'],
        ['key' => 'novenytam', 'name' => 'Növénytám'],
        ['key' => 'tarolo-fatermek', 'name' => 'Tároló fatermék'],
    ]],

    ['key' => 'fahazak', 'name' => 'Faházak', 'children' => [
        ['key' => 'nyaralo', 'name' => 'Nyaraló'],
        ['key' => 'lombhaz', 'name' => 'Lombház'],
        ['key' => 'pavilon', 'name' => 'Pavilon'],
        ['key' => 'uszohaz', 'name' => 'Úszóház'],
    ]],

    ['key' => 'design-dekoracio', 'name' => 'Design, dekoráció', 'children' => [
        ['key' => 'lakasdekoracio', 'name' => 'Lakásdekoráció'],
        ['key' => 'bolti-berendezes', 'name' => 'Bolti berendezés'],
        ['key' => 'viragkoteszet', 'name' => 'Virágkötészet'],
        ['key' => 'cegtablak', 'name' => 'Cégtáblák'],
    ]],
];
