<?php

use App\Core\Csrf;
use App\Core\View;
use App\Integration\Product;

/** @var Product|null $product */
/** @var array<string, string> $values */
?>
<p class="breadcrumb"><a href="/admin/termekek">← Termékek</a></p>

<?php if ($product === null): ?>
    <section class="panel"><p class="muted">A termék nem található.</p></section>
<?php else: ?>
    <section class="panel" style="max-width:680px">
        <header class="panel-head">
            <h2><?= View::e($product->name) ?> <span class="muted mono"><?= View::e($product->sku) ?></span></h2>
        </header>
        <p class="muted" style="margin-top:0">Ezek csak ehhez a termékhez érvényesek. Üresen hagyva az alapértelmezett (terméknév / rövid leírás / globális SEO) jelenik meg.</p>

        <form method="post" action="/admin/termek-seo" class="form-card" style="background:transparent;border:0;padding:0">
            <?= Csrf::field() ?>
            <input type="hidden" name="sku" value="<?= View::e($product->sku) ?>">
            <div class="field">
                <label>Oldal címe (title)</label>
                <input name="title" value="<?= View::e($values['title']) ?>" placeholder="<?= View::e($product->name) ?>">
            </div>
            <div class="field">
                <label>Leírás (meta description)</label>
                <textarea name="description" rows="3" placeholder="<?= View::e($product->short) ?>"><?= View::e($values['description']) ?></textarea>
            </div>
            <div class="field">
                <label>Kulcsszavak (vesszővel)</label>
                <input name="keywords" value="<?= View::e($values['keywords']) ?>">
            </div>
            <div class="field">
                <label>Megosztási kép URL (Open Graph)</label>
                <input name="og_image" value="<?= View::e($values['og_image']) ?>" placeholder="https://...">
            </div>
            <button type="submit" class="btn btn--gold">Mentés</button>
            <a href="/admin/termekek" class="btn btn--outline">Vissza</a>
        </form>
    </section>
<?php endif; ?>
