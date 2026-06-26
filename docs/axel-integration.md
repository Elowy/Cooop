# Axel Pro integráció

A webshop egyetlen porton (`App\Integration\AxelGateway`) keresztül beszél az
Axel Pro készletnyilvántartással és számlázással. Az interfész mögött három
csereszabatos adapter áll; a `shop` többi része nem függ attól, melyik aktív.

A bekötés módja az adminban választható: **Axel integráció** oldal → *Kapcsolat
módja* (`mock` | `xml` | `rest`). A választás mentés után azonnal érvénybe lép
(`axel_gateway` beállítás, config fallback: `config/config.php` → `axel.gateway`).

## Az interfész

```php
interface AxelGateway {
    public function products(): Product[];            // teljes katalógus + készlet + ár
    public function findProduct(string $slug): ?Product;
    public function stockFor(string $sku): ?int;      // aktuális készlet egy cikkre
    public function createInvoice(array $order): InvoiceResult; // rendelés → számlázás
}
```

`Product`: `sku, slug, category, name, unit, priceNet (HUF nettó), vat (%),
stock, icon, short`. `InvoiceResult`: `ok (bool), invoiceNumber (?string),
message (?string)`.

A `createInvoice()` által kapott `$order` tömb kulcsai: `token, number, created,
customer{name,email,phone,company,tax_number,note}, billing{zip,city,address},
shipping{…}|null, items[]{sku,name,unit,qty,price_net,vat,price_gross,subtotal},
totals{gross}`.

---

## 1) XML adapter (`XmlAxelGateway`) — helyi mappás fájlcsere

Ugyanazon a gépen futó Axel Pro-val cserél adatot egy közös könyvtáron át
(beállítás: *XML adatcsere mappa*, `axel_exchange_dir`; fallback:
`config → axel.exchange_dir`). Nincs HTTP.

```
<exchange_dir>/
├── catalog.xml           # Axel → shop: teljes katalógus + készlet
└── orders/
    └── order-<token>.xml  # shop → Axel: rendelésenként egy fájl
```

### `catalog.xml` (Axel írja, a shop olvassa)

A mezőnevek camelCase és snake_case alakot is elfogadnak (pl. `priceNet` vagy
`price_net`).

```xml
<?xml version="1.0" encoding="UTF-8"?>
<catalog>
  <product>
    <sku>EP-TGL-30</sku>
    <slug>britterm-tegla-30</slug>
    <category>tegla</category>
    <name>BRITTERM tégla 30</name>
    <unit>db</unit>
    <priceNet>420</priceNet>
    <vat>27</vat>
    <stock>8600</stock>
    <icon>brick</icon>
    <short>Vázkerámia falazóelem 30 cm falvastagsághoz.</short>
  </product>
  <!-- további <product> elemek -->
</catalog>
```

A `products()`, `findProduct()` és `stockFor()` ezt a fájlt olvassa
(kérésenként egyszer, gyorsítótárazva). Ha a fájl hiányzik vagy hibás, a shop
nem omlik össze – üres katalógust kap.

### `orders/order-<token>.xml` (a shop írja, az Axel olvassa)

A fájlcsere **aszinkron**: a `createInvoice()` csak leteszi a rendelést, és
`ok=true`-t ad **számlaszám nélkül** (a számla az Axel feldolgozása után készül).
Írási hiba esetén `ok=false`.

```xml
<?xml version="1.0" encoding="UTF-8"?>
<order>
  <token>…</token>
  <number>NT-1024</number>
  <created>2026-06-25T10:00:00+02:00</created>
  <customer>
    <name>…</name><email>…</email><phone>…</phone>
    <company>…</company><taxNumber>…</taxNumber><note>…</note>
  </customer>
  <billing><zip>…</zip><city>…</city><address>…</address></billing>
  <shipping><zip>…</zip><city>…</city><address>…</address></shipping>
  <items>
    <item>
      <sku>…</sku><name>…</name><unit>db</unit><qty>2</qty>
      <priceNet>420</priceNet><vat>27</vat><priceGross>533</priceGross><subtotal>1066</subtotal>
    </item>
  </items>
  <total>1066</total>
</order>
```

Az Axel oldali feldolgozás (figyelt mappa / ütemezett import) az Axel
konfigurációja; a shop csak a fájlt teszi le.

---

## 2) REST adapter (`RestAxelGateway`) — HTTP API

Akkor, ha az Axel Pro REST API-t tesz közzé. Beállítás: *REST API URL*
(`axel_api_url`) és *REST API kulcs* (`axel_api_key`). A végpontok az alap-URL-hez
képest relatívak; a hitelesítés az `X-Api-Key` fejlécben.

| Metódus | Végpont | Irány | Tartalom |
|---|---|---|---|
| `GET`  | `{api_url}/products`     | Axel → shop | terméklista (JSON tömb) |
| `GET`  | `{api_url}/stock/{sku}`  | Axel → shop | `{ "sku": "...", "stock": 123 }` |
| `POST` | `{api_url}/invoices`     | shop → Axel | rendelés JSON → számlázási eredmény |

### `GET /products` válasz

```json
[
  {
    "sku": "EP-TGL-30", "slug": "britterm-tegla-30", "category": "tegla",
    "name": "BRITTERM tégla 30", "unit": "db",
    "priceNet": 420, "vat": 27, "stock": 8600,
    "icon": "brick", "short": "Vázkerámia falazóelem…"
  }
]
```

A mezőnevek camelCase és snake_case alakot is elfogadnak (`priceNet`/`price_net`).

### `POST /invoices` (törzs = a fenti `$order`, JSON-ban) → válasz

```json
{ "ok": true, "invoiceNumber": "2026-NT-0042", "message": "Számla kiállítva." }
```

Nem 2xx válasz vagy hálózati hiba esetén `products()` üres tömböt, `stockFor()`
null-t (tartalékként a terméklistából próbálja), `createInvoice()` `ok=false`-t ad.

---

## Tesztelés

A `tests/run.php` mindkét adaptert lefedi hálózat nélkül:
- **XML:** ideiglenes mappába írt minta-`catalog.xml` beolvasása, valamint a
  `createInvoice()` által írt `orders/*.xml` ellenőrzése.
- **REST:** injektált HTTP-kliens (a konstruktor 3. paramétere) ad kanonikus
  JSON-válaszokat, így a parse/leképezés hálózat nélkül tesztelhető.

A mezőlekepezés az éles Axel Pro tényleges sémájához igazítható az adapterek
és e dokumentum frissítésével – az `AxelGateway` interfész változatlan marad.
