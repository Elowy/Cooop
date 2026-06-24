<?php

declare(strict_types=1);

namespace App\Payment;

/**
 * Vékony HTTP-kliens a Barion Smart Gateway v2 API-hoz.
 *
 * A hálózati réteg injektálható (transport callable), így a logika
 * hálózat nélkül tesztelhető. Éles üzemben cURL-t használ.
 */
final class BarionClient
{
    private string $poskey;
    private string $base;
    /** @var callable|null fn(string $method, string $url, ?array $body): string */
    private $transport;

    public function __construct(string $poskey, string $env = 'test', ?callable $transport = null)
    {
        $this->poskey = $poskey;
        $this->base = $env === 'prod' ? 'https://api.barion.com' : 'https://api.test.barion.com';
        $this->transport = $transport;
    }

    /**
     * Fizetés indítása (Payment/Start). A POSKey-t a kliens fűzi hozzá.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function startPayment(array $payload): array
    {
        $payload['POSKey'] = $this->poskey;

        return $this->request('POST', '/v2/Payment/Start', $payload);
    }

    /**
     * Fizetés állapotának lekérése (Payment/GetPaymentState).
     *
     * @return array<string, mixed>
     */
    public function getPaymentState(string $paymentId): array
    {
        $query = http_build_query(['POSKey' => $this->poskey, 'PaymentId' => $paymentId]);

        return $this->request('GET', '/v2/Payment/GetPaymentState?' . $query, null);
    }

    /**
     * @param array<string, mixed>|null $body
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, ?array $body): array
    {
        $url = $this->base . $path;
        $raw = $this->transport !== null
            ? (string) ($this->transport)($method, $url, $body)
            : $this->curl($method, $url, $body);

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, mixed>|null $body
     */
    private function curl(string $method, string $url, ?array $body): string
    {
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('A cURL bővítmény nem érhető el.');
        }
        $ch = curl_init($url);
        $headers = ['Accept: application/json'];
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        if ($method === 'POST') {
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $res = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($res === false) {
            throw new \RuntimeException('Barion HTTP hiba: ' . $err);
        }
        if ($code >= 500) {
            throw new \RuntimeException('Barion szerverhiba: HTTP ' . $code);
        }

        return (string) $res;
    }
}
