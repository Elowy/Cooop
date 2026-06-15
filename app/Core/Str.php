<?php

namespace App\Core;

/**
 * Apró szöveg-segédfüggvények.
 */
final class Str
{
    /**
     * URL-barát „slug" készítése magyar ékezetek kezelésével.
     */
    public static function slug(string $text): string
    {
        $map = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ö' => 'o', 'ő' => 'o',
            'ú' => 'u', 'ü' => 'u', 'ű' => 'u',
            'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ö' => 'o', 'Ő' => 'o',
            'Ú' => 'u', 'Ü' => 'u', 'Ű' => 'u',
        ];
        $text = strtr($text, $map);
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^a-z0-9]+/u', '-', $text) ?? '';
        return trim($text, '-');
    }
}
