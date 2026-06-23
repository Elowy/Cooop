<?php

namespace App\Db;

use PDO;

/**
 * Adatbázis-séma létrehozása (a telepítő hívja). Driver-függő DDL
 * (MySQL élesben, SQLite helyi teszthez).
 */
final class Schema
{
    public static function create(PDO $pdo, string $driver): void
    {
        $sqlite = $driver === 'sqlite';
        $id = $sqlite ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'INT AUTO_INCREMENT PRIMARY KEY';
        $dbl = $sqlite ? 'REAL' : 'DOUBLE';
        $txt = 'TEXT';
        $suffix = $sqlite ? '' : ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4';

        $tables = [
            "CREATE TABLE IF NOT EXISTS users (
                id $id,
                name VARCHAR(190) NOT NULL,
                email VARCHAR(190) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(20) NOT NULL DEFAULT 'customer',
                created_at VARCHAR(40) NOT NULL
            )$suffix",

            "CREATE TABLE IF NOT EXISTS refs (
                id $id,
                name VARCHAR(255) NOT NULL,
                logo VARCHAR(255) NOT NULL DEFAULT '',
                short_text $txt,
                long_text $txt,
                url VARCHAR(500) NOT NULL DEFAULT '',
                featured INT NOT NULL DEFAULT 0,
                created_at VARCHAR(40) NOT NULL
            )$suffix",

            "CREATE TABLE IF NOT EXISTS pois (
                id $id,
                title VARCHAR(255) NOT NULL,
                lat $dbl NOT NULL,
                lng $dbl NOT NULL,
                description $txt,
                link VARCHAR(500) NOT NULL DEFAULT '',
                created_at VARCHAR(40) NOT NULL
            )$suffix",

            "CREATE TABLE IF NOT EXISTS settings (
                skey VARCHAR(190) PRIMARY KEY,
                svalue $txt
            )$suffix",

            "CREATE TABLE IF NOT EXISTS messages (
                id $id,
                created_at VARCHAR(40) NOT NULL,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL DEFAULT '',
                phone VARCHAR(100) NOT NULL DEFAULT '',
                subject VARCHAR(255) NOT NULL DEFAULT '',
                message $txt
            )$suffix",

            "CREATE TABLE IF NOT EXISTS orders (
                token VARCHAR(40) PRIMARY KEY,
                created_at VARCHAR(40) NOT NULL,
                data $txt NOT NULL
            )$suffix",

            "CREATE TABLE IF NOT EXISTS leaders (
                id $id,
                name VARCHAR(190) NOT NULL,
                role VARCHAR(190) NOT NULL DEFAULT '',
                phone VARCHAR(100) NOT NULL DEFAULT '',
                email VARCHAR(190) NOT NULL DEFAULT '',
                photo VARCHAR(255) NOT NULL DEFAULT '',
                created_at VARCHAR(40) NOT NULL
            )$suffix",

            "CREATE TABLE IF NOT EXISTS product_seo (
                sku VARCHAR(64) PRIMARY KEY,
                title VARCHAR(255) NOT NULL DEFAULT '',
                description $txt,
                keywords VARCHAR(255) NOT NULL DEFAULT '',
                og_image VARCHAR(500) NOT NULL DEFAULT ''
            )$suffix",

            "CREATE TABLE IF NOT EXISTS subscribers (
                id $id,
                email VARCHAR(255) NOT NULL UNIQUE,
                name VARCHAR(190) NOT NULL DEFAULT '',
                token VARCHAR(64) NOT NULL DEFAULT '',
                active INT NOT NULL DEFAULT 1,
                created_at VARCHAR(40) NOT NULL
            )$suffix",

            "CREATE TABLE IF NOT EXISTS newsletter_templates (
                id $id,
                name VARCHAR(190) NOT NULL,
                subject VARCHAR(255) NOT NULL DEFAULT '',
                body $txt,
                created_at VARCHAR(40) NOT NULL
            )$suffix",
        ];

        foreach ($tables as $sql) {
            $pdo->exec($sql);
        }

        // Meglévő (korábban létrehozott) táblák kiegészítése új oszlopokkal.
        self::migrate($pdo, $driver);
    }

    /**
     * Hiányzó oszlopok pótlása meglévő táblákon (idempotens, driver-független).
     * Új funkciók deploykor a régi adatbázist is naprakésszé teszi.
     */
    public static function migrate(PDO $pdo, string $driver): void
    {
        self::addColumn($pdo, $driver, 'refs', 'url', "VARCHAR(500) NOT NULL DEFAULT ''");
        self::addColumn($pdo, $driver, 'refs', 'featured', 'INT NOT NULL DEFAULT 0');
    }

    private static function addColumn(PDO $pdo, string $driver, string $table, string $col, string $definition): void
    {
        if (self::hasColumn($pdo, $driver, $table, $col)) {
            return;
        }
        try {
            $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$col} {$definition}");
        } catch (\Throwable $e) {
            // pl. már létezik vagy nem támogatott – nem végzetes
        }
    }

    private static function hasColumn(PDO $pdo, string $driver, string $table, string $col): bool
    {
        try {
            if ($driver === 'sqlite') {
                foreach ($pdo->query("PRAGMA table_info({$table})") as $r) {
                    if (($r['name'] ?? '') === $col) {
                        return true;
                    }
                }
                return false;
            }
            $stmt = $pdo->prepare('SHOW COLUMNS FROM ' . $table . ' LIKE ?');
            $stmt->execute([$col]);
            return $stmt->fetch() !== false;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
