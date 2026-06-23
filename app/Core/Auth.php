<?php

namespace App\Core;

/**
 * Belépés. Telepítés után adatbázis-alapú felhasználókkal (e-mail + jelszó,
 * szerepkörrel); telepítés előtt a config szerinti egyetlen admin-jelszóval.
 */
final class Auth
{
    private const USER = '_user';     // DB felhasználó a munkamenetben
    private const LEGACY = '_admin';  // telepítés előtti egyszerű admin

    /** Telepítés előtti egyszerű jelszavas belépés. */
    public static function attemptLegacy(string $password, string $expected): bool
    {
        $ok = $expected !== '' && hash_equals($expected, $password);
        if ($ok) {
            session_regenerate_id(true);
            $_SESSION[self::LEGACY] = true;
        }
        return $ok;
    }

    /** DB felhasználó beléptetése. */
    public static function loginUser(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION[self::USER] = [
            'id' => (int) $user['id'],
            'name' => (string) $user['name'],
            'email' => (string) $user['email'],
            'role' => (string) $user['role'],
        ];
    }

    public static function user(): ?array
    {
        return $_SESSION[self::USER] ?? null;
    }

    public static function role(): ?string
    {
        if (isset($_SESSION[self::USER])) {
            return $_SESSION[self::USER]['role'];
        }
        return !empty($_SESSION[self::LEGACY]) ? 'admin' : null;
    }

    public static function check(): bool
    {
        return isset($_SESSION[self::USER]) || !empty($_SESSION[self::LEGACY]);
    }

    /** Vezérlőpult-hozzáférés: admin vagy szerkesztő. */
    public static function isStaff(): bool
    {
        $role = self::role();
        return $role === 'admin' || $role === 'editor';
    }

    public static function isAdmin(): bool
    {
        return self::role() === 'admin';
    }

    public static function logout(): void
    {
        unset($_SESSION[self::USER], $_SESSION[self::LEGACY]);
    }
}
