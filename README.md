# Net-Trade Hungary – weboldal

A [net-trade.hu](https://net-trade.hu) tartalmára épülő, teljesen saját
fejlesztésű PHP weboldal és katalógus. A **Net-Trade Hungary Kft.** családi
vállalkozás profilját mutatja be: ipari csomagolás, faipari gyártás (egyedi
raklapok, exportládák), nemzetközi fuvarozás, BRITTERM tégla képviselet, valamint
fenyő fűrészáru- és tűzifa-kereskedelem.

## Jellemzők

- Saját, könnyűsúlyú **MVC** architektúra, keretrendszer és külső függőség nélkül
- Front controller + reguláris kifejezés alapú **útvonalkezelő**
- **PDO** adatbázis-réteg **SQLite vagy MySQL/MariaDB** támogatással – adatbázis
  nélkül demó adatokra esik vissza
- **Felhasználói fiókok**: regisztráció és belépés (bcrypt jelszó, CSRF védelem);
  az első regisztráló automatikusan **admin**
- **Admin felület** (`/admin`): termékek felvétele, szerkesztése, törlése,
  **képfeltöltéssel** (SVG/PNG/JPG/WebP)
- A cég tevékenységeit bemutató **főoldal**, „Rólunk” és „Kapcsolat” oldalak
- Termékkatalógus (raklapok, ládák, fűrészáru, tűzifa, tégla) kategória-szűréssel,
  kereséssel és egységár-mértékegységgel (db / m³ / fm)
- Munkamenet (session) alapú **kosár / ajánlatkérés** folyamat
- **Háromnyelvű** felület (HU / EN / DE) – süti alapú nyelvválasztás, külső
  függőség nélkül (`config/lang.php`, `App\Core\Lang`)
- **Árkalkulátor** (`/kalkulator`) – termék, mennyiség és méret-szorzó alapján
  azonnali, tájékoztató nettó ár
- **Referencia galéria** (`/galeria`) lightbox-os nagyítással
- **Sötét / világos mód** (mentett választás, rendszerbeállítás követése) és
  finom belépő animációk (a `prefers-reduced-motion` tiszteletben tartásával)
- Reszponzív, modern dizájn (mobil menü, sticky fejléc)
- Egyedi SVG illusztrációk – nincs külső kép-/betűtípus-függőség

## Mappastruktúra

```
app/
  Core/          Router, View, Database, Cart, Lang, Auth, Csrf
  Controllers/   (útvonal-kezelők jelenleg az index.php-ban)
  Models/        Product, Category, User
  Views/         sablonok (layouts, partials, oldalak, auth, admin, galéria, kalkulátor)
config/          config.php, lang.php (HU/EN/DE fordítások)
database/        install.php (telepítő), schema.sql, seed.sql
storage/         SQLite adatbázis (futásidőben jön létre, nincs verziókövetve)
public/          webgyökér (index.php, .htaccess, assets/)
```

A webszerver gyökerét a **`public/`** mappára kell állítani.

## Helyi futtatás

Adatbázis nélkül, a PHP beépített szerverével:

```bash
php -S localhost:8000 -t public
```

Majd nyisd meg: http://localhost:8000

> Adatbázis nélkül az oldal a `Product::demo()` mintaadatokból dolgozik,
> így azonnal megtekinthető.

## Adatbázis, regisztráció és admin

A regisztrációhoz, belépéshez és a termékek feltöltéséhez **adatbázis kell**.
Egyetlen parancs felépíti és feltölti a sémát:

```bash
php database/install.php
```

- **Alapból SQLite** (`storage/database.sqlite`) – nem kell külső adatbázis-szerver,
  azonnal működik. A futtatás idempotens (újra lefuttatható).
- **MySQL/MariaDB**-hez állítsd be a környezeti változókat, hozd létre az
  adatbázist, majd futtasd a telepítőt (vagy importáld a `schema.sql` + `seed.sql`-t):

  ```bash
  export DB_DRIVER=mysql DB_NAME=net_trade DB_USER=root DB_PASSWORD=titok
  mysql -u root -p -e "CREATE DATABASE net_trade CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
  php database/install.php
  ```

### Admin létrehozása

A telepítő után **regisztrálj a `/regisztracio` oldalon** – az **első** felhasználó
automatikusan **admin** jogot kap. Belépés után az `/admin` felületen tudsz
termékeket felvenni, szerkeszteni, törölni és képet feltölteni.

> A feltöltött termékképek a `public/assets/img/products/` mappába kerülnek,
> ezért annak írhatónak kell lennie a webszerver számára.

## Konfiguráció

Az alkalmazás beállításai környezeti változókkal felülírhatók:

| Változó | Alapérték | Leírás |
|---|---|---|
| `APP_NAME` | Net-Trade Hungary | Cég neve |
| `APP_URL` | http://localhost:8000 | Alap URL |
| `APP_DEBUG` | true | Hibák megjelenítése |
| `DB_DRIVER` | _(auto)_ | `sqlite` vagy `mysql` (üresen: sqlite ha van fájl, különben mysql) |
| `DB_DATABASE` | `storage/database.sqlite` | SQLite fájl elérési útja |
| `DB_HOST` / `DB_PORT` / `DB_NAME` / `DB_USER` / `DB_PASSWORD` | lásd config | MySQL-kapcsolat |

## Következő lépések

- Ajánlatkérés / rendelés mentése az `orders` táblába és e-mail visszaigazolás
- Kategóriák kezelése és rendelés-lista az admin felületen
- Fizetési szolgáltató integrációja
