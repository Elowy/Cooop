<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Kategória-alapú süti-hozzájárulás kezelése.
 *
 * A hozzájárulás az `nt_consent` sütiben tárolódik, a választott kategóriák
 * kötőjellel összefűzött listájaként (a "necessary" mindig jelen van), pl.:
 *   "necessary"                       → csak a szükséges sütik
 *   "necessary-analytics"             → + forgalommérés
 *   "necessary-analytics-marketing"   → minden
 *
 * Visszafelé kompatibilis a korábbi bináris formátummal ("all" / "necessary").
 */
final class Consent
{
    /** Az opcionális (kapcsolható) kategóriák kulcsai. */
    public const OPTIONAL = ['analytics', 'marketing'];

    /**
     * @return array{set: bool, necessary: bool, analytics: bool, marketing: bool}
     */
    public static function parse(?string $raw): array
    {
        $raw = trim((string) $raw);
        $state = ['set' => $raw !== '', 'necessary' => true, 'analytics' => false, 'marketing' => false];

        if ($raw === '') {
            return $state;
        }

        // Örökölt formátum: minden elfogadva.
        if ($raw === 'all') {
            $state['analytics'] = true;
            $state['marketing'] = true;

            return $state;
        }

        $tokens = array_map('trim', explode('-', $raw));
        foreach (self::OPTIONAL as $cat) {
            if (in_array($cat, $tokens, true)) {
                $state[$cat] = true;
            }
        }

        return $state;
    }

    /**
     * A sütibe írandó érték a kiválasztott opcionális kategóriákból.
     */
    public static function encode(bool $analytics, bool $marketing): string
    {
        $tokens = ['necessary'];
        if ($analytics) {
            $tokens[] = 'analytics';
        }
        if ($marketing) {
            $tokens[] = 'marketing';
        }

        return implode('-', $tokens);
    }
}
