<?php

namespace App\Core;

/**
 * Egyszerű CSRF védelem a POST űrlapokhoz (kosár műveletek).
 */
final class Csrf
{
    private const KEY = '_csrf';

    public static function token(): string
    {
        if (empty($_SESSION[self::KEY])) {
            $_SESSION[self::KEY] = bin2hex(random_bytes(16));
        }
        return $_SESSION[self::KEY];
    }

    public static function check(?string $token): bool
    {
        return is_string($token) && $token !== ''
            && hash_equals($_SESSION[self::KEY] ?? '', $token);
    }

    /** Rejtett mezőt ad vissza az űrlapokba. */
    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . self::token() . '">';
    }
}
