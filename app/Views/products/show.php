<?php

use App\Core\View;

/** @var array<string, mixed> $config */
/** @var array<string, mixed> $product */
$currency = $config['app']['currency'];
$inStock = (int) ($product['stock'] ?? 0) > 0;
?>
<section class="section container">
    <nav class="breadcrumb" aria-label="Morzsamenü">
        <a href="/">Főoldal</a> /
        <a href="/termekek">Termékek</a> /
        <span><?= View::e($product['name']) ?></span>
    </nav>

    <div class="product-detail">
        <div class="product-detail__media">
            <img src="/assets/img/products/<?= View::e($product['image'] ?? 'placeholder.svg') ?>"
                 alt="<?= View::e($product['name']) ?>" width="520" height="420">
        </div>

        <div class="product-detail__info">
            <span class="product-card__cat"><?= View::e($product['category_name'] ?? '') ?></span>
            <h1><?= View::e($product['name']) ?></h1>
            <p class="price price--lg"><?= View::price((float) $product['price'], $currency) ?><?php if (!empty($product['unit'])): ?><small class="price-unit">/ <?= View::e($product['unit']) ?></small><?php endif; ?></p>

            <p class="stock <?= $inStock ? 'stock--in' : 'stock--out' ?>">
                <?= $inStock ? '● ' . View::e($product['stock_label'] ?? 'Raktáron') : '○ Jelenleg nem elérhető' ?>
            </p>

            <p class="product-detail__desc"><?= View::e($product['description'] ?? '') ?></p>

            <form method="post" action="/kosar/hozzaad" class="add-to-cart">
                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                <label class="qty-field">
                    <span>Mennyiség</span>
                    <input type="number" name="qty" value="1" min="1" max="99">
                </label>
                <button type="submit" class="btn btn--primary" <?= $inStock ? '' : 'disabled' ?>>
                    Kosárba teszem
                </button>
            </form>

            <ul class="product-detail__meta">
                <li>100% minőségi garancia – csere vagy javítás</li>
                <li>Saját nyergesvontatóval is szállítunk</li>
                <li>Egyedi méret és nagy mennyiség egyeztetés alapján</li>
            </ul>
        </div>
    </div>
</section>
