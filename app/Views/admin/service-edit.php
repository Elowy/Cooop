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
    <form method="post" action="/admin/szolgaltatasok/mentes" class="form-card" style="background:transparent;border:0;padding:0">
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
            <label>Kép URL-je (a főoldali kártyán és a részletek-ablakban jelenik meg)</label>
            <input name="image" type="url" value="<?= View::e((string) ($service['image'] ?? '')) ?>" placeholder="https://… vagy /assets/img/…">
            <p class="note" style="margin-top:6px">Üresen hagyva a kiválasztott ikon jelenik meg a kártyán.</p>
            <?php if (!empty($service['image'])): ?>
                <img src="<?= View::e((string) $service['image']) ?>" alt="" style="margin-top:10px;max-height:120px;border-radius:10px;border:1px solid var(--line)">
            <?php endif; ?>
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
