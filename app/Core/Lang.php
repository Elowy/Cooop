<?php

namespace App\Core {
    /**
     * Pehelysúlyú fordító-réteg. A magyar (hu) az alap és egyben a fallback;
     * a hiányzó kulcsok magyarul (vagy magával a kulccsal) jelennek meg, így a
     * fokozatos fordítás sosem töri el az oldalt.
     *
     * Használat a sablonokban: <?= t('nav.shop') ?> vagy t('x', ['n' => 5]).
     */
    final class Lang
    {
        /** Elérhető nyelvek: kód => natív megnevezés (a nyelvváltóhoz). */
        public const AVAILABLE = ['hu' => 'Magyar', 'en' => 'English', 'de' => 'Deutsch'];

        private static string $locale = 'hu';
        /** @var array<string, string> */
        private static array $strings = [];
        /** @var array<string, string> */
        private static array $fallback = [];

        public static function init(string $locale, string $dir): void
        {
            self::$locale = self::normalize($locale);
            $base = rtrim($dir, '/');
            self::$fallback = is_file($base . '/hu.php') ? (array) require $base . '/hu.php' : [];
            self::$strings = (self::$locale !== 'hu' && is_file($base . '/' . self::$locale . '.php'))
                ? (array) require $base . '/' . self::$locale . '.php'
                : self::$fallback;
        }

        /** Bemenetből (cookie, URL, Accept-Language) érvényes 2 betűs kód, különben 'hu'. */
        public static function normalize(string $locale): string
        {
            $code = strtolower(substr(trim($locale), 0, 2));
            return isset(self::AVAILABLE[$code]) ? $code : 'hu';
        }

        public static function locale(): string
        {
            return self::$locale;
        }

        /** @return array<string, string> */
        public static function available(): array
        {
            return self::AVAILABLE;
        }

        /**
         * Fordítás kulcs alapján. A {placeholder}-eket a $replacements cseréli.
         *
         * @param array<string, string|int> $replacements
         */
        public static function t(string $key, array $replacements = []): string
        {
            $text = self::$strings[$key] ?? self::$fallback[$key] ?? $key;
            foreach ($replacements as $name => $value) {
                $text = str_replace('{' . $name . '}', (string) $value, $text);
            }
            return $text;
        }

        /**
         * Nyelv-specifikus tartalmi fájl útvonala (pl. bemutatkozas.en.md), ha
         * létezik; különben az eredeti (magyar) fájl. Így a tartalmi oldalak
         * fokozatosan lokalizálhatók egy-egy fájl elhelyezésével.
         */
        public static function file(string $dir, string $filename): string
        {
            $dir = rtrim($dir, '/') . '/';
            if (self::$locale !== 'hu') {
                $dot = strrpos($filename, '.');
                $localized = $dot !== false
                    ? substr($filename, 0, $dot) . '.' . self::$locale . substr($filename, $dot)
                    : $filename . '.' . self::$locale;
                if (is_file($dir . $localized)) {
                    return $dir . $localized;
                }
            }
            return $dir . $filename;
        }

        /* ---- Tartalom-lokalizáció (DB-rekordok nyelvenkénti mezői) ---- */

        /** Tárolt érték (JSON string vagy tömb) → fordítás-tömb. */
        public static function decodeI18n(mixed $value): array
        {
            if (is_array($value)) {
                return $value;
            }
            if (is_string($value) && $value !== '') {
                $decoded = json_decode($value, true);
                return is_array($decoded) ? $decoded : [];
            }
            return [];
        }

        /**
         * A raw rekord adott mezőit a látogató nyelvére fordítja; magyar vagy
         * hiányzó fordítás esetén a mező változatlan marad (fallback).
         *
         * @param array<string, mixed> $row  Tartalmaz egy 'i18n' kulcsot.
         * @param string[] $fields
         * @return array<string, mixed>
         */
        public static function overlay(array $row, array $fields): array
        {
            $loc = self::$locale;
            if ($loc === 'hu' || empty($row['i18n'][$loc]) || !is_array($row['i18n'][$loc])) {
                return $row;
            }
            foreach ($fields as $field) {
                $value = $row['i18n'][$loc][$field] ?? '';
                if (is_string($value) && trim($value) !== '') {
                    $row[$field] = $value;
                }
            }
            return $row;
        }

        /**
         * Admin mentéshez: csak az ismert, nem magyar nyelvek és a megadott
         * mezők, üres értékek nélkül.
         *
         * @param string[] $fields
         * @return array<string, array<string, string>>
         */
        public static function cleanI18n(mixed $input, array $fields): array
        {
            if (!is_array($input)) {
                return [];
            }
            $out = [];
            foreach (array_keys(self::AVAILABLE) as $code) {
                if ($code === 'hu' || empty($input[$code]) || !is_array($input[$code])) {
                    continue;
                }
                $vals = [];
                foreach ($fields as $field) {
                    $value = trim((string) ($input[$code][$field] ?? ''));
                    if ($value !== '') {
                        $vals[$field] = $value;
                    }
                }
                if ($vals !== []) {
                    $out[$code] = $vals;
                }
            }
            return $out;
        }
    }
}

namespace {
    /**
     * Globális rövidítés a sablonokhoz. A Lang autoloadolásakor jön létre, így a
     * Lang::init() (bootstrap) után minden nézetben elérhető.
     *
     * @param array<string, string|int> $replacements
     */
    function t(string $key, array $replacements = []): string
    {
        return \App\Core\Lang::t($key, $replacements);
    }
}
