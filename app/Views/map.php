<?php

use App\Core\View;

/** @var array<int, array<string, mixed>> $pois */
?>
<section class="page-hero">
    <div class="hero-glow" aria-hidden="true"></div>
    <div class="container">
        <p class="eyebrow"><span class="eyebrow-dot"></span> Térkép</p>
        <h1 class="display">Ahol jelen vagyunk</h1>
        <p class="section-sub">Kattints a pontokra a részletekért.</p>
    </div>
</section>

<section class="section section--flush-top">
    <div class="container">
        <div id="map" class="world-map"></div>
        <?php if (!$pois): ?>
            <p class="muted" style="margin-top:16px">Még nincsenek térképpontok.</p>
        <?php endif; ?>
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
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap', maxZoom: 18
    }).addTo(map);
    var pts = window.NT_POIS || [];
    var group = [];
    pts.forEach(function (p) {
        var m = L.marker([p.lat, p.lng]).addTo(map);
        var html = '<strong>' + esc(p.title) + '</strong>';
        if (p.description) { html += '<br>' + esc(p.description); }
        if (p.link) { html += '<br><a href="' + esc(p.link) + '" target="_blank" rel="noopener">Bővebben →</a>'; }
        m.bindPopup(html);
        group.push(m);
    });
    if (group.length) {
        map.fitBounds(L.featureGroup(group).getBounds().pad(0.3));
    }
})();
</script>
