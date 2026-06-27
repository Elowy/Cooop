<?php

namespace App\Blog;

use PDO;

/**
 * Blogbejegyzések tára. Telepítés után adatbázis (posts tábla), előtte JSON fájl.
 * Fájl-módban első használatkor a config/blog.php kezdő-bejegyzésekből töltődik
 * fel; DB-módban a telepítő importálja a fájl-adatokat.
 *
 * Bejegyzés: id, slug, title, excerpt, body (Markdown), cover, author,
 * published (0/1), created (ISO dátum).
 */
final class BlogStore
{
    /** Nyelvenként fordítható mezők. */
    private const I18N_FIELDS = ['title', 'excerpt', 'body'];

    private ?PDO $pdo;
    private string $file;
    private string $seedFile;

    public function __construct(?PDO $pdo = null, ?string $file = null, ?string $seedFile = null)
    {
        $this->pdo = $pdo;
        $this->file = $file ?? dirname(__DIR__, 2) . '/storage/blog.json';
        $this->seedFile = $seedFile ?? dirname(__DIR__, 2) . '/config/blog.php';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(bool $publishedOnly = false, bool $raw = false): array
    {
        if ($this->pdo) {
            $out = [];
            $rows = $this->pdo->query('SELECT id, slug, title, excerpt, body, cover, author, published, created_at, i18n FROM posts ORDER BY created_at DESC, id DESC');
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
            $out = array_values(array_filter($out, static fn ($p) => (int) ($p['published'] ?? 0) === 1));
        }
        if (!$raw) {
            $out = array_map(static fn ($r) => \App\Core\Lang::overlay($r, self::I18N_FIELDS), $out);
        }
        return $out;
    }

    public function find(int $id, bool $raw = false): ?array
    {
        $row = null;
        if ($this->pdo) {
            $stmt = $this->pdo->prepare('SELECT id, slug, title, excerpt, body, cover, author, published, created_at, i18n FROM posts WHERE id = ?');
            $stmt->execute([$id]);
            $r = $stmt->fetch();
            $row = $r ? self::mapRow($r) : null;
        } else {
            foreach ($this->all(false, true) as $p) {
                if ((int) $p['id'] === $id) {
                    $row = $p;
                    break;
                }
            }
        }
        return ($row !== null && !$raw) ? \App\Core\Lang::overlay($row, self::I18N_FIELDS) : $row;
    }

    public function findBySlug(string $slug, bool $publishedOnly = false, bool $raw = false): ?array
    {
        foreach ($this->all($publishedOnly, $raw) as $p) {
            if ((string) $p['slug'] === $slug) {
                return $p;
            }
        }
        return null;
    }

    public function save(array $post): int
    {
        $id = (int) ($post['id'] ?? 0);
        $title = trim((string) ($post['title'] ?? ''));
        $base = self::slugify(trim((string) ($post['slug'] ?? '')) !== '' ? (string) $post['slug'] : $title);
        $slug = $this->uniqueSlug($base, $id > 0 ? $id : null);

        $i18n = \App\Core\Lang::cleanI18n($post['i18n'] ?? null, self::I18N_FIELDS);
        $row = [
            'slug' => $slug,
            'title' => $title,
            'excerpt' => trim((string) ($post['excerpt'] ?? '')),
            'body' => (string) ($post['body'] ?? ''),
            'cover' => (string) ($post['cover'] ?? ''),
            'author' => trim((string) ($post['author'] ?? '')),
            'published' => (int) ($post['published'] ?? 0) === 1 ? 1 : 0,
            'i18n' => $i18n,
        ];

        if ($this->pdo) {
            $i18nJson = $i18n === [] ? null : json_encode($i18n, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($id > 0) {
                $this->pdo->prepare('UPDATE posts SET slug=?, title=?, excerpt=?, body=?, cover=?, author=?, published=?, i18n=? WHERE id=?')
                    ->execute([$row['slug'], $row['title'], $row['excerpt'], $row['body'], $row['cover'], $row['author'], $row['published'], $i18nJson, $id]);
                return $id;
            }
            $this->pdo->prepare('INSERT INTO posts (slug, title, excerpt, body, cover, author, published, created_at, i18n) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)')
                ->execute([$row['slug'], $row['title'], $row['excerpt'], $row['body'], $row['cover'], $row['author'], $row['published'], date('c'), $i18nJson]);
            return (int) $this->pdo->lastInsertId();
        }
        return $this->fileSave($id, $row);
    }

    public function delete(int $id): void
    {
        if ($this->pdo) {
            $this->pdo->prepare('DELETE FROM posts WHERE id = ?')->execute([$id]);
            return;
        }
        $posts = array_values(array_filter($this->all(), static fn ($p) => (int) $p['id'] !== $id));
        $this->persist($posts);
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
        return $text !== '' ? $text : 'bejegyzes';
    }

    /** Ütközésmentes slug: ha foglalt, -2, -3, … utótaggal. */
    private function uniqueSlug(string $base, ?int $excludeId): string
    {
        $taken = [];
        foreach ($this->all() as $p) {
            if ($excludeId !== null && (int) $p['id'] === $excludeId) {
                continue;
            }
            $taken[(string) $p['slug']] = true;
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
        $posts = $this->all();
        if ($id <= 0) {
            $id = $this->nextId($posts);
            $posts[] = array_merge(['id' => $id, 'created' => date('c')], $row);
        } else {
            foreach ($posts as &$existing) {
                if ((int) $existing['id'] === $id) {
                    $existing = array_merge($existing, $row, ['id' => $id]);
                    break;
                }
            }
            unset($existing);
        }
        $this->persist($posts);
        return $id;
    }

    /** Legfrissebb elöl (created szerint, holtversenyben id szerint). */
    private static function sort(array $posts): array
    {
        usort($posts, static function ($a, $b) {
            $ca = (string) ($a['created'] ?? '');
            $cb = (string) ($b['created'] ?? '');
            return $ca === $cb ? ((int) ($b['id'] ?? 0) <=> (int) ($a['id'] ?? 0)) : strcmp($cb, $ca);
        });
        return $posts;
    }

    private static function mapRow(array $r): array
    {
        return [
            'id' => (int) $r['id'],
            'slug' => (string) $r['slug'],
            'title' => (string) $r['title'],
            'excerpt' => (string) ($r['excerpt'] ?? ''),
            'body' => (string) ($r['body'] ?? ''),
            'cover' => (string) ($r['cover'] ?? ''),
            'author' => (string) ($r['author'] ?? ''),
            'published' => (int) ($r['published'] ?? 0),
            'created' => (string) ($r['created_at'] ?? ''),
            'i18n' => \App\Core\Lang::decodeI18n($r['i18n'] ?? null),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function seed(): array
    {
        $rows = is_file($this->seedFile) ? (array) require $this->seedFile : [];
        $out = [];
        $i = 0;
        // A kezdő-bejegyzések régebbi keltezést kapnak (sorrendben), hogy a
        // dátum-szerinti rendezés az első elemet hozza előre.
        $base = strtotime('2026-05-01 09:00:00') ?: time();
        foreach ($rows as $p) {
            $i++;
            $created = date('c', $base + $i * 86400);
            $title = (string) ($p['title'] ?? '');
            $out[] = [
                'id' => $i,
                'slug' => self::slugify((string) ($p['slug'] ?? '') !== '' ? (string) $p['slug'] : $title),
                'title' => $title,
                'excerpt' => (string) ($p['excerpt'] ?? ''),
                'body' => (string) ($p['body'] ?? ''),
                'cover' => (string) ($p['cover'] ?? ''),
                'author' => (string) ($p['author'] ?? ''),
                'published' => (int) ($p['published'] ?? 1) === 0 ? 0 : 1,
                'created' => $created,
            ];
        }
        return $out;
    }

    private function persist(array $posts): void
    {
        $dir = dirname($this->file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        file_put_contents($this->file, json_encode(array_values($posts), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function nextId(array $posts): int
    {
        $max = 0;
        foreach ($posts as $p) {
            $max = max($max, (int) $p['id']);
        }
        return $max + 1;
    }
}
