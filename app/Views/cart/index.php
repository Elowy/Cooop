<?php

use App\Core\View;

/** @var array<string, mixed> $config */
/** @var array{lines: array<int, array<string, mixed>>, total: float} $cart */
$currency = $config['app']['currency'];
?>
<section class="page-head">
    <div class="container"><h1><?= View::e(t('cart.title')) ?></h1></div>
</section>

<section class="section container">
    <?php if (empty($cart['lines'])): ?>
        <div class="empty-state">
            <p><?= View::e(t('cart.empty')) ?></p>
            <a href="/termekek" class="btn btn--primary"><?= View::e(t('cart.start')) ?></a>
        </div>
    <?php else: ?>
        <form method="post" action="/kosar/frissit" class="cart-table-wrap">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th><?= View::e(t('cart.product')) ?></th>
                        <th><?= View::e(t('cart.unit')) ?></th>
                        <th><?= View::e(t('cart.qty')) ?></th>
                        <th><?= View::e(t('cart.subtotal')) ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart['lines'] as $line): $p = $line['product']; ?>
                        <tr>
                            <td data-label="Termék" class="cart-product">
                                <img src="/assets/img/products/<?= View::e($p['image'] ?? 'placeholder.svg') ?>"
                                     alt="" width="64" height="48">
                                <a href="/termek/<?= View::e($p['slug']) ?>"><?= View::e($p['name']) ?></a>
                            </td>
                            <td data-label="Egységár"><?= View::price((float) $p['price'], $currency) ?></td>
                            <td data-label="Mennyiség">
                                <input type="number" name="qty[<?= (int) $p['id'] ?>]"
                                       value="<?= (int) $line['qty'] ?>" min="0" max="99" class="qty-input">
                            </td>
                            <td data-label="Részösszeg"><?= View::price((float) $line['subtotal'], $currency) ?></td>
                            <td data-label="">
                                <button type="submit" formaction="/kosar/torol" name="product_id"
                                        value="<?= (int) $p['id'] ?>" class="link-danger" aria-label="Törlés">✕</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="cart-foot">
                <button type="submit" class="btn btn--ghost"><?= View::e(t('cart.update')) ?></button>
                <div class="cart-summary">
                    <p class="cart-total"><?= View::e(t('cart.total')) ?> <strong><?= View::price((float) $cart['total'], $currency) ?></strong></p>
                    <a href="/penztar" class="btn btn--primary"><?= View::e(t('cart.checkout')) ?></a>
                </div>
            </div>
        </form>
    <?php endif; ?>
</section>
