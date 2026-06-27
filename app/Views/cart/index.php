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
        <h1 class="display"><?= View::e(t('nav.cart')) ?></h1>

        <?php if (!$lines): ?>
            <div class="empty-cart">
                <p><?= View::e(t('shop.cart_empty')) ?></p>
                <a href="/webshop" class="btn btn--gold"><?= View::e(t('home.hero_cta_shop')) ?></a>
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
                            <input type="number" name="qty" value="<?= (int) $line['qty'] ?>" min="1" max="<?= max(1, (int) $p->stock) ?>" aria-label="<?= View::e(t('shop.quantity')) ?>">
                            <button type="submit" class="btn btn--outline btn--sm"><?= View::e(t('shop.update')) ?></button>
                        </form>
                        <span class="cart-sub"><?= View::huf($line['subtotal']) ?></span>
                        <form method="post" action="/kosar/torol" class="cart-del">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="sku" value="<?= View::e($p->sku) ?>">
                            <button type="submit" class="icon-btn" aria-label="<?= View::e(t('shop.delete')) ?>">×</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="cart-summary">
                <div class="cart-totals">
                    <div class="cart-line"><span><?= View::e(t('shop.net_total')) ?></span><span><?= View::huf($netTotal) ?></span></div>
                    <div class="cart-line"><span><?= View::e(t('shop.vat_label')) ?></span><span><?= View::huf($vatTotal) ?></span></div>
                    <div class="cart-total"><span><?= View::e(t('shop.grand_total')) ?></span><strong class="display"><?= View::huf($total) ?></strong></div>
                </div>
                <a href="/penztar" class="btn btn--gold btn--lg btn--block"><?= View::e(t('shop.to_checkout')) ?></a>
                <a href="/webshop" class="cart-continue">← <?= View::e(t('shop.continue_shopping')) ?></a>
                <ul class="trust-row">
                    <li><?= View::e(t('shop.trust_payment')) ?></li>
                    <li><?= View::e(t('shop.trust_invoice')) ?></li>
                    <li><?= View::e(t('shop.trust_delivery')) ?></li>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</section>
