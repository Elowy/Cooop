<?php

use App\Core\View;

/** @var array<string, mixed> $config */
/** @var array<int, array<string, mixed>> $categories */
/** @var array<int, array<string, mixed>> $featured */
?>
<section class="hero">
    <div class="container hero-inner">
        <div class="hero-copy">
            <p class="eyebrow">Borovi fenyő • 1995 óta a szakmában</p>
            <h1>Minőségi <span>faipari megoldások</span>, generációkon át</h1>
            <p class="lead">Egyedi raklapgyártás, ipari csomagolás és nemzetközi szállítmányozás egy kézből – webáruházunkban pedig tartós, dekoratív Vega madáretetők várják.</p>
            <div class="hero-actions">
                <a href="/termekek" class="btn btn--primary">Madáretetők böngészése</a>
                <a href="/kapcsolat" class="btn btn--ghost">Ajánlatot kérek</a>
            </div>
            <ul class="hero-usps">
                <li>✓ Közel 30 év tapasztalat</li>
                <li>✓ ISPM 15 hőkezelés, CE</li>
                <li>✓ 1–1,5 hét átfutási idő</li>
            </ul>
        </div>
        <div class="hero-art" aria-hidden="true">
            <img src="/assets/img/hero.svg" alt="" width="480" height="380">
        </div>
    </div>
</section>

<section class="section container">
    <div class="section-head">
        <h2>Madáretető kategóriák</h2>
        <a href="/termekek" class="link-arrow">Összes termék →</a>
    </div>
    <div class="category-grid">
        <?php foreach ($categories as $cat): ?>
            <a href="/termekek?kategoria=<?= View::e($cat['slug']) ?>" class="category-card">
                <span class="category-card__icon" data-icon="<?= View::e($cat['icon'] ?? '') ?>"></span>
                <span class="category-card__name"><?= View::e($cat['name']) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section class="section section--alt">
    <div class="container">
        <div class="section-head">
            <h2>Kiemelt termékek</h2>
            <a href="/termekek" class="link-arrow">Tovább →</a>
        </div>
        <div class="product-grid">
            <?php foreach ($featured as $product): ?>
                <?php include dirname(__DIR__) . '/Views/partials/product-card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section container">
    <div class="feature-row">
        <div class="feature">
            <h3>Egyedi raklapgyártás</h3>
            <p>Szabványos és egyedi méretű raklapok megbízható, folyamatos alapanyag-forrásból.</p>
        </div>
        <div class="feature">
            <h3>Ipari csomagolás</h3>
            <p>Egyedi faládák és csomagolási megoldások a biztonságos, nemzetközi szállításért.</p>
        </div>
        <div class="feature">
            <h3>Nemzetközi szállítmányozás</h3>
            <p>Projektrendeléseket a megrendeléstől számított 1–1,5 héten belül teljesítünk.</p>
        </div>
    </div>
</section>
