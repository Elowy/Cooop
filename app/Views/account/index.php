<?php

use App\Core\Csrf;
use App\Core\View;

/** @var array<string, mixed> $me */
/** @var array<int, array<string, mixed>> $orders */

// Rendelés-státusz → vásárlóbarát címke + tag-stílus.
$statusLabel = [
    'pending' => ['Fizetésre vár', 'low'],
    'placed' => ['Feldolgozás alatt', 'low'],
    'paid' => ['Fizetve', 'ok'],
];
?>
<section class="section section--clear-top">
    <div class="container account-wrap">
        <div class="account-head">
            <div>
                <h1 class="display">Fiókom</h1>
                <p class="muted">Belépve: <strong><?= View::e((string) $me['name']) ?></strong> · <?= View::e((string) $me['email']) ?></p>
            </div>
            <form method="post" action="/kilepes" class="account-logout">
                <?= Csrf::field() ?>
                <button type="submit" class="btn btn--outline btn--sm">Kilépés</button>
            </form>
        </div>

        <h2 class="account-sub">Rendeléseim</h2>
        <?php if (!$orders): ?>
            <div class="empty-cart">
                <p>Még nincs rendelésed.</p>
                <a href="/webshop" class="btn btn--gold">Irány a webshop</a>
            </div>
        <?php else: ?>
            <div class="order-list">
                <?php foreach ($orders as $o):
                    [$lbl, $tag] = $statusLabel[$o['status'] ?? ''] ?? [(string) ($o['status'] ?? '–'), 'low'];
                    $ts = strtotime((string) ($o['created'] ?? ''));
                    $date = $ts ? date('Y.m.d.', $ts) : ''; ?>
                    <a class="order-row" href="/rendeles/<?= View::e((string) $o['token']) ?>">
                        <div class="order-meta">
                            <strong><?= View::e((string) $o['number']) ?></strong>
                            <small><?= View::e($date) ?> · <?= count($o['items'] ?? []) ?> tétel</small>
                        </div>
                        <span class="tag tag--<?= View::e($tag) ?>"><?= View::e($lbl) ?></span>
                        <strong class="order-total"><?= View::huf((int) ($o['totals']['gross'] ?? 0)) ?></strong>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
