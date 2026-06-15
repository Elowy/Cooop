<?php

use App\Core\View;

/** @var array<string, mixed> $config */
/** @var array{lines: array<int, array<string, mixed>>, total: float} $cart */
$currency = $config['app']['currency'];

if (empty($cart['lines'])) {
    echo '<section class="section container"><div class="empty-state"><p>' . View::e(t('cart.empty')) . '</p>'
        . '<a href="/termekek" class="btn btn--primary">' . View::e(t('cart.start')) . '</a></div></section>';
    return;
}
?>
<section class="page-head">
    <div class="container"><h1><?= View::e(t('checkout.title')) ?></h1></div>
</section>

<section class="section container">
    <div class="checkout-layout">
        <form method="post" action="/penztar" class="checkout-form">
            <h2><?= View::e(t('checkout.billing')) ?></h2>
            <div class="form-grid">
                <label><?= View::e(t('form.name')) ?>
                    <input type="text" name="name" required autocomplete="name">
                </label>
                <label><?= View::e(t('form.email')) ?>
                    <input type="email" name="email" required autocomplete="email">
                </label>
                <label><?= View::e(t('checkout.phone')) ?>
                    <input type="tel" name="phone" autocomplete="tel">
                </label>
                <label><?= View::e(t('checkout.zip')) ?>
                    <input type="text" name="zip" required autocomplete="postal-code">
                </label>
                <label class="span-2"><?= View::e(t('checkout.addr')) ?>
                    <input type="text" name="address" required autocomplete="street-address">
                </label>
                <label class="span-2"><?= View::e(t('checkout.note')) ?>
                    <textarea name="note" rows="3"></textarea>
                </label>
            </div>
            <button type="submit" class="btn btn--primary btn--block"><?= View::e(t('checkout.submit')) ?></button>
        </form>

        <aside class="order-summary">
            <h2><?= View::e(t('checkout.summary')) ?></h2>
            <ul class="order-lines">
                <?php foreach ($cart['lines'] as $line): $p = $line['product']; ?>
                    <li>
                        <span><?= View::e($p['name']) ?> × <?= (int) $line['qty'] ?></span>
                        <span><?= View::price((float) $line['subtotal'], $currency) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p class="order-total">
                <span><?= View::e(t('checkout.total')) ?></span>
                <strong><?= View::price((float) $cart['total'], $currency) ?></strong>
            </p>
            <a href="/kosar" class="link-arrow"><?= View::e(t('checkout.back')) ?></a>
        </aside>
    </div>
</section>
