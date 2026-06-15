<?php

use App\Core\View;

/** @var array<string, mixed> $config */

$items = [
    ['img' => '/assets/img/hero.svg',              'cap' => 'gallery.cap_packaging'],
    ['img' => '/assets/img/products/crate.svg',    'cap' => 'gallery.cap_crate'],
    ['img' => '/assets/img/products/pallet.svg',   'cap' => 'gallery.cap_pallet'],
    ['img' => '/assets/img/products/lumber.svg',   'cap' => 'gallery.cap_lumber'],
    ['img' => '/assets/img/products/firewood.svg', 'cap' => 'gallery.cap_firewood'],
    ['img' => '/assets/img/products/brick.svg',    'cap' => 'gallery.cap_brick'],
];
?>
<section class="page-head">
    <div class="container">
        <h1><?= View::e(t('gallery.title')) ?></h1>
        <p><?= View::e(t('gallery.subtitle')) ?></p>
    </div>
</section>

<section class="section container">
    <div class="gallery-grid">
        <?php foreach ($items as $item): $cap = t($item['cap']); ?>
            <button type="button" class="gallery-item reveal"
                    data-lightbox
                    data-src="<?= View::e($item['img']) ?>"
                    data-caption="<?= View::e($cap) ?>">
                <img src="<?= View::e($item['img']) ?>" alt="<?= View::e($cap) ?>" loading="lazy">
                <span class="gallery-item__cap"><?= View::e($cap) ?></span>
            </button>
        <?php endforeach; ?>
    </div>
</section>

<div class="lightbox" data-lightbox-modal hidden>
    <button type="button" class="lightbox__close" data-lightbox-close aria-label="Bezárás / Close">✕</button>
    <figure class="lightbox__fig">
        <img data-lightbox-img src="" alt="">
        <figcaption data-lightbox-cap></figcaption>
    </figure>
</div>
