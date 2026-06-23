<?php

namespace App\Settings;

use PDO;

/**
 * Kulcs-érték beállítástár. Telepítés után adatbázis (settings tábla),
 * előtte JSON fájl.
 */
final class SettingsStore
{
    private ?PDO $pdo;
    private string $file;

    public function __construct(?PDO $pdo = null, ?string $file = null)
    {
        $this->pdo = $pdo;
        $this->file = $file ?? dirname(__DIR__, 2) . '/storage/settings.json';
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
        if ($this->pdo) {
            $out = [];
            foreach ($this->pdo->query('SELECT skey, svalue FROM settings') as $row) {
                $out[$row['skey']] = $row['svalue'];
            }
            return $out;
        }
        if (!is_file($this->file)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($this->file), true);
        return is_array($data) ? $data : [];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->all());
    }

    /** @param array<string, mixed> $values */
    public function saveMany(array $values): void
    {
        if ($this->pdo) {
            $del = $this->pdo->prepare('DELETE FROM settings WHERE skey = ?');
            $ins = $this->pdo->prepare('INSERT INTO settings (skey, svalue) VALUES (?, ?)');
            foreach ($values as $key => $value) {
                $del->execute([$key]);
                $ins->execute([$key, (string) $value]);
            }
            return;
        }
        $data = array_merge($this->all(), $values);
        $dir = dirname($this->file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        file_put_contents(
            $this->file,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }
}
