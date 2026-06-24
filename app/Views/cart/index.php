<?php

use App\Core\Csrf;
use App\Core\View;

/** @var array<int, array{product: \App\Integration\Product, qty: int, subtotal: int}> $lines */
/** @var int $total */
/** @var array<string, string[]> $images */

$images = $images ?? [];
$netTotal = 0;
foreach ($lines as $line) {
    $netTotal += $line['product']->priceNet * $line['qty'];
}
$vatTotal = $total - $netTotal;
?>
<section class="section section--clear-top">
    <div class="container cart-wrap">
        <h1 class="display">Kosár</h1>

        <?php if (!$lines): ?>
            <div class="empty-cart">
                <p>A kosarad jelenleg üres.</p>
                <a href="/webshop" class="btn btn--gold">Irány a webshop</a>
            </div>
        <?php else: ?>
            <div class="cart-table">
                <?php foreach ($lines as $line): $p = $line['product']; $cImg = $images[$p->sku][0] ?? null; ?>
                    <div class="cart-row">
                        <span class="cart-thumb<?= $cImg ? ' has-image' : '' ?>" data-icon="<?= View::e($p->icon) ?>" aria-hidden="true"><?php if ($cImg): ?><img src="/uploads/products/<?= View::e($cImg) ?>" alt="" loading="lazy"><?php endif; ?></span>
                        <div class="cart-info">
                            <a href="/termek/<?= View::e($p->slug) ?>" class="cart-name"><?= View::e($p->name) ?></a>
                            <small><?= View::huf($p->priceGross()) ?> / <?= View::e($p->unit) ?></small>
                        </div>
                        <form method="post" action="/kosar/frissit" class="cart-qty">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="sku" value="<?= View::e($p->sku) ?>">
                            <input type="number" name="qty" value="<?= (int) $line['qty'] ?>" min="1" max="<?= max(1, (int) $p->stock) ?>" aria-label="Mennyiség">
                            <button type="submit" class="btn btn--outline btn--sm">Frissít</button>
                        </form>
                        <span class="cart-sub"><?= View::huf($line['subtotal']) ?></span>
                        <form method="post" action="/kosar/torol" class="cart-del">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="sku" value="<?= View::e($p->sku) ?>">
                            <button type="submit" class="icon-btn" aria-label="Törlés">×</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="cart-summary">
                <div class="cart-totals">
                    <div class="cart-line"><span>Nettó összesen</span><span><?= View::huf($netTotal) ?></span></div>
                    <div class="cart-line"><span>ÁFA</span><span><?= View::huf($vatTotal) ?></span></div>
                    <div class="cart-total"><span>Végösszeg (bruttó)</span><strong class="display"><?= View::huf($total) ?></strong></div>
                </div>
                <a href="/penztar" class="btn btn--gold btn--lg btn--block">Tovább a pénztárhoz</a>
                <a href="/webshop" class="cart-continue">← Vásárlás folytatása</a>
                <ul class="trust-row">
                    <li>Biztonságos fizetés</li>
                    <li>Számla és garancia</li>
                    <li>Gyors kiszállítás</li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</section>
