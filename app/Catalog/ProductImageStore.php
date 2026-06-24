<?php

namespace App\Catalog;

use PDO;

/**
 * Termékenkénti képgaléria (cikkszám szerint). Telepítés után adatbázis
 * (product_images tábla, a fájlnevek JSON-listája az images oszlopban),
 * előtte JSON fájl. A termékek maguk az Axelből/configból jönnek; ez csak a
 * hozzájuk feltöltött képek fájlneveit tárolja, sorrendben (az első az elsődleges).
 */
final class ProductImageStore
{
    private ?PDO $pdo;
    private string $file;

    public function __construct(?PDO $pdo = null, ?string $file = null)
    {
        $this->pdo = $pdo;
        $this->file = $file ?? dirname(__DIR__, 2) . '/storage/product_images.json';
    }

    /** @return string[] fájlnevek sorrendben (az első az elsődleges/borítókép) */
    public function find(string $sku): array
    {
        if ($this->pdo) {
            $stmt = $this->pdo->prepare('SELECT images FROM product_images WHERE sku = ?');
            $stmt->execute([$sku]);
            $row = $stmt->fetch();
            return self::clean($row ? json_decode((string) $row['images'], true) : []);
        }
        return self::clean($this->fileAll()[$sku] ?? []);
    }

    /** @return array<string, string[]> sku => fájlnevek */
    public function all(): array
    {
        if ($this->pdo) {
            $out = [];
            foreach ($this->pdo->query('SELECT sku, images FROM product_images') as $r) {
                $out[(string) $r['sku']] = self::clean(json_decode((string) $r['images'], true));
            }
            return $out;
        }
        return array_map([self::class, 'clean'], $this->fileAll());
    }

    /** @param string[] $images */
    public function save(string $sku, array $images): void
    {
        $images = self::clean($images);
        if ($this->pdo) {
            $this->pdo->prepare('DELETE FROM product_images WHERE sku = ?')->execute([$sku]);
            if ($images) {
                $this->pdo->prepare('INSERT INTO product_images (sku, images) VALUES (?, ?)')
                    ->execute([$sku, (string) json_encode($images, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
            }
            return;
        }
        $all = $this->fileAll();
        if ($images) {
            $all[$sku] = $images;
        } else {
            unset($all[$sku]);
        }
        $this->persist($all);
    }

    public function add(string $sku, string $filename): void
    {
        $list = $this->find($sku);
        $list[] = $filename;
        $this->save($sku, $list);
    }

    public function remove(string $sku, string $filename): void
    {
        $this->save($sku, array_filter($this->find($sku), static fn ($f) => $f !== $filename));
    }

    /** A megadott képet teszi az első (elsődleges) helyre. */
    public function makePrimary(string $sku, string $filename): void
    {
        $list = $this->find($sku);
        if (!in_array($filename, $list, true)) {
            return;
        }
        $list = array_values(array_filter($list, static fn ($f) => $f !== $filename));
        array_unshift($list, $filename);
        $this->save($sku, $list);
    }

    /** @return string[] */
    private static function clean(mixed $list): array
    {
        return is_array($list) ? array_values(array_filter($list, 'is_string')) : [];
    }

    /** @return array<string, mixed> */
    private function fileAll(): array
    {
        if (!is_file($this->file)) {
            return [];
        }
        $data = json_decode((string) file_get_contents($this->file), true);
        return is_array($data) ? $data : [];
    }

    /** @param array<string, mixed> $all */
    private function persist(array $all): void
    {
        $dir = dirname($this->file);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        file_put_contents($this->file, json_encode($all, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
