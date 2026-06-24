<?php

use App\Core\Csrf;
use App\Core\View;

/** @var array<string, mixed> $order */
/** @var bool $failed */
?>
<section class="section section--clear-top">
    <div class="container narrow">
        <div class="pay-card">
            <span class="pay-lock" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
            </span>
            <p class="eyebrow eyebrow--center"><span class="eyebrow-dot"></span> Biztonságos fizetés</p>
            <h1 class="display">Bankkártyás fizetés</h1>

            <div class="pay-summary">
                <div><span>Rendelésszám</span><strong><?= View::e((string) $order['number']) ?></strong></div>
                <div><span>Fizetendő</span><strong class="gold"><?= View::huf((int) $order['totals']['gross']) ?></strong></div>
            </div>

            <?php if (!empty($failed)): ?>
                <div class="form-alert">A fizetés nem sikerült. Kérjük, próbáld újra, vagy válassz másik fizetési módot.</div>
            <?php endif; ?>

            <form method="post" action="/fizetes/<?= View::e((string) $order['token']) ?>" class="pay-actions">
                <?= Csrf::field() ?>
                <button type="submit" name="result" value="success" class="btn btn--gold btn--lg btn--block">Fizetés jóváhagyása</button>
                <button type="submit" name="result" value="fail" class="btn btn--outline btn--block">Fizetés megszakítása</button>
            </form>

            <p class="pay-note">
                Demó fizetési környezet — az éles bankkártyás fizetés (SimplePay / Barion / Stripe)
                bekötése folyamatban. Valódi terhelés nem történik.
            </p>
        </div>
    </div>
</section>
