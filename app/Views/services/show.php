<?php

use App\Core\Markdown;
use App\Core\View;

/** @var array<string, mixed> $service */
/** @var array<int, array<string, mixed>> $others */
/** @var array<string, mixed> $config */

$icon = trim((string) ($service['icon'] ?? '')) !== '' ? (string) $service['icon'] : 'packaging';
$summary = trim((string) ($service['summary'] ?? ''));
$bodyHtml = Markdown::toHtml((string) ($service['body'] ?? ''));

$base = rtrim((string) ($config['app']['url'] ?? ''), '/');
$serviceLd = [
    '@context' => 'https://schema.org',
    '@type' => 'Service',
    'name' => (string) $service['title'],
    'description' => $summary,
    'url' => $base . '/szolgaltatasok/' . (string) $service['slug'],
    'provider' => ['@type' => 'Organization', 'name' => (string) $config['app']['name'], 'url' => $base . '/'],
    'areaServed' => 'EU',
];
?>
<section class="page-hero">
    <div class="hero-glow" aria-hidden="true"></div>
    <div class="container">
        <p class="breadcrumb breadcrumb--hero"><a href="/szolgaltatasok">← Tevékenységek</a></p>
        <span class="service-hero-icon card-icon" data-icon="<?= View::e($icon) ?>"></span>
        <h1 class="display"><?= View::e((string) $service['title']) ?></h1>
        <?php if ($summary !== ''): ?><p class="section-sub"><?= View::e($summary) ?></p><?php endif; ?>
    </div>
</section>

<section class="section section--flush-top">
    <div class="container narrow">
        <article class="legal-doc service-body">
            <?= $bodyHtml !== '' ? $bodyHtml : '<p class="muted">Ehhez a tevékenységhez hamarosan érkezik a részletes leírás.</p>' ?>
        </article>

        <div class="service-cta">
            <a href="/#kapcsolat" class="btn btn--gold">Ajánlatot kérek</a>
            <a href="/webshop" class="btn btn--outline">Irány a webshop</a>
        </div>

        <?php if (!empty($others)): ?>
            <aside class="service-others">
                <h2 class="service-others-title">További tevékenységeink</h2>
                <div class="service-others-grid">
                    <?php foreach ($others as $o):
                        $oIcon = trim((string) ($o['icon'] ?? '')) !== '' ? (string) $o['icon'] : 'packaging'; ?>
                        <a class="service-chip" href="/szolgaltatasok/<?= View::e((string) $o['slug']) ?>">
                            <span class="service-chip-icon card-icon" data-icon="<?= View::e($oIcon) ?>"></span>
                            <span><?= View::e((string) $o['title']) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </aside>
        <?php endif; ?>
    </div>
</section>
<script type="application/ld+json"><?= json_encode($serviceLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
