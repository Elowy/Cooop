<?php

/**
 * Tevékenységek / szolgáltatások kezdő-adatai (a net-trade.hu „Tevékenységek"
 * menüje alapján). Ez csak a kiinduló feltöltés – utána a vezérlőpult
 * „Tevékenységek" oldaláról szerkeszthetők, bővíthetők.
 *
 * Mezők: title, slug (opcionális, a címből), icon (kártya-ikon kulcs),
 * summary (rövid, a kártyán/menüben), body (Markdown), sort, published.
 */

return [
    [
        'title'   => 'Ipari csomagolás',
        'icon'    => 'packaging',
        'sort'    => 1,
        'summary' => 'Gépek, gépsorok és berendezések szállításra kész, exportbiztos csomagolása – a tengeri, közúti és légi fuvarozás követelményeihez igazítva.',
        'body'    => <<<MD
Gépek, gépsorok és komplett gyártósorok **szállításra való csomagolása** – a
megrendelő telephelyén vagy a miénken. A csomagolást mindig a szállítási módhoz,
a célországhoz és a termék érzékenységéhez tervezzük.

## Szolgáltatásaink

- **Gépcsomagolás** – egyedi méretű fa ládák, kalodák és rögzítés nehéz, túlméretes berendezésekhez.
- **Egyedi csomagolási megoldások** – a termékhez tervezett konstrukció, súlypont- és rögzítésszámítással.
- **„RAPID" csomagolás** – gyors átfutású, helyszíni csomagolás sürgős szállításokhoz.

## Szállítási mód szerint

- **Közúti** – daru-mozgatható és nyitott kalodás konstrukciók.
- **Tengeri** – **VCI** korrózióvédelmi technológia és párazárás a konténer-pára ellen.
- **Légi** – súlyoptimalizált, VCI-védelemmel ellátott csomagolás.

A megfelelő kezelési jelölésekkel és – igény szerint – **ISPM-15** szerinti
hőkezelt, IPPC-jelölt faanyaggal a küldemény a világ bármely pontjára
biztonságosan célba ér.
MD,
    ],
    [
        'title'   => 'Faipari tevékenység',
        'icon'    => 'wood',
        'sort'    => 2,
        'summary' => 'Egyedi és szabványos raklapok, ládák, fatömegcikkek és faszerkezetek gyártása fenntartható forrásból származó faanyagból.',
        'body'    => <<<MD
Fenntartható termelésből származó faanyagból gyártunk **raklapokat, ládákat és
egyedi faszerkezeteket** – standard méretben és teljesen egyedi kivitelben is.

## Mit gyártunk?

- **Egyedi raklapgyártás** – a termékhez méretezett teherbírással, akár különleges raklapformákkal.
- **Egyedi ládagyártás** – zárt és nyitott ládák, kalodák export csomagoláshoz.
- **Fatömegcikkek** – sorozatban gyártott fa elemek, alátétfa, fűrészáru.
- **Egyedi faszerkezetek** – speciális igényekhez tervezett, méretre készült megoldások.

## Miért minket válassz?

Több évtizedes tapasztalat, **100% minőségi garancia** és rugalmas gyártás –
néhány darabtól a nagyobb sorozatig. A faanyagot igény szerint **hőkezelt,
nemzetközi szállításra alkalmas (ISPM-15)** kivitelben is biztosítjuk.
MD,
    ],
    [
        'title'   => 'Nemzetközi árufuvarozás',
        'icon'    => 'truck',
        'sort'    => 3,
        'summary' => 'Közúti árufuvarozás nyergesvontatóval Európa-szerte – megbízható, határidőre teljesített szállítás.',
        'body'    => <<<MD
**Közúti szállítási szolgáltatások** saját és partner nyergesvontatókkal,
elsősorban Európán belül, de tapasztalatunk **négy kontinensre** is kiterjed.

## Lefedettség

Szállítottunk már többek között **Magyarországra, az USA-ba, Brazíliába,
Vietnámba és Dél-Afrikába** – azaz Európán túl Amerikába, Ázsiába és Afrikába is.

## Amit kínálunk

- Megbízható, **határidőre teljesített** fuvarozás.
- A csomagolástól a szállításig **egy kézből** intézett logisztika.
- Rugalmas kapacitás kisebb és nagyobb projektekhez egyaránt.

Kérj ajánlatot a szállítási igényedre – a csomagolással együtt is.
MD,
    ],
    [
        'title'   => 'Ügynöki kereskedelem',
        'icon'    => 'brick',
        'sort'    => 4,
        'summary' => 'Szlovák fűrészáru-nagykereskedelem és a BRITTERM téglagyár hazai képviselete – 30 év tapasztalattal, gyors teljesítéssel.',
        'body'    => <<<MD
Ügynöki kereskedelmi tevékenységünk keretében **építőanyagok és fűrészáru**
beszerzésében és forgalmazásában segítünk megbízható partnereinkkel.

## Szlovák fűrészáru

**30 év tapasztalat** a szlovák fűrészáru forgalmazásában, jellemzően
**1–1,5 héten belüli teljesítéssel**. Kapcsolattartó: Nagy László
(info@net-trade.hu, +36 20 387 1450).

## BRITTERM téglagyár

A szlovákiai **BRITTERM** téglagyár hazai képviselőjeként versenyképes áron
kínálunk minőségi tégla termékeket építkezésekhez.

Kérdés esetén keress minket bizalommal – egyedi mennyiségekre is adunk ajánlatot.
MD,
    ],
    [
        'title'   => 'Pályázatok, oktatás',
        'icon'    => 'learn',
        'sort'    => 5,
        'summary' => 'Pályázati tanácsadás és képzések szervezése – a fejlődést és a szakmai utánpótlást támogatva.',
        'body'    => <<<MD
A gyártás és kereskedelem mellett **pályázatokkal és oktatással** is foglalkozunk,
a fenntartható fejlődést és a szakmai utánpótlást szem előtt tartva.

## Pályázatok

Segítünk a fejlesztési **pályázati lehetőségek** feltérképezésében és a
megvalósításában – a vállalkozás növekedését támogató forrásokhoz.

## Oktatás, képzés

**Képzések és tréningek** szervezése, a szakmai tudás átadása. A területet
Nagy Lászlóné (info@net-trade.hu) koordinálja.

Érdeklődsz egy konkrét pályázat vagy képzés iránt? Vedd fel velünk a kapcsolatot.
MD,
    ],
];
