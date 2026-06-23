<?php

namespace App\Map;

use PDO;

/**
 * Térkép-pontok (POI) tára. Telepítés után adatbázis (pois tábla),
 * előtte JSON fájl.
 */
final class PoiStore
{
    private ?PDO $pdo;
    private string $file;

    public function __construct(?PDO $pdo = null, ?string $file = null)
    {
        $this->pdo = $pdo;
        $this->file = $file ?? dirname(__DIR__, 2) . '/storage/pois.json';
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        if ($this->pdo) {
            $out = [];
            $rows = $this->pdo->query('SELECT id, title, lat, lng, description, link FROM pois ORDER BY id');
            foreach ($rows as $r) {
                $out[] = [
                    'id' => (int) $r['id'], 'title' => $r['title'],
                    'lat' => (float) $r['lat'], 'lng' => (float) $r['lng'],
                    'description' => $r['description'], 'link' => $r['link'],
                ];
            }
            return $out;
        }
        if (!is_file($this->file)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($this->file), true);
        return is_array($data) ? $data : [];
    }

    public function find(int $id): ?array
    {
        if ($this->pdo) {
            $stmt = $this->pdo->prepare('SELECT id, title, lat, lng, description, link FROM pois WHERE id = ?');
            $stmt->execute([$id]);
            $r = $stmt->fetch();
            return $r ? ['id' => (int) $r['id'], 'title' => $r['title'], 'lat' => (float) $r['lat'], 'lng' => (float) $r['lng'], 'description' => $r['description'], 'link' => $r['link']] : null;
        }
        foreach ($this->all() as $poi) {
            if ((int) $poi['id'] === $id) {
                return $poi;
            }
        }
        return null;
    }

    public function save(array $poi): int
    {
        if ($this->pdo) {
            if (!empty($poi['id'])) {
                $this->pdo->prepare('UPDATE pois SET title=?, lat=?, lng=?, description=?, link=? WHERE id=?')
                    ->execute([$poi['title'] ?? '', (float) $poi['lat'], (float) $poi['lng'], $poi['description'] ?? '', $poi['link'] ?? '', (int) $poi['id']]);
                return (int) $poi['id'];
            }
            $this->pdo->prepare('INSERT INTO pois (title, lat, lng, description, link, created_at) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([$poi['title'] ?? '', (float) $poi['lat'], (float) $poi['lng'], $poi['description'] ?? '', $poi['link'] ?? '', date('c')]);
            return (int) $this->pdo->lastInsertId();
        }
        return $this->fileSave($poi);
    }

    public function delete(int $id): void
    {
        if ($this->pdo) {
            $this->pdo->prepare('DELETE FROM pois WHERE id = ?')->execute([$id]);
            return;
        }
        $pois = array_values(array_filter($this->all(), static fn ($p) => (int) $p['id'] !== $id));
        $this->persist($pois);
    }

    private function fileSave(array $poi): int
    {
        $pois = $this->all();
        if (empty($poi['id'])) {
            $poi['id'] = $this->nextId($pois);
            $poi['created'] = date('c');
            $pois[] = $poi;
        } else {
            $id = (int) $poi['id'];
            foreach ($pois as &$existing) {
                if ((int) $existing['id'] === $id) {
                    $existing = array_merge($existing, $poi);
                    break;
                }
            }
            unset($existing);
        }
        $this->persist($pois);
        return (int) $poi['id'];
    }

    private function persist(array $pois): void
    {
        $dir = dirname($this->file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        file_put_contents($this->file, json_encode(array_values($pois), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function nextId(array $pois): int
    {
        $max = 0;
        foreach ($pois as $p) {
            $max = max($max, (int) $p['id']);
        }
        return $max + 1;
    }
}
