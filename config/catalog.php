<?php

/**
 * Placeholder termékkatalógus az új kategóriastruktúrához. A 'category' mező
 * egy levél-kategória key-ére mutat (lásd config/categories.php).
 * Éles üzemben ezt az AxelGateway adaptere tölti fel az Axel Pro adataival.
 *
 * Mezők: sku, slug, category, name, unit, price_net (HUF, nettó), vat (%),
 *        stock, short, icon
 */

return [
    // --- Építőanyagok ---
    ['sku' => 'EP-TGL-30', 'slug' => 'britterm-tegla-30',   'category' => 'tegla',            'name' => 'BRITTERM tégla 30',        'unit' => 'db', 'price_net' => 420,     'vat' => 27, 'stock' => 8600, 'icon' => 'brick',     'short' => 'Vázkerámia falazóelem 30 cm falvastagsághoz.'],
    ['sku' => 'EP-TGL-38', 'slug' => 'britterm-tegla-38',   'category' => 'tegla',            'name' => 'BRITTERM tégla 38',        'unit' => 'db', 'price_net' => 560,     'vat' => 27, 'stock' => 5400, 'icon' => 'brick',     'short' => 'Hőszigetelő vázkerámia 38 cm falvastagsághoz.'],
    ['sku' => 'EP-ATH-12', 'slug' => 'keramia-athidalo-120','category' => 'athidalo',         'name' => 'Kerámia áthidaló 120 cm',  'unit' => 'db', 'price_net' => 4200,    'vat' => 27, 'stock' => 320,  'icon' => 'brick',     'short' => 'Teherhordó kerámia áthidaló nyílásáthidaláshoz.'],
    ['sku' => 'EP-HOM-04', 'slug' => 'mosott-homok-0-4',    'category' => 'homok',            'name' => 'Mosott homok 0-4 mm',      'unit' => 'm³','price_net' => 9800,    'vat' => 27, 'stock' => 150,  'icon' => 'sand',      'short' => 'Osztályozott mosott homok beton- és vakolatkészítéshez.'],
    ['sku' => 'EP-FAP-01', 'slug' => 'fenyo-pallo',         'category' => 'faanyagok',        'name' => 'Fenyő palló 5×15',         'unit' => 'm³','price_net' => 95000,   'vat' => 27, 'stock' => 18,   'icon' => 'wood',      'short' => 'Szárított fenyő palló, építőipari minőség.'],
    ['sku' => 'EP-FAD-01', 'slug' => 'fenyo-deszka',        'category' => 'faanyagok',        'name' => 'Fenyő deszka 2,5×15',      'unit' => 'm³','price_net' => 88000,   'vat' => 27, 'stock' => 24,   'icon' => 'wood',      'short' => 'Sokoldalú fenyő deszka, raktárról.'],

    // --- Kert és szabadidő ---
    ['sku' => 'KE-KUT-01', 'slug' => 'fa-kutyahaz',         'category' => 'kutya',            'name' => 'Fa kutyaház „Bodri”',      'unit' => 'db', 'price_net' => 28900,   'vat' => 27, 'stock' => 22,   'icon' => 'paw',       'short' => 'Hőszigetelt, időjárásálló fa kutyaház.'],
    ['sku' => 'KE-MAC-01', 'slug' => 'macska-kaparofa',     'category' => 'macska',           'name' => 'Macska kaparófa',          'unit' => 'db', 'price_net' => 12900,   'vat' => 27, 'stock' => 40,   'icon' => 'paw',       'short' => 'Többszintes kaparófa pihenőkkel.'],
    ['sku' => 'KE-RAG-01', 'slug' => 'horcsog-ketrec',      'category' => 'ragcsalo',         'name' => 'Hörcsög ketrec',           'unit' => 'db', 'price_net' => 8900,    'vat' => 27, 'stock' => 35,   'icon' => 'paw',       'short' => 'Felszerelt rágcsáló ketrec kiegészítőkkel.'],
    ['sku' => 'KE-MAD-01', 'slug' => 'diszmadar-kalitka',   'category' => 'madar',            'name' => 'Díszmadár kalitka',        'unit' => 'db', 'price_net' => 15900,   'vat' => 27, 'stock' => 18,   'icon' => 'bird',      'short' => 'Tágas kalitka díszmadarak számára.'],
    ['sku' => 'KE-MET-01', 'slug' => 'fa-madareteto',       'category' => 'madaretetes',      'name' => 'Fa madáretető',            'unit' => 'db', 'price_net' => 4900,    'vat' => 27, 'stock' => 120,  'icon' => 'bird',      'short' => 'Tetős fa madáretető kerti kiakasztáshoz.'],
    ['sku' => 'KE-MOD-01', 'slug' => 'madarodu',            'category' => 'madarodu',         'name' => 'Madárodú kismadaraknak',   'unit' => 'db', 'price_net' => 3500,    'vat' => 27, 'stock' => 200,  'icon' => 'bird',      'short' => 'Költőodú énekesmadaraknak, kezeletlen fából.'],
    ['sku' => 'KE-JAT-01', 'slug' => 'fa-homokozo',         'category' => 'kerti-jatszoter',  'name' => 'Fa homokozó fedéllel',     'unit' => 'db', 'price_net' => 34900,   'vat' => 27, 'stock' => 15,   'icon' => 'house',     'short' => 'Fedeles fa homokozó kerti játszótérre.'],
    ['sku' => 'KE-BUT-01', 'slug' => 'kerti-pad-tomorfa',   'category' => 'kerti-butor',      'name' => 'Kerti pad tömörfából',     'unit' => 'db', 'price_net' => 39900,   'vat' => 27, 'stock' => 26,   'icon' => 'deco',      'short' => 'Masszív tömörfa kerti pad, lakkozott.'],
    ['sku' => 'KE-NOV-01', 'slug' => 'magasagyas-120x80',   'category' => 'novenytermesztes', 'name' => 'Magaságyás 120×80',        'unit' => 'db', 'price_net' => 24900,   'vat' => 27, 'stock' => 30,   'icon' => 'leaf',      'short' => 'Fa magaságyás zöldségtermesztéshez.'],
    ['sku' => 'KE-DEK-01', 'slug' => 'kerti-diszkut',       'category' => 'kerti-dekoracio',  'name' => 'Kerti díszkút',            'unit' => 'db', 'price_net' => 18900,   'vat' => 27, 'stock' => 12,   'icon' => 'deco',      'short' => 'Dekoratív fa díszkút virágtartóval.'],
    ['sku' => 'KE-NTM-01', 'slug' => 'novenytamasz-racs',   'category' => 'novenytam',        'name' => 'Növénytámasz rács',        'unit' => 'db', 'price_net' => 2900,    'vat' => 27, 'stock' => 240,  'icon' => 'leaf',      'short' => 'Futónövény-támasz rács, fenyőből.'],
    ['sku' => 'KE-TAR-01', 'slug' => 'szerszamtarolo-lada', 'category' => 'tarolo-fatermek',  'name' => 'Szerszámtároló láda',      'unit' => 'db', 'price_net' => 16900,   'vat' => 27, 'stock' => 28,   'icon' => 'packaging', 'short' => 'Zárható fa tárolóláda kerti szerszámokhoz.'],

    // --- Faházak ---
    ['sku' => 'FH-NYA-24', 'slug' => 'nyaralo-fahaz-24',    'category' => 'nyaralo',          'name' => 'Nyaraló faház 24 m²',      'unit' => 'db', 'price_net' => 1290000, 'vat' => 27, 'stock' => 4,    'icon' => 'house',     'short' => 'Kulcsrakész nyaraló faház, 24 m² alapterület.'],
    ['sku' => 'FH-LOM-01', 'slug' => 'gyermek-lombhaz',     'category' => 'lombhaz',          'name' => 'Gyermek lombház',          'unit' => 'db', 'price_net' => 189000,  'vat' => 27, 'stock' => 8,    'icon' => 'house',     'short' => 'Emelt fa lombház gyerekeknek, létrával.'],
    ['sku' => 'FH-PAV-33', 'slug' => 'kerti-pavilon-3x3',   'category' => 'pavilon',          'name' => 'Kerti pavilon 3×3 m',      'unit' => 'db', 'price_net' => 159000,  'vat' => 27, 'stock' => 10,   'icon' => 'house',     'short' => 'Fa kerti pavilon zsindelytetővel.'],
    ['sku' => 'FH-USZ-01', 'slug' => 'uszohaz-alapmodul',   'category' => 'uszohaz',          'name' => 'Úszóház alapmodul',        'unit' => 'db', 'price_net' => 2490000, 'vat' => 27, 'stock' => 2,    'icon' => 'house',     'short' => 'Úszóház alapmodul, egyedi kivitelezéshez.'],

    // --- Design, dekoráció ---
    ['sku' => 'DD-LAK-01', 'slug' => 'fali-polc-szett',     'category' => 'lakasdekoracio',   'name' => 'Fali polc szett',          'unit' => 'db', 'price_net' => 13900,   'vat' => 27, 'stock' => 45,   'icon' => 'deco',      'short' => 'Háromrészes fa fali polc szett.'],
    ['sku' => 'DD-BOL-01', 'slug' => 'fa-bemutato-allvany', 'category' => 'bolti-berendezes', 'name' => 'Fa bemutató állvány',      'unit' => 'db', 'price_net' => 45900,   'vat' => 27, 'stock' => 14,   'icon' => 'deco',      'short' => 'Bolti termékbemutató állvány tömörfából.'],
    ['sku' => 'DD-VIR-80', 'slug' => 'fa-viraglada-80',     'category' => 'viragkoteszet',    'name' => 'Fa virágláda 80 cm',       'unit' => 'db', 'price_net' => 6900,    'vat' => 27, 'stock' => 60,   'icon' => 'leaf',      'short' => 'Kültéri fa virágláda, impregnált.'],
    ['sku' => 'DD-CEG-01', 'slug' => 'gravirozott-cegtabla','category' => 'cegtablak',        'name' => 'Gravírozott fa cégtábla',  'unit' => 'db', 'price_net' => 22900,   'vat' => 27, 'stock' => 20,   'icon' => 'deco',      'short' => 'Egyedi gravírozású fa cégtábla.'],
];
