<?php

namespace App\Integration;

/**
 * Termék érték-objektum. Az adatok forrása éles üzemben az Axel Pro
 * (az AxelGateway adapterén keresztül), most a placeholder katalógus.
 */
final class Product
{
    public function __construct(
        public readonly string $sku,
        public readonly string $slug,
        public readonly string $category,
        public readonly string $name,
        public readonly string $unit,
        public readonly int $priceNet,
        public readonly int $vat,
        public readonly int $stock,
        public readonly string $icon,
        public readonly string $short,
    ) {
    }

    /** @param array<string, mixed> $row */
    public static function fromArray(array $row): self
    {
        return new self(
            (string) $row['sku'],
            (string) $row['slug'],
            (string) $row['category'],
            (string) $row['name'],
            (string) $row['unit'],
            (int) $row['price_net'],
            (int) $row['vat'],
            (int) $row['stock'],
            (string) $row['icon'],
            (string) $row['short'],
        );
    }

    public function priceGross(): int
    {
        return (int) round($this->priceNet * (1 + $this->vat / 100));
    }

    public function inStock(): bool
    {
        return $this->stock > 0;
    }

    /** Új példány lefordított név / rövid leírással; üres értéknél az eredeti marad. */
    public function withText(string $name, string $short): self
    {
        return new self(
            $this->sku,
            $this->slug,
            $this->category,
            $name !== '' ? $name : $this->name,
            $this->unit,
            $this->priceNet,
            $this->vat,
            $this->stock,
            $this->icon,
            $short !== '' ? $short : $this->short,
        );
    }
}
