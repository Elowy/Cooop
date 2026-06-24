<?php

namespace App\Order;

use PDO;

/**
 * Rendeléstár. Telepítés után adatbázis (orders tábla; a rendelés teljes
 * szerkezete JSON-ként a data oszlopban), előtte JSON fájlok.
 */
final class OrderStore
{
    private ?PDO $pdo;
    private string $dir;

    public function __construct(?PDO $pdo = null, ?string $dir = null)
    {
        $this->pdo = $pdo;
        $this->dir = rtrim($dir ?? dirname(__DIR__, 2) . '/storage/orders', '/\\');
        if (!$this->pdo && !is_dir($this->dir)) {
            @mkdir($this->dir, 0775, true);
        }
    }

    public function save(array $order): void
    {
        $token = (string) ($order['token'] ?? '');
        if (!self::validToken($token)) {
            return;
        }
        if ($this->pdo) {
            $json = json_encode($order, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $this->pdo->prepare('DELETE FROM orders WHERE token = ?')->execute([$token]);
            $this->pdo->prepare('INSERT INTO orders (token, created_at, data) VALUES (?, ?, ?)')
                ->execute([$token, (string) ($order['created'] ?? date('c')), $json]);
            return;
        }
        file_put_contents($this->path($token), json_encode($order, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public function find(string $token): ?array
    {
        if (!self::validToken($token)) {
            return null;
        }
        if ($this->pdo) {
            $stmt = $this->pdo->prepare('SELECT data FROM orders WHERE token = ?');
            $stmt->execute([$token]);
            $row = $stmt->fetch();
            if (!$row) {
                return null;
            }
            $data = json_decode((string) $row['data'], true);
            return is_array($data) ? $data : null;
        }
        if (!is_file($this->path($token))) {
            return null;
        }
        $data = json_decode((string) file_get_contents($this->path($token)), true);
        return is_array($data) ? $data : null;
    }

    public function update(string $token, array $changes): ?array
    {
        $order = $this->find($token);
        if ($order === null) {
            return null;
        }
        $order = array_replace_recursive($order, $changes);
        $this->save($order);
        return $order;
    }

    /** @return array<int, array<string, mixed>> Létrehozás szerint csökkenő. */
    public function all(): array
    {
        if ($this->pdo) {
            $out = [];
            foreach ($this->pdo->query('SELECT data FROM orders ORDER BY created_at DESC') as $row) {
                $data = json_decode((string) $row['data'], true);
                if (is_array($data)) {
                    $out[] = $data;
                }
            }
            return $out;
        }
        $orders = [];
        foreach (glob($this->dir . '/*.json') ?: [] as $file) {
            $data = json_decode((string) file_get_contents($file), true);
            if (is_array($data)) {
                $orders[] = $data;
            }
        }
        usort($orders, static fn ($a, $b) => ($b['created'] ?? '') <=> ($a['created'] ?? ''));
        return $orders;
    }

    /**
     * Egy vásárló rendelései: a hozzá kötött (user_id) vagy az e-mail-egyezésű
     * rendelések, létrehozás szerint csökkenő sorrendben.
     *
     * @return array<int, array<string, mixed>>
     */
    public function forCustomer(int $userId, string $email): array
    {
        $email = strtolower(trim($email));
        return array_values(array_filter($this->all(), static function ($o) use ($userId, $email): bool {
            $oUser = (int) ($o['customer']['user_id'] ?? 0);
            $oEmail = strtolower(trim((string) ($o['customer']['email'] ?? '')));
            return ($userId > 0 && $oUser === $userId) || ($email !== '' && $oEmail === $email);
        }));
    }

    /**
     * Rendelés keresése a fizetési szolgáltató (Barion) PaymentId-je alapján.
     * A callback (IPN) csak a paymentId-t kapja, ez köti vissza a rendeléshez.
     */
    public function findByPaymentId(string $paymentId): ?array
    {
        if ($paymentId === '') {
            return null;
        }
        foreach ($this->all() as $o) {
            if ((string) ($o['payment']['payment_id'] ?? '') === $paymentId) {
                return $o;
            }
        }
        return null;
    }

    public function nextNumber(): string
    {
        if ($this->pdo) {
            $n = (int) $this->pdo->query('SELECT COUNT(*) AS c FROM orders')->fetch()['c'] + 1;
            return 'NT-' . str_pad((string) $n, 6, '0', STR_PAD_LEFT);
        }
        $file = $this->dir . '/_counter';
        $fp = fopen($file, 'c+');
        if ($fp === false) {
            return 'NT-' . date('ymdHis');
        }
        flock($fp, LOCK_EX);
        $n = (int) stream_get_contents($fp) + 1;
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, (string) $n);
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);
        return 'NT-' . str_pad((string) $n, 6, '0', STR_PAD_LEFT);
    }

    public static function validToken(string $token): bool
    {
        return strlen($token) === 32 && ctype_xdigit($token);
    }

    private function path(string $token): string
    {
        return $this->dir . '/' . $token . '.json';
    }
}
