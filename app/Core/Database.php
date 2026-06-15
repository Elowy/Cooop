<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * Egyszerű PDO alapú adatbázis-kapcsolat.
 *
 * Két meghajtót támogat:
 *  - sqlite : fájl alapú, külső szerver nélkül (alapértelmezett, ha a
 *             storage/database.sqlite létezik) – ideális fejlesztéshez,
 *  - mysql  : éles környezethez.
 *
 * Ha nincs elérhető adatbázis, a getConnection() null-t ad vissza, és a
 * modellek a demó adatokra esnek vissza.
 */
final class Database
{
    private static ?PDO $instance = null;
    private static bool $attempted = false;

    public static function getConnection(): ?PDO
    {
        if (self::$attempted) {
            return self::$instance;
        }

        self::$attempted = true;
        $config = require dirname(__DIR__, 2) . '/config/config.php';

        try {
            self::$instance = self::connect($config['database']);
        } catch (PDOException) {
            self::$instance = null;
        }

        return self::$instance;
    }

    /**
     * A ténylegesen használt meghajtó neve (sqlite vagy mysql).
     *
     * @param array<string, mixed> $db
     */
    public static function resolveDriver(array $db): string
    {
        $driver = (string) ($db['driver'] ?? '');
        if ($driver !== '') {
            return $driver;
        }

        // Automatikus: ha van SQLite fájl, azt használjuk, különben MySQL.
        return is_file((string) ($db['sqlite'] ?? '')) ? 'sqlite' : 'mysql';
    }

    /**
     * Új PDO kapcsolat felépítése (a telepítő is ezt használja).
     *
     * @param array<string, mixed> $db
     */
    public static function connect(array $db, ?string $driver = null): PDO
    {
        $driver = $driver ?: self::resolveDriver($db);

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        if ($driver === 'sqlite') {
            $path = (string) $db['sqlite'];
            $dir = dirname($path);
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            $pdo = new PDO('sqlite:' . $path, null, null, $options);
            $pdo->exec('PRAGMA foreign_keys = ON');
            return $pdo;
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $db['host'],
            $db['port'],
            $db['name'],
            $db['charset']
        );

        return new PDO($dsn, $db['user'], $db['password'], $options);
    }
}
