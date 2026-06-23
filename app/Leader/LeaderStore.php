<?php

namespace App\Leader;

use PDO;

/**
 * Vezetők / kapcsolattartók tára. Telepítés után adatbázis (leaders tábla),
 * előtte JSON fájl; fájl-módban első használatkor a config/leaders.php-ból
 * töltődik fel.
 *
 * Vezető: id, name, role, phone, email, photo, created.
 */
final class LeaderStore
{
    private ?PDO $pdo;
    private string $file;
    private string $seedFile;

    public function __construct(?PDO $pdo = null, ?string $file = null, ?string $seedFile = null)
    {
        $this->pdo = $pdo;
        $this->file = $file ?? dirname(__DIR__, 2) . '/storage/leaders.json';
        $this->seedFile = $seedFile ?? dirname(__DIR__, 2) . '/config/leaders.php';
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        if ($this->pdo) {
            $out = [];
            $rows = $this->pdo->query('SELECT id, name, role, phone, email, photo FROM leaders ORDER BY id');
            foreach ($rows as $r) {
                $out[] = ['id' => (int) $r['id'], 'name' => $r['name'], 'role' => $r['role'], 'phone' => $r['phone'], 'email' => $r['email'], 'photo' => $r['photo']];
            }
            return $out;
        }
        if (!is_file($this->file)) {
            $seed = $this->seed();
            $this->persist($seed);
            return $seed;
        }
        $data = json_decode((string) file_get_contents($this->file), true);
        return is_array($data) ? $data : [];
    }

    public function find(int $id): ?array
    {
        if ($this->pdo) {
            $stmt = $this->pdo->prepare('SELECT id, name, role, phone, email, photo FROM leaders WHERE id = ?');
            $stmt->execute([$id]);
            $r = $stmt->fetch();
            return $r ? ['id' => (int) $r['id'], 'name' => $r['name'], 'role' => $r['role'], 'phone' => $r['phone'], 'email' => $r['email'], 'photo' => $r['photo']] : null;
        }
        foreach ($this->all() as $l) {
            if ((int) $l['id'] === $id) {
                return $l;
            }
        }
        return null;
    }

    public function save(array $leader): int
    {
        if ($this->pdo) {
            if (!empty($leader['id'])) {
                $this->pdo->prepare('UPDATE leaders SET name=?, role=?, phone=?, email=?, photo=? WHERE id=?')
                    ->execute([$leader['name'] ?? '', $leader['role'] ?? '', $leader['phone'] ?? '', $leader['email'] ?? '', $leader['photo'] ?? '', (int) $leader['id']]);
                return (int) $leader['id'];
            }
            $this->pdo->prepare('INSERT INTO leaders (name, role, phone, email, photo, created_at) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([$leader['name'] ?? '', $leader['role'] ?? '', $leader['phone'] ?? '', $leader['email'] ?? '', $leader['photo'] ?? '', date('c')]);
            return (int) $this->pdo->lastInsertId();
        }
        return $this->fileSave($leader);
    }

    public function delete(int $id): void
    {
        if ($this->pdo) {
            $this->pdo->prepare('DELETE FROM leaders WHERE id = ?')->execute([$id]);
            return;
        }
        $leaders = array_values(array_filter($this->all(), static fn ($l) => (int) $l['id'] !== $id));
        $this->persist($leaders);
    }

    private function fileSave(array $leader): int
    {
        $leaders = $this->all();
        if (empty($leader['id'])) {
            $leader['id'] = $this->nextId($leaders);
            $leader['created'] = date('c');
            $leaders[] = $leader;
        } else {
            $id = (int) $leader['id'];
            foreach ($leaders as &$existing) {
                if ((int) $existing['id'] === $id) {
                    $existing = array_merge($existing, $leader);
                    break;
                }
            }
            unset($existing);
        }
        $this->persist($leaders);
        return (int) $leader['id'];
    }

    /** @return array<int, array<string, mixed>> */
    private function seed(): array
    {
        $rows = is_file($this->seedFile) ? (array) require $this->seedFile : [];
        $out = [];
        $i = 0;
        foreach ($rows as $r) {
            $out[] = [
                'id' => ++$i,
                'name' => (string) ($r['name'] ?? ''), 'role' => (string) ($r['role'] ?? ''),
                'phone' => (string) ($r['phone'] ?? ''), 'email' => (string) ($r['email'] ?? ''),
                'photo' => (string) ($r['photo'] ?? ''), 'created' => date('c'),
            ];
        }
        return $out;
    }

    private function persist(array $leaders): void
    {
        $dir = dirname($this->file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        file_put_contents($this->file, json_encode(array_values($leaders), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function nextId(array $leaders): int
    {
        $max = 0;
        foreach ($leaders as $l) {
            $max = max($max, (int) $l['id']);
        }
        return $max + 1;
    }
}
