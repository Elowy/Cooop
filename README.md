# Net-Trade Hungary – weboldal

A [net-trade.hu](https://net-trade.hu) cég profiljára épülő, saját fejlesztésű
PHP weboldal. **Teljes újraírás alatt** – jelenleg a **sötét, prémium dizájnú
főoldal** készült el (1. kör). A tartalom egyelőre vázlat / placeholder.

## Felépítés

```
app/
  Core/        Router, View
  Views/       layouts/, partials/, home.php, errors/
config/        config.php
public/        webgyökér (index.php, .htaccess, assets/)
.htaccess      tartalék átirányítás, ha a webgyökér a projekt gyökere
```

A webszerver gyökerét a **`public/`** mappára kell állítani (vagy használd a
gyökér `.htaccess`-t, ami a `public/` alá irányít).

## Futtatás

```bash
php -S localhost:8000 -t public
```

Majd nyisd meg: http://localhost:8000

## Dizájn

- Sötét, prémium megjelenés, **arany/amber** kiemelésekkel
- Display címsorok szerif betűtípussal, törzsszöveg rendszer-sans-szal
- Nagy hero, finom belépő animációk (`prefers-reduced-motion` figyelve)
- Reszponzív, mobil menüvel; külső kép-/betűtípus-függőség nélkül (egyedi SVG-k)

## Következő körök (terv)

- Aloldalak (Tevékenységek, Rólunk, Kapcsolat) külön oldalakon
- Végleges szövegek és képek
- Igény szerint: termékkatalógus, többnyelvűség, admin, adatbázis
