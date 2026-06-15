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
