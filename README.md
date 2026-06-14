# Net Trade – webshop

A [net-trade.hu](https://net-trade.hu) megújult, teljesen saját fejlesztésű PHP
webáruháza – hálózati és IT eszközök (routerek, switchek, kábelek, kamerák,
tárolók) értékesítésére.

## Jellemzők

- Saját, könnyűsúlyú **MVC** architektúra, keretrendszer és külső függőség nélkül
- Front controller + reguláris kifejezés alapú **útvonalkezelő**
- **PDO** adatbázis-réteg (MySQL/MariaDB) – adatbázis nélkül demó adatokra esik vissza
- Termékkatalógus kategória-szűréssel és kereséssel, termékoldalakkal
- Munkamenet (session) alapú **kosár** és pénztár folyamat
- Reszponzív, modern dizájn (mobil menü, sticky fejléc)
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
| `APP_NAME` | Net Trade | Bolt neve |
| `APP_URL` | http://localhost:8000 | Alap URL |
| `APP_DEBUG` | true | Hibák megjelenítése |
| `DB_HOST` … | lásd config | Adatbázis-kapcsolat |

## Következő lépések

- Az eredeti net-trade.hu tartalom (szövegek, termékek, képek) átemelése
- Rendelés mentése az `orders` táblába és e-mail visszaigazolás
- Admin felület a termékek kezeléséhez
- Fizetési szolgáltató integrációja
