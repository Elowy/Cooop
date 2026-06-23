<?php

namespace App\Settings;

/**
 * Egyszerű kulcs-érték beállítástár (JSON fájl), az adminból szerkeszthető
 * oldal-beállításokhoz (pl. kapcsolati csatornák).
 */
final class SettingsStore
{
    private string $file;

    public function __construct(?string $file = null)
    {
        $this->file = $file ?? dirname(__DIR__, 2) . '/storage/settings.json';
    }

    /** @return array<string, mixed> */
    public function all(): array
    {
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
