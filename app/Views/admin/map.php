<?php

use App\Core\Csrf;
use App\Core\View;

/** @var array<int, array<string, mixed>> $pois */
/** @var array<string, mixed>|null $edit */
$edit = $edit ?? null;
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">

<p class="muted" style="margin-top:0">Kattints a térképre a pont helyének kijelöléséhez (vagy húzd a jelölőt), majd töltsd ki az űrlapot és mentsd.</p>

<div id="admin-map" class="world-map world-map--admin"></div>

<div class="admin-cols" style="margin-top:20px">
    <section class="panel">
        <header class="panel-head"><h2><?= $edit ? 'Pont szerkesztése' : 'Új pont' ?></h2></header>
        <form method="post" action="/admin/terkep/mentes" class="form-card" style="background:transparent;border:0;padding:0">
            <?= Csrf::field() ?>
            <?php if ($edit): ?><input type="hidden" name="id" value="<?= (int) $edit['id'] ?>"><?php endif; ?>
            <div class="field"><label>Megnevezés *</label><input name="title" value="<?= View::e((string) ($edit['title'] ?? '')) ?>" required></div>
            <div class="field-row">
                <div class="field"><label>Szélesség (lat)</label><input name="lat" value="<?= View::e((string) ($edit['lat'] ?? '')) ?>" readonly></div>
                <div class="field"><label>Hosszúság (lng)</label><input name="lng" value="<?= View::e((string) ($edit['lng'] ?? '')) ?>" readonly></div>
            </div>
            <div class="field"><label>Leírás</label><textarea name="description" rows="3"><?= View::e((string) ($edit['description'] ?? '')) ?></textarea></div>
            <div class="field"><label>Link (opcionális)</label><input name="link" value="<?= View::e((string) ($edit['link'] ?? '')) ?>" placeholder="https://..."></div>
            <button type="submit" class="btn btn--gold">Mentés</button>
            <?php if ($edit): ?><a href="/admin/terkep" class="btn btn--outline">Új pont</a><?php endif; ?>
        </form>
    </section>

    <section class="panel">
        <header class="panel-head"><h2>Pontok (<?= count($pois) ?>)</h2></header>
        <?php if (!$pois): ?>
            <p class="muted">Még nincs pont.</p>
        <?php else: ?>
            <ul class="mini-list">
                <?php foreach ($pois as $p): ?>
                    <li>
                        <a href="/admin/terkep?id=<?= (int) $p['id'] ?>" class="cart-name"><?= View::e((string) $p['title']) ?></a>
                        <span class="mini-cat"><?= View::e(number_format((float) $p['lat'], 3)) ?>, <?= View::e(number_format((float) $p['lng'], 3)) ?></span>
                        <form method="post" action="/admin/terkep/torles" onsubmit="return confirm('Törlöd?')" style="margin:0">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                            <button class="icon-btn" aria-label="Törlés">×</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
window.NT_POIS = <?= json_encode(array_map(static fn ($p) => [
    'id' => (int) $p['id'], 'title' => (string) $p['title'],
    'lat' => (float) $p['lat'], 'lng' => (float) $p['lng'],
], $pois), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
(function () {
    if (!window.L) { return; }
    var editId = <?= $edit ? (int) $edit['id'] : 0 ?>;
    var hasEdit = <?= $edit ? 'true' : 'false' ?>;
    var startLat = <?= $edit ? (float) $edit['lat'] : 47.5 ?>, startLng = <?= $edit ? (float) $edit['lng'] : 19.0 ?>;
    var latIn = document.querySelector('input[name=lat]'), lngIn = document.querySelector('input[name=lng]');
    var map = L.map('admin-map').setView([startLat, startLng], hasEdit ? 6 : 3);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap', maxZoom: 18 }).addTo(map);

    var picker = null;
    function place(lat, lng) {
        latIn.value = lat.toFixed(6); lngIn.value = lng.toFixed(6);
        if (picker) { picker.setLatLng([lat, lng]); }
        else {
            picker = L.marker([lat, lng], { draggable: true }).addTo(map);
            picker.on('dragend', function () { var ll = picker.getLatLng(); latIn.value = ll.lat.toFixed(6); lngIn.value = ll.lng.toFixed(6); });
        }
    }
    if (hasEdit) { place(startLat, startLng); }
    map.on('click', function (e) { place(e.latlng.lat, e.latlng.lng); });

    (window.NT_POIS || []).forEach(function (p) {
        if (p.id === editId) { return; }
        L.circleMarker([p.lat, p.lng], { radius: 6, color: '#cba14e', fillColor: '#cba14e', fillOpacity: .6 })
            .addTo(map).bindPopup(p.title);
    });
})();
</script>
