<?php

use App\Core\View;

/** @var array<string, mixed> $config */
/** @var array<int, array<string, mixed>> $categories */
/** @var array<int, array<string, mixed>> $featured */
?>
<section class="hero">
    <div class="container hero-inner">
        <div class="hero-copy">
            <p class="eyebrow">Hálózati szakáruház</p>
            <h1>Megbízható <span>hálózati eszközök</span> otthonra és vállalkozásnak</h1>
            <p class="lead">Routerek, switchek, kábelek, kamerák és tárolók egy helyen – szakértői tanácsadással és gyors kiszállítással.</p>
            <div class="hero-actions">
                <a href="/termekek" class="btn btn--primary">Termékek böngészése</a>
                <a href="/kapcsolat" class="btn btn--ghost">Tanácsot kérek</a>
            </div>
            <ul class="hero-usps">
                <li>✓ Gyors kiszállítás</li>
                <li>✓ Szakértői támogatás</li>
                <li>✓ Garancia minden termékre</li>
            </ul>
        </div>
        <div class="hero-art" aria-hidden="true">
            <img src="/assets/img/hero.svg" alt="" width="480" height="380">
        </div>
    </div>
</section>

<section class="section container">
    <div class="section-head">
        <h2>Kategóriák</h2>
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
            <h3>Szakértői tanácsadás</h3>
            <p>Segítünk kiválasztani a vállalkozásodhoz vagy otthonodhoz legjobban illő hálózati megoldást.</p>
        </div>
        <div class="feature">
            <h3>Gyors kiszállítás</h3>
            <p>Raktáron lévő termékeinket 1–2 munkanapon belül kézbesítjük országszerte.</p>
        </div>
        <div class="feature">
            <h3>Garancia és szerviz</h3>
            <p>Minden termékre garanciát vállalunk, probléma esetén pedig gyorsan intézkedünk.</p>
        </div>
    </div>
</section>
