<?php

namespace App\Db;

use PDO;

/**
 * PDO kapcsolat (MySQL élesben, SQLite helyi teszthez).
 */
final class Database
{
    private static ?PDO $pdo = null;

    /** @param array<string, mixed> $cfg */
    public static function make(array $cfg): PDO
    {
        $driver = $cfg['driver'] ?? 'mysql';

        if ($driver === 'sqlite') {
            $path = $cfg['name'] ?: dirname(__DIR__, 2) . '/storage/app.sqlite';
            $dir = dirname($path);
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            $pdo = new PDO('sqlite:' . $path);
        } else {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $cfg['host'] ?? 'localhost',
                $cfg['port'] ?? '3306',
                $cfg['name'] ?? ''
            );
            $pdo = new PDO($dsn, (string) ($cfg['user'] ?? ''), (string) ($cfg['pass'] ?? ''));
        }

        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    }

    /** @param array<string, mixed> $cfg */
    public static function instance(array $cfg): PDO
    {
        return self::$pdo ??= self::make($cfg);
    }
}
