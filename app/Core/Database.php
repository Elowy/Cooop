<?php

namespace App\Core;

use PDO;
use PDOException;

/**
 * Egyszerű PDO alapú adatbázis-kapcsolat singleton.
 *
 * Ha az adatbázis nem érhető el (pl. fejlesztés közben), a getConnection()
 * null-t ad vissza, a modellek pedig demó adatokra esnek vissza.
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
        $db = $config['database'];

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
        } catch (PDOException) {
            self::$instance = null;
        }

        return self::$instance;
    }
}
