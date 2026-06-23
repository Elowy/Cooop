<?php

use App\Core\Csrf;
use App\Core\View;

/** @var array<int, array<string, mixed>> $references */
?>
<div class="admin-toolbar">
    <p class="result-count" style="margin:0"><?= count($references) ?> referencia</p>
    <a href="/admin/referenciak/szerkesztes" class="btn btn--gold btn--sm">+ Új referencia</a>
</div>

<?php if (!$references): ?>
    <section class="panel"><p class="muted">Még nincs referencia. Adj hozzá egyet a „+ Új referencia" gombbal.</p></section>
<?php else: ?>
    <div class="table-wrap">
        <table class="admin-table">
            <thead><tr><th>Logó</th><th>Név</th><th>Rövid leírás</th><th>Bővebb</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($references as $ref): ?>
                    <tr>
                        <td>
                            <?php if (!empty($ref['logo'])): ?>
                                <img class="ref-thumb" src="/uploads/references/<?= View::e((string) $ref['logo']) ?>" alt="">
                            <?php else: ?>
                                <span class="reference-monogram reference-monogram--sm"><?= View::e(mb_strtoupper(mb_substr((string) $ref['name'], 0, 1))) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><a href="/admin/referenciak/szerkesztes?id=<?= (int) $ref['id'] ?>"><?= View::e((string) $ref['name']) ?></a></td>
                        <td class="muted"><?= View::e((string) ($ref['short'] ?? '')) ?></td>
                        <td><?= !empty($ref['long']) ? '✓' : '<span class="muted">—</span>' ?></td>
                        <td class="ta-r">
                            <form method="post" action="/admin/referenciak/torles" onsubmit="return confirm('Biztosan törlöd?')" style="margin:0;display:inline">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= (int) $ref['id'] ?>">
                                <button class="icon-btn" aria-label="Törlés">×</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
