<?php

namespace App\Payment;

/**
 * Fizetési szolgáltató mögötti egységes kapu (mint az AxelGateway).
 * A pénztár ezen az interfészen át indít fizetést; hogy mögötte mock,
 * SimplePay, Barion vagy Stripe van, az később egyetlen osztály cseréje.
 */
interface PaymentGateway
{
    /** Megjelenítendő név (pl. a pénztár fizetési mód választójában). */
    public function label(): string;

    /**
     * Fizetés indítása. Visszaadja az URL-t, ahová a vásárlót küldeni kell
     * (valódi szolgáltatónál a PSP fizetőoldala; mocknál a belső teszt-oldal).
     *
     * @param array<string, mixed> $order
     */
    public function start(array $order): string;
}
