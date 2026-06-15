# Net-Trade Hungary – webshop

A [net-trade.hu](https://net-trade.hu) mintájára épülő, saját fejlesztésű PHP
webáruház. A **Net-Trade Hungary Kft.** balassagyarmati családi vállalkozás,
közel 30 év tapasztalattal a borovi fenyő kereskedelmében: egyedi raklapgyártás,
ipari csomagolás, nemzetközi szállítmányozás és oktatás – webáruházában pedig
tartós, dekoratív **Vega madáretetők** kaphatók.

## Jellemzők

- Saját, könnyűsúlyú **MVC** architektúra, keretrendszer és külső függőség nélkül
- Front controller + reguláris kifejezés alapú **útvonalkezelő**
- **PDO** adatbázis-réteg (MySQL/MariaDB) – adatbázis nélkül demó adatokra esik vissza
- Termékkatalógus (Vega madáretetők) kategória-szűréssel és kereséssel, termékoldalakkal
- Munkamenet (session) alapú **kosár** és pénztár folyamat
- Reszponzív, természetes (fa / fenyő) arculatú dizájn (mobil menü, sticky fejléc)
- Egyedi SVG illusztrációk – nincs külső kép-/betűtípus-függőség

## Mappastruktúra

```
app/
  Core/          Router, View, Database, Cart
  Controllers/   (útvonal-kezelők jelenleg az index.php-ban)
  Models/        Product, Category
  Views/         sablonok (layouts, partials, oldalak)
config/          config.php (env változókból olvas)
database/        schema.sql, seed.sql
public/          webgyökér (index.php, .htaccess, assets/)
```

A webszerver gyökerét a **`public/`** mappára kell állítani.

## Helyi futtatás

Adatbázis nélkül, a PHP beépített szerverével:

```bash
php -S localhost:8000 -t public
```

Majd nyisd meg: http://localhost:8000

> Adatbázis nélkül a webshop a `Product::demo()` mintaadatokból dolgozik,
> így azonnal megtekinthető.

## Adatbázis beállítása (opcionális)

```bash
mysql -u root -p -e "CREATE DATABASE net_trade CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
mysql -u root -p net_trade < database/schema.sql
mysql -u root -p net_trade < database/seed.sql
```

A kapcsolati adatok környezeti változókból állíthatók (lásd `config/config.php`):
`DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`.

## Konfiguráció

Az alkalmazás beállításai környezeti változókkal felülírhatók:

| Változó | Alapérték | Leírás |
|---|---|---|
| `APP_NAME` | Net-Trade Hungary | Bolt neve |
| `APP_URL` | http://localhost:8000 | Alap URL |
| `APP_DEBUG` | true | Hibák megjelenítése |
| `DB_HOST` … | lásd config | Adatbázis-kapcsolat |

## Kapcsolat

A valós cég elérhetősége: **info@net-trade.hu**, **+36 20 387 1450**,
Balassagyarmat. A termékek, árak és illusztrációk demonstrációs célúak.

## Következő lépések

- További Vega madáretető-változatok és valós termékfotók átemelése
- Rendelés mentése az `orders` táblába és e-mail visszaigazolás
- Admin felület a termékek kezeléséhez
- Fizetési szolgáltató integrációja
