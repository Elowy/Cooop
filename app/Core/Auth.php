<?php

namespace App\Core;

/**
 * Minimalista, munkamenet alapú admin hitelesítés.
 */
final class Auth
{
    private const KEY = 'admin_authenticated';

    public static function check(): bool
    {
        return !empty($_SESSION[self::KEY]);
    }

    /**
     * Belépési kísérlet a konfigurált admin adatok alapján.
     *
     * @param array<string, mixed> $config
     */
    public static function attempt(string $user, string $password, array $config): bool
    {
        $admin = $config['admin'] ?? [];
        $expectedUser = (string) ($admin['user'] ?? '');
        $hash = (string) ($admin['password_hash'] ?? '');

        $userOk = $expectedUser !== '' && hash_equals($expectedUser, $user);
        $passOk = $hash !== ''
            ? password_verify($password, $hash)
            : hash_equals((string) ($admin['password'] ?? ''), $password);

        if ($userOk && $passOk) {
            session_regenerate_id(true);
            $_SESSION[self::KEY] = true;
            return true;
        }

        return false;
    }

    public static function logout(): void
    {
        unset($_SESSION[self::KEY]);
    }
}
