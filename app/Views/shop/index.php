<?php

use App\Catalog\Categories;
use App\Core\Csrf;
use App\Core\View;
use App\Integration\Product;

/** @var Product[] $products */
/** @var array<int, array<string, mixed>> $catsTree */
/** @var string $activeCat */
/** @var string[] $activePath */
/** @var string $sort */
/** @var string $q */
/** @var int|null $min */
/** @var int|null $max */
/** @var bool $inStockOnly */
/** @var Categories $cats */
/** @var array<string, string[]> $images */
/** @var array<string, mixed> $config */

$images = $images ?? [];
$q = $q ?? '';
$min = $min ?? null;
$max = $max ?? null;
$inStockOnly = $inStockOnly ?? false;
$hasFilter = $q !== '' || $min !== null || $max !== null || $inStockOnly;
$title = $activeCat !== '' ? $cats->name($activeCat) : 'Termékeink';
$LOW = 15; // e készletszint alatt „már csak X" sürgetést mutatunk

/** Oldalsáv kategóriafa – csak az aktív ág kibontva (accordion). */
$renderTree = function (array $nodes) use (&$renderTree, $activeCat, $activePath): void {
    echo '<ul class="cat-tree">';
    foreach ($nodes as $node) {
        $key = $node['key'];
        $current = $key === $activeCat;
        $open = in_array($key, $activePath, true);
        $children = $node['children'] ?? [];
        echo '<li>';
        printf(
            '<a href="/webshop?kat=%s" class="cat-link%s%s">%s</a>',
            urlencode($key),
            $current ? ' is-current' : '',
            (!$current && $open) ? ' is-open' : '',
            View::e($node['name'])
        );
        if ($children && $open) {
            $renderTree($children);
        }
        echo '</li>';
    }
    echo '</ul>';
};
?>
<section class="page-hero page-hero--shop">
    <div class="hero-glow" aria-hidden="true"></div>
    <div class="container">
        <p class="eyebrow"><span class="eyebrow-dot"></span> Webshop</p>
        <h1 class="display"><?= View::e($title) ?></h1>
        <p class="section-sub">Az árak bruttó, az ÁFÁ-t tartalmazó árak. Raktáron lévő termékek gyors kiszállítással.</p>
    </div>
</section>

<section class="section section--flush-top">
    <div class="container shop-layout">
        <aside class="shop-sidebar">
            <h2 class="sidebar-title">Kategóriák</h2>
            <a href="/webshop" class="cat-all<?= $activeCat === '' ? ' is-current' : '' ?>">Összes termék</a>
            <?php $renderTree($catsTree); ?>

            <form method="get" action="/webshop" class="shop-filters">
                <h2 class="sidebar-title">Keresés és szűrés</h2>
                <?php if ($activeCat !== ''): ?><input type="hidden" name="kat" value="<?= View::e($activeCat) ?>"><?php endif; ?>
                <?php if ($sort !== ''): ?><input type="hidden" name="rendezes" value="<?= View::e($sort) ?>"><?php endif; ?>
                <div class="field">
                    <label for="f-q">Kulcsszó</label>
                    <input id="f-q" type="search" name="q" value="<?= View::e($q) ?>" placeholder="Név vagy cikkszám">
                </div>
                <div class="field-row">
                    <div class="field"><label for="f-min">Ár min.</label><input id="f-min" type="number" name="min" min="0" inputmode="numeric" value="<?= $min !== null ? (int) $min : '' ?>"></div>
                    <div class="field"><label for="f-max">Ár max.</label><input id="f-max" type="number" name="max" min="0" inputmode="numeric" value="<?= $max !== null ? (int) $max : '' ?>"></div>
                </div>
                <label class="check"><input type="checkbox" name="keszlet" value="1"<?= $inStockOnly ? ' checked' : '' ?>> Csak raktáron lévő</label>
                <button type="submit" class="btn btn--gold btn--sm btn--block">Szűrés</button>
                <?php if ($hasFilter): ?>
                    <a href="/webshop<?= $activeCat !== '' ? '?kat=' . urlencode($activeCat) : '' ?>" class="filter-clear">Szűrők törlése</a>
                <?php endif; ?>
            </form>
        </aside>

        <div class="shop-main">
            <nav class="breadcrumb" aria-label="Morzsamenü">
                <a href="/webshop">Webshop</a>
                <?php foreach ($activePath as $i => $key): ?>
                    <span class="sep">/</span>
                    <?php if ($i === count($activePath) - 1): ?>
                        <span class="current"><?= View::e($cats->name($key)) ?></span>
                    <?php else: ?>
                        <a href="/webshop?kat=<?= urlencode($key) ?>"><?= View::e($cats->name($key)) ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>

            <div class="shop-toolbar">
                <p class="result-count"><?= count($products) ?> termék</p>
                <?php if ($products): ?>
                    <form method="get" action="/webshop" class="sort-form">
                        <?php if ($activeCat !== ''): ?><input type="hidden" name="kat" value="<?= View::e($activeCat) ?>"><?php endif; ?>
                        <?php if ($q !== ''): ?><input type="hidden" name="q" value="<?= View::e($q) ?>"><?php endif; ?>
                        <?php if ($min !== null): ?><input type="hidden" name="min" value="<?= (int) $min ?>"><?php endif; ?>
                        <?php if ($max !== null): ?><input type="hidden" name="max" value="<?= (int) $max ?>"><?php endif; ?>
                        <?php if ($inStockOnly): ?><input type="hidden" name="keszlet" value="1"><?php endif; ?>
                        <label class="sort-label" for="sort-select">Rendezés</label>
                        <select id="sort-select" name="rendezes" class="sort-select" data-autosubmit>
                            <option value=""<?= $sort === '' ? ' selected' : '' ?>>Alapértelmezett</option>
                            <option value="ar-fel"<?= $sort === 'ar-fel' ? ' selected' : '' ?>>Ár szerint növekvő</option>
                            <option value="ar-le"<?= $sort === 'ar-le' ? ' selected' : '' ?>>Ár szerint csökkenő</option>
                            <option value="nev"<?= $sort === 'nev' ? ' selected' : '' ?>>Név szerint (A–Z)</option>
                        </select>
                        <button type="submit" class="btn btn--outline btn--sm sort-go">Rendez</button>
                    </form>
                <?php endif; ?>
            </div>

            <?php if (!$products): ?>
                <p class="empty">
                    <?= $hasFilter ? 'Nincs a szűrésnek megfelelő termék.' : 'Ebben a kategóriában jelenleg nincs termék.' ?>
                    <?php if ($hasFilter): ?><a href="/webshop<?= $activeCat !== '' ? '?kat=' . urlencode($activeCat) : '' ?>">Szűrők törlése</a><?php endif; ?>
                </p>
            <?php else: ?>
                <div class="card-grid product-grid">
                    <?php foreach ($products as $p): $pImg = $images[$p->sku][0] ?? null; ?>
                        <article class="card product-card reveal">
                            <a class="product-media<?= $pImg ? ' has-image' : '' ?>" href="/termek/<?= View::e($p->slug) ?>" data-icon="<?= View::e($p->icon) ?>" aria-label="<?= View::e($p->name) ?>">
                                <?php if ($pImg): ?><img src="/uploads/products/<?= View::e($pImg) ?>" alt="<?= View::e($p->name) ?>" loading="lazy"><?php endif; ?>
                                <?php if (!$p->inStock()): ?>
                                    <span class="badge badge--out">Elfogyott</span>
                                <?php elseif ($p->stock <= $LOW): ?>
                                    <span class="badge badge--low">Már csak <?= (int) $p->stock ?> <?= View::e($p->unit) ?></span>
                                <?php else: ?>
                                    <span class="badge">Raktáron</span>
                                <?php endif; ?>
                            </a>
                            <h3><a href="/termek/<?= View::e($p->slug) ?>"><?= View::e($p->name) ?></a></h3>
                            <p><?= View::e($p->short) ?></p>
                            <div class="price-row">
                                <span class="price"><?= View::huf($p->priceGross()) ?></span>
                                <span class="price-unit">/ <?= View::e($p->unit) ?> · bruttó</span>
                            </div>
                            <p class="price-net">nettó <?= View::huf($p->priceNet) ?> + <?= (int) $p->vat ?>% áfa</p>
                            <form method="post" action="/kosar/hozzaad" class="add-form add-form--card">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="sku" value="<?= View::e($p->sku) ?>">
                                <label class="qty-mini">
                                    <span class="vh">Mennyiség (<?= View::e($p->unit) ?>)</span>
                                    <input type="number" name="qty" value="1" min="1" max="<?= max(1, (int) $p->stock) ?>" inputmode="numeric"<?= $p->inStock() ? '' : ' disabled' ?>>
                                </label>
                                <button type="submit" class="btn btn--gold btn--sm"<?= $p->inStock() ? '' : ' disabled' ?>>
                                    Kosárba
                                </button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php
$base = rtrim((string) ($config['app']['url'] ?? ''), '/');
$ldFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG;

$breadcrumbLd = ['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => []];
$crumbs = [['name' => 'Webshop', 'url' => $base . '/webshop']];
foreach ($activePath as $key) {
    $crumbs[] = ['name' => $cats->name($key), 'url' => $base . '/webshop?kat=' . urlencode($key)];
}
foreach ($crumbs as $i => $c) {
    $breadcrumbLd['itemListElement'][] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $c['name'], 'item' => $c['url']];
}

$itemListLd = ['@context' => 'https://schema.org', '@type' => 'ItemList', 'itemListElement' => []];
foreach ($products as $i => $p) {
    $itemListLd['itemListElement'][] = ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $p->name, 'url' => $base . '/termek/' . rawurlencode($p->slug)];
}
?>
<script type="application/ld+json"><?= json_encode($breadcrumbLd, $ldFlags) ?></script>
<?php if ($products): ?><script type="application/ld+json"><?= json_encode($itemListLd, $ldFlags) ?></script><?php endif; ?>
