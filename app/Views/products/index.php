<?php

use App\Core\View;

/** @var array<string, mixed> $config */
/** @var array<int, array<string, mixed>> $categories */
/** @var array<int, array<string, mixed>> $products */
/** @var string|null $activeCat */
/** @var string|null $search */
?>
<section class="page-head">
    <div class="container">
        <h1><?= View::e(t('nav.products')) ?></h1>
        <p><?= View::e(t('products.subtitle')) ?></p>
    </div>
</section>

<section class="section container">
    <div class="shop-layout">
        <aside class="shop-sidebar">
            <form method="get" action="/termekek" class="search-form" role="search">
                <input type="search" name="kereses" placeholder="<?= View::e(t('products.search')) ?>"
                       value="<?= View::e($search ?? '') ?>" aria-label="<?= View::e(t('products.search_btn')) ?>">
                <button type="submit" class="btn btn--small"><?= View::e(t('products.search_btn')) ?></button>
            </form>

            <h2 class="sidebar-title"><?= View::e(t('products.categories')) ?></h2>
            <ul class="filter-list">
                <li><a href="/termekek" class="<?= $activeCat ? '' : 'is-active' ?>"><?= View::e(t('products.all')) ?></a></li>
                <?php foreach ($categories as $cat): ?>
                    <li>
                        <a href="/termekek?kategoria=<?= View::e($cat['slug']) ?>"
                           class="<?= $activeCat === $cat['slug'] ? 'is-active' : '' ?>">
                            <?= View::e($cat['name']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </aside>

        <div class="shop-main">
            <p class="result-count"><?= count($products) ?> <?= View::e(t('products.count')) ?></p>
            <?php if (empty($products)): ?>
                <div class="empty-state">
                    <p><?= View::e(t('products.empty')) ?></p>
                    <a href="/termekek" class="btn btn--ghost"><?= View::e(t('products.clear')) ?></a>
                </div>
            <?php else: ?>
                <div class="product-grid">
                    <?php foreach ($products as $product): ?>
                        <?php include dirname(__DIR__) . '/partials/product-card.php'; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
