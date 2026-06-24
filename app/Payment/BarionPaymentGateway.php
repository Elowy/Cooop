<?php

declare(strict_types=1);

namespace App\Payment;

use App\Order\OrderStore;

/**
 * Barion fizetési kapu. A pénztár ezen át indít bankkártyás fizetést:
 * felépíti a Payment/Start kérést, elindítja a Barionnál, eltárolja a
 * kapott PaymentId-t a rendelésen, majd visszaadja a Barion fizetőoldalának
 * URL-jét (GatewayUrl), ahová a vásárlót átirányítjuk.
 */
final class BarionPaymentGateway implements PaymentGateway
{
    private BarionClient $client;
    private OrderStore $orders;
    /** @var array<string, mixed> base_url, payee, currency, locale */
    private array $cfg;

    /**
     * @param array<string, mixed> $cfg
     */
    public function __construct(BarionClient $client, OrderStore $orders, array $cfg)
    {
        $this->client = $client;
        $this->orders = $orders;
        $this->cfg = $cfg;
    }

    public function label(): string
    {
        return 'Bankkártya (Barion)';
    }

    public function start(array $order): string
    {
        $token = (string) ($order['token'] ?? '');
        $payload = self::buildStartPayload($order, $this->cfg);

        try {
            $res = $this->client->startPayment($payload);
        } catch (\Throwable $e) {
            error_log('Barion start hiba: ' . $e->getMessage());
            $this->orders->update($token, ['payment' => ['status' => 'failed']]);

            return '/penztar?fizetes=hiba';
        }

        $paymentId = (string) ($res['PaymentId'] ?? '');
        $gatewayUrl = (string) ($res['GatewayUrl'] ?? '');
        if ($paymentId === '' || $gatewayUrl === '') {
            $errors = is_array($res['Errors'] ?? null) ? json_encode($res['Errors']) : '';
            error_log('Barion start válaszhiba: ' . $errors);
            $this->orders->update($token, ['payment' => ['status' => 'failed']]);

            return '/penztar?fizetes=hiba';
        }

        $this->orders->update($token, ['payment' => ['provider' => 'barion', 'payment_id' => $paymentId]]);

        return $gatewayUrl;
    }

    /**
     * A Payment/Start kérés törzse a rendelésből (a POSKey-t a kliens fűzi hozzá).
     *
     * @param array<string, mixed> $order
     * @param array<string, mixed> $cfg
     * @return array<string, mixed>
     */
    public static function buildStartPayload(array $order, array $cfg): array
    {
        $token = (string) ($order['token'] ?? '');
        $base = rtrim((string) ($cfg['base_url'] ?? ''), '/');
        $payee = (string) ($cfg['payee'] ?? '');
        $currency = (string) ($cfg['currency'] ?? 'HUF');
        $locale = (string) ($cfg['locale'] ?? 'hu-HU');

        $items = [];
        foreach (($order['items'] ?? []) as $it) {
            $qty = (float) ($it['qty'] ?? 1);
            $unitGross = (float) ($it['price_gross'] ?? 0);
            $items[] = [
                'Name' => (string) ($it['name'] ?? 'Termék'),
                'Description' => (string) ($it['name'] ?? 'Termék'),
                'Quantity' => $qty,
                'Unit' => (string) ($it['unit'] ?? 'db'),
                'UnitPrice' => $unitGross,
                'ItemTotal' => (float) ($it['subtotal'] ?? ($unitGross * $qty)),
            ];
        }

        $total = (float) ($order['totals']['gross'] ?? 0);

        return [
            'PaymentType' => 'Immediate',
            'PaymentRequestId' => $token,
            'OrderNumber' => (string) ($order['number'] ?? ''),
            'FundingSources' => ['All'],
            'GuestCheckOut' => true,
            'InitiateRecurrence' => false,
            'Locale' => $locale,
            'Currency' => $currency,
            'RedirectUrl' => $base . '/barion/vissza?token=' . $token,
            'CallbackUrl' => $base . '/barion/callback',
            'Transactions' => [[
                'POSTransactionId' => $token,
                'Payee' => $payee,
                'Total' => $total,
                'Comment' => 'Net-Trade rendelés ' . (string) ($order['number'] ?? ''),
                'Items' => $items,
            ]],
        ];
    }

    /** A Barion fizetési állapot sikeresnek számít-e (teljesült fizetés). */
    public static function isPaid(string $barionStatus): bool
    {
        return $barionStatus === 'Succeeded';
    }

    /** A Barion fizetési állapot végleges kudarc-e (nincs értelme tovább várni). */
    public static function isFinalFailure(string $barionStatus): bool
    {
        return in_array($barionStatus, ['Canceled', 'Expired', 'Failed'], true);
    }
}
