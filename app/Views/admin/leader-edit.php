<?php

use App\Core\Csrf;
use App\Core\View;

/** @var array<string, mixed>|null $leader */
$leader = $leader ?? [];
?>
<p class="breadcrumb"><a href="/admin/vezetok">← Vezetők</a></p>

<section class="panel" style="max-width:560px">
    <form method="post" action="/admin/vezetok/mentes" enctype="multipart/form-data" class="form-card" style="background:transparent;border:0;padding:0">
        <?= Csrf::field() ?>
        <?php if (!empty($leader['id'])): ?><input type="hidden" name="id" value="<?= (int) $leader['id'] ?>"><?php endif; ?>

        <div class="field"><label>Név *</label><input name="name" value="<?= View::e((string) ($leader['name'] ?? '')) ?>" required></div>
        <div class="field"><label>Beosztás</label><input name="role" value="<?= View::e((string) ($leader['role'] ?? '')) ?>" placeholder="pl. Ügyvezető · járműmérnök"></div>
        <div class="field-row">
            <div class="field"><label>Telefon</label><input name="phone" value="<?= View::e((string) ($leader['phone'] ?? '')) ?>"></div>
            <div class="field"><label>E-mail</label><input type="email" name="email" value="<?= View::e((string) ($leader['email'] ?? '')) ?>"></div>
        </div>

        <div class="field">
            <label>Fotó (JPG, PNG vagy WEBP, max 3 MB)</label>
            <?php if (!empty($leader['photo'])): ?>
                <div style="margin-bottom:10px"><img class="ref-thumb" src="/uploads/team/<?= View::e((string) $leader['photo']) ?>" alt=""></div>
            <?php endif; ?>
            <input type="file" name="photo" accept="image/jpeg,image/png,image/webp">
            <?php if (!empty($leader['photo'])): ?><p class="note" style="margin-top:6px">Új fájl feltöltése felülírja a mostanit.</p><?php endif; ?>
        </div>

        <button type="submit" class="btn btn--gold">Mentés</button>
        <a href="/admin/vezetok" class="btn btn--outline">Mégse</a>
    </form>
</section>
