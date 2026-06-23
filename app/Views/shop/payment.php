<?php

use App\Core\Csrf;
use App\Core\View;

/** @var array<string, mixed> $order */
/** @var bool $failed */
?>
<section class="section section--clear-top">
    <div class="container narrow">
        <div class="pay-card">
            <span class="pay-badge">Teszt fizetés</span>
            <h1 class="display">Bankkártyás fizetés</h1>
            <p class="muted">
                Rendelés: <strong><?= View::e((string) $order['number']) ?></strong> —
                fizetendő: <strong class="gold"><?= View::huf((int) $order['totals']['gross']) ?></strong>
            </p>

            <?php if (!empty($failed)): ?>
                <div class="form-alert">A fizetés sikertelen volt. Próbáld újra.</div>
            <?php endif; ?>

            <p class="pay-note">
                Ez egy szimulált fizetőoldal — valódi szolgáltató (SimplePay / Barion / Stripe)
                a következő körben kerül be. Válassz egy kimenetet:
            </p>

            <form method="post" action="/fizetes/<?= View::e((string) $order['token']) ?>" class="pay-actions">
                <?= Csrf::field() ?>
                <button type="submit" name="result" value="success" class="btn btn--gold btn--lg">Sikeres fizetés</button>
                <button type="submit" name="result" value="fail" class="btn btn--outline btn--lg">Sikertelen fizetés</button>
            </form>
        </div>
    </div>
</section>
