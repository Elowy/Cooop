<?php

namespace App\Core;

use App\Models\Category;
use App\Models\Product;
use PDO;
use PDOException;

/**
 * Egyszerű PDO alapú adatbázis-kapcsolat singleton.
 *
 * Először a konfigurált (MySQL/MariaDB) kapcsolatot próbálja. Ha az nem
 * érhető el, automatikusan egy helyi SQLite fájlra esik vissza
 * (storage/database.sqlite), amelyet első alkalommal a demó adatokkal tölt
 * fel – így a webshop és az admin felület külön szerver nélkül is működik.
 * Ha az SQLite sem elérhető, a getConnection() null-t ad vissza, a modellek
 * pedig a beégetett demó adatokra esnek vissza (csak olvasás).
 */
final class Database
{
    private static ?PDO $instance = null;
    private static bool $attempted = false;
    private static string $driver = 'none';

    public static function getConnection(): ?PDO
    {
        if (self::$attempted) {
            return self::$instance;
        }

        self::$attempted = true;
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $db = $config['database'];

        // 1) A konfigurált (MySQL) kapcsolat.
        $dsn = sprintf(
            '%s:host=%s;port=%s;dbname=%s;charset=%s',
            $db['driver'],
            $db['host'],
            $db['port'],
            $db['name'],
            $db['charset']
        );

        try {
            self::$instance = new PDO($dsn, $db['user'], $db['password'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            self::$driver = (string) $db['driver'];
            return self::$instance;
        } catch (PDOException) {
            self::$instance = null;
        }

        // 2) Visszaesés helyi SQLite fájlra (nulla konfiguráció).
        if (in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            try {
                $dir = dirname(__DIR__, 2) . '/storage';
                if (!is_dir($dir)) {
                    mkdir($dir, 0775, true);
                }
                $file = $dir . '/database.sqlite';

                $pdo = new PDO('sqlite:' . $file, null, null, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]);
                $pdo->exec('PRAGMA foreign_keys = ON');

                self::$instance = $pdo;
                self::$driver = 'sqlite';
                self::bootstrapSqlite($pdo);
            } catch (PDOException) {
                self::$instance = null;
                self::$driver = 'none';
            }
        }

        return self::$instance;
    }

    /** Az aktív adatbázis-illesztő neve: mysql | sqlite | none. */
    public static function driver(): string
    {
        return self::$driver;
    }

    /**
     * SQLite séma létrehozása és (egyszeri) feltöltése a demó adatokkal.
     */
    private static function bootstrapSqlite(PDO $pdo): void
    {
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS categories (
                id   INTEGER PRIMARY KEY AUTOINCREMENT,
                slug TEXT NOT NULL UNIQUE,
                name TEXT NOT NULL,
                icon TEXT
            )'
        );
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS products (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                category_id INTEGER,
                slug        TEXT NOT NULL UNIQUE,
                name        TEXT NOT NULL,
                short       TEXT,
                description TEXT,
                price       REAL NOT NULL DEFAULT 0,
                image       TEXT DEFAULT 'placeholder.svg',
                stock       INTEGER NOT NULL DEFAULT 0,
                featured    INTEGER NOT NULL DEFAULT 0,
                active      INTEGER NOT NULL DEFAULT 1,
                created_at  TEXT DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE SET NULL
            )"
        );
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS orders (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                name       TEXT NOT NULL,
                email      TEXT NOT NULL,
                phone      TEXT,
                zip        TEXT,
                address    TEXT,
                note       TEXT,
                total      REAL NOT NULL DEFAULT 0,
                status     TEXT NOT NULL DEFAULT 'new',
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )"
        );
        $pdo->exec(
            'CREATE TABLE IF NOT EXISTS order_items (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id   INTEGER NOT NULL,
                product_id INTEGER,
                name       TEXT NOT NULL,
                price      REAL NOT NULL,
                qty        INTEGER NOT NULL,
                FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE
            )'
        );

        $count = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
        if ($count === 0) {
            self::seedFromDemo($pdo);
        }
    }

    private static function seedFromDemo(PDO $pdo): void
    {
        $catStmt = $pdo->prepare(
            'INSERT INTO categories (slug, name, icon) VALUES (:slug, :name, :icon)'
        );
        $idBySlug = [];
        foreach (Category::demo() as $c) {
            $catStmt->execute([
                'slug' => $c['slug'],
                'name' => $c['name'],
                'icon' => $c['icon'] ?? null,
            ]);
            $idBySlug[$c['slug']] = (int) $pdo->lastInsertId();
        }

        $pStmt = $pdo->prepare(
            'INSERT INTO products (category_id, slug, name, short, description, price, image, stock, featured, active)
             VALUES (:cat, :slug, :name, :short, :description, :price, :image, :stock, :featured, 1)'
        );
        foreach (Product::demo() as $p) {
            $pStmt->execute([
                'cat'         => $idBySlug[$p['category_slug']] ?? null,
                'slug'        => $p['slug'],
                'name'        => $p['name'],
                'short'       => $p['short'] ?? null,
                'description' => $p['description'] ?? null,
                'price'       => $p['price'],
                'image'       => $p['image'] ?? 'placeholder.svg',
                'stock'       => $p['stock'] ?? 0,
                'featured'    => !empty($p['featured']) ? 1 : 0,
            ]);
        }
    }
}
