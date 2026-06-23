<?php

namespace App\Payment;

/**
 * Teszt fizetési szolgáltató: nem hív külső rendszert, hanem a webshop
 * belső "fizetőoldalára" (/fizetes/{token}) irányít, ahol szimulálható a
 * sikeres/sikertelen fizetés. Éles üzemben ezt váltja le egy valódi
 * SimplePay/Barion/Stripe adapter.
 */
final class MockPaymentGateway implements PaymentGateway
{
    public function label(): string
    {
        return 'Bankkártya (teszt)';
    }

    public function start(array $order): string
    {
        return '/fizetes/' . (string) ($order['token'] ?? '');
    }
}
