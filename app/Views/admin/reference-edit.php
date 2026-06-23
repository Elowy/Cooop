<?php

use App\Core\Csrf;
use App\Core\View;

/** @var array<string, mixed>|null $ref */
$ref = $ref ?? [];
?>
<p class="breadcrumb"><a href="/admin/referenciak">← Referenciák</a></p>

<section class="panel" style="max-width:680px">
    <form method="post" action="/admin/referenciak/mentes" enctype="multipart/form-data" class="form-card" style="background:transparent;border:0;padding:0">
        <?= Csrf::field() ?>
        <?php if (!empty($ref['id'])): ?><input type="hidden" name="id" value="<?= (int) $ref['id'] ?>"><?php endif; ?>

        <div class="field">
            <label>Cég neve *</label>
            <input name="name" value="<?= View::e((string) ($ref['name'] ?? '')) ?>" required>
        </div>

        <div class="field">
            <label>Logó (JPG, PNG vagy WEBP, max 3 MB)</label>
            <?php if (!empty($ref['logo'])): ?>
                <div style="margin-bottom:10px"><img class="ref-thumb" src="/uploads/references/<?= View::e((string) $ref['logo']) ?>" alt=""></div>
            <?php endif; ?>
            <input type="file" name="logo" accept="image/jpeg,image/png,image/webp">
            <?php if (!empty($ref['logo'])): ?><p class="note" style="margin-top:6px">Új fájl feltöltése felülírja a mostanit.</p><?php endif; ?>
        </div>

        <div class="field">
            <label>Rövid leírás (a kártyán látszik)</label>
            <input name="short" value="<?= View::e((string) ($ref['short'] ?? '')) ?>">
        </div>

        <div class="field">
            <label>Bővebb leírás (kattintáskor jelenik meg)</label>
            <textarea name="long" rows="7"><?= View::e((string) ($ref['long'] ?? '')) ?></textarea>
        </div>

        <button type="submit" class="btn btn--gold">Mentés</button>
        <a href="/admin/referenciak" class="btn btn--outline">Mégse</a>
    </form>
</section>
