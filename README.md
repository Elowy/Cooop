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

> **Adatbázis automatikusan:** ha nincs elérhető MySQL kapcsolat, az alkalmazás
> egy helyi **SQLite** fájlra esik vissza (`storage/database.sqlite`), amelyet
> első indításkor a demó adatokkal (Vega madáretetők) tölt fel. Így a webshop és
> az admin felület MySQL szerver nélkül is azonnal, teljes funkcionalitással
> működik. Ha az SQLite sem elérhető, a webshop a beégetett demó adatokból
> dolgozik (csak olvasás).

## Admin felület

A termékek és kategóriák kezelése (létrehozás, szerkesztés, törlés, képfeltöltés)
a **`/admin`** útvonalon érhető el.

- Belépés: **`/admin/bejelentkezes`**
- Alapértelmezett adatok: felhasználó `admin`, jelszó `admin123`
- A módosítások azonnal megjelennek a webshopban (SQLite vagy MySQL háttértárban)

Funkciók:

- **Kategóriák**: listázás, létrehozás, szerkesztés, törlés (a slug a névből generálódik)
- **Termékek**: listázás, létrehozás, szerkesztés, törlés, **képfeltöltés**
  (SVG/PNG/JPG/WEBP/GIF, max. 2 MB), kiemelés és aktív/inaktív állapot
- Munkamenet alapú belépés, **CSRF-védett** űrlapok

> ⚠️ **Éles használat előtt** mindenképp állítsd be saját belépési adataidat a
> környezeti változókkal (`ADMIN_USER`, `ADMIN_PASSWORD` vagy `ADMIN_PASSWORD_HASH`).

## Adatbázis beállítása (opcionális, MySQL)

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
| `DB_HOST` … | lásd config | MySQL kapcsolat (ha nincs, SQLite-ra esik vissza) |
| `ADMIN_USER` | admin | Admin felhasználónév |
| `ADMIN_PASSWORD` | admin123 | Admin jelszó (vagy `ADMIN_PASSWORD_HASH`) |

## Kapcsolat

A valós cég elérhetősége: **info@net-trade.hu**, **+36 20 387 1450**,
Balassagyarmat. A termékek, árak és illusztrációk demonstrációs célúak.

## Következő lépések

- További Vega madáretető-változatok és valós termékfotók átemelése
- Rendelés mentése az `orders` táblába és e-mail visszaigazolás
- Több admin felhasználó és jogosultsági szintek
- Fizetési szolgáltató integrációja
