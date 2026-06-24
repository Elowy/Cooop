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
$LOW = 15;
$phone = (string) ($config['contact']['phone'] ?? '');
?>
<section class="section section--clear-top product-detail">
    <div class="container">
        <nav class="breadcrumb" aria-label="Morzsamenü">
            <a href="/webshop">Webshop</a>
            <?php foreach ($catPath as $key): ?>
                <span class="sep">/</span>
                <a href="/webshop?kat=<?= urlencode($key) ?>"><?= View::e($cats->name($key)) ?></a>
            <?php endforeach; ?>
            <span class="sep">/</span>
            <span class="current"><?= View::e($product->name) ?></span>
        </nav>

        <div class="detail-grid">
            <div class="detail-media reveal" data-icon="<?= View::e($product->icon) ?>" aria-hidden="true">
                <?php if (!$product->inStock()): ?>
                    <span class="badge badge--out">Elfogyott</span>
                <?php elseif ($product->stock <= $LOW): ?>
                    <span class="badge badge--low">Már csak <?= (int) $product->stock ?> <?= View::e($product->unit) ?></span>
                <?php else: ?>
                    <span class="badge">Raktáron · <?= (int) $product->stock ?> <?= View::e($product->unit) ?></span>
                <?php endif; ?>
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
                        <input type="number" name="qty" value="1" min="1" max="<?= max(1, (int) $product->stock) ?>" inputmode="numeric"<?= $product->inStock() ? '' : ' disabled' ?>>
                    </label>
                    <button type="submit" class="btn btn--gold btn--lg"<?= $product->inStock() ? '' : ' disabled' ?>>
                        Kosárba teszem
                    </button>
                </form>

                <ul class="assurance">
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 12l2 2 4-4"/><circle cx="12" cy="12" r="9"/></svg>
                        <span><strong>Számla és garancia</strong><br>Minden vásárlásról számlát adunk.</span>
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 7h11v8H3z"/><path d="M14 10h4l3 3v2h-7z"/><circle cx="7" cy="17" r="1.6"/><circle cx="17" cy="17" r="1.6"/></svg>
                        <span><strong>Szállítás vagy átvétel</strong><br>Futárszolgálattal vagy személyesen.</span>
                    </li>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                        <span><strong>Biztonságos fizetés</strong><br>Bankkártyával vagy átutalással.</span>
                    </li>
                    <?php if ($phone !== ''): ?>
                    <li>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 4h4l2 5-3 2a12 12 0 0 0 5 5l2-3 5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z"/></svg>
                        <span><strong>Kérdésed van?</strong><br><a href="tel:<?= View::e(preg_replace('/\s+/', '', $phone)) ?>"><?= View::e($phone) ?></a></span>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <div class="detail-specs">
            <h2>Termékadatok</h2>
            <table class="spec-table">
                <tbody>
                    <tr><th>Cikkszám</th><td><?= View::e($product->sku) ?></td></tr>
                    <tr><th>Kategória</th><td><a href="/webshop?kat=<?= urlencode($product->category) ?>"><?= View::e($cats->name($product->category)) ?></a></td></tr>
                    <tr><th>Kiszerelés</th><td><?= View::e($product->unit) ?></td></tr>
                    <tr><th>Nettó egységár</th><td><?= View::huf($product->priceNet) ?> / <?= View::e($product->unit) ?></td></tr>
                    <tr><th>ÁFA</th><td><?= (int) $product->vat ?>%</td></tr>
                    <tr><th>Bruttó egységár</th><td><?= View::huf($product->priceGross()) ?> / <?= View::e($product->unit) ?></td></tr>
                    <tr><th>Elérhetőség</th><td><?= $product->inStock() ? 'Raktáron (' . (int) $product->stock . ' ' . View::e($product->unit) . ')' : 'Elfogyott' ?></td></tr>
                </tbody>
            </table>
            <p class="note">A feltüntetett készlet tájékoztató jellegű; nagyobb mennyiség esetén kérjük, egyeztess velünk.</p>
        </div>

        <?php if ($related): ?>
            <div class="related">
                <h2>Hasonló termékek</h2>
                <div class="card-grid related-grid">
                    <?php foreach ($related as $r): ?>
                        <article class="card product-card reveal">
                            <a class="product-media" href="/termek/<?= View::e($r->slug) ?>" data-icon="<?= View::e($r->icon) ?>" aria-label="<?= View::e($r->name) ?>">
                                <?php if (!$r->inStock()): ?><span class="badge badge--out">Elfogyott</span>
                                <?php else: ?><span class="badge">Raktáron</span><?php endif; ?>
                            </a>
                            <h3><a href="/termek/<?= View::e($r->slug) ?>"><?= View::e($r->name) ?></a></h3>
                            <div class="price-row">
                                <span class="price"><?= View::huf($r->priceGross()) ?></span>
                                <span class="price-unit">/ <?= View::e($r->unit) ?></span>
                            </div>
                            <a href="/termek/<?= View::e($r->slug) ?>" class="btn btn--outline btn--sm btn--block">Megnézem</a>
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
