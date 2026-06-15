<?php

use App\Core\View;

/** @var array<int, array<string, mixed>> $products */
/** @var array<int, array<string, mixed>> $categories */
$active = count(array_filter($products, static fn ($p) => !empty($p['active'])));
?>
<div class="admin-head">
    <h1>Vezérlőpult</h1>
    <div class="admin-head__actions">
        <a href="/admin/termekek/uj" class="btn btn--primary btn--small">+ Új termék</a>
        <a href="/admin/kategoriak/uj" class="btn btn--ghost btn--small">+ Új kategória</a>
    </div>
</div>

<div class="admin-stats">
    <div class="admin-stat"><strong><?= count($products) ?></strong><span>termék összesen</span></div>
    <div class="admin-stat"><strong><?= $active ?></strong><span>aktív termék</span></div>
    <div class="admin-stat"><strong><?= count($categories) ?></strong><span>kategória</span></div>
</div>

<div class="admin-card">
    <div class="admin-card__head">
        <h2>Legutóbbi termékek</h2>
        <a href="/admin/termekek" class="link-arrow">Összes termék →</a>
    </div>
    <table class="admin-table">
        <thead><tr><th>Termék</th><th>Kategória</th><th>Ár</th><th>Állapot</th></tr></thead>
        <tbody>
        <?php foreach (array_slice($products, 0, 5) as $p): ?>
            <tr>
                <td><a href="/admin/termekek/<?= (int) $p['id'] ?>/szerkesztes"><?= View::e($p['name']) ?></a></td>
                <td><?= View::e($p['category_name'] ?? '–') ?></td>
                <td><?= View::price((float) $p['price']) ?></td>
                <td>
                    <?php if (!empty($p['active'])): ?>
                        <span class="pill pill--ok">aktív</span>
                    <?php else: ?>
                        <span class="pill pill--off">inaktív</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($products)): ?>
            <tr><td colspan="4" class="admin-empty">Még nincs termék.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
