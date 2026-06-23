<?php

namespace App\Message;

use PDO;

/**
 * Kapcsolatfelvételi üzenetek tára. Telepítés után adatbázis (messages tábla),
 * előtte JSON fájlok.
 */
final class MessageStore
{
    private ?PDO $pdo;
    private string $dir;

    public function __construct(?PDO $pdo = null, ?string $dir = null)
    {
        $this->pdo = $pdo;
        $this->dir = rtrim($dir ?? dirname(__DIR__, 2) . '/storage/messages', '/\\');
        if (!$this->pdo && !is_dir($this->dir)) {
            @mkdir($this->dir, 0775, true);
        }
    }

    public function save(array $message): void
    {
        if ($this->pdo) {
            $this->pdo->prepare(
                'INSERT INTO messages (created_at, name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?, ?)'
            )->execute([
                (string) ($message['created'] ?? date('c')),
                (string) ($message['name'] ?? ''),
                (string) ($message['email'] ?? ''),
                (string) ($message['phone'] ?? ''),
                (string) ($message['subject'] ?? ''),
                (string) ($message['message'] ?? ''),
            ]);
            return;
        }
        $id = (string) ($message['id'] ?? (date('Ymd-His') . '-' . bin2hex(random_bytes(3))));
        $message['id'] = $id;
        file_put_contents(
            $this->dir . '/' . $id . '.json',
            json_encode($message, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    /** @return array<int, array<string, mixed>> Legújabb elöl. */
    public function all(): array
    {
        if ($this->pdo) {
            $out = [];
            $rows = $this->pdo->query('SELECT id, created_at, name, email, phone, subject, message FROM messages ORDER BY id DESC');
            foreach ($rows as $r) {
                $out[] = [
                    'id' => (int) $r['id'], 'created' => $r['created_at'], 'name' => $r['name'],
                    'email' => $r['email'], 'phone' => $r['phone'], 'subject' => $r['subject'], 'message' => $r['message'],
                ];
            }
            return $out;
        }
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
        if ($this->pdo) {
            return (int) $this->pdo->query('SELECT COUNT(*) AS c FROM messages')->fetch()['c'];
        }
        return count(glob($this->dir . '/*.json') ?: []);
    }
}
