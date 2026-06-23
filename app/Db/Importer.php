<?php

namespace App\Db;

use App\Leader\LeaderStore;
use App\Map\PoiStore;
use App\Message\MessageStore;
use App\Order\OrderStore;
use App\Reference\ReferenceStore;
use App\Seo\ProductSeoStore;
use App\Settings\SettingsStore;
use PDO;

/**
 * Meglévő fájl-alapú adatok átemelése az adatbázisba (a telepítő hívja).
 * Csak akkor másol, ha a cél DB-tábla még üres – így nem ír felül semmit.
 */
final class Importer
{
    public static function run(PDO $pdo): void
    {
        // Beállítások
        self::guard(static function () use ($pdo): void {
            $file = (new SettingsStore())->all();
            if ($file) {
                (new SettingsStore($pdo))->saveMany($file);
            }
        });

        // Referenciák (fájl-módban a partnerlistából töltődnek fel)
        self::guard(static function () use ($pdo): void {
            $db = new ReferenceStore($pdo);
            if (count($db->all()) === 0) {
                foreach ((new ReferenceStore())->all() as $ref) {
                    unset($ref['id']);
                    $db->save($ref);
                }
            }
        });

        // Térkép-pontok
        self::guard(static function () use ($pdo): void {
            $db = new PoiStore($pdo);
            if (count($db->all()) === 0) {
                foreach ((new PoiStore())->all() as $poi) {
                    unset($poi['id']);
                    $db->save($poi);
                }
            }
        });

        // Vezetők (fájl-módban a config/leaders.php-ból töltődnek fel)
        self::guard(static function () use ($pdo): void {
            $db = new LeaderStore($pdo);
            if (count($db->all()) === 0) {
                foreach ((new LeaderStore())->all() as $leader) {
                    unset($leader['id']);
                    $db->save($leader);
                }
            }
        });

        // Üzenetek
        self::guard(static function () use ($pdo): void {
            $db = new MessageStore($pdo);
            if ($db->count() === 0) {
                foreach (array_reverse((new MessageStore())->all()) as $msg) {
                    $db->save($msg);
                }
            }
        });

        // Rendelések
        self::guard(static function () use ($pdo): void {
            $db = new OrderStore($pdo);
            if (count($db->all()) === 0) {
                foreach ((new OrderStore())->all() as $order) {
                    $db->save($order);
                }
            }
        });

        // Termék-SEO
        self::guard(static function () use ($pdo): void {
            $db = new ProductSeoStore($pdo);
            if (count($db->all()) === 0) {
                foreach ((new ProductSeoStore())->all() as $sku => $row) {
                    $db->save((string) $sku, $row);
                }
            }
        });
    }

    private static function guard(callable $fn): void
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            // best-effort import; egy hiba ne állítsa meg a telepítést
        }
    }
}
