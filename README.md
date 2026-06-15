# Net-Trade Hungary – weboldal

A [net-trade.hu](https://net-trade.hu) tartalmára épülő, teljesen saját
fejlesztésű PHP weboldal és katalógus. A **Net-Trade Hungary Kft.** családi
vállalkozás profilját mutatja be: ipari csomagolás, faipari gyártás (egyedi
raklapok, exportládák), nemzetközi fuvarozás, BRITTERM tégla képviselet, valamint
fenyő fűrészáru- és tűzifa-kereskedelem.

## Jellemzők

- Saját, könnyűsúlyú **MVC** architektúra, keretrendszer és külső függőség nélkül
- Front controller + reguláris kifejezés alapú **útvonalkezelő**
- **PDO** adatbázis-réteg (MySQL/MariaDB) – adatbázis nélkül demó adatokra esik vissza
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
  Core/          Router, View, Database, Cart, Lang
  Controllers/   (útvonal-kezelők jelenleg az index.php-ban)
  Models/        Product, Category
  Views/         sablonok (layouts, partials, oldalak, galéria, kalkulátor)
config/          config.php, lang.php (HU/EN/DE fordítások)
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

> Adatbázis nélkül az oldal a `Product::demo()` mintaadatokból dolgozik,
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
| `APP_NAME` | Net-Trade Hungary | Cég neve |
| `APP_URL` | http://localhost:8000 | Alap URL |
| `APP_DEBUG` | true | Hibák megjelenítése |
| `DB_HOST` … | lásd config | Adatbázis-kapcsolat |

## Következő lépések

- Ajánlatkérés / rendelés mentése az `orders` táblába és e-mail visszaigazolás
- Admin felület a termékek és kategóriák kezeléséhez
- Galéria a referencia-csomagolásokról és gyártott termékekről
- Többnyelvűség (a net-trade.hu több nyelven is elérhető)
