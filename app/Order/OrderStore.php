<?php

namespace App\Order;

/**
 * Egyszerű, fájl-alapú rendeléstár (JSON fájlok). Adatbázis nélkül, Windows
 * alatt is jól működik. Minden rendelés egy {token}.json fájl; a sorszámot
 * egy zárolt számláló adja.
 */
final class OrderStore
{
    private string $dir;

    public function __construct(string $dir)
    {
        $this->dir = rtrim($dir, '/\\');
        if (!is_dir($this->dir)) {
            @mkdir($this->dir, 0775, true);
        }
    }

    public function save(array $order): void
    {
        $token = (string) ($order['token'] ?? '');
        if (!self::validToken($token)) {
            return;
        }
        file_put_contents(
            $this->path($token),
            json_encode($order, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    public function find(string $token): ?array
    {
        if (!self::validToken($token) || !is_file($this->path($token))) {
            return null;
        }
        $data = json_decode((string) file_get_contents($this->path($token)), true);
        return is_array($data) ? $data : null;
    }

    /** Mély összefésülés a meglévő rendeléssel. */
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

    /** Növekvő sorszám, pl. NT-000042. */
    public function nextNumber(): string
    {
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
