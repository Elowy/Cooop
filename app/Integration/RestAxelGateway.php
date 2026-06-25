<?php

namespace App\Integration;

/**
 * Axel Pro – REST API adapter (HTTP).
 *
 * Akkor használatos, ha az Axel Pro egy HTTP REST API-t tesz közzé. A végpontok
 * a beállított alap-URL-hez (api_url) képest relatívak, a hitelesítés az
 * `X-Api-Key` fejlécben megadott kulccsal (api_key) történik:
 *
 *   GET  {api_url}/products        → [{sku,slug,category,name,unit,priceNet,vat,stock,icon,short}, …]
 *   GET  {api_url}/stock/{sku}     → {"sku":"…","stock": 123}
 *   POST {api_url}/invoices        ← rendelés JSON; → {"ok":true,"invoiceNumber":"…","message":"…"}
 *
 * A JSON termékmezők camelCase és snake_case alakot is elfogadnak. Hibatűrés:
 * nem 2xx válasz vagy hálózati hiba esetén products() üres tömböt, stockFor()
 * null-t, createInvoice() ok=false-t ad – a shop nem omlik össze.
 *
 * A HTTP-hívás injektálható (tesztelhetőség): a konstruktor 3. paramétere egy
 * callable(method, url, headers[], ?body): array{status:int, body:string}.
 * Üresen hagyva a beépített cURL-kliens fut.
 */
final class RestAxelGateway implements AxelGateway
{
    private string $base;
    private string $apiKey;
    /** @var callable(string,string,array,?string):array{status:int,body:string} */
    private $http;

    /** @var Product[]|null Lusta, kérésenkénti gyorsítótár. */
    private ?array $cache = null;

    public function __construct(string $apiUrl, string $apiKey = '', ?callable $http = null)
    {
        $this->base = rtrim($apiUrl, '/');
        $this->apiKey = $apiKey;
        $this->http = $http ?? [$this, 'curlRequest'];
    }

    public function products(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }
        $this->cache = [];
        if ($this->base === '') {
            return $this->cache;
        }
        [$status, $body] = $this->request('GET', '/products');
        if ($status < 200 || $status >= 300) {
            return $this->cache;
        }
        $rows = json_decode($body, true);
        if (!is_array($rows)) {
            return $this->cache;
        }
        foreach ($rows as $row) {
            if (is_array($row)) {
                $this->cache[] = self::productFromJson($row);
            }
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
        if ($this->base === '' || $sku === '') {
            return null;
        }
        [$status, $body] = $this->request('GET', '/stock/' . rawurlencode($sku));
        if ($status >= 200 && $status < 300) {
            $data = json_decode($body, true);
            if (is_array($data) && isset($data['stock']) && is_numeric($data['stock'])) {
                return (int) $data['stock'];
            }
        }
        // Tartalék: a terméklistából is kiolvasható a készlet.
        foreach ($this->products() as $product) {
            if ($product->sku === $sku) {
                return $product->stock;
            }
        }
        return null;
    }

    public function createInvoice(array $order): InvoiceResult
    {
        if ($this->base === '') {
            return new InvoiceResult(false, null, 'Nincs beállítva Axel API URL.');
        }
        $payload = json_encode($order, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        [$status, $body] = $this->request('POST', '/invoices', $payload === false ? '{}' : $payload);
        if ($status < 200 || $status >= 300) {
            return new InvoiceResult(false, null, 'Az Axel API hibát adott (HTTP ' . $status . ').');
        }
        $data = json_decode($body, true);
        if (!is_array($data)) {
            return new InvoiceResult(false, null, 'Az Axel API válasza értelmezhetetlen.');
        }
        $number = isset($data['invoiceNumber']) ? (string) $data['invoiceNumber'] : (isset($data['invoice_number']) ? (string) $data['invoice_number'] : null);
        return new InvoiceResult(
            (bool) ($data['ok'] ?? false),
            $number !== '' ? $number : null,
            isset($data['message']) ? (string) $data['message'] : null
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function productFromJson(array $row): Product
    {
        $pick = static fn (array $keys, mixed $default = '') => self::first($row, $keys, $default);
        return Product::fromArray([
            'sku' => (string) $pick(['sku']),
            'slug' => (string) $pick(['slug']),
            'category' => (string) $pick(['category']),
            'name' => (string) $pick(['name']),
            'unit' => (string) $pick(['unit']),
            'price_net' => (int) $pick(['priceNet', 'price_net'], 0),
            'vat' => (int) $pick(['vat'], 0),
            'stock' => (int) $pick(['stock'], 0),
            'icon' => (string) $pick(['icon']),
            'short' => (string) $pick(['short']),
        ]);
    }

    /**
     * @param array<string, mixed> $row
     * @param string[] $keys
     */
    private static function first(array $row, array $keys, mixed $default): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row)) {
                return $row[$key];
            }
        }
        return $default;
    }

    /**
     * @return array{0:int,1:string} [status, body]
     */
    private function request(string $method, string $path, ?string $body = null): array
    {
        $headers = ['Accept: application/json'];
        if ($this->apiKey !== '') {
            $headers[] = 'X-Api-Key: ' . $this->apiKey;
        }
        if ($body !== null) {
            $headers[] = 'Content-Type: application/json';
        }
        $res = ($this->http)($method, $this->base . $path, $headers, $body);
        return [(int) ($res['status'] ?? 0), (string) ($res['body'] ?? '')];
    }

    /**
     * @param string[] $headers
     * @return array{status:int,body:string}
     */
    private function curlRequest(string $method, string $url, array $headers, ?string $body): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 15,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        $resp = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ['status' => $status, 'body' => $resp === false ? '' : (string) $resp];
    }
}
