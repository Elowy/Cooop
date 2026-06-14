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
            ['id' => 1, 'slug' => 'routerek', 'name' => 'Routerek', 'icon' => 'router'],
            ['id' => 2, 'slug' => 'switchek', 'name' => 'Switchek', 'icon' => 'switch'],
            ['id' => 3, 'slug' => 'kabelek', 'name' => 'Kábelek', 'icon' => 'cable'],
            ['id' => 4, 'slug' => 'kamerak', 'name' => 'Kamerák', 'icon' => 'camera'],
            ['id' => 5, 'slug' => 'tarolok', 'name' => 'Tárolók', 'icon' => 'nas'],
        ];
    }
}
