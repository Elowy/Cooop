<?php

use App\Core\Csrf;
use App\Core\View;
use App\Integration\Product;

/** @var Product|null $product */
/** @var array<string, array<string, string>> $i18n */
$i18n = $i18n ?? [];
$tr = static fn (string $lang, string $field): string => View::e((string) ($i18n[$lang][$field] ?? ''));
?>
<p class="breadcrumb"><a href="/admin/termekek">← Termékek</a></p>

<?php if ($product === null): ?>
    <section class="panel"><p class="muted">A termék nem található.</p></section>
<?php else: ?>
    <section class="panel" style="max-width:680px">
        <header class="panel-head">
            <h2><?= View::e($product->name) ?> <span class="muted mono"><?= View::e($product->sku) ?></span></h2>
        </header>
        <p class="muted" style="margin-top:0">A termék neve és rövid leírása a látogató nyelvén. Üresen hagyva a magyar (Axelből jövő) szöveg jelenik meg. A termékek maguk az Axelből jönnek — itt csak a fordításuk szerkeszthető.</p>

        <form method="post" action="/admin/termek-forditasok" class="form-card" style="background:transparent;border:0;padding:0">
            <?= Csrf::field() ?>
            <input type="hidden" name="sku" value="<?= View::e($product->sku) ?>">
            <?php foreach (['en' => 'Angol (EN)', 'de' => 'Német (DE)'] as $lang => $label): ?>
                <fieldset class="i18n-lang" style="border:1px solid var(--line);border-radius:12px;margin:0 0 16px">
                    <legend><?= View::e($label) ?></legend>
                    <div class="field">
                        <label>Terméknév</label>
                        <input name="i18n[<?= $lang ?>][name]" value="<?= $tr($lang, 'name') ?>" placeholder="<?= View::e($product->name) ?>">
                    </div>
                    <div class="field">
                        <label>Rövid leírás</label>
                        <textarea name="i18n[<?= $lang ?>][short]" rows="2" placeholder="<?= View::e($product->short) ?>"><?= $tr($lang, 'short') ?></textarea>
                    </div>
                </fieldset>
            <?php endforeach; ?>
            <button type="submit" class="btn btn--gold">Mentés</button>
            <a href="/admin/termekek" class="btn btn--outline">Vissza</a>
        </form>
    </section>
<?php endif; ?>
