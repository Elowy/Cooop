<?php

namespace App\Newsletter;

use PDO;

/**
 * Hírlevél-feliratkozók tára. Telepítés után adatbázis (subscribers tábla),
 * előtte JSON fájl.
 *
 * Feliratkozó: id, email, name, token, active, created.
 */
final class SubscriberStore
{
    private ?PDO $pdo;
    private string $file;

    public function __construct(?PDO $pdo = null, ?string $file = null)
    {
        $this->pdo = $pdo;
        $this->file = $file ?? dirname(__DIR__, 2) . '/storage/subscribers.json';
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        if ($this->pdo) {
            $out = [];
            $rows = $this->pdo->query('SELECT id, email, name, token, active, created_at FROM subscribers ORDER BY id DESC');
            foreach ($rows as $r) {
                $out[] = $this->row($r);
            }
            return $out;
        }
        if (!is_file($this->file)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($this->file), true);
        return is_array($data) ? array_reverse($data) : [];
    }

    /** @return array<int, array<string, mixed>> Csak az aktív feliratkozók. */
    public function active(): array
    {
        return array_values(array_filter($this->all(), static fn ($s) => !empty($s['active'])));
    }

    public function count(): int
    {
        if ($this->pdo) {
            return (int) $this->pdo->query('SELECT COUNT(*) FROM subscribers')->fetchColumn();
        }
        return count($this->all());
    }

    public function activeCount(): int
    {
        return count($this->active());
    }

    public function findByEmail(string $email): ?array
    {
        $email = mb_strtolower(trim($email));
        if ($this->pdo) {
            $stmt = $this->pdo->prepare('SELECT id, email, name, token, active, created_at FROM subscribers WHERE email = ?');
            $stmt->execute([$email]);
            $r = $stmt->fetch();
            return $r ? $this->row($r) : null;
        }
        foreach ($this->all() as $s) {
            if (mb_strtolower((string) $s['email']) === $email) {
                return $s;
            }
        }
        return null;
    }

    public function findByToken(string $token): ?array
    {
        if ($token === '') {
            return null;
        }
        if ($this->pdo) {
            $stmt = $this->pdo->prepare('SELECT id, email, name, token, active, created_at FROM subscribers WHERE token = ?');
            $stmt->execute([$token]);
            $r = $stmt->fetch();
            return $r ? $this->row($r) : null;
        }
        foreach ($this->all() as $s) {
            if ((string) ($s['token'] ?? '') === $token) {
                return $s;
            }
        }
        return null;
    }

    /**
     * Feliratkoztat egy e-mailt. Ha már létezik, szükség esetén újraaktiválja.
     * @return bool true, ha tényleg új feliratkozó jött létre.
     */
    public function subscribe(string $email, string $name = ''): bool
    {
        $email = mb_strtolower(trim($email));
        $name = trim($name);
        $existing = $this->findByEmail($email);
        if ($existing !== null) {
            if (empty($existing['active'])) {
                $this->setActive((int) $existing['id'], true);
            }
            return false;
        }
        $token = bin2hex(random_bytes(16));
        if ($this->pdo) {
            $this->pdo->prepare('INSERT INTO subscribers (email, name, token, active, created_at) VALUES (?, ?, ?, 1, ?)')
                ->execute([$email, $name, $token, date('c')]);
            return true;
        }
        $rows = $this->fileRows();
        $rows[] = ['id' => $this->nextId($rows), 'email' => $email, 'name' => $name, 'token' => $token, 'active' => 1, 'created' => date('c')];
        $this->persist($rows);
        return true;
    }

    public function setActive(int $id, bool $active): void
    {
        if ($this->pdo) {
            $this->pdo->prepare('UPDATE subscribers SET active = ? WHERE id = ?')->execute([$active ? 1 : 0, $id]);
            return;
        }
        $rows = $this->fileRows();
        foreach ($rows as &$s) {
            if ((int) $s['id'] === $id) {
                $s['active'] = $active ? 1 : 0;
                break;
            }
        }
        unset($s);
        $this->persist($rows);
    }

    /** Leiratkoztat token alapján; visszaadja a feliratkozót, vagy null-t. */
    public function unsubscribeByToken(string $token): ?array
    {
        $s = $this->findByToken($token);
        if ($s === null) {
            return null;
        }
        $this->setActive((int) $s['id'], false);
        return $s;
    }

    public function delete(int $id): void
    {
        if ($this->pdo) {
            $this->pdo->prepare('DELETE FROM subscribers WHERE id = ?')->execute([$id]);
            return;
        }
        $rows = array_values(array_filter($this->fileRows(), static fn ($s) => (int) $s['id'] !== $id));
        $this->persist($rows);
    }

    /** @param array<string, mixed> $r */
    private function row(array $r): array
    {
        return [
            'id' => (int) $r['id'], 'email' => (string) $r['email'], 'name' => (string) ($r['name'] ?? ''),
            'token' => (string) ($r['token'] ?? ''), 'active' => (int) ($r['active'] ?? 1),
            'created' => (string) ($r['created_at'] ?? ''),
        ];
    }

    /** Nyers fájl-sorok (tárolási sorrendben). */
    private function fileRows(): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($this->file), true);
        return is_array($data) ? $data : [];
    }

    private function persist(array $rows): void
    {
        $dir = dirname($this->file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        file_put_contents($this->file, json_encode(array_values($rows), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function nextId(array $rows): int
    {
        $max = 0;
        foreach ($rows as $r) {
            $max = max($max, (int) $r['id']);
        }
        return $max + 1;
    }
}
