<?php

use App\Catalog\Categories;
use App\Core\Csrf;
use App\Core\View;
use App\Integration\Product;

/** @var Product[] $products */
/** @var array<int, array<string, mixed>> $catsTree */
/** @var string $activeCat */
/** @var string[] $activePath */
/** @var Categories $cats */

$title = $activeCat !== '' ? $cats->name($activeCat) : 'Termékeink';

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
        <p class="section-sub">Placeholder árak és készlet — éles üzemben az Axel Pro-ból frissül.</p>
    </div>
</section>

<section class="section section--flush-top">
    <div class="container shop-layout">
        <aside class="shop-sidebar">
            <h2 class="sidebar-title">Kategóriák</h2>
            <a href="/webshop" class="cat-all<?= $activeCat === '' ? ' is-current' : '' ?>">Összes termék</a>
            <?php $renderTree($catsTree); ?>
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

            <p class="result-count"><?= count($products) ?> termék</p>

            <?php if (!$products): ?>
                <p class="empty">Ebben a kategóriában jelenleg nincs termék.</p>
            <?php else: ?>
                <div class="card-grid product-grid">
                    <?php foreach ($products as $p): ?>
                        <article class="card product-card reveal">
                            <a class="product-media" href="/termek/<?= View::e($p->slug) ?>" data-icon="<?= View::e($p->icon) ?>" aria-label="<?= View::e($p->name) ?>">
                                <?php if (!$p->inStock()): ?><span class="badge badge--out">Elfogyott</span>
                                <?php else: ?><span class="badge">Raktáron</span><?php endif; ?>
                            </a>
                            <h3><a href="/termek/<?= View::e($p->slug) ?>"><?= View::e($p->name) ?></a></h3>
                            <p><?= View::e($p->short) ?></p>
                            <div class="price-row">
                                <span class="price"><?= View::huf($p->priceGross()) ?></span>
                                <span class="price-unit">/ <?= View::e($p->unit) ?> · bruttó</span>
                            </div>
                            <form method="post" action="/kosar/hozzaad" class="add-form">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="sku" value="<?= View::e($p->sku) ?>">
                                <button type="submit" class="btn btn--gold btn--sm btn--block"<?= $p->inStock() ? '' : ' disabled' ?>>
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
