<?php

use App\Core\Csrf;
use App\Core\View;

/** @var array<string, mixed> $config */
/** @var array<string, mixed>|null $product */
/** @var array<int, array<string, mixed>> $categories */
/** @var string $action */
$p = $product ?? [];
$currency = $config['app']['currency'];
$val = static fn (string $k, $d = '') => View::e((string) ($p[$k] ?? $d));
?>
<section class="page-head">
    <div class="container">
        <h1><?= $product ? View::e(t('admin.edit')) : View::e(t('admin.new_product')) ?></h1>
        <p><a href="/admin" class="link-arrow">← <?= View::e(t('admin.back')) ?></a></p>
    </div>
</section>

<section class="section container">
    <form method="post" action="<?= View::e($action) ?>" class="admin-form" enctype="multipart/form-data">
        <?= Csrf::field() ?>
        <div class="form-grid">
            <label class="span-2"><?= View::e(t('admin.name')) ?>
                <input type="text" name="name" value="<?= $val('name') ?>" required>
            </label>

            <label><?= View::e(t('admin.category')) ?>
                <select name="category_id">
                    <option value="">–</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= (int) ($p['category_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>>
                            <?= View::e($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label><?= View::e(t('admin.slug')) ?>
                <input type="text" name="slug" value="<?= $val('slug') ?>" placeholder="<?= View::e(t('admin.slug_hint')) ?>">
            </label>

            <label><?= View::e(t('admin.price')) ?> (<?= View::e($currency) ?>)
                <input type="number" name="price" value="<?= $val('price', '0') ?>" min="0" step="0.01" required>
            </label>

            <label><?= View::e(t('admin.unit')) ?>
                <input type="text" name="unit" value="<?= $val('unit', 'db') ?>" placeholder="db / m³ / fm">
            </label>

            <label><?= View::e(t('admin.stock')) ?>
                <input type="number" name="stock" value="<?= $val('stock', '0') ?>" min="0" step="1">
            </label>

            <label class="span-2"><?= View::e(t('admin.short')) ?>
                <input type="text" name="short" value="<?= $val('short') ?>" maxlength="300">
            </label>

            <label class="span-2"><?= View::e(t('admin.description')) ?>
                <textarea name="description" rows="5"><?= $val('description') ?></textarea>
            </label>

            <label class="span-2"><?= View::e(t('admin.image')) ?>
                <input type="file" name="image" accept=".svg,.png,.jpg,.jpeg,.webp">
                <?php if (!empty($p['image'])): ?>
                    <span class="admin-current-img">
                        <img src="/assets/img/products/<?= View::e($p['image']) ?>" alt="" width="80" height="60">
                        <small><?= View::e(t('admin.current_image')) ?>: <?= View::e($p['image']) ?></small>
                    </span>
                <?php endif; ?>
            </label>

            <label class="checkbox"><input type="checkbox" name="featured" value="1" <?= !empty($p['featured']) ? 'checked' : '' ?>> <?= View::e(t('admin.featured')) ?></label>
            <label class="checkbox"><input type="checkbox" name="active" value="1" <?= (!$product || !empty($p['active'])) ? 'checked' : '' ?>> <?= View::e(t('admin.active')) ?></label>
        </div>

        <div class="admin-form-actions">
            <button type="submit" class="btn btn--primary"><?= View::e(t('admin.save')) ?></button>
            <a href="/admin" class="btn btn--ghost"><?= View::e(t('admin.cancel')) ?></a>
        </div>
    </form>
</section>
