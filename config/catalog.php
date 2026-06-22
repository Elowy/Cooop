<?php

/**
 * Placeholder termékkatalógus. Ez szimulálja az Axel Pro-ból érkező
 * termék- és készletadatokat – éles bekötéskor az AxelGateway adaptere
 * (XML import/export vagy REST API) tölti fel ugyanezt a szerkezetet.
 *
 * Mezők: sku, slug, category, name, unit, price_net (HUF, nettó), vat (%),
 *        stock (egységben), short, icon
 */

return [
    ['sku' => 'CSM-EXP-01', 'slug' => 'export-lada',        'category' => 'csomagolas', 'name' => 'Exportláda',            'unit' => 'db',  'price_net' => 12000, 'vat' => 27, 'stock' => 35,   'icon' => 'packaging', 'short' => 'Méretre gyártott, ISPM-15 jelölésű exportláda.'],
    ['sku' => 'CSM-RKL-01', 'slug' => 'egyutas-raklap',     'category' => 'csomagolas', 'name' => 'Egyutas raklap',        'unit' => 'db',  'price_net' => 3500,  'vat' => 27, 'stock' => 240,  'icon' => 'packaging', 'short' => 'Könnyű, költséghatékony egyutas szállítóraklap.'],

    ['sku' => 'FUR-PAL-01', 'slug' => 'fenyo-pallo',        'category' => 'furesz',     'name' => 'Fenyő palló',           'unit' => 'm³', 'price_net' => 95000, 'vat' => 27, 'stock' => 18,   'icon' => 'wood',      'short' => 'Szárított fenyő palló, építőipari minőség.'],
    ['sku' => 'FUR-DSZ-01', 'slug' => 'fenyo-deszka',       'category' => 'furesz',     'name' => 'Fenyő deszka',          'unit' => 'm³', 'price_net' => 88000, 'vat' => 27, 'stock' => 12,   'icon' => 'wood',      'short' => 'Sokoldalú fenyő deszka, raktárról.'],

    ['sku' => 'TUZ-VGY-01', 'slug' => 'vegyes-tuzifa',      'category' => 'tuzifa',     'name' => 'Vegyes tűzifa',         'unit' => 'm³', 'price_net' => 32000, 'vat' => 5,  'stock' => 60,   'icon' => 'lumber',    'short' => 'Konyhakész vegyes tűzifa, kiszállítással.'],
    ['sku' => 'TUZ-BUK-01', 'slug' => 'bukk-tuzifa',        'category' => 'tuzifa',     'name' => 'Bükk tűzifa',           'unit' => 'm³', 'price_net' => 45000, 'vat' => 5,  'stock' => 25,   'icon' => 'lumber',    'short' => 'Magas fűtőértékű, hasított bükk tűzifa.'],

    ['sku' => 'TGL-B30-01', 'slug' => 'britterm-tegla-30',  'category' => 'tegla',      'name' => 'BRITTERM tégla 30',     'unit' => 'db',  'price_net' => 420,   'vat' => 27, 'stock' => 8600, 'icon' => 'brick',     'short' => 'Vázkerámia falazóelem, 30 cm falvastagsághoz.'],
    ['sku' => 'TGL-B38-01', 'slug' => 'britterm-tegla-38',  'category' => 'tegla',      'name' => 'BRITTERM tégla 38',     'unit' => 'db',  'price_net' => 560,   'vat' => 27, 'stock' => 5400, 'icon' => 'brick',     'short' => 'Hőszigetelő vázkerámia, 38 cm falvastagsághoz.'],
];
