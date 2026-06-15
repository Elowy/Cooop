<?php

namespace App\Core;

use RuntimeException;

/**
 * Termékkép-feltöltés kezelése a public/assets/img/products/ mappába.
 */
final class Upload
{
    private const MAX_BYTES = 2 * 1024 * 1024; // 2 MB

    /** @var array<string, string> kiterjesztés => elfogadott MIME */
    private const ALLOWED = [
        'svg'  => 'image/svg+xml',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'gif'  => 'image/gif',
    ];

    /**
     * Feltöltött kép feldolgozása.
     *
     * @param array<string, mixed>|null $file  a $_FILES megfelelő eleme
     * @param string|null $current  meglévő képfájl neve (megtartás, ha nincs új)
     * @return string a használandó képfájl neve
     */
    public static function image(?array $file, ?string $current = null): string
    {
        // Nincs új fájl: marad a jelenlegi (vagy a placeholder).
        if ($file === null || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return $current ?: 'placeholder.svg';
        }

        $error = (int) $file['error'];
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('A fájl feltöltése sikertelen (hibakód: ' . $error . ').');
        }

        if ((int) $file['size'] > self::MAX_BYTES) {
            throw new RuntimeException('A kép túl nagy (legfeljebb 2 MB engedélyezett).');
        }

        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        if (!isset(self::ALLOWED[$ext])) {
            throw new RuntimeException('Nem támogatott képformátum: .' . $ext);
        }

        // MIME ellenőrzés (SVG-nél a finfo gyakran text/* vagy xml, ezt elnézzük).
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file((string) $file['tmp_name']);
        $mimeOk = $mime === self::ALLOWED[$ext]
            || ($ext === 'svg' && in_array($mime, ['image/svg+xml', 'text/plain', 'text/xml', 'application/xml'], true));
        if (!$mimeOk) {
            throw new RuntimeException('A fájl tartalma nem egyezik a kiterjesztéssel.');
        }

        $base = Str::slug(pathinfo((string) $file['name'], PATHINFO_FILENAME)) ?: 'kep';
        $name = $base . '-' . substr(bin2hex(random_bytes(4)), 0, 8) . '.' . $ext;
        $dir = dirname(__DIR__, 2) . '/public/assets/img/products';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        if (!move_uploaded_file((string) $file['tmp_name'], $dir . '/' . $name)) {
            throw new RuntimeException('A feltöltött fájl mentése nem sikerült.');
        }

        return $name;
    }
}
