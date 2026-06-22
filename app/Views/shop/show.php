<?php

use App\Core\Csrf;
use App\Core\View;
use App\Integration\Product;

/** @var Product $product */
?>
<section class="section section--clear-top product-detail">
    <div class="container">
        <p class="breadcrumb"><a href="/webshop">← Vissza a webshopba</a></p>

        <div class="detail-grid">
            <div class="detail-media reveal" data-icon="<?= View::e($product->icon) ?>" aria-hidden="true">
                <?php if (!$product->inStock()): ?><span class="badge badge--out">Elfogyott</span>
                <?php else: ?><span class="badge">Raktáron · <?= (int) $product->stock ?> <?= View::e($product->unit) ?></span><?php endif; ?>
            </div>

            <div class="detail-copy reveal">
                <p class="eyebrow"><span class="eyebrow-dot"></span> Cikkszám: <?= View::e($product->sku) ?></p>
                <h1 class="display"><?= View::e($product->name) ?></h1>
                <p class="detail-short"><?= View::e($product->short) ?></p>

                <div class="price-block">
                    <span class="price price--lg"><?= View::huf($product->priceGross()) ?></span>
                    <span class="price-unit">/ <?= View::e($product->unit) ?> · bruttó (nettó <?= View::huf($product->priceNet) ?> + <?= (int) $product->vat ?>% áfa)</span>
                </div>

                <form method="post" action="/kosar/hozzaad" class="add-form add-form--detail">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="sku" value="<?= View::e($product->sku) ?>">
                    <label class="qty">
                        <span>Mennyiség (<?= View::e($product->unit) ?>)</span>
                        <input type="number" name="qty" value="1" min="1" max="<?= max(1, (int) $product->stock) ?>" inputmode="numeric">
                    </label>
                    <button type="submit" class="btn btn--gold btn--lg"<?= $product->inStock() ? '' : ' disabled' ?>>
                        Kosárba teszem
                    </button>
                </form>

                <p class="note">Placeholder termékleírás. A részletes adatlap és a képek a következő körben kerülnek be.</p>
            </div>
        </div>
    </div>
</section>
