<?php

use App\Core\Csrf;
use App\Core\View;

/** @var array<int, array<string, mixed>> $leaders */
?>
<div class="admin-toolbar">
    <p class="result-count" style="margin:0"><?= count($leaders) ?> vezető</p>
    <a href="/admin/vezetok/szerkesztes" class="btn btn--gold btn--sm">+ Új vezető</a>
</div>

<?php if (!$leaders): ?>
    <section class="panel"><p class="muted">Még nincs vezető. Adj hozzá egyet a „+ Új vezető” gombbal.</p></section>
<?php else: ?>
    <div class="table-wrap">
        <table class="admin-table">
            <thead><tr><th>Fotó</th><th>Név</th><th>Beosztás</th><th>Telefon</th><th>E-mail</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($leaders as $l): ?>
                    <tr>
                        <td>
                            <?php if (!empty($l['photo'])): ?>
                                <img class="ref-thumb" src="/uploads/team/<?= View::e((string) $l['photo']) ?>" alt="">
                            <?php else: ?>
                                <span class="reference-monogram reference-monogram--sm"><?= View::e(mb_strtoupper(mb_substr((string) $l['name'], 0, 1))) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><a href="/admin/vezetok/szerkesztes?id=<?= (int) $l['id'] ?>"><?= View::e((string) $l['name']) ?></a></td>
                        <td class="muted"><?= View::e((string) ($l['role'] ?? '')) ?></td>
                        <td class="muted"><?= View::e((string) ($l['phone'] ?? '')) ?></td>
                        <td class="muted"><?= View::e((string) ($l['email'] ?? '')) ?></td>
                        <td class="ta-r">
                            <form method="post" action="/admin/vezetok/torles" onsubmit="return confirm('Biztosan törlöd?')" style="margin:0;display:inline">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= (int) $l['id'] ?>">
                                <button class="icon-btn" aria-label="Törlés">×</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
