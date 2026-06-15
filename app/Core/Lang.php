<?php

namespace App\Core;

/**
 * Könnyűsúlyú többnyelvűség (HU / EN / DE).
 *
 * A nyelvet a ?lang query paraméter vagy a `lang` süti határozza meg,
 * alapértelmezés a magyar. A fordítások a config/lang.php fájlban élnek;
 * hiányzó kulcs esetén a magyar érték, végső soron maga a kulcs a fallback.
 */
final class Lang
{
    private const DEFAULT = 'hu';

    /** @var array<string, array<string, string>> */
    private static array $all = [];
    private static string $current = self::DEFAULT;

    public static function boot(): void
    {
        self::$all = require dirname(__DIR__, 2) . '/config/lang.php';

        $code = $_GET['lang'] ?? $_COOKIE['lang'] ?? self::DEFAULT;
        if (!is_string($code) || !isset(self::$all[$code])) {
            $code = self::DEFAULT;
        }
        self::$current = $code;

        // Választás megőrzése sütiben (csak ha kérték és még nincs kimenet).
        if (isset($_GET['lang']) && !headers_sent()) {
            setcookie('lang', $code, [
                'expires'  => time() + 60 * 60 * 24 * 365,
                'path'     => '/',
                'samesite' => 'Lax',
            ]);
        }
    }

    public static function code(): string
    {
        return self::$current;
    }

    /** @return array<string, string> nyelvkód => felirat */
    public static function available(): array
    {
        return ['hu' => 'HU', 'en' => 'EN', 'de' => 'DE'];
    }

    /** Fordítás kulcs alapján, magyar majd kulcs fallbackkel. */
    public static function t(string $key): string
    {
        return self::$all[self::$current][$key]
            ?? self::$all[self::DEFAULT][$key]
            ?? $key;
    }

    /** Az aktuális URL ugyanarra az oldalra, megadott nyelvre állítva. */
    public static function switchUrl(string $code): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        parse_str((string) parse_url($uri, PHP_URL_QUERY), $params);
        $params['lang'] = $code;
        return $path . '?' . http_build_query($params);
    }
}
