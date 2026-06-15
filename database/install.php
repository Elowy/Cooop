<?php

declare(strict_types=1);

/**
 * Adatbázis telepítő / migráló.
 *
 * Használat (a projekt gyökeréből):
 *     php database/install.php
 *
 * Alapértelmezésben SQLite-ot használ (storage/database.sqlite), így külső
 * adatbázis-szerver nélkül is azonnal működik. MySQL-hez állítsd be a
 * DB_DRIVER=mysql és a DB_* környezeti változókat (az adatbázist előtte hozd
 * létre), majd futtasd ezt a scriptet vagy importáld a schema.sql / seed.sql-t.
 *
 * A script idempotens: a táblákat csak akkor hozza létre, ha még nincsenek,
 * és csak üres katalógus esetén tölti fel a demó adatokat.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Ez a script csak parancssorból futtatható.\n");
}

// Minimalista autoloader az App\ névtérhez.
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $file = dirname(__DIR__) . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

use App\Core\Database;
use App\Models\Category;
use App\Models\Product;

$config = require dirname(__DIR__) . '/config/config.php';
$db = $config['database'];

// Telepítéskor – ha nincs explicit meghajtó – SQLite az alapértelmezett.
$driver = (string) ($db['driver'] ?? '');
if ($driver === '') {
    $driver = 'sqlite';
}

echo "» Net-Trade telepítő – meghajtó: {$driver}\n";

try {
    $pdo = Database::connect($db, $driver);
} catch (Throwable $e) {
    fwrite(STDERR, "✗ Nem sikerült csatlakozni az adatbázishoz: {$e->getMessage()}\n");
    if ($driver === 'mysql') {
        fwrite(STDERR, "  Ellenőrizd, hogy az adatbázis létezik-e, és a DB_* beállítások helyesek-e.\n");
    }
    exit(1);
}

$pk  = $driver === 'sqlite' ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT AUTO_INCREMENT PRIMARY KEY';
$eng = $driver === 'sqlite' ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

$tables = [
    "CREATE TABLE IF NOT EXISTS categories (
        id {$pk},
        slug VARCHAR(80) NOT NULL UNIQUE,
        name VARCHAR(120) NOT NULL,
        icon VARCHAR(40) DEFAULT NULL
    ){$eng}",

    "CREATE TABLE IF NOT EXISTS products (
        id {$pk},
        category_id INT DEFAULT NULL,
        slug VARCHAR(140) NOT NULL UNIQUE,
        name VARCHAR(200) NOT NULL,
        short VARCHAR(300) DEFAULT NULL,
        description TEXT,
        price DECIMAL(10,2) NOT NULL DEFAULT 0,
        unit VARCHAR(20) NOT NULL DEFAULT 'db',
        image VARCHAR(140) DEFAULT 'placeholder.svg',
        stock INT NOT NULL DEFAULT 0,
        featured TINYINT(1) NOT NULL DEFAULT 0,
        active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE SET NULL
    ){$eng}",

    "CREATE TABLE IF NOT EXISTS users (
        id {$pk},
        name VARCHAR(160) NOT NULL,
        email VARCHAR(190) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(20) NOT NULL DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ){$eng}",

    "CREATE TABLE IF NOT EXISTS orders (
        id {$pk},
        name VARCHAR(160) NOT NULL,
        email VARCHAR(160) NOT NULL,
        phone VARCHAR(40) DEFAULT NULL,
        zip VARCHAR(20) DEFAULT NULL,
        address VARCHAR(255) DEFAULT NULL,
        note TEXT,
        total DECIMAL(10,2) NOT NULL DEFAULT 0,
        status VARCHAR(30) NOT NULL DEFAULT 'new',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ){$eng}",

    "CREATE TABLE IF NOT EXISTS order_items (
        id {$pk},
        order_id INT NOT NULL,
        product_id INT DEFAULT NULL,
        name VARCHAR(200) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        qty INT NOT NULL,
        CONSTRAINT fk_items_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE
    ){$eng}",
];

foreach ($tables as $sql) {
    $pdo->exec($sql);
}
echo "✓ Táblák létrehozva / ellenőrizve.\n";

// Kategóriák feltöltése, ha üres.
$catCount = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
if ($catCount === 0) {
    $stmt = $pdo->prepare('INSERT INTO categories (slug, name, icon) VALUES (:slug, :name, :icon)');
    foreach (Category::demo() as $c) {
        $stmt->execute(['slug' => $c['slug'], 'name' => $c['name'], 'icon' => $c['icon'] ?? null]);
    }
    echo "✓ " . count(Category::demo()) . " kategória feltöltve.\n";
}

// Slug → id leképezés a termékekhez.
$catMap = [];
foreach ($pdo->query('SELECT id, slug FROM categories')->fetchAll() as $row) {
    $catMap[$row['slug']] = (int) $row['id'];
}

// Termékek feltöltése, ha üres.
$prodCount = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
if ($prodCount === 0) {
    $stmt = $pdo->prepare(
        'INSERT INTO products (category_id, slug, name, short, description, price, unit, image, stock, featured, active)
         VALUES (:category_id, :slug, :name, :short, :description, :price, :unit, :image, :stock, :featured, 1)'
    );
    foreach (Product::demo() as $p) {
        $stmt->execute([
            'category_id' => $catMap[$p['category_slug']] ?? null,
            'slug'        => $p['slug'],
            'name'        => $p['name'],
            'short'       => $p['short'] ?? '',
            'description' => $p['description'] ?? '',
            'price'       => $p['price'],
            'unit'        => $p['unit'] ?? 'db',
            'image'       => $p['image'] ?? 'placeholder.svg',
            'stock'       => $p['stock'] ?? 0,
            'featured'    => !empty($p['featured']) ? 1 : 0,
        ]);
    }
    echo "✓ " . count(Product::demo()) . " termék feltöltve.\n";
}

$users = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

echo "\n✅ Kész. Az adatbázis használatra kész";
echo $driver === 'sqlite' ? " (" . $db['sqlite'] . ").\n" : ".\n";
if ($users === 0) {
    echo "→ Most regisztrálj a /regisztracio oldalon – az első felhasználó automatikusan ADMIN lesz.\n";
} else {
    echo "→ {$users} felhasználó van a rendszerben.\n";
}
