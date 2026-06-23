<?php

use App\Core\View;

/** @var array<string, mixed> $order */

$method = $order['payment']['method'] ?? '';
$payStatus = $order['payment']['status'] ?? '';
$paid = $payStatus === 'paid';
$transfer = $method === 'transfer';
?>
<section class="section section--clear-top">
    <div class="container narrow">
        <div class="confirm-head">
            <span class="confirm-check" aria-hidden="true">✓</span>
            <h1 class="display">Köszönjük a rendelést!</h1>
            <p class="muted">
                Rendelésszám: <strong><?= View::e((string) $order['number']) ?></strong>
                <?php if ($paid): ?> · <span class="tag tag--ok">Fizetve</span>
                <?php elseif ($transfer): ?> · <span class="tag tag--low">Átutalásra vár</span>
                <?php endif; ?>
            </p>
            <p>Visszaigazolást küldünk a <strong><?= View::e((string) $order['customer']['email']) ?></strong> címre.</p>
        </div>

        <?php if ($transfer): ?>
            <div class="info-box">
                <strong>Banki átutalás</strong>
                <p>Kérjük, utald a <strong><?= View::huf((int) $order['totals']['gross']) ?></strong> összeget a
                visszaigazoló e-mailben szereplő számlaszámra, a közleményben a rendelésszámmal
                (<?= View::e((string) $order['number']) ?>).</p>
            </div>
        <?php endif; ?>

        <div class="summary-card">
            <h2>A rendelés tételei</h2>
            <ul class="summary-items">
                <?php foreach ($order['items'] as $it): ?>
                    <li>
                        <span><?= View::e((string) $it['name']) ?> <small>× <?= (int) $it['qty'] ?> <?= View::e((string) $it['unit']) ?></small></span>
                        <span><?= View::huf((int) $it['subtotal']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <div class="summary-total">
                <span>Végösszeg (bruttó)</span>
                <strong class="display"><?= View::huf((int) $order['totals']['gross']) ?></strong>
            </div>
        </div>

        <p class="note">
            Számlázás:
            <?php if (!empty($order['invoice']['ok'])): ?>
                kész — számla: <strong><?= View::e((string) $order['invoice']['number']) ?></strong>.
            <?php else: ?>
                az Axel Pro bekötése után automatikusan elkészül (NAV-jelentéssel együtt).
            <?php endif; ?>
        </p>

        <p class="center-cta"><a href="/webshop" class="btn btn--outline">Vissza a webshopba</a></p>
    </div>
</section>
