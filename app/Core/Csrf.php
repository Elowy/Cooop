<?php

namespace App\Core;

/**
 * Egyszerű CSRF védelem munkamenet alapú tokennel.
 */
final class Csrf
{
    private const KEY = '_csrf';

    public static function token(): string
    {
        if (empty($_SESSION[self::KEY])) {
            $_SESSION[self::KEY] = bin2hex(random_bytes(32));
        }
        return (string) $_SESSION[self::KEY];
    }

    public static function check(?string $token): bool
    {
        return is_string($token) && $token !== ''
            && hash_equals($_SESSION[self::KEY] ?? '', $token);
    }

    /** Kész rejtett mező űrlapokhoz. */
    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . View::e(self::token()) . '">';
    }
}
