<?php

use App\Core\Csrf;
use App\Core\Database;
use App\Core\View;

/** @var array<string, mixed> $config */
/** @var array<int, array<string, mixed>> $products */
/** @var string|null $flash */
$currency = $config['app']['currency'];
$flash = $flash ?? null;
$hasDb = Database::getConnection() !== null;
$flashKeys = ['created' => 'admin.created', 'updated' => 'admin.updated', 'deleted' => 'admin.deleted'];
?>
<section class="page-head">
    <div class="container admin-head">
        <div>
            <h1><?= View::e(t('admin.title')) ?></h1>
            <p><?= count($products) ?> <?= View::e(t('products.count')) ?></p>
        </div>
        <a href="/admin/termek/uj" class="btn btn--primary">+ <?= View::e(t('admin.new_product')) ?></a>
    </div>
</section>

<section class="section container">
    <?php if ($flash && isset($flashKeys[$flash])): ?>
        <div class="alert alert--success"><?= View::e(t($flashKeys[$flash])) ?></div>
    <?php endif; ?>

    <?php if (!$hasDb): ?>
        <div class="alert alert--error"><?= View::e(t('admin.no_db')) ?></div>
    <?php endif; ?>

    <div class="table-scroll">
        <table class="cart-table admin-table">
            <thead>
                <tr>
                    <th></th>
                    <th><?= View::e(t('admin.name')) ?></th>
                    <th><?= View::e(t('admin.category')) ?></th>
                    <th><?= View::e(t('admin.price')) ?></th>
                    <th><?= View::e(t('admin.stock')) ?></th>
                    <th><?= View::e(t('admin.status')) ?></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                    <tr>
                        <td class="cart-product">
                            <img src="/assets/img/products/<?= View::e($p['image'] ?? 'placeholder.svg') ?>" alt="" width="48" height="36">
                        </td>
                        <td><?= View::e($p['name']) ?></td>
                        <td><?= View::e($p['category_name'] ?? '–') ?></td>
                        <td><?= View::price((float) $p['price'], $currency) ?> / <?= View::e($p['unit'] ?? 'db') ?></td>
                        <td><?= (int) ($p['stock'] ?? 0) ?></td>
                        <td>
                            <?php if (!empty($p['featured'])): ?><span class="badge badge--featured">★</span> <?php endif; ?>
                            <?php if (empty($p['active'])): ?><span class="badge badge--off"><?= View::e(t('admin.inactive')) ?></span><?php endif; ?>
                        </td>
                        <td class="admin-actions">
                            <a href="/admin/termek/<?= (int) $p['id'] ?>/szerkeszt" class="btn btn--small"><?= View::e(t('admin.edit')) ?></a>
                            <form method="post" action="/admin/termek/<?= (int) $p['id'] ?>/torles"
                                  onsubmit="return confirm('<?= View::e(t('admin.confirm_delete')) ?>');">
                                <?= Csrf::field() ?>
                                <button type="submit" class="btn btn--small btn--danger"><?= View::e(t('admin.delete')) ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
