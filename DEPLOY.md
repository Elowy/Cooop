# Telepítés Windows Server VPS-re (IIS + PHP + HTTPS)

Ez az útmutató a weboldalt **ugyanarra a Windows Server VPS-re** telepíti, ahol
az **Axel Pro** is fut. Így a kettő **helyben**, az internet felé kitettség nélkül
tud adatot cserélni (lásd a végén az *Axel integráció* részt).

> Áttekintés: PHP + IIS telepítése → projekt másolása → IIS site a domainre →
> HTTPS (Let's Encrypt) → konfiguráció → Axel közös mappa.

Előfeltételek:
- Adminisztrátori (RDP) hozzáférés a VPS-hez.
- Egy **domain név**, aminek az **A rekordja a VPS publikus IP-jére** mutat
  (a HTTPS-hez ez kell előbb). Pl. `www.te-domained.hu → 203.0.113.10`.
- Az Axel Pro fut a gépen.

---

## 1. PHP telepítése

1. Töltsd le a **PHP 8.2+ (x64, Non-Thread-Safe, VS16)** ZIP-et innen:
   https://windows.php.net/download/  → *„VS16 x64 Non Thread Safe”*.
2. Csomagold ki ide: `C:\php`.
3. Másold a `php.ini-production` fájlt `php.ini` néven, majd nyisd meg és
   engedélyezd (vedd ki a `;`-t a sor elejéről) legalább ezeket:
   ```ini
   extension_dir = "ext"
   extension=mbstring
   extension=openssl
   extension=fileinfo
   ```
   (A projekt nem használ adatbázist, így PDO nem kötelező.)
4. Add hozzá a `C:\php`-t a rendszer **PATH**-hoz, majd ellenőrizd PowerShellben:
   ```powershell
   php -v
   ```

## 2. IIS + CGI szerepkör bekapcsolása

**Server Manager → Add roles and features → Web Server (IIS)**, és a
*Role Services* alatt mindenképp pipáld be:
- **Web Server → Application Development → CGI** (ez kell a PHP FastCGI-hoz)
- **Web Server → Common HTTP Features** (Static Content, Default Document)

PowerShell-lel egy sorban:
```powershell
Install-WindowsFeature -Name Web-Server,Web-CGI,Web-Static-Content,Web-Default-Doc -IncludeManagementTools
```

## 3. URL Rewrite modul

Töltsd le és telepítd az **IIS URL Rewrite 2.1** modult:
https://www.iis.net/downloads/microsoft/url-rewrite
(Ez értelmezi a projekt `public/web.config` átírási szabályait.)

## 4. PHP bekötése az IIS-be (FastCGI)

1. IIS Manager → a **szerver** csomópont → **Handler Mappings** →
   *Add Module Mapping…*:
   - Request path: `*.php`
   - Module: `FastCgiModule`
   - Executable: `C:\php\php-cgi.exe`
   - Name: `PHP_via_FastCGI`
2. Rákérdez, hogy létrehozza-e a FastCGI alkalmazást → **Yes**.

## 5. A projekt a szerverre

A repo klónozása (telepíts Git for Windows-t, vagy másold a fájlokat):
```powershell
cd C:\
git clone <REPO_URL> nettrade
```
A **webgyökér a `C:\nettrade\public` mappa lesz** (nem a projekt gyökere!).

## 6. Helyi konfiguráció (titkok, útvonalak)

```powershell
copy C:\nettrade\config\config.local.php.example C:\nettrade\config\config.local.php
notepad C:\nettrade\config\config.local.php
```
Töltsd ki:
```php
return [
    'app'   => ['url' => 'https://www.te-domained.hu', 'debug' => false],
    'admin' => ['password' => 'EGY-ERŐS-JELSZÓ'],
    'axel'  => ['gateway' => 'mock', 'exchange_dir' => 'C:\\axel-exchange'],
];
```
> Ez a fájl nem kerül a verziókövetésbe, és felülírja a `config.php` alapértékeit.

## 7. IIS website létrehozása a domainre

IIS Manager → **Sites → Add Website**:
- Site name: `nettrade`
- Physical path: `C:\nettrade\public`
- Binding: `http`, port `80`, **Host name:** `www.te-domained.hu`

Az átírási szabályok és a biztonsági fejlécek már a `public/web.config`-ban vannak.

Jogosultság: az `IIS_IUSRS` csoportnak **olvasás** kell a `C:\nettrade` mappára
(általában alapból megvan; ha 403/500 jön, add hozzá a mappa *Security* fülén).

Próba: böngészőben `http://www.te-domained.hu` – be kell töltődnie a főoldalnak.

## 8. HTTPS (Let's Encrypt, ingyenes, automatikus megújítás)

1. Töltsd le a **win-acme** eszközt: https://www.win-acme.com/ → csomagold ki.
2. Futtasd rendszergazdaként: `wacs.exe`
3. Válaszd: **N** (új tanúsítvány) → a felkínált IIS site-ok közül a `nettrade`-et →
   e-mail megadása → elfogadás. A win-acme automatikusan:
   - lekéri a Let's Encrypt tanúsítványt (a 80-as porton ellenőrizve a domaint),
   - létrehozza a **https (443) bindinget**,
   - beállít egy **ütemezett feladatot a megújításra** (60 naponta).
4. Ajánlott: IIS-ben a site-on belül **HTTP → HTTPS átirányítás** (URL Rewrite
   szabály vagy a „HTTP Redirect” funkció), hogy a 80-as is HTTPS-re menjen.

Ellenőrzés: `https://www.te-domained.hu` – zöld lakat, oldal betölt.

## 9. Ellenőrző lista

- [ ] Főoldal, `/webshop`, termékoldal, `/kosar` betölt HTTPS-en.
- [ ] `/admin` → belépés a beállított jelszóval, a vezérlőpult működik.
- [ ] `config.local.php`-ban `debug = false` (élesben nincs hibakijelzés).
- [ ] Windows tűzfal: csak **80** és **443** publikus. Az Axel Pro **ne** legyen
      kívülről elérhető.

## 10. Frissítés (új verzió kitelepítése)

```powershell
cd C:\nettrade
git pull
```
PHP-újraindítás nem kell (a FastCGI felveszi a változást). A `config.local.php`
helyben marad, mert nincs verziókövetve.

---

## Axel Pro integráció (ugyanazon a gépen)

Mivel a weboldal és az Axel **egy gépen** van, egy **közös helyi mappán** keresztül
cserélnek adatot — pl. `C:\axel-exchange` (ez az `axel.exchange_dir` a konfigban):

```
Weboldal  ──írja──►  C:\axel-exchange\orders\    (új rendelések XML-ben)
Axel Pro  ──beimportálja──►  számláz + NAV-jelentés
Axel Pro  ──exportálja──►  C:\axel-exchange\stock\ (készlet/árak XML)
Weboldal  ──beolvassa──►  frissíti az elérhetőséget
```

Teendők a bekötéshez (a következő fejlesztési kör):
1. Hozd létre a mappát, és adj **írás/olvasás** jogot rajta az `IIS_IUSRS`-nek
   **és** annak a felhasználónak, akivel az Axel fut.
2. Az **Axel Pro-ban** állítsd be az automatikus **import/export mappát** erre a
   könyvtárra (a pontos lépésekhez az Axel a mérvadó — lásd a nyitott kérdéseket
   a vezérlőpult *Axel integráció* oldalán).
3. A weboldalon a `config.local.php`-ban: `'gateway' => 'xml'`. Ekkor a
   `MockAxelGateway` helyére egy `XmlAxelGateway` lép (a fájlformátum az Axel
   import/export sémája szerint) — a webshop többi része változatlan marad.

> Az Axel Pro asztali program; nem kell és nem is ajánlott az internet felé
> kinyitni. Csak a weboldal publikus, az Axel a helyi mappán át kapja/adja az
> adatot.

### Egyszerűbb HTTPS-alternatíva (opcionális)
Ha nem akarsz IIS-sel bajlódni, a **Caddy** (https://caddyserver.com/) Windowson
is fut, és **automatikus HTTPS**-t ad egyetlen `Caddyfile`-lal; a PHP-t
`php_fastcgi`-val a `C:\php\php-cgi.exe`-hez kötve szolgálja ki. IIS mellett ez
csak alternatíva — egyszerre az egyiket használd.

---

## Automatikus FTP deploy (GitHub Actions)

A `.github/workflows/deploy.yml` minden **`main` ágra való push** után FTP-vel
kitelepíti a weboldalt a tárhelyre (cPanel). Kézzel is indítható az Actions fülön.

### 1. FTP titkok beállítása
GitHub → a repó **Settings → Secrets and variables → Actions → New repository secret**,
és vedd fel ezeket:

| Titok neve        | Érték                                                        |
|-------------------|-------------------------------------------------------------|
| `FTP_SERVER`      | az FTP szerver címe (pl. `ftp.a-domained.hu`)               |
| `FTP_USERNAME`    | az FTP felhasználónév (cPanelben hozhatsz létre FTP-fiókot) |
| `FTP_PASSWORD`    | az FTP jelszó                                                |
| `FTP_REMOTE_DIR`  | *(opcionális)* célmappa, pl. `/public_html/` vagy `/nettrade/` (alapért.: `./`) |

> Amíg a három kötelező titok nincs beállítva, a workflow **lefut, de a deployt
> kihagyja** (csak figyelmeztet) — nem lesz piros hiba.

### 2. Célmappa (`FTP_REMOTE_DIR`)
- Ha a domain **document rootja a projekt `public/` mappája**, akkor a teljes
  projektet egy szülőmappába töltsd (pl. `/nettrade/`), és a docrootot oda állítsd.
- Ha a tárhely **fix `public_html`** és oda kell a projekt gyökere, akkor
  `FTP_REMOTE_DIR=/public_html/` — a gyökér `.htaccess` viszi a `public/` alá.

### 3. Amit a deploy NEM bánt
A workflow kihagyja (sosem törli/írja felül a szerveren): `config/config.local.php`
(titkok), `storage/**` (rendelések, üzenetek), `.github/`, `.git*`, `*.md`.
Csak a megváltozott fájlokat tölti fel (inkrementális szinkron).

### 4. FTPS vs FTP
A workflow alapból **`ftps`** (titkosított). Ha a tárhely csak sima FTP-t tud
vagy TLS-hibát ad, a `deploy.yml`-ben írd át a `protocol`-t `ftp`-re.
