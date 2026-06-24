<?php

namespace App\Core;

/**
 * Egyszerű natív PHP sablonmotor layout támogatással.
 */
final class View
{
    /**
     * @param array<string, mixed> $data
     */
    public static function render(string $template, array $data = [], string $layout = 'main'): string
    {
        $data['config'] = require dirname(__DIR__, 2) . '/config/config.php';

        $content = self::capture("Views/{$template}", $data);

        if ($layout === '') {
            return $content;
        }

        $data['content'] = $content;
        return self::capture("Views/layouts/{$layout}", $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    private static function capture(string $path, array $data): string
    {
        $file = dirname(__DIR__) . '/' . $path . '.php';
        if (!is_file($file)) {
            return "<!-- hiányzó sablon: {$path} -->";
        }

        extract($data, EXTR_SKIP);
        ob_start();
        include $file;
        return (string) ob_get_clean();
    }

    /** Biztonságos HTML kimenet. */
    public static function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** Forint formázás, pl. 12000 → "12 000 Ft". */
    public static function huf(int $amount): string
    {
        return number_format($amount, 0, ',', "\u{00A0}") . "\u{00A0}Ft";
    }

    /**
     * Statikus asset URL cache-busting verzióval (?v=fájl-mtime). Így a böngésző
     * sokáig cache-elheti a fájlt, de deploy/módosítás után azonnal újratölti.
     */
    public static function asset(string $path): string
    {
        $file = dirname(__DIR__, 2) . '/public' . $path;
        $version = is_file($file) ? (string) filemtime($file) : '';
        return $version !== '' ? $path . '?v=' . $version : $path;
    }
}
