<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Str;

/**
 * Termék modell.
 *
 * Ha van adatbázis-kapcsolat, onnan dolgozik, egyébként a demó adatokra esik
 * vissza, hogy az oldal adatbázis nélkül is megtekinthető legyen.
 */
final class Product
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(?string $categorySlug = null, ?string $search = null): array
    {
        $pdo = Database::getConnection();

        if ($pdo === null) {
            return self::filterDemo(self::demo(), $categorySlug, $search);
        }

        $sql = 'SELECT p.*, c.slug AS category_slug, c.name AS category_name
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                WHERE p.active = 1';
        $params = [];

        if ($categorySlug !== null) {
            $sql .= ' AND c.slug = :cat';
            $params['cat'] = $categorySlug;
        }
        if ($search !== null && $search !== '') {
            $sql .= ' AND (p.name LIKE :q OR p.description LIKE :q)';
            $params['q'] = '%' . $search . '%';
        }
        $sql .= ' ORDER BY p.featured DESC, p.name ASC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findBySlug(string $slug): ?array
    {
        $pdo = Database::getConnection();

        if ($pdo === null) {
            foreach (self::demo() as $product) {
                if ($product['slug'] === $slug) {
                    return $product;
                }
            }
            return null;
        }

        $stmt = $pdo->prepare(
            'SELECT p.*, c.slug AS category_slug, c.name AS category_name
             FROM products p LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.slug = :slug AND p.active = 1 LIMIT 1'
        );
        $stmt->execute(['slug' => $slug]);
        $product = $stmt->fetch();
        return $product ?: null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function featured(int $limit = 6): array
    {
        $all = self::all();
        $featured = array_values(array_filter($all, static fn ($p) => !empty($p['featured'])));
        return array_slice($featured ?: $all, 0, $limit);
    }

    /**
     * Minden termék (aktív és inaktív is) az admin listához.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function adminAll(): array
    {
        $pdo = Database::getConnection();
        if ($pdo === null) {
            return self::demo();
        }
        $stmt = $pdo->query(
            'SELECT p.*, c.slug AS category_slug, c.name AS category_name
             FROM products p LEFT JOIN categories c ON c.id = p.category_id
             ORDER BY p.id DESC'
        );
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        $pdo = Database::getConnection();
        if ($pdo === null) {
            return null;
        }
        $stmt = $pdo->prepare(
            'SELECT p.*, c.slug AS category_slug, c.name AS category_name
             FROM products p LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Új termék létrehozása. Visszaadja az új azonosítót, vagy false-t.
     *
     * @param array<string, mixed> $data
     */
    public static function create(array $data): int|false
    {
        $pdo = Database::getConnection();
        if ($pdo === null) {
            return false;
        }
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            return false;
        }
        $slug = self::uniqueSlug((string) ($data['slug'] ?? '') ?: $name);
        $stmt = $pdo->prepare(
            'INSERT INTO products (category_id, slug, name, short, description, price, image, stock, featured, active)
             VALUES (:cat, :slug, :name, :short, :description, :price, :image, :stock, :featured, :active)'
        );
        $stmt->execute(self::params($data, $slug, $name));
        return (int) $pdo->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function update(int $id, array $data): bool
    {
        $pdo = Database::getConnection();
        if ($pdo === null) {
            return false;
        }
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            return false;
        }
        $slug = self::uniqueSlug((string) ($data['slug'] ?? '') ?: $name, $id);
        $stmt = $pdo->prepare(
            'UPDATE products SET category_id = :cat, slug = :slug, name = :name, short = :short,
             description = :description, price = :price, image = :image, stock = :stock,
             featured = :featured, active = :active WHERE id = :id'
        );
        $params = self::params($data, $slug, $name);
        $params['id'] = $id;
        return $stmt->execute($params);
    }

    public static function delete(int $id): bool
    {
        $pdo = Database::getConnection();
        if ($pdo === null) {
            return false;
        }
        $stmt = $pdo->prepare('DELETE FROM products WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Beviteli adatok normalizálása paraméter-tömbbé.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private static function params(array $data, string $slug, string $name): array
    {
        $catId = (int) ($data['category_id'] ?? 0);
        return [
            'cat'         => $catId > 0 ? $catId : null,
            'slug'        => $slug,
            'name'        => $name,
            'short'       => trim((string) ($data['short'] ?? '')) ?: null,
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
            'price'       => max(0, (int) round((float) ($data['price'] ?? 0))),
            'image'       => trim((string) ($data['image'] ?? '')) ?: 'placeholder.svg',
            'stock'       => max(0, (int) ($data['stock'] ?? 0)),
            'featured'    => !empty($data['featured']) ? 1 : 0,
            'active'      => isset($data['active']) ? (!empty($data['active']) ? 1 : 0) : 1,
        ];
    }

    private static function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $pdo = Database::getConnection();
        $slug = Str::slug($base) ?: 'termek';
        if ($pdo === null) {
            return $slug;
        }
        $candidate = $slug;
        $i = 2;
        while (true) {
            $sql = 'SELECT COUNT(*) FROM products WHERE slug = :slug';
            $params = ['slug' => $candidate];
            if ($ignoreId !== null) {
                $sql .= ' AND id <> :id';
                $params['id'] = $ignoreId;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            if ((int) $stmt->fetchColumn() === 0) {
                return $candidate;
            }
            $candidate = $slug . '-' . $i++;
        }
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    private static function filterDemo(array $items, ?string $categorySlug, ?string $search): array
    {
        return array_values(array_filter($items, static function ($p) use ($categorySlug, $search) {
            $okCat = $categorySlug === null || $p['category_slug'] === $categorySlug;
            $okSearch = $search === null || $search === ''
                || stripos($p['name'] . ' ' . $p['description'], $search) !== false;
            return $okCat && $okSearch;
        }));
    }

    /**
     * Demó termékek (adatbázis nélküli megtekintéshez).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function demo(): array
    {
        return [
            [
                'id' => 1, 'slug' => 'vega-klasszikus-madareteto', 'name' => 'Vega klasszikus madáretető',
                'price' => 4990, 'category_slug' => 'klasszikus', 'category_name' => 'Klasszikus',
                'image' => 'feeder-classic.svg', 'featured' => 1, 'stock' => 40,
                'short' => 'Hőkezelt borovi fenyőből készült, időtálló klasszikus etető.',
                'description' => 'A Vega klasszikus madáretető praktikus megoldás, amely különlegessé teszi kertjét vagy erkélyét a téli hónapokban. Hőkezelt borovi fenyőből és farostlemezből készül, IPPC és ISPM 15 szabvány szerint, CE-tanúsítvánnyal. A gyártás során nem használtunk és nem keletkezett környezetre káros anyag.',
            ],
            [
                'id' => 2, 'slug' => 'vega-ketpalcas-madareteto', 'name' => 'Vega kétpálcás madáretető',
                'price' => 5490, 'category_slug' => 'klasszikus', 'category_name' => 'Klasszikus',
                'image' => 'feeder-twobar.svg', 'featured' => 0, 'stock' => 35,
                'short' => 'Két ülőpálcával a kisebb énekesmadaraknak.',
                'description' => 'Két ülőpálcával ellátott klasszikus madáretető, amely stabil, szellős etetőfelületet kínál a kisebb énekesmadaraknak. Hőkezelt borovi fenyőből, tartós kivitelben, kertbe és erkélyre egyaránt.',
            ],
            [
                'id' => 3, 'slug' => 'vega-pagoda-madareteto', 'name' => 'Vega pagoda madáretető',
                'price' => 6990, 'category_slug' => 'modern', 'category_name' => 'Modern',
                'image' => 'feeder-pagoda.svg', 'featured' => 1, 'stock' => 22,
                'short' => 'Letisztult pagoda forma, modern kertek dísze.',
                'description' => 'A Vega pagoda madáretető letisztult, modern formavilágával bármely kert dísze lehet. Tágas etetőfelülete több madár egyidejű etetését is lehetővé teszi. Hőkezelt borovi fenyőből, CE-tanúsítvánnyal.',
            ],
            [
                'id' => 4, 'slug' => 'vega-modern-madareteto', 'name' => 'Vega modern madáretető',
                'price' => 7490, 'category_slug' => 'modern', 'category_name' => 'Modern',
                'image' => 'feeder-modern.svg', 'featured' => 0, 'stock' => 18,
                'short' => 'Minimalista vonalvezetés, natúr felület.',
                'description' => 'Minimalista vonalvezetésű, natúr felületű madáretető kortárs homlokzatokhoz és modern kertekhez. Hőkezelt borovi fenyőből készül, időtálló és esztétikus megoldás.',
            ],
            [
                'id' => 5, 'slug' => 'vega-nagy-csaladi-madareteto', 'name' => 'Vega nagy családi madáretető',
                'price' => 8990, 'category_slug' => 'nagy', 'category_name' => 'Nagy méretű',
                'image' => 'feeder-large.svg', 'featured' => 1, 'stock' => 14,
                'short' => 'Nagyobb befogadóképesség egész télre.',
                'description' => 'Nagy befogadóképességű madáretető, amely több madár egyidejű etetését teszi lehetővé egész télen át. Robusztus, hőkezelt borovi fenyő szerkezet, ISPM 15 szabvány szerint.',
            ],
            [
                'id' => 6, 'slug' => 'vega-fuggesztheto-madareteto', 'name' => 'Vega függeszthető madáretető',
                'price' => 4490, 'category_slug' => 'fuggesztheto', 'category_name' => 'Függeszthető',
                'image' => 'feeder-hanging.svg', 'featured' => 1, 'stock' => 50,
                'short' => 'Faágra vagy konzolra akasztható, kompakt etető.',
                'description' => 'Faágra vagy konzolra egyszerűen felakasztható, könnyű és kompakt madáretető a kertbe vagy a balkonra. Hőkezelt borovi fenyőből, tartós kivitelben.',
            ],
            [
                'id' => 7, 'slug' => 'vega-oszlopos-madareteto', 'name' => 'Vega oszlopos madáretető',
                'price' => 7990, 'category_slug' => 'nagy', 'category_name' => 'Nagy méretű',
                'image' => 'feeder-post.svg', 'featured' => 0, 'stock' => 12,
                'short' => 'Talajba állítható oszlopos etető nyitott kertbe.',
                'description' => 'Talajba állítható, oszlopos kivitelű madáretető, amely nyitott kertbe, gyepre is kiváló. Stabil láb, tágas tető, hőkezelt borovi fenyőből, CE-tanúsítvánnyal.',
            ],
            [
                'id' => 8, 'slug' => 'vega-mini-balkon-madareteto', 'name' => 'Vega mini balkon madáretető',
                'price' => 3990, 'category_slug' => 'fuggesztheto', 'category_name' => 'Függeszthető',
                'image' => 'feeder-mini.svg', 'featured' => 0, 'stock' => 60,
                'short' => 'Helytakarékos mini etető erkélyre, ablakpárkányra.',
                'description' => 'Helytakarékos, mini méretű madáretető erkélyre vagy ablakpárkányra, ahol kevés a hely. Könnyen felakasztható, hőkezelt borovi fenyőből készült, esztétikus darab.',
            ],
        ];
    }
}
