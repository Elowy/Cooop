<?php

namespace App\Catalog;

use App\Core\Lang;
use PDO;

/**
 * Termékenkénti fordítások (cikkszám szerint). A termékek maguk az Axelből /
 * configból jönnek; ez csak a hozzájuk tartozó EN/DE szövegeket tárolja:
 * { "EP-TGL-30": { "en": {"name":"…","short":"…"}, "de": {…} } }.
 *
 * Telepítés után adatbázis (product_i18n tábla), előtte JSON fájl.
 */
final class ProductI18nStore
{
    /** Nyelvenként fordítható termékmezők. */
    private const FIELDS = ['name', 'short'];

    private ?PDO $pdo;
    private string $file;

    public function __construct(?PDO $pdo = null, ?string $file = null)
    {
        $this->pdo = $pdo;
        $this->file = $file ?? dirname(__DIR__, 2) . '/storage/product_i18n.json';
    }

    /**
     * Egy SKU fordításai ({"en":{...},"de":{...}}), vagy üres tömb.
     *
     * @return array<string, array<string, string>>
     */
    public function find(string $sku): array
    {
        return $this->all()[$sku] ?? [];
    }

    public function save(string $sku, mixed $i18n): void
    {
        $clean = Lang::cleanI18n($i18n, self::FIELDS);
        if ($this->pdo) {
            $this->pdo->prepare('DELETE FROM product_i18n WHERE sku = ?')->execute([$sku]);
            if ($clean !== []) {
                $this->pdo->prepare('INSERT INTO product_i18n (sku, data) VALUES (?, ?)')
                    ->execute([$sku, json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
            }
            return;
        }
        $all = $this->fileAll();
        if ($clean === []) {
            unset($all[$sku]);
        } else {
            $all[$sku] = $clean;
        }
        $this->persist($all);
    }

    /**
     * Minden fordítás SKU szerint.
     *
     * @return array<string, array<string, array<string, string>>>
     */
    public function all(): array
    {
        if ($this->pdo) {
            $out = [];
            foreach ($this->pdo->query('SELECT sku, data FROM product_i18n') as $r) {
                $decoded = json_decode((string) $r['data'], true);
                $out[(string) $r['sku']] = is_array($decoded) ? $decoded : [];
            }
            return $out;
        }
        return $this->fileAll();
    }

    /** @return array<string, array<string, array<string, string>>> */
    private function fileAll(): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($this->file), true);
        return is_array($data) ? $data : [];
    }

    private function persist(array $all): void
    {
        $dir = dirname($this->file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        file_put_contents($this->file, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
