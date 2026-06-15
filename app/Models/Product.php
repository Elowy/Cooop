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
     * Egy termék azonosító alapján (admin, aktivitástól függetlenül).
     *
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        $pdo = Database::getConnection();
        if ($pdo === null) {
            foreach (self::demo() as $product) {
                if ((int) $product['id'] === $id) {
                    return $product;
                }
            }
            return null;
        }
        $stmt = $pdo->prepare(
            'SELECT p.*, c.slug AS category_slug, c.name AS category_name
             FROM products p LEFT JOIN categories c ON c.id = p.category_id
             WHERE p.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    /**
     * Összes termék az admin listához (inaktívak is).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function allForAdmin(): array
    {
        $pdo = Database::getConnection();
        if ($pdo === null) {
            return self::demo();
        }
        $stmt = $pdo->query(
            'SELECT p.*, c.name AS category_name FROM products p
             LEFT JOIN categories c ON c.id = p.category_id
             ORDER BY p.created_at DESC, p.id DESC'
        );
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Új termék létrehozása. Visszaadja az új azonosítót.
     *
     * @param array<string, mixed> $data
     */
    public static function create(array $data): int
    {
        $pdo = Database::getConnection();
        if ($pdo === null) {
            return 0;
        }
        $stmt = $pdo->prepare(
            'INSERT INTO products (category_id, slug, name, short, description, price, unit, image, stock, featured, active)
             VALUES (:category_id, :slug, :name, :short, :description, :price, :unit, :image, :stock, :featured, :active)'
        );
        $stmt->execute(self::bind($data));
        return (int) $pdo->lastInsertId();
    }

    /**
     * Meglévő termék módosítása.
     *
     * @param array<string, mixed> $data
     */
    public static function update(int $id, array $data): void
    {
        $pdo = Database::getConnection();
        if ($pdo === null) {
            return;
        }
        $params = self::bind($data);
        $params['id'] = $id;
        $stmt = $pdo->prepare(
            'UPDATE products SET category_id = :category_id, slug = :slug, name = :name,
                short = :short, description = :description, price = :price, unit = :unit,
                image = :image, stock = :stock, featured = :featured, active = :active
             WHERE id = :id'
        );
        $stmt->execute($params);
    }

    public static function delete(int $id): void
    {
        $pdo = Database::getConnection();
        if ($pdo === null) {
            return;
        }
        $stmt = $pdo->prepare('DELETE FROM products WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    /**
     * Slug képzése névből (ékezetek eltávolításával).
     */
    public static function slugify(string $text): string
    {
        $map = [
            'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ö'=>'o','ő'=>'o','ú'=>'u','ü'=>'u','ű'=>'u',
            'Á'=>'a','É'=>'e','Í'=>'i','Ó'=>'o','Ö'=>'o','Ő'=>'o','Ú'=>'u','Ü'=>'u','Ű'=>'u',
        ];
        $text = strtr($text, $map);
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
        return trim($text, '-') ?: 'termek';
    }

    /**
     * Beviteli adatok normalizálása a prepared statementhez.
     *
     * @param array<string, mixed> $d
     * @return array<string, mixed>
     */
    private static function bind(array $d): array
    {
        return [
            'category_id' => ($d['category_id'] ?? null) ?: null,
            'slug'        => (string) ($d['slug'] ?? ''),
            'name'        => (string) ($d['name'] ?? ''),
            'short'       => (string) ($d['short'] ?? ''),
            'description' => (string) ($d['description'] ?? ''),
            'price'       => (float) ($d['price'] ?? 0),
            'unit'        => (string) ($d['unit'] ?? 'db'),
            'image'       => (string) ($d['image'] ?? 'placeholder.svg'),
            'stock'       => (int) ($d['stock'] ?? 0),
            'featured'    => !empty($d['featured']) ? 1 : 0,
            'active'      => !empty($d['active']) ? 1 : 0,
        ];
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
                'id' => 1, 'slug' => 'eur-raklap-epal', 'name' => 'EUR (EPAL) raklap',
                'price' => 4990, 'unit' => 'db', 'category_slug' => 'raklapok', 'category_name' => 'Raklapok',
                'image' => 'pallet.svg', 'featured' => 1, 'stock' => 1, 'stock_label' => 'Raktárról',
                'short' => 'Szabványos 1200×800 mm EUR raklap, EPAL minőségben.',
                'description' => 'Szabványos 1200×800 mm-es EUR raklap, EPAL előírásoknak megfelelő kivitelben, hőkezelt (ISPM-15 / IPPC jelöléssel kérhető) faanyagból. Ipari logisztikához, raktározáshoz és exporthoz egyaránt. Az ár tájékoztató jellegű, nettó / darab.',
            ],
            [
                'id' => 2, 'slug' => 'egyutas-raklap', 'name' => 'Egyutas raklap',
                'price' => 2490, 'unit' => 'db', 'category_slug' => 'raklapok', 'category_name' => 'Raklapok',
                'image' => 'pallet.svg', 'featured' => 0, 'stock' => 1, 'stock_label' => 'Raktárról',
                'short' => 'Költséghatékony egyutas raklap egyszeri szállításhoz.',
                'description' => 'Könnyűszerkezetes egyutas (egyszer használatos) raklap, ideális egyszeri kiszállításokhoz és exporthoz, ahol nincs szükség visszáru-kezelésre. Több méretben gyártjuk. Az ár tájékoztató jellegű, nettó / darab.',
            ],
            [
                'id' => 3, 'slug' => 'egyedi-meretu-raklap', 'name' => 'Egyedi méretű raklap',
                'price' => 3990, 'unit' => 'db', 'category_slug' => 'raklapok', 'category_name' => 'Raklapok',
                'image' => 'pallet.svg', 'featured' => 1, 'stock' => 1, 'stock_label' => 'Gyártásra',
                'short' => 'Méretre gyártott raklap a termék pontos paramétereihez.',
                'description' => 'A megrendelő terméke és terhelése alapján méretre tervezett és gyártott raklap, megerősített kivitelben is. Nehéz gépekhez, gyártósori alkatrészekhez. Egyedi igény esetén kérjen ajánlatot. Az ár tájékoztató jellegű, nettó / darab.',
            ],
            [
                'id' => 4, 'slug' => 'exportlada-retegelt', 'name' => 'Exportláda (rétegelt lemez)',
                'price' => 14900, 'unit' => 'db', 'category_slug' => 'ladak-csomagolas', 'category_name' => 'Ládák és csomagolás',
                'image' => 'crate.svg', 'featured' => 1, 'stock' => 1, 'stock_label' => 'Gyártásra',
                'short' => 'Stabil rétegelt lemez exportláda gépek és alkatrészek szállításához.',
                'description' => 'Méretre gyártott rétegelt lemez (sperrholz) exportláda, raklaptalppal, gépek, berendezések és nagy értékű áruk tengeri, légi és közúti szállításához. Igény szerint VCI korrózióvédelemmel és rögzítéssel. Az ár tájékoztató jellegű, nettó / darab.',
            ],
            [
                'id' => 5, 'slug' => 'falada-deszka', 'name' => 'Faláda (deszkaláda)',
                'price' => 9900, 'unit' => 'db', 'category_slug' => 'ladak-csomagolas', 'category_name' => 'Ládák és csomagolás',
                'image' => 'crate.svg', 'featured' => 0, 'stock' => 1, 'stock_label' => 'Gyártásra',
                'short' => 'Hagyományos deszkaláda nehéz és terjedelmes áruhoz.',
                'description' => 'Tömör fűrészáruból készült deszkaláda (rekesz) nehéz, terjedelmes vagy szabálytalan formájú áruk csomagolásához. Hőkezelt faanyagból, ISPM-15 jelöléssel is kérhető. Az ár tájékoztató jellegű, nettó / darab.',
            ],
            [
                'id' => 6, 'slug' => 'fenyo-fureszaru-gerenda', 'name' => 'Fenyő fűrészáru – gerenda',
                'price' => 145000, 'unit' => 'm³', 'category_slug' => 'fureszaru', 'category_name' => 'Fűrészáru',
                'image' => 'lumber.svg', 'featured' => 1, 'stock' => 1, 'stock_label' => 'Készleten',
                'short' => 'Szlovák fenyő gerenda, folyamatos, minőségi alapanyagból.',
                'description' => 'Szlovák fűrészüzemekből származó fenyő gerenda, megbízható és folyamatos minőségben. Tetőszerkezetekhez, ácsmunkákhoz, építkezéshez. Viszonteladói és projektmennyiség 1–1,5 héten belül. Az ár tájékoztató jellegű, nettó / m³.',
            ],
            [
                'id' => 7, 'slug' => 'fenyo-palló-deszka', 'name' => 'Fenyő palló és deszka',
                'price' => 138000, 'unit' => 'm³', 'category_slug' => 'fureszaru', 'category_name' => 'Fűrészáru',
                'image' => 'lumber.svg', 'featured' => 0, 'stock' => 1, 'stock_label' => 'Készleten',
                'short' => 'Fenyő palló és deszka több méretben, nagykereskedelmi áron.',
                'description' => 'Fenyő palló és deszka különböző vastagságban és hosszban, közel 30 év fakereskedelmi tapasztalattal a háttérben. Folyamatos, minőség-ellenőrzött szlovák alapanyag. Az ár tájékoztató jellegű, nettó / m³.',
            ],
            [
                'id' => 8, 'slug' => 'tetolec-fenyo', 'name' => 'Fenyő tetőléc',
                'price' => 320, 'unit' => 'fm', 'category_slug' => 'fureszaru', 'category_name' => 'Fűrészáru',
                'image' => 'lumber.svg', 'featured' => 0, 'stock' => 1, 'stock_label' => 'Készleten',
                'short' => 'Szabványos fenyő tetőléc tetőfedéshez.',
                'description' => 'Egészséges, jól szárított fenyő tetőléc tetőfedéshez és állványozáshoz, több méretben. Nagy mennyiségben is rendelhető. Az ár tájékoztató jellegű, nettó / folyóméter.',
            ],
            [
                'id' => 9, 'slug' => 'hasitott-tuzifa-kemenyfa', 'name' => 'Hasított tűzifa (keményfa)',
                'price' => 32000, 'unit' => 'm³', 'category_slug' => 'tuzifa', 'category_name' => 'Tűzifa',
                'image' => 'firewood.svg', 'featured' => 1, 'stock' => 1, 'stock_label' => 'Készleten',
                'short' => 'Vegyes keményfa hasított tűzifa, kiszállítással.',
                'description' => 'Vegyes keményfa (tölgy, bükk, gyertyán) hasított tűzifa, kívánságra konyhakészre vágva. Kiváló fűtőértékkel, igény szerint kiszállítással. Az ár tájékoztató jellegű, nettó / m³ (ömlesztett).',
            ],
            [
                'id' => 10, 'slug' => 'fenyo-tuzifa', 'name' => 'Fenyő tűzifa / melléktermék',
                'price' => 21000, 'unit' => 'm³', 'category_slug' => 'tuzifa', 'category_name' => 'Tűzifa',
                'image' => 'firewood.svg', 'featured' => 0, 'stock' => 1, 'stock_label' => 'Készleten',
                'short' => 'Fenyő tűzifa a faipari feldolgozás melléktermékéből.',
                'description' => 'A faipari feldolgozás során keletkező fenyő melléktermék tűzifaként, mert nálunk minden faforgács hasznosul. Gyújtósnak és kazánba egyaránt. Az ár tájékoztató jellegű, nettó / m³ (ömlesztett).',
            ],
            [
                'id' => 11, 'slug' => 'britterm-falazotegla', 'name' => 'BRITTERM falazótégla',
                'price' => 280, 'unit' => 'db', 'category_slug' => 'teglak', 'category_name' => 'BRITTERM téglák',
                'image' => 'brick.svg', 'featured' => 1, 'stock' => 1, 'stock_label' => 'Rendelésre',
                'short' => 'Szlovák BRITTERM falazótégla, hivatalos magyar képviselet.',
                'description' => 'A szlovák BRITTERM falazótéglái, melyeket 2017 óta képviselünk Magyarországon. Jó hőszigetelés, pontos méret és kiváló ár-érték arány teherhordó és válaszfalakhoz. Az ár tájékoztató jellegű, nettó / darab.',
            ],
            [
                'id' => 12, 'slug' => 'britterm-keménytegla', 'name' => 'BRITTERM kéménytégla',
                'price' => 420, 'unit' => 'db', 'category_slug' => 'teglak', 'category_name' => 'BRITTERM téglák',
                'image' => 'brick.svg', 'featured' => 0, 'stock' => 1, 'stock_label' => 'Rendelésre',
                'short' => 'BRITTERM kémény- és blokktégla rendszerelemek.',
                'description' => 'BRITTERM kémény- és blokktégla rendszerelemek megbízható minőségben, közvetlenül a gyártótól. Projektmennyiség egyeztetés alapján. Az ár tájékoztató jellegű, nettó / darab.',
            ],
        ];
    }
}
