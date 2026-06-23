<?php

namespace App\Integration;

/**
 * Ideiglenes Axel-adapter: a placeholder katalógusból szolgál ki adatokat,
 * a számlázást pedig még nem köti be. Éles bekötéskor ezt váltja le egy
 * XmlAxelGateway (fájl import/export) vagy egy RestAxelGateway (HTTP API)
 * – az AxelGateway interfész változatlan marad.
 */
final class MockAxelGateway implements AxelGateway
{
    /** @var Product[] */
    private array $products;

    public function __construct(?string $catalogPath = null)
    {
        $path = $catalogPath ?? dirname(__DIR__, 2) . '/config/catalog.php';
        $rows = is_file($path) ? (array) require $path : [];
        $this->products = array_map([Product::class, 'fromArray'], $rows);
    }

    public function products(): array
    {
        return $this->products;
    }

    public function findProduct(string $slug): ?Product
    {
        foreach ($this->products as $product) {
            if ($product->slug === $slug) {
                return $product;
            }
        }
        return null;
    }

    public function stockFor(string $sku): ?int
    {
        foreach ($this->products as $product) {
            if ($product->sku === $sku) {
                return $product->stock;
            }
        }
        return null;
    }

    public function createInvoice(array $order): InvoiceResult
    {
        // Az éles Axel-bekötés (XML vagy REST) ide kerül.
        return new InvoiceResult(
            false,
            null,
            'Az Axel Pro számlázás még nincs bekötve (mock adapter).'
        );
    }
}
