<?php

use App\Core\Csrf;
use App\Core\View;

/** @var array<int, array<string, mixed>> $products */
/** @var string|null $flash */
?>
<div class="admin-head">
    <h1>Termékek</h1>
    <div class="admin-head__actions">
        <a href="/admin/termekek/uj" class="btn btn--primary btn--small">+ Új termék</a>
    </div>
</div>

<?php if (!empty($flash)): ?>
    <div class="alert alert--success"><?= View::e($flash) ?></div>
<?php endif; ?>

<div class="admin-card">
    <table class="admin-table">
        <thead>
            <tr><th></th><th>Név</th><th>Kategória</th><th>Ár</th><th>Készlet</th><th>Állapot</th><th class="ta-right">Műveletek</th></tr>
        </thead>
        <tbody>
        <?php foreach ($products as $p): ?>
            <tr>
                <td class="admin-thumb">
                    <img src="/assets/img/products/<?= View::e($p['image'] ?? 'placeholder.svg') ?>" alt="" width="44" height="44">
                </td>
                <td><strong><?= View::e($p['name']) ?></strong></td>
                <td><?= View::e($p['category_name'] ?? '–') ?></td>
                <td><?= View::price((float) $p['price']) ?></td>
                <td><?= (int) ($p['stock'] ?? 0) ?></td>
                <td>
                    <?php if (!empty($p['active'])): ?>
                        <span class="pill pill--ok">aktív</span>
                    <?php else: ?>
                        <span class="pill pill--off">inaktív</span>
                    <?php endif; ?>
                    <?php if (!empty($p['featured'])): ?>
                        <span class="pill pill--star">kiemelt</span>
                    <?php endif; ?>
                </td>
                <td class="ta-right">
                    <a href="/admin/termekek/<?= (int) $p['id'] ?>/szerkesztes" class="btn btn--ghost btn--small">Szerkesztés</a>
                    <form method="post" action="/admin/termekek/<?= (int) $p['id'] ?>/torles" class="inline-form"
                          onsubmit="return confirm('Biztosan törlöd ezt a terméket?');">
                        <?= Csrf::field() ?>
                        <button type="submit" class="btn btn--danger btn--small">Törlés</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($products)): ?>
            <tr><td colspan="7" class="admin-empty">Még nincs termék.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
