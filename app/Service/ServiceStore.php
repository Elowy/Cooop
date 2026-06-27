<?php

namespace App\Service;

use PDO;

/**
 * Tevékenységek / szolgáltatások tára. Telepítés után adatbázis (services
 * tábla), előtte JSON fájl. Fájl-módban első használatkor a config/services.php
 * kezdő-adatokból töltődik fel; DB-módban a telepítő importálja.
 *
 * Szolgáltatás: id, slug, title, icon, image (URL), summary, body (Markdown),
 * sort, published (0/1), created (ISO dátum).
 */
final class ServiceStore
{
    private ?PDO $pdo;
    private string $file;
    private string $seedFile;

    public function __construct(?PDO $pdo = null, ?string $file = null, ?string $seedFile = null)
    {
        $this->pdo = $pdo;
        $this->file = $file ?? dirname(__DIR__, 2) . '/storage/services.json';
        $this->seedFile = $seedFile ?? dirname(__DIR__, 2) . '/config/services.php';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(bool $publishedOnly = false, bool $raw = false): array
    {
        if ($this->pdo) {
            $out = [];
            $rows = $this->pdo->query('SELECT id, slug, title, icon, image, summary, body, sort, published, created_at, i18n FROM services ORDER BY sort, id');
            foreach ($rows as $r) {
                $out[] = self::mapRow($r);
            }
        } else {
            if (!is_file($this->file)) {
                $seed = $this->seed();
                $this->persist($seed);
                $out = $seed;
            } else {
                $data = json_decode((string) file_get_contents($this->file), true);
                $out = is_array($data) ? $data : [];
            }
            $out = self::sort($out);
        }

        if ($publishedOnly) {
            $out = array_values(array_filter($out, static fn ($s) => (int) ($s['published'] ?? 0) === 1));
        }
        // A storefront a látogató nyelvén kapja a fordított mezőket; az admin a raw-t kéri.
        if (!$raw) {
            $out = array_map([self::class, 'localize'], $out);
        }
        return $out;
    }

    /**
     * Könnyű menü-adat (slug + cím) a publikált szolgáltatásokról, sorrendben.
     * @return array<int, array{slug: string, title: string}>
     */
    public function menu(): array
    {
        return array_map(
            static fn ($s) => ['slug' => (string) $s['slug'], 'title' => (string) $s['title']],
            $this->all(true)
        );
    }

    public function find(int $id, bool $raw = false): ?array
    {
        $row = null;
        if ($this->pdo) {
            $stmt = $this->pdo->prepare('SELECT id, slug, title, icon, image, summary, body, sort, published, created_at, i18n FROM services WHERE id = ?');
            $stmt->execute([$id]);
            $r = $stmt->fetch();
            $row = $r ? self::mapRow($r) : null;
        } else {
            foreach ($this->all(false, true) as $s) {
                if ((int) $s['id'] === $id) {
                    $row = $s;
                    break;
                }
            }
        }
        return ($row !== null && !$raw) ? self::localize($row) : $row;
    }

    public function findBySlug(string $slug, bool $publishedOnly = false, bool $raw = false): ?array
    {
        foreach ($this->all($publishedOnly, $raw) as $s) {
            if ((string) $s['slug'] === $slug) {
                return $s;
            }
        }
        return null;
    }

    public function save(array $service): int
    {
        $id = (int) ($service['id'] ?? 0);
        $title = trim((string) ($service['title'] ?? ''));
        $base = self::slugify(trim((string) ($service['slug'] ?? '')) !== '' ? (string) $service['slug'] : $title);
        $slug = $this->uniqueSlug($base, $id > 0 ? $id : null);

        $i18n = self::cleanI18n($service['i18n'] ?? null, ['title', 'summary', 'body']);
        $row = [
            'slug' => $slug,
            'title' => $title,
            'icon' => trim((string) ($service['icon'] ?? '')),
            'image' => trim((string) ($service['image'] ?? '')),
            'summary' => trim((string) ($service['summary'] ?? '')),
            'body' => (string) ($service['body'] ?? ''),
            'sort' => (int) ($service['sort'] ?? 0),
            'published' => (int) ($service['published'] ?? 0) === 1 ? 1 : 0,
            'i18n' => $i18n,
        ];

        if ($this->pdo) {
            $i18nJson = $i18n === [] ? null : json_encode($i18n, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($id > 0) {
                $this->pdo->prepare('UPDATE services SET slug=?, title=?, icon=?, image=?, summary=?, body=?, sort=?, published=?, i18n=? WHERE id=?')
                    ->execute([$row['slug'], $row['title'], $row['icon'], $row['image'], $row['summary'], $row['body'], $row['sort'], $row['published'], $i18nJson, $id]);
                return $id;
            }
            $this->pdo->prepare('INSERT INTO services (slug, title, icon, image, summary, body, sort, published, created_at, i18n) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute([$row['slug'], $row['title'], $row['icon'], $row['image'], $row['summary'], $row['body'], $row['sort'], $row['published'], date('c'), $i18nJson]);
            return (int) $this->pdo->lastInsertId();
        }
        return $this->fileSave($id, $row);
    }

    public function delete(int $id): void
    {
        if ($this->pdo) {
            $this->pdo->prepare('DELETE FROM services WHERE id = ?')->execute([$id]);
            return;
        }
        $services = array_values(array_filter($this->all(), static fn ($s) => (int) $s['id'] !== $id));
        $this->persist($services);
    }

    /** URL-barát azonosító a címből (magyar ékezetek átírásával). */
    public static function slugify(string $text): string
    {
        $map = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ö' => 'o', 'ő' => 'o',
            'ú' => 'u', 'ü' => 'u', 'ű' => 'u',
            'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ö' => 'o', 'Ő' => 'o',
            'Ú' => 'u', 'Ü' => 'u', 'Ű' => 'u',
        ];
        $text = strtr($text, $map);
        $text = mb_strtolower($text, 'UTF-8');
        $text = (string) preg_replace('/[^a-z0-9]+/u', '-', $text);
        $text = trim($text, '-');
        return $text !== '' ? $text : 'szolgaltatas';
    }

    /** Ütközésmentes slug: ha foglalt, -2, -3, … utótaggal. */
    private function uniqueSlug(string $base, ?int $excludeId): string
    {
        $taken = [];
        foreach ($this->all() as $s) {
            if ($excludeId !== null && (int) $s['id'] === $excludeId) {
                continue;
            }
            $taken[(string) $s['slug']] = true;
        }
        $slug = $base;
        $i = 2;
        while (isset($taken[$slug])) {
            $slug = $base . '-' . $i;
            $i++;
        }
        return $slug;
    }

    private function fileSave(int $id, array $row): int
    {
        $services = $this->all();
        if ($id <= 0) {
            $id = $this->nextId($services);
            $services[] = array_merge(['id' => $id, 'created' => date('c')], $row);
        } else {
            foreach ($services as &$existing) {
                if ((int) $existing['id'] === $id) {
                    $existing = array_merge($existing, $row, ['id' => $id]);
                    break;
                }
            }
            unset($existing);
        }
        $this->persist($services);
        return $id;
    }

    /** Sorrend szerint (sort), holtversenyben id szerint. */
    private static function sort(array $services): array
    {
        usort($services, static function ($a, $b) {
            $sa = (int) ($a['sort'] ?? 0);
            $sb = (int) ($b['sort'] ?? 0);
            return $sa === $sb ? ((int) ($a['id'] ?? 0) <=> (int) ($b['id'] ?? 0)) : ($sa <=> $sb);
        });
        return $services;
    }

    private static function mapRow(array $r): array
    {
        return [
            'id' => (int) $r['id'],
            'slug' => (string) $r['slug'],
            'title' => (string) $r['title'],
            'icon' => (string) ($r['icon'] ?? ''),
            'image' => (string) ($r['image'] ?? ''),
            'summary' => (string) ($r['summary'] ?? ''),
            'body' => (string) ($r['body'] ?? ''),
            'sort' => (int) ($r['sort'] ?? 0),
            'published' => (int) ($r['published'] ?? 0),
            'created' => (string) ($r['created_at'] ?? ''),
            'i18n' => self::decodeI18n($r['i18n'] ?? null),
        ];
    }

    /** A raw rekord lefordított mezőkkel a látogató nyelvén (HU vagy hiány → változatlan). */
    private static function localize(array $row): array
    {
        $loc = \App\Core\Lang::locale();
        if ($loc === 'hu' || empty($row['i18n'][$loc]) || !is_array($row['i18n'][$loc])) {
            return $row;
        }
        foreach (['title', 'summary', 'body'] as $f) {
            $v = $row['i18n'][$loc][$f] ?? '';
            if (is_string($v) && trim($v) !== '') {
                $row[$f] = $v;
            }
        }
        return $row;
    }

    /** @return array<string, array<string, string>> */
    private static function decodeI18n(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    /**
     * Csak az ismert (nem magyar) nyelvek és a megadott mezők, üres értékek nélkül.
     *
     * @param string[] $fields
     * @return array<string, array<string, string>>
     */
    private static function cleanI18n(mixed $input, array $fields): array
    {
        if (!is_array($input)) {
            return [];
        }
        $out = [];
        foreach (array_keys(\App\Core\Lang::available()) as $code) {
            if ($code === 'hu' || empty($input[$code]) || !is_array($input[$code])) {
                continue;
            }
            $vals = [];
            foreach ($fields as $f) {
                $v = trim((string) ($input[$code][$f] ?? ''));
                if ($v !== '') {
                    $vals[$f] = $v;
                }
            }
            if ($vals !== []) {
                $out[$code] = $vals;
            }
        }
        return $out;
    }

    /** @return array<int, array<string, mixed>> */
    private function seed(): array
    {
        $rows = is_file($this->seedFile) ? (array) require $this->seedFile : [];
        $out = [];
        $i = 0;
        foreach ($rows as $s) {
            $i++;
            $title = (string) ($s['title'] ?? '');
            $out[] = [
                'id' => $i,
                'slug' => self::slugify((string) ($s['slug'] ?? '') !== '' ? (string) $s['slug'] : $title),
                'title' => $title,
                'icon' => (string) ($s['icon'] ?? ''),
                'image' => (string) ($s['image'] ?? ''),
                'summary' => (string) ($s['summary'] ?? ''),
                'body' => (string) ($s['body'] ?? ''),
                'sort' => (int) ($s['sort'] ?? $i),
                'published' => (int) ($s['published'] ?? 1) === 0 ? 0 : 1,
                'created' => date('c'),
            ];
        }
        return $out;
    }

    private function persist(array $services): void
    {
        $dir = dirname($this->file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        file_put_contents($this->file, json_encode(array_values($services), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function nextId(array $services): int
    {
        $max = 0;
        foreach ($services as $s) {
            $max = max($max, (int) $s['id']);
        }
        return $max + 1;
    }
}
