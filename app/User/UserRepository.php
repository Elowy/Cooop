<?php

namespace App\User;

use PDO;

/**
 * Felhasználók (users tábla) – lekérdezés, létrehozás, módosítás, törlés.
 */
final class UserRepository
{
    /** @var array<string, string> rang => megjelenített név */
    public const ROLES = ['admin' => 'Admin', 'editor' => 'Szerkesztő', 'customer' => 'Vásárló'];

    public function __construct(private PDO $pdo)
    {
    }

    public function count(): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) AS c FROM users')->fetch()['c'];
    }

    /** @return array<int, array<string, mixed>> */
    public function all(): array
    {
        return $this->pdo->query('SELECT id, name, email, role, created_at FROM users ORDER BY id')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public function create(string $name, string $email, string $passwordHash, string $role): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (name, email, password, role, created_at) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$name, $email, $passwordHash, $role, date('Y-m-d H:i:s')]);
        return (int) $this->pdo->lastInsertId();
    }

    /** @param array<string, mixed> $fields name|email|role|password */
    public function update(int $id, array $fields): void
    {
        $cols = [];
        $vals = [];
        foreach (['name', 'email', 'role', 'password'] as $key) {
            if (array_key_exists($key, $fields)) {
                $cols[] = "$key = ?";
                $vals[] = $fields[$key];
            }
        }
        if (!$cols) {
            return;
        }
        $vals[] = $id;
        $this->pdo->prepare('UPDATE users SET ' . implode(', ', $cols) . ' WHERE id = ?')->execute($vals);
    }

    public function delete(int $id): void
    {
        $this->pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    }

    public static function roleLabel(string $role): string
    {
        return self::ROLES[$role] ?? $role;
    }
}
