<?php

namespace App\Core;

/**
 * Egyszerű, munkamenet-alapú belépés a vezérlőpulthoz. Egyetlen, konfigurált
 * jelszót használ (config/config.php → admin.password, élesben ADMIN_PASSWORD).
 */
final class Auth
{
    private const KEY = '_admin';

    public static function attempt(string $password, string $expected): bool
    {
        $ok = $expected !== '' && hash_equals($expected, $password);
        if ($ok) {
            session_regenerate_id(true);
            $_SESSION[self::KEY] = true;
        }
        return $ok;
    }

    public static function check(): bool
    {
        return !empty($_SESSION[self::KEY]);
    }

    public static function logout(): void
    {
        unset($_SESSION[self::KEY]);
    }
}
