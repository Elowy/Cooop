<?php

namespace App\Reference;

use PDO;

/**
 * Referenciák tára. Telepítés után adatbázis (refs tábla), előtte JSON fájl.
 * Fájl-módban első használatkor a partnerlistából (config/partners.php) töltődik
 * fel; DB-módban a telepítő importálja a fájl-adatokat.
 *
 * Referencia: id, name, logo, short, long, created.
 */
final class ReferenceStore
{
    /** Nyelvenként fordítható mezők. */
    private const I18N_FIELDS = ['name', 'short', 'long'];

    private ?PDO $pdo;
    private string $file;
    private string $seedFile;

    public function __construct(?PDO $pdo = null, ?string $file = null, ?string $seedFile = null)
    {
        $this->pdo = $pdo;
        $this->file = $file ?? dirname(__DIR__, 2) . '/storage/references.json';
        $this->seedFile = $seedFile ?? dirname(__DIR__, 2) . '/config/partners.php';
    }

    /** @return array<int, array<string, mixed>> */
    public function all(bool $raw = false): array
    {
        if ($this->pdo) {
            $out = [];
            $rows = $this->pdo->query('SELECT id, name, logo, short_text, long_text, url, featured, i18n FROM refs ORDER BY featured DESC, id');
            foreach ($rows as $r) {
                $out[] = [
                    'id' => (int) $r['id'], 'name' => $r['name'], 'logo' => $r['logo'],
                    'short' => (string) $r['short_text'], 'long' => (string) $r['long_text'],
                    'url' => (string) ($r['url'] ?? ''), 'featured' => (int) ($r['featured'] ?? 0),
                    'i18n' => \App\Core\Lang::decodeI18n($r['i18n'] ?? null),
                ];
            }
        } elseif (!is_file($this->file)) {
            $seed = $this->seed();
            $this->persist($seed);
            $out = self::sort($seed);
        } else {
            $data = json_decode((string) file_get_contents($this->file), true);
            $out = is_array($data) ? self::sort($data) : [];
        }
        if (!$raw) {
            $out = array_map(static fn ($r) => \App\Core\Lang::overlay($r, self::I18N_FIELDS), $out);
        }
        return $out;
    }

    /** Kiemeltek előre, azon belül id szerint. */
    private static function sort(array $refs): array
    {
        usort($refs, static function ($a, $b) {
            $fa = (int) ($a['featured'] ?? 0);
            $fb = (int) ($b['featured'] ?? 0);
            return $fa === $fb ? ((int) ($a['id'] ?? 0) <=> (int) ($b['id'] ?? 0)) : ($fb <=> $fa);
        });
        return $refs;
    }

    public function find(int $id, bool $raw = false): ?array
    {
        $row = null;
        if ($this->pdo) {
            $stmt = $this->pdo->prepare('SELECT id, name, logo, short_text, long_text, url, featured, i18n FROM refs WHERE id = ?');
            $stmt->execute([$id]);
            $r = $stmt->fetch();
            $row = $r ? ['id' => (int) $r['id'], 'name' => $r['name'], 'logo' => $r['logo'], 'short' => (string) $r['short_text'], 'long' => (string) $r['long_text'], 'url' => (string) ($r['url'] ?? ''), 'featured' => (int) ($r['featured'] ?? 0), 'i18n' => \App\Core\Lang::decodeI18n($r['i18n'] ?? null)] : null;
        } else {
            foreach ($this->all(true) as $ref) {
                if ((int) $ref['id'] === $id) {
                    $row = $ref;
                    break;
                }
            }
        }
        return ($row !== null && !$raw) ? \App\Core\Lang::overlay($row, self::I18N_FIELDS) : $row;
    }

    public function save(array $ref): int
    {
        $i18n = \App\Core\Lang::cleanI18n($ref['i18n'] ?? null, self::I18N_FIELDS);
        if ($this->pdo) {
            $i18nJson = $i18n === [] ? null : json_encode($i18n, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (!empty($ref['id'])) {
                $this->pdo->prepare('UPDATE refs SET name=?, logo=?, short_text=?, long_text=?, url=?, featured=?, i18n=? WHERE id=?')
                    ->execute([$ref['name'] ?? '', $ref['logo'] ?? '', $ref['short'] ?? '', $ref['long'] ?? '', $ref['url'] ?? '', (int) ($ref['featured'] ?? 0), $i18nJson, (int) $ref['id']]);
                return (int) $ref['id'];
            }
            $this->pdo->prepare('INSERT INTO refs (name, logo, short_text, long_text, url, featured, created_at, i18n) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute([$ref['name'] ?? '', $ref['logo'] ?? '', $ref['short'] ?? '', $ref['long'] ?? '', $ref['url'] ?? '', (int) ($ref['featured'] ?? 0), date('c'), $i18nJson]);
            return (int) $this->pdo->lastInsertId();
        }
        $ref['i18n'] = $i18n;
        return $this->fileSave($ref);
    }

    public function delete(int $id): void
    {
        if ($this->pdo) {
            $this->pdo->prepare('DELETE FROM refs WHERE id = ?')->execute([$id]);
            return;
        }
        $refs = array_values(array_filter($this->all(), static fn ($r) => (int) $r['id'] !== $id));
        $this->persist($refs);
    }

    private function fileSave(array $ref): int
    {
        $refs = $this->all();
        if (empty($ref['id'])) {
            $ref['id'] = $this->nextId($refs);
            $ref['created'] = date('c');
            $refs[] = $ref;
        } else {
            $id = (int) $ref['id'];
            foreach ($refs as &$existing) {
                if ((int) $existing['id'] === $id) {
                    $existing = array_merge($existing, $ref);
                    break;
                }
            }
            unset($existing);
        }
        $this->persist($refs);
        return (int) $ref['id'];
    }

    /** @return array<int, array<string, mixed>> */
    private function seed(): array
    {
        $rows = is_file($this->seedFile) ? (array) require $this->seedFile : [];
        $out = [];
        $i = 0;
        foreach ($rows as $p) {
            $out[] = ['id' => ++$i, 'name' => (string) ($p['name'] ?? ''), 'logo' => '', 'short' => (string) ($p['note'] ?? ''), 'long' => '', 'url' => '', 'featured' => 0, 'created' => date('c')];
        }
        return $out;
    }

    private function persist(array $refs): void
    {
        $dir = dirname($this->file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        file_put_contents($this->file, json_encode(array_values($refs), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function nextId(array $refs): int
    {
        $max = 0;
        foreach ($refs as $r) {
            $max = max($max, (int) $r['id']);
        }
        return $max + 1;
    }
}
