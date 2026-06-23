<?php

namespace App\Core;

/**
 * Minimalista Markdown → HTML (címsorok, félkövér, listák, vízszintes vonal,
 * linkek, bekezdések) a jogi dokumentumok megjelenítéséhez. A # szintek
 * eggyel lejjebb tolódnak, hogy az oldal saját <h1>-e alatt legyenek.
 */
final class Markdown
{
    public static function toHtml(string $md): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $md) ?: [];
        $html = '';
        $para = [];
        $inList = false;

        $flushPara = static function () use (&$para, &$html): void {
            if ($para) {
                $html .= '<p>' . implode(' ', $para) . "</p>\n";
                $para = [];
            }
        };
        $closeList = static function () use (&$inList, &$html): void {
            if ($inList) {
                $html .= "</ul>\n";
                $inList = false;
            }
        };

        foreach ($lines as $raw) {
            $trim = trim($raw);
            if ($trim === '') {
                $flushPara();
                $closeList();
                continue;
            }
            if ($trim === '---' || $trim === '***') {
                $flushPara();
                $closeList();
                $html .= "<hr>\n";
                continue;
            }
            if (preg_match('/^(#{1,6})\s+(.*)$/', $trim, $m)) {
                $flushPara();
                $closeList();
                $level = min(6, strlen($m[1]) + 1);
                $html .= "<h{$level}>" . self::inline($m[2]) . "</h{$level}>\n";
                continue;
            }
            if (preg_match('/^[-*]\s+(.*)$/', $trim, $m)) {
                $flushPara();
                if (!$inList) {
                    $html .= "<ul>\n";
                    $inList = true;
                }
                $html .= '<li>' . self::inline($m[1]) . "</li>\n";
                continue;
            }
            $closeList();
            $para[] = self::inline($trim);
        }
        $flushPara();
        $closeList();
        return $html;
    }

    private static function inline(string $text): string
    {
        $text = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace_callback('#(https?://[^\s<]+)#', static function ($m) {
            $url = rtrim($m[1], '.,;');
            return '<a href="' . $url . '" target="_blank" rel="noopener">' . $url . '</a>';
        }, $text);
        return (string) $text;
    }
}
