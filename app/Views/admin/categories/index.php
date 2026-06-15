<?php

use App\Core\Csrf;
use App\Core\View;

/** @var array<int, array<string, mixed>> $categories */
/** @var string|null $flash */
?>
<div class="admin-head">
    <h1>Kategóriák</h1>
    <div class="admin-head__actions">
        <a href="/admin/kategoriak/uj" class="btn btn--primary btn--small">+ Új kategória</a>
    </div>
</div>

<?php if (!empty($flash)): ?>
    <div class="alert alert--success"><?= View::e($flash) ?></div>
<?php endif; ?>

<div class="admin-card">
    <table class="admin-table">
        <thead>
            <tr><th>Név</th><th>Slug</th><th>Termékek</th><th class="ta-right">Műveletek</th></tr>
        </thead>
        <tbody>
        <?php foreach ($categories as $c): ?>
            <tr>
                <td><strong><?= View::e($c['name']) ?></strong></td>
                <td><code><?= View::e($c['slug']) ?></code></td>
                <td><?= (int) ($c['product_count'] ?? 0) ?> db</td>
                <td class="ta-right">
                    <a href="/admin/kategoriak/<?= (int) $c['id'] ?>/szerkesztes" class="btn btn--ghost btn--small">Szerkesztés</a>
                    <form method="post" action="/admin/kategoriak/<?= (int) $c['id'] ?>/torles" class="inline-form"
                          onsubmit="return confirm('Biztosan törlöd ezt a kategóriát? A hozzá tartozó termékek kategória nélkül maradnak.');">
                        <?= Csrf::field() ?>
                        <button type="submit" class="btn btn--danger btn--small">Törlés</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($categories)): ?>
            <tr><td colspan="4" class="admin-empty">Még nincs kategória.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
