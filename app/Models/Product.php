<?php

namespace App\Models;

use App\Core\Database;

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
                'id' => 1, 'slug' => 'wifi6-router-ax3000', 'name' => 'WiFi 6 Router AX3000',
                'price' => 28990, 'category_slug' => 'routerek', 'category_name' => 'Routerek',
                'image' => 'router.svg', 'featured' => 1, 'stock' => 24,
                'short' => 'Nagy sebességű WiFi 6 router otthonra és kis irodába.',
                'description' => 'Dual-band WiFi 6 (802.11ax) router 3000 Mbps összesített sebességgel, 4 db Gigabit LAN porttal és OFDMA technológiával a stabil, gyors kapcsolatért akár sok eszköz esetén is.',
            ],
            [
                'id' => 2, 'slug' => 'gigabit-switch-8-port', 'name' => 'Gigabit Switch 8 portos',
                'price' => 12490, 'category_slug' => 'switchek', 'category_name' => 'Switchek',
                'image' => 'switch.svg', 'featured' => 1, 'stock' => 50,
                'short' => 'Fémházas, csendes 8 portos Gigabit switch.',
                'description' => 'Plug & play 8 portos Gigabit Ethernet switch, fémházban, ventilátor nélküli csendes működéssel. Ideális hálózat bővítéséhez otthon vagy irodában.',
            ],
            [
                'id' => 3, 'slug' => 'cat6-utp-kabel-305m', 'name' => 'Cat6 UTP kábel 305m',
                'price' => 34900, 'category_slug' => 'kabelek', 'category_name' => 'Kábelek',
                'image' => 'cable.svg', 'featured' => 0, 'stock' => 15,
                'short' => 'Réz CAT6 UTP installációs kábel dobozos kiszerelésben.',
                'description' => '305 méteres CAT6 UTP installációs kábel, tömör réz erekkel, 250 MHz sávszélességgel strukturált hálózatok kiépítéséhez.',
            ],
            [
                'id' => 4, 'slug' => 'poe-ip-kamera-4mp', 'name' => 'PoE IP kamera 4MP',
                'price' => 19990, 'category_slug' => 'kamerak', 'category_name' => 'Kamerák',
                'image' => 'camera.svg', 'featured' => 1, 'stock' => 32,
                'short' => '4 megapixeles kültéri PoE IP biztonsági kamera.',
                'description' => '4MP felbontású kültéri (IP67) PoE IP kamera éjjellátóval (30m IR), mozgásérzékeléssel és H.265 tömörítéssel a hatékony tárolásért.',
            ],
            [
                'id' => 5, 'slug' => 'access-point-ceiling-ax1800', 'name' => 'Access Point mennyezeti AX1800',
                'price' => 23490, 'category_slug' => 'routerek', 'category_name' => 'Routerek',
                'image' => 'ap.svg', 'featured' => 0, 'stock' => 18,
                'short' => 'Mennyezetre szerelhető WiFi 6 access point.',
                'description' => 'Mennyezetre szerelhető WiFi 6 access point 1800 Mbps sebességgel, PoE táplálással és központi menedzsment támogatással nagyobb terek lefedéséhez.',
            ],
            [
                'id' => 6, 'slug' => 'patch-panel-24-port', 'name' => 'Patch panel 24 portos',
                'price' => 8990, 'category_slug' => 'kabelek', 'category_name' => 'Kábelek',
                'image' => 'panel.svg', 'featured' => 0, 'stock' => 40,
                'short' => '19" 1U CAT6 patch panel rackszekrénybe.',
                'description' => '19 colos, 1U magas, 24 portos CAT6 patch panel rendezett, professzionális hálózati szereléshez rackszekrényekbe.',
            ],
            [
                'id' => 7, 'slug' => 'nas-2-bay', 'name' => 'NAS adattároló 2 lemezes',
                'price' => 64900, 'category_slug' => 'tarolok', 'category_name' => 'Tárolók',
                'image' => 'nas.svg', 'featured' => 1, 'stock' => 9,
                'short' => 'Kétlemezes hálózati adattároló otthonra és irodába.',
                'description' => 'Kétlemezes (2-bay) NAS központi adattároláshoz, automatikus mentéshez és médiaszerver funkcióhoz, RAID támogatással és gigabites hálózati csatlakozással.',
            ],
            [
                'id' => 8, 'slug' => 'szunetmentes-tapegyseg-650va', 'name' => 'Szünetmentes tápegység 650VA',
                'price' => 17990, 'category_slug' => 'tarolok', 'category_name' => 'Tárolók',
                'image' => 'ups.svg', 'featured' => 0, 'stock' => 21,
                'short' => 'UPS a hálózati eszközök védelméhez áramkimaradás ellen.',
                'description' => '650VA / 360W szünetmentes tápegység (UPS) túlfeszültség-védelemmel, ami áramkimaradás esetén is biztosítja a router és NAS folyamatos működését.',
            ],
        ];
    }
}
