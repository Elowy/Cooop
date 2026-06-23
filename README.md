# Net-Trade Hungary – webáruház

Saját fejlesztésű PHP webáruház (keretrendszer nélkül), **sötét, prémium**
dizájnnal. A készlet és a számlázás később az **Axel Pro** ügyviteli programhoz
kapcsolódik (egy adapter-interfészen át). A tartalom egyelőre vázlat / placeholder.

## Felépítés

```
app/
  Core/         Router, View, Cart, Csrf, Auth
  Catalog/      Categories (kategóriafa)
  Integration/  AxelGateway (interfész) + Product, InvoiceResult, MockAxelGateway
  Views/        layouts/, partials/, home, shop/, cart/, admin/, errors/
config/         config.php, categories.php, catalog.php, config.local.php(.example)
public/         webgyökér (index.php, .htaccess, web.config, assets/)
DEPLOY.md       telepítés Windows Server VPS-re (IIS + PHP + HTTPS + Axel)
```

A webszerver gyökerét a **`public/`** mappára kell állítani.

## Futtatás (helyi fejlesztés)

```bash
php -S localhost:8000 -t public
```
- Webáruház: http://localhost:8000/webshop
- Vezérlőpult: http://localhost:8000/admin (alapértelmezett jelszó: `admin`)
- Hibakijelzéshez: `APP_DEBUG=true php -S localhost:8000 -t public`

## Konfiguráció

A `config/config.php` az alapértékeket adja; ezeket felülírhatod
- környezeti változókkal (`APP_URL`, `APP_DEBUG`, `ADMIN_PASSWORD`, `AXEL_DIR`…), vagy
- egy `config/config.local.php` fájllal (másold a `.example`-ből). Ez nem kerül
  verziókövetésbe, és Windows/IIS alatt kényelmesebb a titkok/útvonalak megadására.

## Funkciók (eddig)

- **Főoldal**: hero, fő kategóriák, „Rólunk", CTA – egyedi SVG-kkel, animációkkal.
- **Webáruház**: hierarchikus kategóriafa (oldalsáv + morzsamenü), termékoldal,
  munkamenet-kosár (CSRF-fel).
- **Vezérlőpult** (`/admin`): belépés, készlet/termék áttekintés, kategóriák,
  rendelések (placeholder), Axel-integráció állapot.
- **Axel adapter**: a shop egyetlen interfészen (`App\Integration\AxelGateway`)
  beszél az Axellel; jelenleg `MockAxelGateway`, később `xml`/`rest` adapter.

## Telepítés

Lásd **[DEPLOY.md](DEPLOY.md)** – teljes Windows Server (IIS + PHP) telepítés,
domain + Let's Encrypt HTTPS, és az Axel Pro helyi mappás integrációja
(ugyanazon a VPS-en futó Axelhez).

## Következő körök (terv)

- Pénztár: online fizetés + rendelés-megerősítés, rendelések a vezérlőpulton.
- Éles Axel-bekötés: `XmlAxelGateway` (helyi mappás) – rendelés → számla + NAV.
- Készlet-szinkron ütemezve; valódi termékadatok betöltése.
