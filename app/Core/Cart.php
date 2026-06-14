<?php

namespace App\Core;

use App\Models\Product;

/**
 * Munkamenet alapú kosár.
 */
final class Cart
{
    private const KEY = 'cart';

    /** @return array<int, int> termék id => mennyiség */
    public static function items(): array
    {
        return $_SESSION[self::KEY] ?? [];
    }

    public static function add(int $productId, int $qty = 1): void
    {
        $items = self::items();
        $items[$productId] = ($items[$productId] ?? 0) + max(1, $qty);
        $_SESSION[self::KEY] = $items;
    }

    public static function update(int $productId, int $qty): void
    {
        $items = self::items();
        if ($qty <= 0) {
            unset($items[$productId]);
        } else {
            $items[$productId] = $qty;
        }
        $_SESSION[self::KEY] = $items;
    }

    public static function remove(int $productId): void
    {
        $items = self::items();
        unset($items[$productId]);
        $_SESSION[self::KEY] = $items;
    }

    public static function clear(): void
    {
        unset($_SESSION[self::KEY]);
    }

    public static function count(): int
    {
        return array_sum(self::items());
    }

    /**
     * Kosár tartalma termékadatokkal kiegészítve.
     *
     * @return array{lines: array<int, array<string, mixed>>, total: float}
     */
    public static function detailed(): array
    {
        $lines = [];
        $total = 0.0;

        foreach (self::items() as $id => $qty) {
            $product = self::lookup((int) $id);
            if ($product === null) {
                continue;
            }
            $subtotal = (float) $product['price'] * $qty;
            $total += $subtotal;
            $lines[] = [
                'product'  => $product,
                'qty'      => $qty,
                'subtotal' => $subtotal,
            ];
        }

        return ['lines' => $lines, 'total' => $total];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function lookup(int $id): ?array
    {
        foreach (Product::all() as $product) {
            if ((int) $product['id'] === $id) {
                return $product;
            }
        }
        return null;
    }
}
