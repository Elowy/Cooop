<?php

namespace App\Models;

use App\Core\Database;

/**
 * Felhasználó modell. Adatbázist igényel – DB nélkül nem működik a
 * regisztráció / belépés.
 */
final class User
{
    /** @return array<string, mixed>|null */
    public static function findByEmail(string $email): ?array
    {
        $pdo = Database::getConnection();
        if ($pdo === null) {
            return null;
        }
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        return $stmt->fetch() ?: null;
    }

    /** @return array<string, mixed>|null */
    public static function find(int $id): ?array
    {
        $pdo = Database::getConnection();
        if ($pdo === null) {
            return null;
        }
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function count(): int
    {
        $pdo = Database::getConnection();
        if ($pdo === null) {
            return 0;
        }
        return (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    /**
     * Új felhasználó létrehozása. A jelszót már hash-elve várja.
     * Visszaadja az új azonosítót, vagy 0-t hiba esetén.
     */
    public static function create(string $name, string $email, string $passwordHash, string $role = 'user'): int
    {
        $pdo = Database::getConnection();
        if ($pdo === null) {
            return 0;
        }
        $stmt = $pdo->prepare(
            'INSERT INTO users (name, email, password, role) VALUES (:name, :email, :password, :role)'
        );
        $stmt->execute([
            'name'     => $name,
            'email'    => $email,
            'password' => $passwordHash,
            'role'     => $role,
        ]);
        return (int) $pdo->lastInsertId();
    }
}
