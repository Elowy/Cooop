<?php

use App\Core\Csrf;
use App\Core\View;

/** @var array<string, mixed>|null $category */
/** @var string|null $error */
$isEdit = !empty($category['id']);
$action = $isEdit
    ? '/admin/kategoriak/' . (int) $category['id'] . '/szerkesztes'
    : '/admin/kategoriak/uj';
?>
<div class="admin-head">
    <h1><?= $isEdit ? 'Kategória szerkesztése' : 'Új kategória' ?></h1>
    <a href="/admin/kategoriak" class="link-arrow">← Vissza a listához</a>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert--error"><?= View::e($error) ?></div>
<?php endif; ?>

<div class="admin-card admin-card--form">
    <form method="post" action="<?= $action ?>" class="admin-form">
        <?= Csrf::field() ?>
        <label>Név *
            <input type="text" name="name" required value="<?= View::e($category['name'] ?? '') ?>">
        </label>
        <label>Slug <span class="muted">(üresen hagyva a névből generáljuk)</span>
            <input type="text" name="slug" value="<?= View::e($category['slug'] ?? '') ?>" placeholder="pl. klasszikus">
        </label>
        <label>Ikon kulcs <span class="muted">(opcionális)</span>
            <input type="text" name="icon" value="<?= View::e($category['icon'] ?? 'feeder') ?>">
        </label>
        <div class="admin-form__actions">
            <button type="submit" class="btn btn--primary">Mentés</button>
            <a href="/admin/kategoriak" class="btn btn--ghost">Mégse</a>
        </div>
    </form>
</div>
