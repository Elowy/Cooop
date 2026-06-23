<?php

namespace App\Seo;

use PDO;

/**
 * Termékenkénti SEO felülírások (cikkszám szerint). Telepítés után adatbázis
 * (product_seo tábla), előtte JSON fájl. A termékek maguk az Axelből/configból
 * jönnek; ez csak a hozzájuk tartozó SEO-mezőket tárolja.
 */
final class ProductSeoStore
{
    private ?PDO $pdo;
    private string $file;

    public function __construct(?PDO $pdo = null, ?string $file = null)
    {
        $this->pdo = $pdo;
        $this->file = $file ?? dirname(__DIR__, 2) . '/storage/product_seo.json';
    }

    public function find(string $sku): ?array
    {
        if ($this->pdo) {
            $stmt = $this->pdo->prepare('SELECT sku, title, description, keywords, og_image FROM product_seo WHERE sku = ?');
            $stmt->execute([$sku]);
            return $stmt->fetch() ?: null;
        }
        return $this->fileAll()[$sku] ?? null;
    }

    public function save(string $sku, array $data): void
    {
        $row = [
            'title' => (string) ($data['title'] ?? ''),
            'description' => (string) ($data['description'] ?? ''),
            'keywords' => (string) ($data['keywords'] ?? ''),
            'og_image' => (string) ($data['og_image'] ?? ''),
        ];
        if ($this->pdo) {
            $this->pdo->prepare('DELETE FROM product_seo WHERE sku = ?')->execute([$sku]);
            $this->pdo->prepare('INSERT INTO product_seo (sku, title, description, keywords, og_image) VALUES (?, ?, ?, ?, ?)')
                ->execute([$sku, $row['title'], $row['description'], $row['keywords'], $row['og_image']]);
            return;
        }
        $all = $this->fileAll();
        $all[$sku] = $row;
        $this->persist($all);
    }

    /** @return array<string, array<string, mixed>> sku => sor */
    public function all(): array
    {
        if ($this->pdo) {
            $out = [];
            foreach ($this->pdo->query('SELECT sku, title, description, keywords, og_image FROM product_seo') as $r) {
                $out[$r['sku']] = $r;
            }
            return $out;
        }
        return $this->fileAll();
    }

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
