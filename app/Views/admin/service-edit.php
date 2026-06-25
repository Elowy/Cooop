<?php

use App\Core\Csrf;
use App\Core\View;

/** @var array<string, mixed>|null $service */
$service = $service ?? [];
$curIcon = trim((string) ($service['icon'] ?? '')) !== '' ? (string) $service['icon'] : 'packaging';

// Választható kártya-ikonok (a public/assets/css/style.css data-icon készletéből).
$icons = [
    'packaging' => 'Csomag', 'wood' => 'Fa (deszka)', 'lumber' => 'Fűrészáru', 'truck' => 'Teherautó',
    'brick' => 'Tégla', 'learn' => 'Oktatás', 'house' => 'Ház', 'leaf' => 'Levél',
    'deco' => 'Dekoráció', 'sand' => 'Homok', 'paw' => 'Mancs', 'bird' => 'Madár',
];
?>
<p class="breadcrumb"><a href="/admin/szolgaltatasok">← Tevékenységek</a></p>

<section class="panel" style="max-width:760px">
    <form method="post" action="/admin/szolgaltatasok/mentes" enctype="multipart/form-data" class="form-card" style="background:transparent;border:0;padding:0">
        <?= Csrf::field() ?>
        <?php if (!empty($service['id'])): ?><input type="hidden" name="id" value="<?= (int) $service['id'] ?>"><?php endif; ?>

        <div class="field">
            <label>Cím *</label>
            <input name="title" value="<?= View::e((string) ($service['title'] ?? '')) ?>" required>
        </div>

        <div class="field-row">
            <div class="field">
                <label>Ikon</label>
                <select name="icon">
                    <?php foreach ($icons as $key => $label): ?>
                        <option value="<?= View::e($key) ?>"<?= $key === $curIcon ? ' selected' : '' ?>><?= View::e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label>Sorrend (kisebb előrébb)</label>
                <input name="sort" type="number" value="<?= (int) ($service['sort'] ?? 0) ?>">
            </div>
        </div>

        <div class="field">
            <label>Egyedi URL-rész (slug) — üresen a címből generálódik</label>
            <input name="slug" value="<?= View::e((string) ($service['slug'] ?? '')) ?>" placeholder="pl. ipari-csomagolas">
            <?php if (!empty($service['slug'])): ?><p class="note" style="margin-top:6px">Jelenlegi cím: <code>/szolgaltatasok/<?= View::e((string) $service['slug']) ?></code></p><?php endif; ?>
        </div>

        <div class="field">
            <label>Kép (a főoldali kártyán és a részletek-ablakban jelenik meg)</label>
            <?php if (!empty($service['image'])): ?>
                <div style="margin-bottom:10px">
                    <img src="<?= View::e((string) $service['image']) ?>" alt="" style="max-height:120px;border-radius:10px;border:1px solid var(--line);display:block">
                    <label class="check" style="display:flex;align-items:center;gap:8px;margin-top:8px">
                        <input type="checkbox" name="image_remove" value="1">
                        <span>Jelenlegi kép eltávolítása (mentéskor)</span>
                    </label>
                </div>
            <?php endif; ?>
            <input name="image_file" type="file" accept="image/jpeg,image/png,image/webp">
            <p class="note" style="margin-top:6px">Tölts fel képet (JPG / PNG / WEBP, max 16 MB) — vagy add meg URL-ként alább. Üresen a kiválasztott ikon jelenik meg a kártyán.</p>
            <input name="image" type="text" value="<?= View::e((string) ($service['image'] ?? '')) ?>" placeholder="https://… vagy /uploads/…" style="margin-top:8px">
        </div>

        <div class="field">
            <label>Rövid összefoglaló (a kártyán és a menüben látszik)</label>
            <textarea name="summary" rows="2"><?= View::e((string) ($service['summary'] ?? '')) ?></textarea>
        </div>

        <div class="field">
            <label>Tartalom (Markdown: # címsor, **félkövér**, - lista, üres sor = új bekezdés)</label>
            <textarea name="body" rows="16" style="font-family:var(--sans)"><?= View::e((string) ($service['body'] ?? '')) ?></textarea>
        </div>

        <label class="check" style="display:flex;align-items:center;gap:9px;margin-bottom:16px">
            <input type="checkbox" name="published" value="1"<?= (int) ($service['published'] ?? 1) === 1 ? ' checked' : '' ?>>
            <span>Publikált (látszik a nyilvános oldalon és a menüben)</span>
        </label>

        <button type="submit" class="btn btn--gold">Mentés</button>
        <a href="/admin/szolgaltatasok" class="btn btn--outline">Mégse</a>
    </form>
</section>
