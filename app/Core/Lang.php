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
