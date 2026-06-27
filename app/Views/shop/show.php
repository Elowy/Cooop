<?php

use App\Catalog\Categories;
use App\Core\Csrf;
use App\Core\View;
use App\Integration\Product;

/** @var Product $product */
/** @var string[] $catPath */
/** @var Categories $cats */
/** @var Product[] $related */
/** @var array<string, mixed> $config */

$related = $related ?? [];
$images = $images ?? [];
$LOW = 15;
$phone = (string) ($config['contact']['phone'] ?? '');

// Készlet-badge (a galériában és az ikonos nézetben is ezt használjuk).
ob_start(); ?>
<?php if (!$product->inStock()): ?><span class="badge badge--out"><?= View::e(t('shop.out_of_stock')) ?></span>
<?php elseif ($product->stock <= $LOW): ?><span class="badge badge--low"><?= View::e(t('shop.only_left', ['n' => (int) $product->stock, 'unit' => $product->unit])) ?></span>
<?php else: ?><span class="badge"><?= View::e(t('shop.in_stock')) ?> · <?= (int) $product->stock ?> <?= View::e($product->unit) ?></span><?php endif;
$badge = ob_get_clean();
?>
<section class="section section--clear-top product-detail">
    <div class="container">
        <nav class="breadcrumb" aria-label="Morzsamenü">
            <a href="/webshop"><?= View::e(t('nav.shop')) ?></a>
            <?php foreach ($catPath as $key): ?>
                <span class="sep">/</span>
                <a href="/webshop?kat=<?= urlencode($key) ?>"><?= View::e($cats->name($key)) ?></a>
            <?php endforeach; ?>
            <span class="sep">/</span>
            <span class="current"><?= View::e($product->name) ?></span>
        </nav>

        <div class="detail-grid">
            <?php if ($images): ?>
                <div class="detail-media detail-gallery reveal">
                    <div class="gallery-main">
                        <img id="gallery-main-img" src="/uploads/products/<?= View::e($images[0]) ?>" alt="<?= View::e($product->name) ?>">
                        <?= $badge ?>
                    </div>
                    <?php if (count($images) > 1): ?>
                        <div class="gallery-thumbs">
                            <?php foreach ($images as $i => $f): ?>
                                <button type="button" class="gallery-thumb<?= $i === 0 ? ' is-active' : '' ?>" data-gallery-thumb="/uploads/products/<?= View::e($f) ?>" aria-label="<?= View::e($product->name) ?> – <?= $i + 1 ?>. kép">
                                    <img src="/uploads/products/<?= View::e($f) ?>" alt="" loading="lazy">
                                </button>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="detail-media reveal" data-icon="<?= View::e($product->icon) ?>" aria-hidden="true">
                    <?= $badge ?>
                </div>
            <?php endif; ?>

            <div class="detail-copy reveal">
                <p class="eyebrow"><span class="eyebrow-dot"></span> <?= View::e(t('shop.sku')) ?>: <?= View::e($product->sku) ?></p>
                <h1 class="display"><?= View::e($product->name) ?></h1>
                <p class="detail-short"><?= View::e($product->short) ?></p>

                <div class="price-block">
                    <span class="price price--lg"><?= View::huf($product->priceGross()) ?></span>
                    <span class="price-unit">/ <?= View::e($product->unit) ?> · <?= View::e(t('shop.gross')) ?> (<?= View::e(t('shop.net')) ?> <?= View::huf($product->priceNet) ?> + <?= (int) $product->vat ?>% <?= View::e(t('shop.vat')) ?>)</span>
                </div>

                <form method="post" action="/kosar/hozzaad" class="add-form add-form--detail">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="sku" value="<?= View::e($product->sku) ?>">
                    <label class="qty">
                        <span><?= View::e(t('shop.quantity')) ?> (<?= View::e($product->unit) ?>)</span>
                        <input type="number" name="qty" value="1" min="1" max="<?= max(1, (int) $product->stock) ?>" inputmode="numeric"<?= $product->inStock() ? '' : ' disabled' ?>>
                    </label>
                    <button type="submit" class="btn btn--gold btn--lg"<?= $product->inStock() ? '' : ' disabled' ?>>
                        <?= View::e(t('shop.add_to_cart')) ?>
                    </button>
                </form>

                <ul class="assurance">
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
                        <span><strong><?= View::e(t('shop.assure_invoice_t')) ?></strong><br><?= View::e(t('shop.assure_invoice_d')) ?></span>
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 7h11v8H3z"/><path d="M14 10h4l3 3v2h-7z"/><circle cx="7" cy="17" r="1.6"/><circle cx="17" cy="17" r="1.6"/></svg>
                        <span><strong><?= View::e(t('shop.assure_delivery_t')) ?></strong><br><?= View::e(t('shop.assure_delivery_d')) ?></span>
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                        <span><strong><?= View::e(t('shop.assure_payment_t')) ?></strong><br><?= View::e(t('shop.assure_payment_d')) ?></span>
                    </li>
                    <?php if ($phone !== ''): ?>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 4h4l2 5-3 2a12 12 0 0 0 5 5l2-3 5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/></svg>
                        <span><strong><?= View::e(t('shop.assure_question_t')) ?></strong><br><a href="tel:<?= View::e(preg_replace('/\s+/', '', $phone)) ?>"><?= View::e($phone) ?></a></span>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <div class="detail-specs">
            <h2><?= View::e(t('shop.specs')) ?></h2>
            <table class="spec-table">
                <tbody>
                    <tr><th><?= View::e(t('shop.sku')) ?></th><td><?= View::e($product->sku) ?></td></tr>
                    <tr><th><?= View::e(t('shop.spec_category')) ?></th><td><a href="/webshop?kat=<?= urlencode($product->category) ?>"><?= View::e($cats->name($product->category)) ?></a></td></tr>
                    <tr><th><?= View::e(t('shop.spec_packaging')) ?></th><td><?= View::e($product->unit) ?></td></tr>
                    <tr><th><?= View::e(t('shop.spec_unit_price_net')) ?></th><td><?= View::huf($product->priceNet) ?> / <?= View::e($product->unit) ?></td></tr>
                    <tr><th><?= View::e(t('shop.vat_label')) ?></th><td><?= (int) $product->vat ?>%</td></tr>
                    <tr><th><?= View::e(t('shop.spec_unit_price_gross')) ?></th><td><?= View::huf($product->priceGross()) ?> / <?= View::e($product->unit) ?></td></tr>
                    <tr><th><?= View::e(t('shop.spec_availability')) ?></th><td><?= $product->inStock() ? View::e(t('shop.in_stock')) . ' (' . (int) $product->stock . ' ' . View::e($product->unit) . ')' : View::e(t('shop.out_of_stock')) ?></td></tr>
                </tbody>
            </table>
            <p class="note"><?= View::e(t('shop.stock_note')) ?></p>
        </div>

        <?php if ($related): ?>
            <div class="related">
                <h2><?= View::e(t('shop.related')) ?></h2>
                <div class="card-grid related-grid">
                    <?php foreach ($related as $r): ?>
                        <article class="card product-card reveal">
                            <a class="product-media" href="/termek/<?= View::e($r->slug) ?>" data-icon="<?= View::e($r->icon) ?>" aria-label="<?= View::e($r->name) ?>">
                                <?php if (!$r->inStock()): ?><span class="badge badge--out"><?= View::e(t('shop.out_of_stock')) ?></span>
                                <?php else: ?><span class="badge"><?= View::e(t('shop.in_stock')) ?></span><?php endif; ?>
                            </a>
                            <h3><a href="/termek/<?= View::e($r->slug) ?>"><?= View::e($r->name) ?></a></h3>
                            <div class="price-row">
                                <span class="price"><?= View::huf($r->priceGross()) ?></span>
                                <span class="price-unit">/ <?= View::e($r->unit) ?></span>
                            </div>
                            <a href="/termek/<?= View::e($r->slug) ?>" class="btn btn--outline btn--sm btn--block"><?= View::e(t('shop.view')) ?></a>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php
$base = rtrim((string) ($config['app']['url'] ?? ''), '/');
$ldFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG;

$productLd = [
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $product->name,
    'sku' => $product->sku,
    'category' => $cats->name($product->category),
    'description' => $product->short,
    'brand' => ['@type' => 'Brand', 'name' => (string) ($config['app']['name'] ?? '')],
    'offers' => [
        '@type' => 'Offer',
        'url' => $base . '/termek/' . rawurlencode($product->slug),
        'priceCurrency' => (string) ($config['shop']['currency'] ?? 'HUF'),
        'price' => (string) $product->priceGross(),
        'availability' => 'https://schema.org/' . ($product->inStock() ? 'InStock' : 'OutOfStock'),
        'itemCondition' => 'https://schema.org/NewCondition',
    ],
];
if ($images) {
    $productLd['image'] = array_map(static fn ($f) => $base . '/uploads/products/' . $f, $images);
}

$breadcrumbLd = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => []];
$crumbs = [['name' => 'Webshop', 'url' => $base . '/webshop']];
foreach ($catPath as $key) {
    $crumbs[] = ['name' => $cats->name($key), 'url' => $base . '/webshop?kat=' . urlencode($key)];
}
$crumbs[] = ['name' => $product->name, 'url' => $base . '/termek/' . rawurlencode($product->slug)];
foreach ($crumbs as $i => $c) {
    $breadcrumbLd['itemListElement'][] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $c['name'], 'item' => $c['url']];
}
?>
<script type="application/ld+json"><?= json_encode($productLd, $ldFlags) ?></script>
<script type="application/ld+json"><?= json_encode($breadcrumbLd, $ldFlags) ?></script>
