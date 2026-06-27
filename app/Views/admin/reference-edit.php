<?php

use App\Core\Csrf;
use App\Core\View;

/** @var array<string, mixed>|null $ref */
$ref = $ref ?? [];
$i18n = is_array($ref['i18n'] ?? null) ? $ref['i18n'] : [];
$tr = static fn (string $lang, string $field): string => View::e((string) ($i18n[$lang][$field] ?? ''));
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
            <label>Logó (JPG, PNG vagy WEBP, max 16 MB)</label>
            <?php if (!empty($ref['logo'])): ?>
                <div style="margin-bottom:10px"><img class="ref-thumb" src="/uploads/references/<?= View::e((string) $ref['logo']) ?>" alt=""></div>
            <?php endif; ?>
            <input type="file" name="logo" accept="image/jpeg,image/png,image/webp">
            <?php if (!empty($ref['logo'])): ?><p class="note" style="margin-top:6px">Új fájl feltöltése felülírja a mostanit.</p><?php endif; ?>
        </div>

        <div class="field">
            <label>Hivatkozás (a partner weboldala, nem kötelező)</label>
            <input name="url" type="url" value="<?= View::e((string) ($ref['url'] ?? '')) ?>" placeholder="https://partner.hu">
        </div>

        <label class="check" style="display:flex;align-items:center;gap:9px;margin-bottom:16px">
            <input type="checkbox" name="featured" value="1"<?= !empty($ref['featured']) ? ' checked' : '' ?>>
            <span>Kiemelt partner (arany szegéllyel, elöl jelenik meg)</span>
        </label>

        <div class="field">
            <label>Rövid leírás (a kártyán látszik)</label>
            <input name="short" value="<?= View::e((string) ($ref['short'] ?? '')) ?>">
        </div>

        <div class="field">
            <label>Bővebb leírás (kattintáskor jelenik meg)</label>
            <textarea name="long" rows="7"><?= View::e((string) ($ref['long'] ?? '')) ?></textarea>
        </div>

        <details class="i18n-box"<?= $i18n !== [] ? ' open' : '' ?>>
            <summary>Fordítások (EN / DE) — üresen a magyar jelenik meg</summary>
            <?php foreach (['en' => 'Angol (EN)', 'de' => 'Német (DE)'] as $lang => $label): ?>
                <fieldset class="i18n-lang">
                    <legend><?= View::e($label) ?></legend>
                    <div class="field">
                        <label>Név</label>
                        <input name="i18n[<?= $lang ?>][name]" value="<?= $tr($lang, 'name') ?>">
                    </div>
                    <div class="field">
                        <label>Rövid leírás</label>
                        <input name="i18n[<?= $lang ?>][short]" value="<?= $tr($lang, 'short') ?>">
                    </div>
                    <div class="field">
                        <label>Bővebb leírás</label>
                        <textarea name="i18n[<?= $lang ?>][long]" rows="5"><?= $tr($lang, 'long') ?></textarea>
                    </div>
                </fieldset>
            <?php endforeach; ?>
        </details>

        <button type="submit" class="btn btn--gold">Mentés</button>
        <a href="/admin/referenciak" class="btn btn--outline">Mégse</a>
    </form>
</section>
