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
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $data['config'] = $config;

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
            return "<!-- Hiányzó sablon: {$path} -->";
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

    /** Ár formázása. */
    public static function price(float|int $amount, string $currency = 'Ft'): string
    {
        return number_format((float) $amount, 0, ',', ' ') . ' ' . $currency;
    }
}
