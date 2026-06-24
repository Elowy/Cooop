<?php

use App\Core\Csrf;
use App\Core\View;

/** @var array<int, array<string, mixed>> $services */
?>
<div class="admin-toolbar">
    <p class="result-count" style="margin:0"><?= count($services) ?> tevékenység</p>
    <a href="/admin/szolgaltatasok/szerkesztes" class="btn btn--gold btn--sm">+ Új tevékenység</a>
</div>

<?php if (!$services): ?>
    <section class="panel"><p class="muted">Még nincs tevékenység. Hozz létre egyet a „+ Új tevékenység” gombbal.</p></section>
<?php else: ?>
    <div class="table-wrap">
        <table class="admin-table">
            <thead><tr><th>Ikon</th><th>Cím</th><th>Állapot</th><th>Sorrend</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($services as $s): ?>
                    <tr>
                        <td><span class="card-icon card-icon--sm" data-icon="<?= View::e(trim((string) ($s['icon'] ?? '')) !== '' ? (string) $s['icon'] : 'packaging') ?>"></span></td>
                        <td>
                            <a href="/admin/szolgaltatasok/szerkesztes?id=<?= (int) $s['id'] ?>"><?= View::e((string) $s['title']) ?></a>
                            <br><a class="muted" style="font-size:.8rem" href="/szolgaltatasok/<?= View::e((string) $s['slug']) ?>" target="_blank" rel="noopener">/szolgaltatasok/<?= View::e((string) $s['slug']) ?></a>
                        </td>
                        <td>
                            <?php if ((int) ($s['published'] ?? 0) === 1): ?>
                                <span class="pill pill--ok">Publikált</span>
                            <?php else: ?>
                                <span class="pill">Vázlat</span>
                            <?php endif; ?>
                        </td>
                        <td class="muted"><?= (int) ($s['sort'] ?? 0) ?></td>
                        <td class="ta-r">
                            <form method="post" action="/admin/szolgaltatasok/torles" onsubmit="return confirm('Biztosan törlöd?')" style="margin:0;display:inline">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                <button class="icon-btn" aria-label="Törlés">×</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
