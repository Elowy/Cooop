<?php

/**
 * Blog kezdő-bejegyzések. Ez csak a kiinduló feltöltés – utána a vezérlőpult
 * „Blog" oldaláról szerkeszthetők, bővíthetők és onnan tölthető fel borítókép.
 *
 * Mezők: title, slug (opcionális, a címből generálódik), excerpt (rövid
 * összefoglaló a kártyához), body (Markdown), author, published (1/0).
 */

return [
    [
        'title'   => 'Mire figyeljünk az exportcsomagolásnál?',
        'excerpt' => 'A tengerentúli szállítás más követelményeket támaszt, mint a belföldi. Összeszedtük a legfontosabb szempontokat a biztonságos célba éréshez.',
        'author'  => 'Net-Trade Hungary',
        'published' => 1,
        'body'    => <<<MD
Az exportcsomagolás nem csupán egy doboz és némi töltőanyag kérdése. A termék
hosszú utat tesz meg, gyakran több átrakodással, eltérő klímán és páratartalom
mellett. Az alábbi szempontok segítenek elkerülni a szállítás közbeni kárt.

## Klíma és páratartalom

A tengeri konténerben a hőmérséklet-ingadozás miatt lecsapódó pára (úgynevezett
„konténereső") komoly gondot okozhat. Érdemes **párazáró fóliát** és
nedvszívó (szilikagél) betétet alkalmazni, fémalkatrészeknél pedig
korrózióvédő (VCI) csomagolást.

## Teherbírás és rögzítés

- A raklap és a láda mérete a termékhez igazodjon, ne fordítva.
- A súlypont legyen alacsonyan és középen.
- A belső rögzítés akadályozza meg az elmozdulást átrakodáskor.

## Jelölés

A nemzetközi szállításnál a megfelelő **kezelési jelek** (törékeny, ezzel
felfelé, esőtől óvni) és a fitoszanitáris jelölés egyaránt fontos.

Ha egyedi méretű, exportbiztos csomagolásra van szükséged, keress minket
bizalommal – a tervezéstől a legyártásig mindenben segítünk.
MD,
    ],
    [
        'title'   => 'ISPM-15: miért fontos a hőkezelt raklap?',
        'excerpt' => 'A fa csomagolóanyagok nemzetközi szabványa, az ISPM-15 sok exportpiacon kötelező. Röviden összefoglaljuk, mit jelent a gyakorlatban.',
        'author'  => 'Net-Trade Hungary',
        'published' => 1,
        'body'    => <<<MD
Az **ISPM-15** a fa csomagolóanyagokra (raklap, láda, alátétfa) vonatkozó
nemzetközi növény-egészségügyi szabvány. Célja, hogy a fában megbúvó kártevők
ne terjedjenek országhatárokon át.

## Mit ír elő?

A 6 mm-nél vastagabb tűlevelű és lombos fát **hőkezeléssel** (HT – a fa
maghőmérséklete legalább 56 °C-ot ér el 30 percen át) kell kezelni, majd a
hivatalos **IPPC-jelöléssel** ellátni.

## Hol kötelező?

Számos ország – köztük az USA, Kína, Ausztrália és Kanada – csak ISPM-15
jelöléssel ellátott fa csomagolóanyagot enged be. Jelölés nélkül a küldemény
visszafordítható vagy megsemmisíthető.

## Hogyan segítünk?

Raklapjaink és ládáink igény szerint **hőkezelt, IPPC-jelölt** kivitelben is
elérhetők, így nyugodtan szállíthatsz a világ bármely pontjára. Kérdés esetén
vedd fel velünk a kapcsolatot.
MD,
    ],
    [
        'title'   => 'Egyedi raklap vagy szabványos? Így válassz',
        'excerpt' => 'Nem mindig a szabványos EUR-raklap a legjobb megoldás. Megmutatjuk, mikor éri meg egyedi méretben gondolkodni.',
        'author'  => 'Net-Trade Hungary',
        'published' => 1,
        'body'    => <<<MD
A szabványos EUR-raklap kényelmes és sok helyen elfogadott, de nem minden
termékhez ideális. Az alábbi szempontok segítenek eldönteni, mikor érdemes
egyedi méretben gondolkodni.

## Mikor elég a szabványos?

- A termék mérete és súlya jól illeszkedik a 1200×800 mm-es raklaphoz.
- Csererendszerű (pool) logisztikában mozogsz.
- A raktári állványaid EUR-méretre vannak kialakítva.

## Mikor jobb az egyedi?

- **Túlméretes vagy szabálytalan** alakú gép, berendezés.
- Jobb **helykihasználás** a konténerben vagy a kamionban.
- Speciális **teherbírás** vagy rögzítési pont szükséges.

## A mi megközelítésünk

Több évtizedes tapasztalattal tervezünk és gyártunk **standard és egyedi**
méretű raklapokat, ládákat egyaránt. Mondd el, mit szállítasz, és segítünk
kiválasztani a leggazdaságosabb, legbiztonságosabb megoldást.
MD,
    ],
];
