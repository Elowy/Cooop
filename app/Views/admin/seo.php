<?php

use App\Core\Csrf;
use App\Core\View;

/** @var array<string, string> $values */
?>
<section class="panel" style="max-width:680px">
    <header class="panel-head"><h2>SEO beállítások</h2></header>
    <p class="muted" style="margin-top:0">Ezek jelennek meg a böngésző címsorában, a keresőkben és a közösségi megosztáskor (Open Graph).</p>

    <form method="post" action="/admin/seo" class="form-card" style="background:transparent;border:0;padding:0">
        <?= Csrf::field() ?>
        <div class="field">
            <label>Oldal címe (title)</label>
            <input name="seo_title" value="<?= View::e($values['seo_title']) ?>">
        </div>
        <div class="field">
            <label>Leírás (meta description) — ~155 karakter ajánlott</label>
            <textarea name="seo_description" rows="3"><?= View::e($values['seo_description']) ?></textarea>
        </div>
        <div class="field">
            <label>Kulcsszavak (vesszővel elválasztva)</label>
            <input name="seo_keywords" value="<?= View::e($values['seo_keywords']) ?>" placeholder="raklap, ipari csomagolás, fűrészáru, fuvarozás">
        </div>
        <div class="field">
            <label>Megosztási kép URL (Open Graph, ajánlott 1200×630)</label>
            <input name="seo_og_image" value="<?= View::e($values['seo_og_image']) ?>" placeholder="https://a-domained.hu/assets/img/hero.svg">
        </div>
        <div class="field">
            <label>Google Analytics azonosító (Measurement ID)</label>
            <input name="ga_id" value="<?= View::e($values['ga_id']) ?>" placeholder="G-XXXXXXXXXX">
            <p class="note" style="margin-top:6px">Csak a <strong>Statisztika</strong> süti-kategória elfogadása esetén töltődik be.</p>
        </div>
        <div class="field">
            <label>Facebook Pixel azonosító</label>
            <input name="fb_pixel" value="<?= View::e($values['fb_pixel']) ?>" placeholder="123456789012345">
            <p class="note" style="margin-top:6px">Csak a <strong>Marketing</strong> süti-kategória elfogadása esetén töltődik be.</p>
        </div>
        <button type="submit" class="btn btn--gold">Mentés</button>
    </form>
</section>
