<?php

namespace App\Core;

/**
 * Munkamenet-alapú kosár. Csak SKU → mennyiség párokat tárol; az árakat és
 * a készletet mindig az AxelGateway adja, hogy ne avuljanak el a kosárban.
 */
final class Cart
{
    private const KEY = 'cart';
    private const MAX = 9999;

    /** @return array<string, int> SKU => mennyiség */
    public static function items(): array
    {
        return $_SESSION[self::KEY] ?? [];
    }

    public static function add(string $sku, int $qty = 1): void
    {
        $items = self::items();
        $items[$sku] = self::clamp(($items[$sku] ?? 0) + $qty);
        self::store($items);
    }

    public static function set(string $sku, int $qty): void
    {
        $items = self::items();
        if ($qty <= 0) {
            unset($items[$sku]);
        } else {
            $items[$sku] = self::clamp($qty);
        }
        self::store($items);
    }

    public static function remove(string $sku): void
    {
        $items = self::items();
        unset($items[$sku]);
        self::store($items);
    }

    public static function clear(): void
    {
        unset($_SESSION[self::KEY]);
    }

    /** Összes darabszám (kosár jelvény). */
    public static function count(): int
    {
        return array_sum(self::items());
    }

    private static function clamp(int $qty): int
    {
        return max(1, min(self::MAX, $qty));
    }

    /** @param array<string, int> $items */
    private static function store(array $items): void
    {
        $_SESSION[self::KEY] = $items;
    }
}
