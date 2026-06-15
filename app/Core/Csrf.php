<?php

namespace App\Core;

/**
 * Egyszerű CSRF token kezelés az admin űrlapokhoz.
 */
final class Csrf
{
    private const KEY = 'csrf_token';

    public static function token(): string
    {
        if (empty($_SESSION[self::KEY])) {
            $_SESSION[self::KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::KEY];
    }

    public static function check(?string $token): bool
    {
        return is_string($token)
            && $token !== ''
            && !empty($_SESSION[self::KEY])
            && hash_equals($_SESSION[self::KEY], $token);
    }

    /** Rejtett input mező az űrlapokba. */
    public static function field(): string
    {
        return '<input type="hidden" name="_token" value="'
            . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}
