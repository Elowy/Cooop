<?php

namespace App\Message;

/**
 * Egyszerű, fájl-alapú tár a kapcsolatfelvételi üzeneteknek (JSON fájlok).
 */
final class MessageStore
{
    private string $dir;

    public function __construct(string $dir)
    {
        $this->dir = rtrim($dir, '/\\');
        if (!is_dir($this->dir)) {
            @mkdir($this->dir, 0775, true);
        }
    }

    public function save(array $message): void
    {
        $id = (string) ($message['id'] ?? (date('Ymd-His') . '-' . bin2hex(random_bytes(3))));
        $message['id'] = $id;
        file_put_contents(
            $this->dir . '/' . $id . '.json',
            json_encode($message, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    /** @return array<int, array<string, mixed>> Létrehozás szerint csökkenő. */
    public function all(): array
    {
        $out = [];
        foreach (glob($this->dir . '/*.json') ?: [] as $file) {
            $data = json_decode((string) file_get_contents($file), true);
            if (is_array($data)) {
                $out[] = $data;
            }
        }
        usort($out, static fn ($a, $b) => ($b['created'] ?? '') <=> ($a['created'] ?? ''));
        return $out;
    }

    public function count(): int
    {
        return count(glob($this->dir . '/*.json') ?: []);
    }
}
