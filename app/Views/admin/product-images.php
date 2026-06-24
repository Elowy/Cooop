<?php

use App\Core\Csrf;
use App\Core\View;
use App\Integration\Product;

/** @var Product|null $product */
/** @var string[] $images */
?>
<p class="breadcrumb"><a href="/admin/termekek">← Termékek</a></p>

<?php if ($product === null): ?>
    <section class="panel"><p class="muted">A termék nem található.</p></section>
<?php else: ?>
    <section class="panel" style="max-width:760px">
        <header class="panel-head">
            <h2><?= View::e($product->name) ?> <span class="muted mono"><?= View::e($product->sku) ?></span></h2>
        </header>
        <p class="muted" style="margin-top:0">
            Az első kép az elsődleges (borító- és megosztási kép). JPG, PNG vagy WEBP, képenként max 16 MB.
        </p>

        <form method="post" action="/admin/termek-kepek/feltoltes" class="form-card" enctype="multipart/form-data" style="background:transparent;border:0;padding:0">
            <?= Csrf::field() ?>
            <input type="hidden" name="sku" value="<?= View::e($product->sku) ?>">
            <div class="field">
                <label for="pi-files">Új kép(ek) feltöltése</label>
                <input id="pi-files" type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple>
            </div>
            <button type="submit" class="btn btn--gold">Feltöltés</button>
        </form>

        <?php if (!$images): ?>
            <p class="muted" style="margin-top:22px">Ehhez a termékhez még nincs kép feltöltve – a webshopban a kategória-ikon jelenik meg helyette.</p>
        <?php else: ?>
            <div class="img-manage">
                <?php foreach ($images as $i => $file): ?>
                    <figure class="img-tile<?= $i === 0 ? ' is-primary' : '' ?>">
                        <img src="/uploads/products/<?= View::e($file) ?>" alt="" loading="lazy">
                        <?php if ($i === 0): ?><span class="img-primary-badge">Elsődleges</span><?php endif; ?>
                        <div class="img-actions">
                            <?php if ($i !== 0): ?>
                                <form method="post" action="/admin/termek-kepek/elsodleges">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="sku" value="<?= View::e($product->sku) ?>">
                                    <input type="hidden" name="file" value="<?= View::e($file) ?>">
                                    <button type="submit" class="btn btn--outline btn--sm">Elsődleges</button>
                                </form>
                            <?php endif; ?>
                            <form method="post" action="/admin/termek-kepek/torles" onsubmit="return confirm('Biztosan törlöd ezt a képet?')">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="sku" value="<?= View::e($product->sku) ?>">
                                <input type="hidden" name="file" value="<?= View::e($file) ?>">
                                <button type="submit" class="btn btn--outline btn--sm btn--danger">Törlés</button>
                            </form>
                        </div>
                    </figure>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>
