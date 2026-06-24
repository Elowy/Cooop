<?php

use App\Core\View;

/** @var array<int, array<string, mixed>> $services */
?>
<section class="page-hero">
    <div class="hero-glow" aria-hidden="true"></div>
    <div class="container">
        <p class="eyebrow"><span class="eyebrow-dot"></span> Tevékenységek</p>
        <h1 class="display">Amivel foglalkozunk</h1>
        <p class="section-sub">Ipari csomagolástól a faipari gyártáson át a nemzetközi fuvarozásig — egy kézből.</p>
    </div>
</section>

<section class="section section--flush-top">
    <div class="container">
        <?php if (!$services): ?>
            <p class="muted" style="text-align:center">Hamarosan részletezzük a tevékenységeinket.</p>
        <?php else: ?>
            <div class="card-grid">
                <?php foreach ($services as $s):
                    $icon = trim((string) ($s['icon'] ?? '')) !== '' ? (string) $s['icon'] : 'packaging'; ?>
                    <a class="card cat-card reveal" href="/szolgaltatasok/<?= View::e((string) $s['slug']) ?>">
                        <span class="card-icon" data-icon="<?= View::e($icon) ?>"></span>
                        <h3><?= View::e((string) $s['title']) ?></h3>
                        <p><?= View::e((string) ($s['summary'] ?? '')) ?></p>
                        <span class="card-link">Részletek →</span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
