<?php

use App\Core\Csrf;
use App\Core\View;

/** @var array<string, mixed>|null $product */
/** @var array<int, array<string, mixed>> $categories */
/** @var string|null $error */
$isEdit = !empty($product['id']);
$action = $isEdit
    ? '/admin/termekek/' . (int) $product['id'] . '/szerkesztes'
    : '/admin/termekek/uj';
$image = $product['image'] ?? 'placeholder.svg';
$selectedCat = (int) ($product['category_id'] ?? 0);
?>
<div class="admin-head">
    <h1><?= $isEdit ? 'Termék szerkesztése' : 'Új termék' ?></h1>
    <a href="/admin/termekek" class="link-arrow">← Vissza a listához</a>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert--error"><?= View::e($error) ?></div>
<?php endif; ?>

<div class="admin-card admin-card--form">
    <form method="post" action="<?= $action ?>" class="admin-form" enctype="multipart/form-data">
        <?= Csrf::field() ?>

        <label>Név *
            <input type="text" name="name" required value="<?= View::e($product['name'] ?? '') ?>">
        </label>

        <div class="admin-form__grid">
            <label>Kategória
                <select name="category_id">
                    <option value="">– nincs –</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= $selectedCat === (int) $c['id'] ? 'selected' : '' ?>>
                            <?= View::e($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Ár (Ft)
                <input type="number" name="price" min="0" step="1" value="<?= View::e((string) ($product['price'] ?? 0)) ?>">
            </label>
            <label>Készlet
                <input type="number" name="stock" min="0" step="1" value="<?= View::e((string) ($product['stock'] ?? 0)) ?>">
            </label>
            <label>Slug <span class="muted">(opcionális)</span>
                <input type="text" name="slug" value="<?= View::e($product['slug'] ?? '') ?>">
            </label>
        </div>

        <label>Rövid leírás
            <input type="text" name="short" value="<?= View::e($product['short'] ?? '') ?>" maxlength="300">
        </label>

        <label>Részletes leírás
            <textarea name="description" rows="5"><?= View::e($product['description'] ?? '') ?></textarea>
        </label>

        <div class="admin-form__grid">
            <label class="admin-upload">Termékkép feltöltése
                <input type="file" name="image" accept=".svg,.png,.jpg,.jpeg,.webp,.gif">
                <span class="muted">SVG, PNG, JPG, WEBP vagy GIF – max. 2 MB. Üresen hagyva a meglévő kép marad.</span>
            </label>
            <div class="admin-current-img">
                <span class="muted">Jelenlegi kép</span>
                <img src="/assets/img/products/<?= View::e($image) ?>" alt="" width="90" height="68">
            </div>
        </div>

        <div class="admin-checks">
            <label class="check"><input type="checkbox" name="featured" value="1" <?= !empty($product['featured']) ? 'checked' : '' ?>> Kiemelt termék</label>
            <label class="check"><input type="checkbox" name="active" value="1" <?= (!isset($product['active']) || !empty($product['active'])) ? 'checked' : '' ?>> Aktív (látható a webshopban)</label>
        </div>

        <div class="admin-form__actions">
            <button type="submit" class="btn btn--primary">Mentés</button>
            <a href="/admin/termekek" class="btn btn--ghost">Mégse</a>
        </div>
    </form>
</div>
