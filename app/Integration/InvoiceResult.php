<?php

namespace App\Integration;

/**
 * Egy számlázási kísérlet eredménye (Axel felé továbbított rendelésre).
 */
final class InvoiceResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly ?string $invoiceNumber = null,
        public readonly ?string $message = null,
    ) {
    }
}
