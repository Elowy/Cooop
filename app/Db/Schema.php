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
        ];

        foreach ($tables as $sql) {
            $pdo->exec($sql);
        }
    }
}
