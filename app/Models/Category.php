<?php

namespace App\Models;

use App\Core\Database;

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

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        $pdo = Database::getConnection();
        if ($pdo === null) {
            foreach (self::demo() as $c) {
                if ((int) $c['id'] === $id) {
                    return $c;
                }
            }
            return null;
        }
        $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Új kategória létrehozása (ha még nincs ilyen slug). Visszaadja az azonosítót.
     */
    public static function create(string $name, string $slug, string $icon = ''): int
    {
        $pdo = Database::getConnection();
        if ($pdo === null) {
            return 0;
        }
        $stmt = $pdo->prepare('INSERT INTO categories (slug, name, icon) VALUES (:slug, :name, :icon)');
        $stmt->execute(['slug' => $slug, 'name' => $name, 'icon' => $icon ?: null]);
        return (int) $pdo->lastInsertId();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function demo(): array
    {
        return [
            ['id' => 1, 'slug' => 'raklapok', 'name' => 'Raklapok', 'icon' => 'pallet'],
            ['id' => 2, 'slug' => 'ladak-csomagolas', 'name' => 'Ládák és csomagolás', 'icon' => 'crate'],
            ['id' => 3, 'slug' => 'fureszaru', 'name' => 'Fűrészáru', 'icon' => 'lumber'],
            ['id' => 4, 'slug' => 'tuzifa', 'name' => 'Tűzifa', 'icon' => 'firewood'],
            ['id' => 5, 'slug' => 'teglak', 'name' => 'BRITTERM téglák', 'icon' => 'brick'],
        ];
    }
}
