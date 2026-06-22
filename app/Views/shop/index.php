<?php

use App\Core\Csrf;
use App\Core\View;
use App\Integration\Product;

/** @var Product[] $products */
/** @var string $activeCat */

$categories = [
    ''           => 'Összes',
    'csomagolas' => 'Csomagolás',
    'furesz'     => 'Fűrészáru',
    'tuzifa'     => 'Tűzifa',
    'tegla'      => 'Tégla',
];
?>
<section class="page-hero">
    <div class="hero-glow" aria-hidden="true"></div>
    <div class="container">
        <p class="eyebrow"><span class="eyebrow-dot"></span> Webshop</p>
        <h1 class="display">Termékeink <span class="gold">raktárról</span></h1>
        <p class="section-sub">Placeholder árak és készlet — éles üzemben az Axel Pro-ból frissül.</p>
    </div>
</section>

<section class="section section--flush-top">
    <div class="container">
        <div class="filters reveal">
            <?php foreach ($categories as $key => $label): ?>
                <a href="/webshop<?= $key ? '?kat=' . urlencode($key) : '' ?>"
                   class="chip<?= $activeCat === $key ? ' is-active' : '' ?>"><?= View::e($label) ?></a>
            <?php endforeach; ?>
        </div>

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
</section>
