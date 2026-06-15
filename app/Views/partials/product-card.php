<?php

use App\Core\View;

/** @var array<string, mixed> $product */
/** @var array<string, mixed> $config */
$currency = $config['app']['currency'];
?>
<article class="product-card reveal">
    <a href="/termek/<?= View::e($product['slug']) ?>" class="product-card__media">
        <img src="/assets/img/products/<?= View::e($product['image'] ?? 'placeholder.svg') ?>"
             alt="<?= View::e($product['name']) ?>" loading="lazy" width="320" height="220">
        <?php if (!empty($product['featured'])): ?>
            <span class="badge badge--featured">Kiemelt</span>
        <?php endif; ?>
    </a>
    <div class="product-card__body">
        <span class="product-card__cat"><?= View::e($product['category_name'] ?? '') ?></span>
        <h3 class="product-card__title">
            <a href="/termek/<?= View::e($product['slug']) ?>"><?= View::e($product['name']) ?></a>
        </h3>
        <p class="product-card__desc"><?= View::e($product['short'] ?? $product['description'] ?? '') ?></p>
        <div class="product-card__foot">
            <span class="price"><?= View::price((float) $product['price'], $currency) ?><?php if (!empty($product['unit'])): ?><small class="price-unit">/ <?= View::e($product['unit']) ?></small><?php endif; ?></span>
            <form method="post" action="/kosar/hozzaad">
                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                <button type="submit" class="btn btn--small"><?= View::e(t('btn.add')) ?></button>
            </form>
        </div>
    </div>
</article>
