<?php

namespace App\Integration;

use App\Catalog\ProductI18nStore;
use App\Core\Lang;

/**
 * Az Axel-kapu köré tett réteg, amely a termékek nevét és rövid leírását a
 * látogató nyelvére fordítja (a ProductI18nStore SKU-szerinti fordításaiból).
 * A készlet, az ár és a számlázás érintetlen marad; magyar nyelven vagy hiányzó
 * fordítás esetén az eredeti (Axelből jövő) szöveg marad.
 */
final class LocalizingAxelGateway implements AxelGateway
{
    /** @var array<string, array<string, array<string, string>>>|null Kérésenkénti gyorsítótár. */
    private ?array $map = null;

    public function __construct(
        private readonly AxelGateway $inner,
        private readonly ProductI18nStore $i18n,
    ) {
    }

    public function products(): array
    {
        return array_map([$this, 'localize'], $this->inner->products());
    }

    public function findProduct(string $slug): ?Product
    {
        $product = $this->inner->findProduct($slug);
        return $product !== null ? $this->localize($product) : null;
    }

    public function stockFor(string $sku): ?int
    {
        return $this->inner->stockFor($sku);
    }

    public function createInvoice(array $order): InvoiceResult
    {
        return $this->inner->createInvoice($order);
    }

    private function localize(Product $product): Product
    {
        $loc = Lang::locale();
        if ($loc === 'hu') {
            return $product;
        }
        $this->map ??= $this->i18n->all();
        $tr = $this->map[$product->sku][$loc] ?? null;
        if (!is_array($tr)) {
            return $product;
        }
        return $product->withText((string) ($tr['name'] ?? ''), (string) ($tr['short'] ?? ''));
    }
}
