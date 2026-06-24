<?php

use App\Core\Csrf;
use App\Core\View;

/** @var array<string, mixed> $config */
/** @var array<int, array<string, mixed>> $topCats */
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
    ['n' => '30+', 'l' => 'év tapasztalat'],
    ['n' => (string) count($references), 'l' => 'referencia'],
    ['n' => '5', 'l' => 'fő tevékenység'],
    ['n' => '3', 'l' => 'szállítási mód'],
];
?>
<section class="hero" id="top">
    <div class="hero-bg" data-parallax aria-hidden="true"><?= file_get_contents(dirname(__DIR__, 2) . '/public/assets/img/forest.svg') ?></div>
    <div class="hero-scrim" aria-hidden="true"></div>
    <div class="container hero-inner">
        <div class="hero-copy reveal">
            <p class="eyebrow"><span class="eyebrow-dot"></span> Az ügyfél sikere a mi sikerünk!</p>
            <h1 class="display">
                Amit ránk bíznak,<br><span class="gold">azt biztonságban</span> szállítjuk.
            </h1>
            <p class="hero-lead">
                Családi vállalkozás: egyedi raklapgyártás, ipari csomagolás, nemzetközi
                árufuvarozás és fűrészáru-nagykereskedelem. Megoldásaink a tengeri, közúti
                és légi szállítás szigorú követelményeihez igazodnak.
            </p>
            <div class="hero-actions">
                <a href="/webshop" class="btn btn--gold">Irány a webshop</a>
                <a href="#kategoriak" class="btn btn--outline">Kategóriák</a>
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

<section class="section" id="kategoriak">
    <div class="container">
        <header class="section-head reveal">
            <p class="eyebrow"><span class="eyebrow-dot"></span> Kategóriák</p>
            <h2 class="display">Fő kategóriáink</h2>
            <p class="section-sub">Négy fő terület — a webshopban tovább böngészhető alkategóriákkal.</p>
        </header>

        <div class="card-grid">
            <?php foreach ($topCats as $cat): $meta = $catMeta[$cat['key']] ?? ['icon' => 'packaging', 'text' => '']; ?>
                <a class="card cat-card reveal" href="/webshop?kat=<?= urlencode($cat['key']) ?>">
                    <span class="card-icon" data-icon="<?= View::e($meta['icon']) ?>"></span>
                    <h3><?= View::e($cat['name']) ?></h3>
                    <p><?= View::e($meta['text']) ?></p>
                    <span class="card-link"><?= count($cat['children']) ?> alkategória →</span>
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
            <p class="eyebrow"><span class="eyebrow-dot"></span> Bemutatkozás</p>
            <h2 class="display">Több évtizedes tapasztalat, <span class="gold">megbízható kézből</span></h2>
            <p>
                Cégünk több évtizedes tapasztalattal foglalkozik fa alapú raklapok, ládák és
                csomagolóanyagok gyártásával, valamint ipari gépek és berendezések szakszerű
                csomagolásával.
            </p>
            <p>
                Megoldásainkat úgy alakítjuk ki, hogy megfeleljenek a tengeri, közúti és légi
                szállítás szigorú követelményeinek, így partnereink biztonságban tudhatják
                termékeiket a világ bármely pontjára történő szállítás során.
            </p>
            <ul class="ticks">
                <li>Standard és egyedi méretű raklapok, fa ládák</li>
                <li>Ipari gépek és berendezések csomagolása</li>
                <li>Export csomagolás a nemzetközi szállításhoz</li>
            </ul>
            <a href="#kapcsolat" class="btn btn--outline">Kapcsolatfelvétel</a>
        </div>
    </div>
</section>

<?php if (!empty($pois)): ?>
<section class="section section--alt" id="terkep">
    <div class="container">
        <header class="section-head reveal">
            <p class="eyebrow"><span class="eyebrow-dot"></span> Lefedettség</p>
            <h2 class="display">Ahová biztonságban eljutottak csomagjaink</h2>
            <p class="section-sub">Kattints a pontokra a részletekért.</p>
        </header>
        <div id="map" class="world-map reveal"></div>
    </div>
</section>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
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
    var map = L.map('map', { scrollWheelZoom: false }).setView([30, 10], 2);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap', maxZoom: 18 }).addTo(map);
    var group = [];
    (window.NT_POIS || []).forEach(function (p) {
        var m = L.marker([p.lat, p.lng]).addTo(map);
        var html = '<strong>' + esc(p.title) + '</strong>';
        if (p.description) { html += '<br>' + esc(p.description); }
        if (p.link && /^https?:\/\//i.test(p.link)) { html += '<br><a href="' + esc(p.link) + '" target="_blank" rel="noopener">Bővebben →</a>'; }
        m.bindPopup(html);
        group.push(m);
    });
    if (group.length) { map.fitBounds(L.featureGroup(group).getBounds(), { padding: [36, 36], maxZoom: 6 }); }
})();
</script>
<?php endif; ?>

<?php if (!empty($references)): ?>
<section class="section" id="referenciak">
    <div class="container">
        <header class="section-head reveal">
            <p class="eyebrow"><span class="eyebrow-dot"></span> Referenciák</p>
            <h2 class="display">Akiknek dolgozunk</h2>
            <p class="section-sub">Válogatás partnereink és referenciáink közül – kattints a részletekért.</p>
        </header>
        <div class="reference-grid">
            <?php foreach ($references as $ref): ?>
                <a class="reference-card reveal<?= !empty($ref['featured']) ? ' reference-card--featured' : '' ?>" href="/referencia/<?= (int) $ref['id'] ?>" data-ref-open="<?= (int) $ref['id'] ?>">
                    <?php if (!empty($ref['featured'])): ?><span class="reference-badge">★ Kiemelt</span><?php endif; ?>
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
    </div>

    <div class="modal" id="reference-modal" data-modal hidden>
        <div class="modal-backdrop" data-modal-close></div>
        <div class="modal-box" role="dialog" aria-modal="true" aria-labelledby="ref-modal-title">
            <button class="modal-close" data-modal-close aria-label="Bezárás">&times;</button>
            <div class="modal-head">
                <div class="modal-logo" data-ref-logo></div>
                <h3 id="ref-modal-title" class="display" data-ref-name></h3>
            </div>
            <p class="modal-short muted" data-ref-short></p>
            <div class="modal-body" data-ref-long></div>
            <a class="btn btn--outline modal-link" data-ref-link target="_blank" rel="noopener" hidden>Weboldal megtekintése →</a>
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
            <p class="eyebrow"><span class="eyebrow-dot"></span> Kapcsolat</p>
            <h2 class="display">Lépjünk kapcsolatba</h2>
            <p class="section-sub">Kérdése van vagy ajánlatot kérne? Írjon nekünk — hamarosan válaszolunk.</p>
        </header>

        <div class="contact-grid reveal">
            <div class="contact-form-col">
                <?php if (!empty($contactSent)): ?>
                    <div class="form-success">
                        <span class="confirm-check" aria-hidden="true">✓</span>
                        <h3>Köszönjük az üzenetet!</h3>
                        <p class="muted">Hamarosan felvesszük Önnel a kapcsolatot a megadott elérhetőségen.</p>
                    </div>
                <?php else: ?>
                    <form method="post" action="/kapcsolat" class="form-card" novalidate>
                        <?= Csrf::field() ?>
                        <div class="field-row">
                            <div class="field"><label for="ct-name">Név *</label><input id="ct-name" name="name" autocomplete="name" value="<?= $cv('name') ?>"><?= $cerr('name') ?></div>
                            <div class="field"><label for="ct-company">Cég</label><input id="ct-company" name="company" autocomplete="organization" value="<?= $cv('company') ?>"></div>
                        </div>
                        <div class="field-row">
                            <div class="field"><label for="ct-phone">Telefon</label><input id="ct-phone" name="phone" type="tel" autocomplete="tel" inputmode="tel" value="<?= $cv('phone') ?>"></div>
                            <div class="field"><label for="ct-email">E-mail *</label><input id="ct-email" type="email" name="email" autocomplete="email" value="<?= $cv('email') ?>"><?= $cerr('email') ?></div>
                        </div>
                        <div class="field"><label for="ct-message">Üzenet *</label><textarea id="ct-message" name="message" rows="5"><?= $cv('message') ?></textarea><?= $cerr('message') ?></div>
                        <label class="check"><input type="checkbox" name="privacy"> Elfogadom az adatkezelési tájékoztatót. *</label>
                        <?= $cerr('privacy') ?>
                        <button type="submit" class="btn btn--gold btn--lg">Üzenet küldése</button>
                    </form>
                <?php endif; ?>
            </div>

            <aside class="contact-info">
                <h3>Itt találsz minket</h3>
                <ul class="contact-list">
                    <li><span>Cím</span><strong><?= View::e($cfg['address']) ?></strong></li>
                    <li><span>Nyitvatartás</span><strong><?= View::e($cfg['hours']) ?></strong></li>
                    <li><span>Telefon</span>
                        <a href="tel:<?= View::e(str_replace(' ', '', $cfg['phone'])) ?>"><?= View::e($cfg['phone']) ?></a>
                        <?php if (!empty($cfg['phone2'])): ?> · <a href="tel:<?= View::e(str_replace(' ', '', $cfg['phone2'])) ?>"><?= View::e($cfg['phone2']) ?></a><?php endif; ?>
                    </li>
                    <li><span>E-mail</span><a href="mailto:<?= View::e($cfg['email']) ?>"><?= View::e($cfg['email']) ?></a></li>
                </ul>
            </aside>
        </div>

        <div class="contact-map reveal">
            <iframe class="contact-map-frame" title="Térkép – telephely" loading="lazy" allowfullscreen
                    referrerpolicy="no-referrer-when-downgrade"
                    src="https://maps.google.com/maps?q=<?= urlencode($cfg['address']) ?>&amp;z=15&amp;output=embed"></iframe>
            <?php if (!empty($cfg['map_url'])): ?>
                <a href="<?= View::e($cfg['map_url']) ?>" class="btn btn--outline" target="_blank" rel="noopener">Útvonalterv a telephelyre →</a>
            <?php endif; ?>
        </div>

        <?php if (!empty($leaders)): ?>
        <div class="team reveal">
            <h3 class="team-title">Akikkel személyesen is találkozhatsz</h3>
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
