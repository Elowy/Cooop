<?php

use App\Core\Csrf;
use App\Core\View;

/** @var array<string, string> $values */
?>
<section class="panel" style="max-width:720px">
    <header class="panel-head"><h2>Pályázati közzététel</h2></header>
    <p class="muted" style="margin-top:0">A kötelező pályázati nyilvánosság (infoblokk + projektadatok). A nyilvános <code>/palyazat</code> oldal és a lábléc-link csak akkor jelenik meg, ha legalább az infoblokk-kép, a projekt címe vagy a leírás ki van töltve. Az üresen hagyott mezők nem jelennek meg.</p>

    <form method="post" action="/admin/palyazat" enctype="multipart/form-data" class="form-card" style="background:transparent;border:0;padding:0">
        <?= Csrf::field() ?>

        <div class="field">
            <label>Infoblokk-kép (a támogatótól kapott kötelező arculati kép)</label>
            <?php if (!empty($values['grant_image'])): ?>
                <div style="margin-bottom:10px">
                    <img src="<?= View::e($values['grant_image']) ?>" alt="" style="max-height:150px;border-radius:10px;border:1px solid var(--line);display:block">
                    <label class="check" style="display:flex;align-items:center;gap:8px;margin-top:8px">
                        <input type="checkbox" name="grant_image_remove" value="1">
                        <span>Jelenlegi kép eltávolítása (mentéskor)</span>
                    </label>
                </div>
            <?php endif; ?>
            <input name="grant_image_file" type="file" accept="image/jpeg,image/png,image/webp">
            <p class="note" style="margin-top:6px">JPG / PNG / WEBP, max 16 MB.</p>
        </div>

        <div class="field">
            <label>Projekt címe</label>
            <input name="grant_title" value="<?= View::e($values['grant_title']) ?>" placeholder="pl. Technológiai fejlesztés a Net-Trade Hungary Kft.-nél">
        </div>

        <div class="field">
            <label>Projekt azonosító</label>
            <input name="grant_id" value="<?= View::e($values['grant_id']) ?>" placeholder="pl. GINOP-1.2.3-8-4-4-16-2017-01234">
        </div>

        <div class="field">
            <label>Alap / program</label>
            <input name="grant_fund" value="<?= View::e($values['grant_fund']) ?>" placeholder="pl. Széchenyi 2020 / GINOP">
        </div>

        <div class="field">
            <label>Támogatás összege</label>
            <input name="grant_amount" value="<?= View::e($values['grant_amount']) ?>" placeholder="pl. 12 345 678 Ft">
        </div>

        <div class="field">
            <label>Támogatás intenzitása</label>
            <input name="grant_intensity" value="<?= View::e($values['grant_intensity']) ?>" placeholder="pl. 60%">
        </div>

        <div class="field">
            <label>Megvalósítás kezdete</label>
            <input name="grant_from" value="<?= View::e($values['grant_from']) ?>" placeholder="pl. 2023.01.01">
        </div>

        <div class="field">
            <label>Megvalósítás befejezése</label>
            <input name="grant_to" value="<?= View::e($values['grant_to']) ?>" placeholder="pl. 2024.12.31">
        </div>

        <div class="field">
            <label>Leírás (Markdown: # címsor, **félkövér**, - lista, üres sor = új bekezdés)</label>
            <textarea name="grant_body" rows="10" style="font-family:var(--sans)"><?= View::e($values['grant_body']) ?></textarea>
        </div>

        <button type="submit" class="btn btn--gold">Mentés</button>
        <a href="/palyazat" class="btn btn--outline" target="_blank" rel="noopener">Nyilvános oldal megnyitása</a>
    </form>
</section>
