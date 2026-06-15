<?php

use App\Core\View;

/** @var array<string, mixed> $config */
/** @var array<int, array<string, mixed>> $categories */
/** @var array<int, array<string, mixed>> $featured */

$services = [
    ['icon' => 'packaging', 'title' => t('svc.packaging.title'), 'text' => t('svc.packaging.text')],
    ['icon' => 'pallet',    'title' => t('svc.woodwork.title'),  'text' => t('svc.woodwork.text')],
    ['icon' => 'truck',     'title' => t('svc.freight.title'),   'text' => t('svc.freight.text')],
    ['icon' => 'brick',     'title' => t('svc.brick.title'),     'text' => t('svc.brick.text')],
    ['icon' => 'lumber',    'title' => t('svc.lumber.title'),    'text' => t('svc.lumber.text')],
    ['icon' => 'education', 'title' => t('svc.education.title'),  'text' => t('svc.education.text')],
];
?>
<section class="hero">
    <div class="container hero-inner">
        <div class="hero-copy reveal">
            <p class="eyebrow"><?= View::e(t('hero.eyebrow')) ?></p>
            <h1><?= t('hero.title_html') ?></h1>
            <p class="lead"><?= View::e(t('hero.lead')) ?></p>
            <div class="hero-actions">
                <a href="/termekek" class="btn btn--primary"><?= View::e(t('btn.browse')) ?></a>
                <a href="/kapcsolat" class="btn btn--ghost"><?= View::e(t('btn.quote')) ?></a>
            </div>
            <ul class="hero-usps">
                <li>✓ <?= View::e(t('hero.usp1')) ?></li>
                <li>✓ <?= View::e(t('hero.usp2')) ?></li>
                <li>✓ <?= View::e(t('hero.usp3')) ?></li>
            </ul>
        </div>
        <div class="hero-art reveal" aria-hidden="true">
            <img src="/assets/img/hero.svg" alt="" width="480" height="380">
        </div>
    </div>
</section>

<section class="section container">
    <div class="section-head">
        <h2><?= View::e(t('home.activities')) ?></h2>
        <a href="/rolunk" class="link-arrow"><?= View::e(t('link.about')) ?></a>
    </div>
    <div class="feature-row feature-row--services">
        <?php foreach ($services as $s): ?>
            <div class="feature feature--service reveal">
                <span class="feature__icon" data-icon="<?= View::e($s['icon']) ?>"></span>
                <h3><?= View::e($s['title']) ?></h3>
                <p><?= View::e($s['text']) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="section section--alt">
    <div class="container">
        <div class="section-head">
            <h2><?= View::e(t('home.range')) ?></h2>
            <a href="/termekek" class="link-arrow"><?= View::e(t('btn.all')) ?></a>
        </div>
        <div class="category-grid">
            <?php foreach ($categories as $cat): ?>
                <a href="/termekek?kategoria=<?= View::e($cat['slug']) ?>" class="category-card reveal">
                    <span class="category-card__icon" data-icon="<?= View::e($cat['icon'] ?? '') ?>"></span>
                    <span class="category-card__name"><?= View::e($cat['name']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="section-head" style="margin-top:48px;">
            <h2><?= View::e(t('home.featured')) ?></h2>
            <a href="/termekek" class="link-arrow"><?= View::e(t('btn.more')) ?></a>
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
        <div class="feature reveal">
            <h3><?= View::e(t('val.quality.title')) ?></h3>
            <p><?= View::e(t('val.quality.text')) ?></p>
        </div>
        <div class="feature reveal">
            <h3><?= View::e(t('val.sustain.title')) ?></h3>
            <p><?= View::e(t('val.sustain.text')) ?></p>
        </div>
        <div class="feature reveal">
            <h3><?= View::e(t('val.service.title')) ?></h3>
            <p><?= View::e(t('val.service.text')) ?></p>
        </div>
    </div>
</section>
