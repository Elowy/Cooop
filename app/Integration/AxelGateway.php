<?php

namespace App\Integration;

/**
 * Az Axel Pro felé vezető egyetlen kapu (port).
 *
 * A webshop kizárólag ezen az interfészen át beszél az Axel Pro
 * készletnyilvántartással és számlázással. Hogy a megvalósítás mögött
 * XML import/export vagy REST API van-e, az implementáció részlete – a
 * shop többi része nem függ tőle. Így az integráció módja később egyetlen
 * osztály lecserélésével változtatható.
 */
interface AxelGateway
{
    /**
     * Teljes terméklista (Axel = a készlet és az árak forrása).
     *
     * @return Product[]
     */
    public function products(): array;

    /** Egy termék a webes azonosító (slug) alapján. */
    public function findProduct(string $slug): ?Product;

    /** Aktuális készlet egy cikkre (SKU), vagy null ha ismeretlen. */
    public function stockFor(string $sku): ?int;

    /**
     * Megrendelés átadása az Axelnek számlázásra (és NAV-jelentésre).
     *
     * @param array<string, mixed> $order Vevő + tételek normalizált formában.
     */
    public function createInvoice(array $order): InvoiceResult;
}
