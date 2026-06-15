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
            ['id' => 1, 'slug' => 'klasszikus', 'name' => 'Klasszikus', 'icon' => 'feeder'],
            ['id' => 2, 'slug' => 'modern', 'name' => 'Modern', 'icon' => 'feeder'],
            ['id' => 3, 'slug' => 'nagy', 'name' => 'Nagy méretű', 'icon' => 'feeder'],
            ['id' => 4, 'slug' => 'fuggesztheto', 'name' => 'Függeszthető', 'icon' => 'feeder'],
        ];
    }
}
