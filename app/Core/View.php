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

    /** ISO dátum magyar formában, pl. "2026-05-05" → "2026. május 5." */
    public static function dateHu(string $iso): string
    {
        $ts = strtotime($iso);
        if ($ts === false) {
            return '';
        }
        $months = [
            1 => 'január', 2 => 'február', 3 => 'március', 4 => 'április',
            5 => 'május', 6 => 'június', 7 => 'július', 8 => 'augusztus',
            9 => 'szeptember', 10 => 'október', 11 => 'november', 12 => 'december',
        ];
        return date('Y', $ts) . '. ' . $months[(int) date('n', $ts)] . ' ' . date('j', $ts) . '.';
    }

    /** ISO dátum a látogató nyelvén (HU: 2026. május 5. · EN: 5 May 2026 · DE: 5. Mai 2026). */
    public static function date(string $iso): string
    {
        $ts = strtotime($iso);
        if ($ts === false) {
            return '';
        }
        $months = [
            'hu' => [1 => 'január', 'február', 'március', 'április', 'május', 'június', 'július', 'augusztus', 'szeptember', 'október', 'november', 'december'],
            'en' => [1 => 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
            'de' => [1 => 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'],
        ];
        $loc = \App\Core\Lang::locale();
        $name = $months[$loc][(int) date('n', $ts)] ?? $months['hu'][(int) date('n', $ts)];
        $y = date('Y', $ts);
        $d = (int) date('j', $ts);
        return match ($loc) {
            'en' => "{$d} {$name} {$y}",
            'de' => "{$d}. {$name} {$y}",
            default => "{$y}. {$name} {$d}.",
        };
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
