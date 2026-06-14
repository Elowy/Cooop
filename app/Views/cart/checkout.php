<?php

use App\Core\View;

/** @var array<string, mixed> $config */
/** @var array{lines: array<int, array<string, mixed>>, total: float} $cart */
$currency = $config['app']['currency'];

if (empty($cart['lines'])) {
    echo '<section class="section container"><div class="empty-state"><p>A kosarad üres.</p>'
        . '<a href="/termekek" class="btn btn--primary">Vásárlás</a></div></section>';
    return;
}
?>
<section class="page-head">
    <div class="container"><h1>Pénztár</h1></div>
</section>

<section class="section container">
    <div class="checkout-layout">
        <form method="post" action="/penztar" class="checkout-form">
            <h2>Számlázási adatok</h2>
            <div class="form-grid">
                <label>Név
                    <input type="text" name="name" required autocomplete="name">
                </label>
                <label>E-mail
                    <input type="email" name="email" required autocomplete="email">
                </label>
                <label>Telefonszám
                    <input type="tel" name="phone" autocomplete="tel">
                </label>
                <label>Irányítószám
                    <input type="text" name="zip" required autocomplete="postal-code">
                </label>
                <label class="span-2">Cím
                    <input type="text" name="address" required autocomplete="street-address">
                </label>
                <label class="span-2">Megjegyzés
                    <textarea name="note" rows="3"></textarea>
                </label>
            </div>
            <button type="submit" class="btn btn--primary btn--block">Rendelés véglegesítése</button>
        </form>

        <aside class="order-summary">
            <h2>Rendelés összegzése</h2>
            <ul class="order-lines">
                <?php foreach ($cart['lines'] as $line): $p = $line['product']; ?>
                    <li>
                        <span><?= View::e($p['name']) ?> × <?= (int) $line['qty'] ?></span>
                        <span><?= View::price((float) $line['subtotal'], $currency) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p class="order-total">
                <span>Összesen</span>
                <strong><?= View::price((float) $cart['total'], $currency) ?></strong>
            </p>
            <a href="/kosar" class="link-arrow">← Vissza a kosárhoz</a>
        </aside>
    </div>
</section>
