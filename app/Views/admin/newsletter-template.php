<?php

use App\Core\Csrf;
use App\Core\View;

/** @var array<string, mixed>|null $tpl */
$tpl = $tpl ?? [];
?>
<p class="breadcrumb"><a href="/admin/hirlevel">← Hírlevél</a></p>

<section class="panel" style="max-width:760px">
    <form method="post" action="/admin/hirlevel/sablon/mentes" class="form-card" style="background:transparent;border:0;padding:0">
        <?= Csrf::field() ?>
        <?php if (!empty($tpl['id'])): ?><input type="hidden" name="id" value="<?= (int) $tpl['id'] ?>"><?php endif; ?>

        <div class="field">
            <label>Sablon neve *</label>
            <input name="name" value="<?= View::e((string) ($tpl['name'] ?? '')) ?>" placeholder="Pl. Havi hírlevél" required>
        </div>

        <div class="field">
            <label>Tárgy</label>
            <input name="subject" value="<?= View::e((string) ($tpl['subject'] ?? '')) ?>" placeholder="A levél tárgya">
        </div>

        <div class="field">
            <label>Tartalom (HTML megengedett)</label>
            <textarea name="body" rows="14" placeholder="A hírlevél törzse…"><?= View::e((string) ($tpl['body'] ?? '')) ?></textarea>
        </div>

        <button type="submit" class="btn btn--gold">Mentés</button>
        <a href="/admin/hirlevel" class="btn btn--outline">Mégse</a>
    </form>
</section>
