<?php

use App\Core\Csrf;
use App\Core\Markdown;
use App\Core\View;

/** @var array<string, mixed> $config */
/** @var array<int, array<string, mixed>> $topCats */
/** @var array<int, array<string, mixed>> $services */
/** @var array<int, array<string, mixed>> $references */
/** @var array<int, array<string, mixed>> $leaders */
/** @var array<int, array<string, mixed>> $pois */
/** @var bool $contactSent */
/** @var array<string, string> $contactErrors */
/** @var array<string, mixed> $contactOld */

// Megjelenítési adatok a fő kategóriákhoz (ikon + rövid leírás).
$catMeta = [
    'epitoanyagok'     => ['icon' => 'brick', 'text' => 'Tégla, áthidaló, homok és faanyag.'],
    'kert-szabadido'   => ['icon' => 'leaf',  'text' => 'Kert, háziállat, játszótér és növény.'],
    'fahazak'          => ['icon' => 'house', 'text' => 'Nyaraló, lombház, pavilon, úszóház.'],
    'design-dekoracio' => ['icon' => 'deco',  'text' => 'Lakás- és üzletdekoráció, cégtáblák.'],
];

$stats = [
    ['n' => '30+', 'l' => t('home.stat_experience')],
    ['n' => (string) count($references), 'l' => t('home.stat_reference')],
    ['n' => '5', 'l' => t('home.stat_activity')],
    ['n' => '3', 'l' => t('home.stat_shipping')],
];
?>
<section class="hero" id="top">
    <div class="hero-bg" data-parallax aria-hidden="true"><?= file_get_contents(dirname(__DIR__, 2) . '/public/assets/img/forest.svg') ?></div>
    <div class="hero-scrim" aria-hidden="true"></div>
    <div class="container hero-inner">
        <div class="hero-copy hero-enter">
            <p class="eyebrow"><span class="eyebrow-dot"></span> <?= View::e(t('home.hero_eyebrow')) ?></p>
            <h1 class="display">
                <?= View::e(t('home.hero_title_1')) ?><br><span class="gold"><?= View::e(t('home.hero_title_2')) ?></span> <?= View::e(t('home.hero_title_3')) ?>
            </h1>
            <p class="hero-lead">
                <?= View::e(t('home.hero_lead')) ?>
            </p>
            <div class="hero-actions">
                <a href="/webshop" class="btn btn--gold"><?= View::e(t('home.hero_cta_shop')) ?></a>
                <a href="#kategoriak" class="btn btn--outline"><?= View::e(t('home.hero_cta_categories')) ?></a>
            </div>
        </div>
        <div class="hero-visual reveal" aria-hidden="true">
            <img src="/assets/img/hero.svg" alt="" width="560" height="520">
        </div>
    </div>

    <div class="container">
        <div class="stats-bar reveal">
            <?php foreach ($stats as $s): ?>
                <div class="stat">
                    <span class="stat-n display"><?= View::e($s['n']) ?></span>
                    <span class="stat-l"><?= View::e($s['l']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php if (!empty($services)): ?>
<section class="section" id="tevekenysegek">
    <div class="container">
        <header class="section-head reveal">
            <p class="eyebrow"><span class="eyebrow-dot"></span> <?= View::e(t('home.services_eyebrow')) ?></p>
            <h2 class="display"><?= View::e(t('home.services_title')) ?></h2>
            <p class="section-sub"><?= View::e(t('home.services_sub')) ?></p>
        </header>

        <div class="card-grid service-grid">
            <?php foreach ($services as $svc): ?>
                <?php
                $slug = (string) ($svc['slug'] ?? '');
                $img = trim((string) ($svc['image'] ?? ''));
                $icon = trim((string) ($svc['icon'] ?? '')) !== '' ? (string) $svc['icon'] : 'packaging';
                $title = (string) ($svc['title'] ?? '');
                ?>
                <article class="card service-card reveal">
                    <a class="service-card-link" href="/szolgaltatasok/<?= View::e($slug) ?>" data-service-open="<?= View::e($slug) ?>">
                        <span class="service-card-media">
                            <?php if ($img !== ''): ?>
                                <img src="<?= View::e($img) ?>" alt="<?= View::e($title) ?>" loading="lazy">
                            <?php else: ?>
                                <span class="card-icon" data-icon="<?= View::e($icon) ?>"></span>
                            <?php endif; ?>
                        </span>
                        <span class="service-card-body">
                            <span class="service-card-title"><?= View::e($title) ?></span>
                            <?php if (!empty($svc['summary'])): ?><span class="service-card-sum"><?= View::e((string) $svc['summary']) ?></span><?php endif; ?>
                            <span class="card-link"><?= View::e(t('common.details')) ?> →</span>
                        </span>
                    </a>
                    <template data-service-content="<?= View::e($slug) ?>">
                        <article class="service-detail">
                            <?php if ($img !== ''): ?><img class="service-detail-img" src="<?= View::e($img) ?>" alt="<?= View::e($title) ?>"><?php endif; ?>
                            <p class="eyebrow"><span class="eyebrow-dot"></span> <?= View::e(t('home.service_detail_eyebrow')) ?></p>
                            <h2 class="display"><?= View::e($title) ?></h2>
                            <div class="legal-doc"><?= Markdown::toHtml((string) ($svc['body'] ?? '')) ?></div>
                        </article>
                    </template>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<div class="service-modal" data-service-modal hidden>
    <div class="service-modal-backdrop" data-service-close></div>
    <div class="service-modal-dialog" role="dialog" aria-modal="true" aria-label="<?= View::e(t('home.service_modal_label')) ?>">
        <button type="button" class="service-modal-close" data-service-close aria-label="<?= View::e(t('common.close')) ?>">&times;</button>
        <div class="service-modal-content" data-service-target></div>
    </div>
</div>
<?php endif; ?>

<section class="section" id="kategoriak">
    <div class="container">
        <header class="section-head reveal">
            <p class="eyebrow"><span class="eyebrow-dot"></span> <?= View::e(t('home.categories_eyebrow')) ?></p>
            <h2 class="display"><?= View::e(t('home.categories_title')) ?></h2>
            <p class="section-sub"><?= View::e(t('home.categories_sub')) ?></p>
        </header>

        <div class="card-grid">
            <?php foreach ($topCats as $cat): $meta = $catMeta[$cat['key']] ?? ['icon' => 'packaging', 'text' => '']; ?>
                <a class="card cat-card reveal" href="/webshop?kat=<?= urlencode($cat['key']) ?>">
                    <span class="card-icon" data-icon="<?= View::e($meta['icon']) ?>"></span>
                    <h3><?= View::e($cat['name']) ?></h3>
                    <p><?= View::e($meta['text']) ?></p>
                    <span class="card-link"><?= View::e(t('home.categories_count', ['n' => count($cat['children'])])) ?> →</span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section--alt" id="rolunk">
    <div class="container about-grid">
        <div class="about-visual reveal" aria-hidden="true">
            <img src="/assets/img/about.svg" alt="" width="520" height="440">
        </div>
        <div class="about-copy reveal">
            <p class="eyebrow"><span class="eyebrow-dot"></span> <?= View::e(t('home.about_eyebrow')) ?></p>
            <h2 class="display"><?= View::e(t('home.about_title')) ?> <span class="gold"><?= View::e(t('home.about_title_em')) ?></span></h2>
            <p><?= View::e(t('home.about_p1')) ?></p>
            <p><?= View::e(t('home.about_p2')) ?></p>
            <ul class="ticks">
                <li><?= View::e(t('home.about_tick1')) ?></li>
                <li><?= View::e(t('home.about_tick2')) ?></li>
                <li><?= View::e(t('home.about_tick3')) ?></li>
            </ul>
            <div class="hero-actions">
                <a href="/bemutatkozas" class="btn btn--gold"><?= View::e(t('common.more_about_us')) ?></a>
                <a href="#kapcsolat" class="btn btn--outline"><?= View::e(t('common.contact_us')) ?></a>
            </div>
        </div>
    </div>
</section>

<?php if (!empty($pois)): ?>
<section class="section section--alt" id="terkep">
    <div class="container">
        <header class="section-head reveal">
            <p class="eyebrow"><span class="eyebrow-dot"></span> <?= View::e(t('home.map_eyebrow')) ?></p>
            <h2 class="display"><?= View::e(t('home.map_title')) ?></h2>
            <p class="section-sub"><?= View::e(t('home.map_sub')) ?></p>
        </header>
        <div id="map" class="world-map reveal"></div>
    </div>
</section>
<link rel="stylesheet" href="/assets/vendor/leaflet/leaflet.css">
<script src="/assets/vendor/leaflet/leaflet.js"></script>
<script>
window.NT_POIS = <?= json_encode(array_map(static fn ($p) => [
    'title' => (string) $p['title'],
    'lat' => (float) $p['lat'],
    'lng' => (float) $p['lng'],
    'description' => (string) ($p['description'] ?? ''),
    'link' => (string) ($p['link'] ?? ''),
], $pois), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
(function () {
    if (!window.L || !document.getElementById('map')) { return; }
    var esc = function (s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); };
    var moreLabel = <?= json_encode(t('common.more'), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
    // Statikus térkép: nem mozgatható/nagyítható, csak a pontok kattinthatók.
    var map = L.map('map', {
        scrollWheelZoom: false,
        dragging: false,
        doubleClickZoom: false,
        boxZoom: false,
        keyboard: false,
        touchZoom: false,
        zoomControl: false,
        tap: false
    }).setView([30, 10], 2);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap', maxZoom: 18 }).addTo(map);
    var group = [];
    (window.NT_POIS || []).forEach(function (p) {
        var m = L.marker([p.lat, p.lng]).addTo(map);
        var html = '<strong>' + esc(p.title) + '</strong>';
        if (p.description) { html += '<br>' + esc(p.description); }
        if (p.link && /^https?:\/\//i.test(p.link)) { html += '<br><a href="' + esc(p.link) + '" target="_blank" rel="noopener">' + esc(moreLabel) + ' →</a>'; }
        m.bindPopup(html);
        group.push(m);
    });
    // Az összes pontra illesztés, hogy mind látszódjon. Kisebb képernyőn (alacsonyabb
    // térkép) szorosabb padding, hogy a pontok kitöltsék a nézetet, ne maradjon üres hely.
    if (group.length) {
        var pad = window.matchMedia('(max-width: 680px)').matches ? 22 : 40;
        map.fitBounds(L.featureGroup(group).getBounds(), { padding: [pad, pad], maxZoom: 6 });
    }
})();
</script>
<?php endif; ?>

<?php if (!empty($references)): ?>
<?php
// Alapból csak a kiemelt referenciák látszanak; a többi összecsukva, gombbal nyitható.
$featuredRefs = array_values(array_filter($references, static fn ($r) => !empty($r['featured'])));
$restRefs = array_values(array_filter($references, static fn ($r) => empty($r['featured'])));
// Ha egyik sincs kiemelve, ne legyen végtelen lista (főleg mobilon): alapból
// csak az első néhány látszik (egy desktop-sornyi), a többi a gombbal nyitható.
if ($featuredRefs === []) {
    $featuredRefs = array_slice($references, 0, 6);
    $restRefs = array_slice($references, 6);
}
$orderedRefs = array_merge($featuredRefs, $restRefs);
$featuredCount = count($featuredRefs);
?>
<section class="section" id="referenciak">
    <div class="container">
        <header class="section-head reveal">
            <p class="eyebrow"><span class="eyebrow-dot"></span> <?= View::e(t('home.references_eyebrow')) ?></p>
            <h2 class="display"><?= View::e(t('home.references_title')) ?></h2>
            <p class="section-sub"><?= View::e(t('home.references_sub')) ?></p>
        </header>
        <div class="reference-grid" data-ref-grid>
            <?php foreach ($orderedRefs as $idx => $ref): $collapsed = $idx >= $featuredCount; ?>
                <a class="reference-card <?= $collapsed ? 'is-collapsed' : 'reveal' ?><?= !empty($ref['featured']) ? ' reference-card--featured' : '' ?>" href="/referencia/<?= (int) $ref['id'] ?>" data-ref-open="<?= (int) $ref['id'] ?>">
                    <?php if (!empty($ref['featured'])): ?><span class="reference-badge">★ <?= View::e(t('home.ref_featured')) ?></span><?php endif; ?>
                    <span class="reference-logo">
                        <?php if (!empty($ref['logo'])): ?>
                            <img src="/uploads/references/<?= View::e((string) $ref['logo']) ?>" alt="<?= View::e((string) $ref['name']) ?>" loading="lazy">
                        <?php else: ?>
                            <span class="reference-monogram"><?= View::e(mb_strtoupper(mb_substr((string) $ref['name'], 0, 1))) ?></span>
                        <?php endif; ?>
                    </span>
                    <span class="reference-name"><?= View::e((string) $ref['name']) ?></span>
                    <?php if (!empty($ref['short'])): ?>
                        <span class="reference-note"><?= View::e((string) $ref['short']) ?></span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </div>
        <?php if ($restRefs !== []): ?>
            <div class="reference-more reveal">
                <button type="button" class="btn btn--outline" data-ref-toggle aria-expanded="false"
                        data-more="<?= View::e(t('home.references_more', ['n' => count($restRefs)])) ?>"
                        data-less="<?= View::e(t('home.references_less')) ?>"><?= View::e(t('home.references_more', ['n' => count($restRefs)])) ?></button>
            </div>
        <?php endif; ?>
    </div>

    <div class="modal" id="reference-modal" data-modal hidden>
        <div class="modal-backdrop" data-modal-close></div>
        <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="ref-modal-title">
            <button class="modal-close" data-modal-close aria-label="<?= View::e(t('common.close')) ?>">&times;</button>
            <div class="modal-head">
                <div class="modal-logo" data-ref-logo></div>
                <h3 id="ref-modal-title" class="display" data-ref-name></h3>
            </div>
            <p class="modal-short muted" data-ref-short></p>
            <div class="modal-body" data-ref-long></div>
            <a class="btn btn--outline modal-link" data-ref-link target="_blank" rel="noopener" hidden><?= View::e(t('home.ref_website')) ?> →</a>
        </div>
    </div>
    <script>
    window.NT_REFS = <?= json_encode(array_map(static fn ($r) => [
        'id' => (int) $r['id'], 'name' => (string) $r['name'], 'logo' => (string) ($r['logo'] ?? ''),
        'short' => (string) ($r['short'] ?? ''), 'long' => (string) ($r['long'] ?? ''),
        'url' => (string) ($r['url'] ?? ''),
    ], $references), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    </script>
</section>
<?php endif; ?>

<?php
$cfg = $config['contact'];
$cv = static fn (string $k): string => View::e((string) ($contactOld[$k] ?? ''));
$cerr = static fn (string $k): string => isset($contactErrors[$k])
    ? '<p class="field-err">' . View::e($contactErrors[$k]) . '</p>' : '';
$teamDir = dirname(__DIR__, 2) . '/public/uploads/team/';
?>
<section class="section section--alt" id="kapcsolat">
    <div class="container">
        <header class="section-head reveal">
            <p class="eyebrow"><span class="eyebrow-dot"></span> <?= View::e(t('home.contact_eyebrow')) ?></p>
            <h2 class="display"><?= View::e(t('home.contact_title')) ?></h2>
            <p class="section-sub"><?= View::e(t('home.contact_sub')) ?></p>
        </header>

        <div class="contact-grid reveal">
            <div class="contact-form-col">
                <?php if (!empty($contactSent)): ?>
                    <div class="form-success">
                        <span class="confirm-check" aria-hidden="true">✓</span>
                        <h3><?= View::e(t('home.contact_success_title')) ?></h3>
                        <p class="muted"><?= View::e(t('home.contact_success_text')) ?></p>
                    </div>
                <?php else: ?>
                    <form method="post" action="/kapcsolat" class="form-card" novalidate>
                        <?= Csrf::field() ?>
                        <div class="field-row">
                            <div class="field"><label for="ct-name"><?= View::e(t('home.contact_name')) ?> *</label><input id="ct-name" name="name" autocomplete="name" value="<?= $cv('name') ?>"><?= $cerr('name') ?></div>
                            <div class="field"><label for="ct-company"><?= View::e(t('home.contact_company')) ?></label><input id="ct-company" name="company" autocomplete="organization" value="<?= $cv('company') ?>"></div>
                        </div>
                        <div class="field-row">
                            <div class="field"><label for="ct-phone"><?= View::e(t('home.contact_phone')) ?></label><input id="ct-phone" name="phone" type="tel" autocomplete="tel" inputmode="tel" value="<?= $cv('phone') ?>"></div>
                            <div class="field"><label for="ct-email"><?= View::e(t('home.contact_email')) ?> *</label><input id="ct-email" type="email" name="email" autocomplete="email" value="<?= $cv('email') ?>"><?= $cerr('email') ?></div>
                        </div>
                        <div class="field"><label for="ct-message"><?= View::e(t('home.contact_message')) ?> *</label><textarea id="ct-message" name="message" rows="5"><?= $cv('message') ?></textarea><?= $cerr('message') ?></div>
                        <label class="check"><input type="checkbox" name="privacy"> <?= View::e(t('home.contact_privacy')) ?> *</label>
                        <?= $cerr('privacy') ?>
                        <button type="submit" class="btn btn--gold btn--lg"><?= View::e(t('home.contact_submit')) ?></button>
                    </form>
                <?php endif; ?>
            </div>

            <aside class="contact-info">
                <h3><?= View::e(t('home.contact_find_us')) ?></h3>
                <ul class="contact-list">
                    <li><span><?= View::e(t('home.contact_label_address')) ?></span><strong><?= View::e($cfg['address']) ?></strong></li>
                    <li><span><?= View::e(t('home.contact_label_hours')) ?></span><strong><?= View::e($cfg['hours']) ?></strong></li>
                    <li><span><?= View::e(t('home.contact_phone')) ?></span>
                        <a href="tel:<?= View::e(str_replace(' ', '', $cfg['phone'])) ?>"><?= View::e($cfg['phone']) ?></a>
                        <?php if (!empty($cfg['phone2'])): ?> · <a href="tel:<?= View::e(str_replace(' ', '', $cfg['phone2'])) ?>"><?= View::e($cfg['phone2']) ?></a><?php endif; ?>
                    </li>
                    <li><span><?= View::e(t('home.contact_email')) ?></span><a href="mailto:<?= View::e($cfg['email']) ?>"><?= View::e($cfg['email']) ?></a></li>
                </ul>
            </aside>
        </div>

        <div class="contact-map reveal">
            <iframe class="contact-map-frame" title="Térkép – telephely" loading="lazy" allowfullscreen
                    referrerpolicy="no-referrer-when-downgrade"
                    src="https://maps.google.com/maps?q=<?= urlencode($cfg['address']) ?>&amp;z=15&amp;output=embed"></iframe>
            <?php if (!empty($cfg['map_url'])): ?>
                <a href="<?= View::e($cfg['map_url']) ?>" class="btn btn--outline" target="_blank" rel="noopener"><?= View::e(t('home.contact_directions')) ?> →</a>
            <?php endif; ?>
        </div>

        <?php if (!empty($leaders)): ?>
        <div class="team reveal">
            <h3 class="team-title"><?= View::e(t('home.team_title')) ?></h3>
            <div class="team-grid">
                <?php foreach ($leaders as $p):
                    $hasPhoto = !empty($p['photo']) && is_file($teamDir . $p['photo']); ?>
                    <article class="team-card reveal">
                        <div class="team-photo">
                            <?php if ($hasPhoto): ?>
                                <img src="/uploads/team/<?= View::e((string) $p['photo']) ?>" alt="<?= View::e((string) $p['name']) ?>" loading="lazy">
                            <?php else: ?>
                                <span class="team-monogram"><?= View::e(mb_strtoupper(mb_substr((string) $p['name'], 0, 1))) ?></span>
                            <?php endif; ?>
                        </div>
                        <h4><?= View::e((string) $p['name']) ?></h4>
                        <p class="team-role"><?= View::e((string) $p['role']) ?></p>
                        <div class="team-contact">
                            <?php if (!empty($p['phone'])): ?><a href="tel:<?= View::e(str_replace(' ', '', (string) $p['phone'])) ?>"><?= View::e((string) $p['phone']) ?></a><?php endif; ?>
                            <a href="mailto:<?= View::e((string) $p['email']) ?>"><?= View::e((string) $p['email']) ?></a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php
// Lokális SEO: LocalBusiness strukturált adat a cég adataiból.
$base = rtrim((string) $config['app']['url'], '/');
$addr = (string) ($cfg['address'] ?? '');
$postal = ['@type' => 'PostalAddress', 'streetAddress' => $addr, 'addressCountry' => 'HU'];
if (preg_match('/^(\d{4})\s+([^,]+),\s*(.+)$/u', $addr, $m)) {
    $postal = [
        '@type' => 'PostalAddress',
        'postalCode' => $m[1],
        'addressLocality' => trim($m[2]),
        'streetAddress' => trim($m[3]),
        'addressCountry' => 'HU',
    ];
}
$phones = array_values(array_filter([(string) ($cfg['phone'] ?? ''), (string) ($cfg['phone2'] ?? '')]));
$localBusinessLd = [
    '@context' => 'https://schema.org',
    '@type' => 'LocalBusiness',
    'name' => (string) $config['app']['name'],
    'url' => $base . '/',
    'image' => $base . '/assets/img/logo.svg',
    'logo' => $base . '/assets/img/logo.svg',
    'email' => (string) ($cfg['email'] ?? ''),
    'telephone' => $phones[0] ?? '',
    'address' => $postal,
    'openingHours' => 'Mo-Fr 08:00-16:00',
    'priceRange' => '$$',
];
if ($phones) {
    $localBusinessLd['contactPoint'] = array_map(static fn ($t) => [
        '@type' => 'ContactPoint', 'telephone' => $t, 'contactType' => 'sales',
    ], $phones);
}
?>
<script type="application/ld+json"><?= json_encode($localBusinessLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
