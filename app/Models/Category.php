<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Str;

final class Category
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        $pdo = Database::getConnection();

        if ($pdo === null) {
            return self::demo();
        }

        $stmt = $pdo->query('SELECT * FROM categories ORDER BY name ASC');
        return $stmt ? $stmt->fetchAll() : self::demo();
    }

    /**
     * Kategóriák a hozzájuk tartozó terméksszámmal (admin listához).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function withCounts(): array
    {
        $pdo = Database::getConnection();
        if ($pdo === null) {
            return self::demo();
        }
        $stmt = $pdo->query(
            'SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS product_count
             FROM categories c ORDER BY c.name ASC'
        );
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        $pdo = Database::getConnection();
        if ($pdo === null) {
            return null;
        }
        $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Új kategória létrehozása. Visszaadja az új azonosítót, vagy false-t.
     *
     * @param array<string, mixed> $data
     */
    public static function create(array $data): int|false
    {
        $pdo = Database::getConnection();
        if ($pdo === null) {
            return false;
        }
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            return false;
        }
        $slug = self::uniqueSlug((string) ($data['slug'] ?? '') ?: $name);
        $stmt = $pdo->prepare(
            'INSERT INTO categories (slug, name, icon) VALUES (:slug, :name, :icon)'
        );
        $stmt->execute([
            'slug' => $slug,
            'name' => $name,
            'icon' => trim((string) ($data['icon'] ?? 'feeder')) ?: 'feeder',
        ]);
        return (int) $pdo->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = Database::getConnection();
        if ($pdo === null) {
            return false;
        }
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            return false;
        }
        $slug = self::uniqueSlug((string) ($data['slug'] ?? '') ?: $name, $id);
        $stmt = $pdo->prepare(
            'UPDATE categories SET slug = :slug, name = :name, icon = :icon WHERE id = :id'
        );
        return $stmt->execute([
            'slug' => $slug,
            'name' => $name,
            'icon' => trim((string) ($data['icon'] ?? 'feeder')) ?: 'feeder',
            'id'   => $id,
        ]);
    }

    public static function delete(int $id): bool
    {
        $pdo = Database::getConnection();
        if ($pdo === null) {
            return false;
        }
        $stmt = $pdo->prepare('DELETE FROM categories WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Egyedi slug előállítása (ütközés esetén -2, -3, … toldalékkal).
     */
    private static function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $pdo = Database::getConnection();
        $slug = Str::slug($base) ?: 'kategoria';
        if ($pdo === null) {
            return $slug;
        }
        $candidate = $slug;
        $i = 2;
        while (true) {
            $sql = 'SELECT COUNT(*) FROM categories WHERE slug = :slug';
            $params = ['slug' => $candidate];
            if ($ignoreId !== null) {
                $sql .= ' AND id <> :id';
                $params['id'] = $ignoreId;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            if ((int) $stmt->fetchColumn() === 0) {
                return $candidate;
            }
            $candidate = $slug . '-' . $i++;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function demo(): array
    {
        return [
            ['id' => 1, 'slug' => 'klasszikus', 'name' => 'Klasszikus', 'icon' => 'feeder'],
            ['id' => 2, 'slug' => 'modern', 'name' => 'Modern', 'icon' => 'feeder'],
            ['id' => 3, 'slug' => 'nagy', 'name' => 'Nagy méretű', 'icon' => 'feeder'],
            ['id' => 4, 'slug' => 'fuggesztheto', 'name' => 'Függeszthető', 'icon' => 'feeder'],
        ];
    }
}
