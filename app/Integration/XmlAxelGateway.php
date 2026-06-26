<?php

namespace App\Integration;

use DOMDocument;
use SimpleXMLElement;

/**
 * Axel Pro – helyi mappás XML fájlcsere adapter.
 *
 * Ugyanazon a gépen (VPS) futó Axel Pro-val cserél adatot egy közös könyvtáron
 * keresztül; nincs HTTP. A könyvtár szerkezete (a $exchangeDir alatt):
 *
 *   catalog.xml            – Axel → shop: teljes terméklista + készlet + árak.
 *   orders/order-<id>.xml  – shop → Axel: rendelésenként egy fájl számlázásra.
 *
 * catalog.xml séma (a mezőnevek camelCase és snake_case alakot is elfogadnak):
 *   <catalog>
 *     <product>
 *       <sku>EP-TGL-30</sku><slug>britterm-tegla-30</slug>
 *       <category>tegla</category><name>BRITTERM tégla 30</name>
 *       <unit>db</unit><priceNet>420</priceNet><vat>27</vat>
 *       <stock>8600</stock><icon>brick</icon><short>…</short>
 *     </product>
 *   </catalog>
 *
 * A fájlcsere aszinkron: a createInvoice() csak leteszi a rendelés-XML-t az
 * orders/ mappába (amit az Axel figyel), így azonnali számlaszámot nem ad
 * vissza – a számla az Axel feldolgozása után készül.
 *
 * Hibatűrés: ha a catalog.xml hiányzik vagy hibás, products() üres tömböt ad
 * (a shop nem omlik össze); a createInvoice írási hibát ok=false-szal jelez.
 */
final class XmlAxelGateway implements AxelGateway
{
    private string $dir;

    /** @var Product[]|null Lusta, kérésenkénti gyorsítótár. */
    private ?array $cache = null;

    public function __construct(string $exchangeDir)
    {
        $this->dir = rtrim($exchangeDir, '/');
    }

    public function products(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }
        $this->cache = [];
        $file = $this->dir . '/catalog.xml';
        if ($this->dir === '' || !is_file($file) || !is_readable($file)) {
            return $this->cache;
        }
        $xml = $this->load((string) file_get_contents($file));
        if ($xml === null) {
            return $this->cache;
        }
        foreach ($xml->product as $node) {
            $this->cache[] = Product::fromArray([
                'sku' => $this->text($node, 'sku'),
                'slug' => $this->text($node, 'slug'),
                'category' => $this->text($node, 'category'),
                'name' => $this->text($node, 'name'),
                'unit' => $this->text($node, 'unit'),
                'price_net' => (int) $this->text($node, 'priceNet', 'price_net'),
                'vat' => (int) $this->text($node, 'vat'),
                'stock' => (int) $this->text($node, 'stock'),
                'icon' => $this->text($node, 'icon'),
                'short' => $this->text($node, 'short'),
            ]);
        }
        return $this->cache;
    }

    public function findProduct(string $slug): ?Product
    {
        foreach ($this->products() as $product) {
            if ($product->slug === $slug) {
                return $product;
            }
        }
        return null;
    }

    public function stockFor(string $sku): ?int
    {
        foreach ($this->products() as $product) {
            if ($product->sku === $sku) {
                return $product->stock;
            }
        }
        return null;
    }

    public function createInvoice(array $order): InvoiceResult
    {
        if ($this->dir === '') {
            return new InvoiceResult(false, null, 'Nincs beállítva Axel adatcsere-könyvtár.');
        }
        $ordersDir = $this->dir . '/orders';
        if (!is_dir($ordersDir) && !@mkdir($ordersDir, 0775, true) && !is_dir($ordersDir)) {
            return new InvoiceResult(false, null, 'Az Axel orders/ mappa nem hozható létre: ' . $ordersDir);
        }
        $ref = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($order['token'] ?? $order['number'] ?? '')) ?: 'rendeles';
        $path = $ordersDir . '/order-' . $ref . '.xml';
        if (@file_put_contents($path, $this->buildOrderXml($order)) === false) {
            return new InvoiceResult(false, null, 'A rendelés-XML nem írható: ' . $path);
        }
        return new InvoiceResult(
            true,
            null,
            'Rendelés átadva az Axelnek (orders/order-' . $ref . '.xml). A számla az Axel feldolgozása után készül.'
        );
    }

    /** @param array<string, mixed> $order */
    private function buildOrderXml(array $order): string
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = true;
        $root = $doc->createElement('order');
        $doc->appendChild($root);

        $add = static function (\DOMElement $parent, string $name, mixed $value) use ($doc): void {
            $parent->appendChild($doc->createElement($name))
                ->appendChild($doc->createTextNode((string) $value));
        };

        $add($root, 'token', (string) ($order['token'] ?? ''));
        $add($root, 'number', (string) ($order['number'] ?? ''));
        $add($root, 'created', (string) ($order['created'] ?? ''));

        $c = (array) ($order['customer'] ?? []);
        $customer = $doc->createElement('customer');
        $root->appendChild($customer);
        $add($customer, 'name', (string) ($c['name'] ?? ''));
        $add($customer, 'email', (string) ($c['email'] ?? ''));
        $add($customer, 'phone', (string) ($c['phone'] ?? ''));
        $add($customer, 'company', (string) ($c['company'] ?? ''));
        $add($customer, 'taxNumber', (string) ($c['tax_number'] ?? ''));
        $add($customer, 'note', (string) ($c['note'] ?? ''));

        foreach (['billing', 'shipping'] as $key) {
            $addr = $order[$key] ?? null;
            if (is_array($addr)) {
                $el = $doc->createElement($key);
                $root->appendChild($el);
                $add($el, 'zip', (string) ($addr['zip'] ?? ''));
                $add($el, 'city', (string) ($addr['city'] ?? ''));
                $add($el, 'address', (string) ($addr['address'] ?? ''));
            }
        }

        $items = $doc->createElement('items');
        $root->appendChild($items);
        foreach ((array) ($order['items'] ?? []) as $it) {
            $item = $doc->createElement('item');
            $items->appendChild($item);
            $add($item, 'sku', (string) ($it['sku'] ?? ''));
            $add($item, 'name', (string) ($it['name'] ?? ''));
            $add($item, 'unit', (string) ($it['unit'] ?? ''));
            $add($item, 'qty', (string) ($it['qty'] ?? 0));
            $add($item, 'priceNet', (string) ($it['price_net'] ?? 0));
            $add($item, 'vat', (string) ($it['vat'] ?? 0));
            $add($item, 'priceGross', (string) ($it['price_gross'] ?? 0));
            $add($item, 'subtotal', (string) ($it['subtotal'] ?? 0));
        }

        $add($root, 'total', (string) ($order['totals']['gross'] ?? 0));

        return (string) $doc->saveXML();
    }

    private function load(string $xml): ?SimpleXMLElement
    {
        if (trim($xml) === '') {
            return null;
        }
        $prev = libxml_use_internal_errors(true);
        $parsed = simplexml_load_string($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        return $parsed === false ? null : $parsed;
    }

    /** Az első nem üres gyermek-elem szövege a megadott nevek közül. */
    private function text(SimpleXMLElement $node, string ...$names): string
    {
        foreach ($names as $name) {
            if (isset($node->{$name})) {
                $val = trim((string) $node->{$name});
                if ($val !== '') {
                    return $val;
                }
            }
        }
        return '';
    }
}
