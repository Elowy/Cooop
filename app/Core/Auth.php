<?php

namespace App\Core;

use App\Models\User;

/**
 * Munkamenet alapú autentikáció.
 */
final class Auth
{
    private const KEY = 'user_id';

    /** @var array<string, mixed>|null|false belső gyorsítótár */
    private static array|null|false $cache = false;

    public static function attempt(string $email, string $password): bool
    {
        $user = User::findByEmail($email);
        if ($user !== null && password_verify($password, (string) $user['password'])) {
            self::login($user);
            return true;
        }
        return false;
    }

    /** @param array<string, mixed> $user */
    public static function login(array $user): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $_SESSION[self::KEY] = (int) $user['id'];
        self::$cache = $user;
    }

    public static function logout(): void
    {
        unset($_SESSION[self::KEY]);
        self::$cache = false;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): ?int
    {
        $id = $_SESSION[self::KEY] ?? null;
        return $id ? (int) $id : null;
    }

    /** @return array<string, mixed>|null */
    public static function user(): ?array
    {
        if (self::$cache !== false) {
            return self::$cache ?: null;
        }

        $id = self::id();
        self::$cache = $id ? (User::find($id) ?? null) : null;
        return self::$cache ?: null;
    }

    public static function isAdmin(): bool
    {
        $user = self::user();
        return $user !== null && ($user['role'] ?? 'user') === 'admin';
    }
}
