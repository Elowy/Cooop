<?php

use App\Core\View;

/** @var array<int, array{title: string, links: array<int, array{url: string, label: string}>}> $groups */
?>
<section class="page-hero">
    <div class="hero-glow" aria-hidden="true"></div>
    <div class="container">
        <p class="eyebrow"><span class="eyebrow-dot"></span> Oldaltérkép</p>
        <h1 class="display">Minden oldalunk egy helyen</h1>
        <p class="section-sub">Böngéssz a teljes kínálatunkban – vagy ugorj egyből oda, amit keresel.</p>
    </div>
</section>

<section class="section section--flush-top">
    <div class="container">
        <div class="sitemap-cols">
            <?php foreach ($groups as $group): ?>
                <?php if (empty($group['links'])) { continue; } ?>
                <div class="sitemap-group">
                    <h2 class="sitemap-group-title"><?= View::e((string) $group['title']) ?></h2>
                    <ul class="sitemap-list">
                        <?php foreach ($group['links'] as $link): ?>
                            <li><a href="<?= View::e((string) $link['url']) ?>"><?= View::e((string) $link['label']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
