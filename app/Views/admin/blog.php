<?php

use App\Core\Csrf;
use App\Core\View;

/** @var array<int, array<string, mixed>> $posts */
?>
<div class="admin-toolbar">
    <p class="result-count" style="margin:0"><?= count($posts) ?> bejegyzés</p>
    <a href="/admin/blog/szerkesztes" class="btn btn--gold btn--sm">+ Új bejegyzés</a>
</div>

<?php if (!$posts): ?>
    <section class="panel"><p class="muted">Még nincs blogbejegyzés. Hozz létre egyet a „+ Új bejegyzés” gombbal.</p></section>
<?php else: ?>
    <div class="table-wrap">
        <table class="admin-table">
            <thead><tr><th>Borító</th><th>Cím</th><th>Állapot</th><th>Dátum</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($posts as $post): ?>
                    <tr>
                        <td>
                            <?php if (!empty($post['cover'])): ?>
                                <img class="ref-thumb" src="/uploads/blog/<?= View::e((string) $post['cover']) ?>" alt="">
                            <?php else: ?>
                                <span class="reference-monogram reference-monogram--sm"><?= View::e(mb_strtoupper(mb_substr((string) $post['title'], 0, 1))) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="/admin/blog/szerkesztes?id=<?= (int) $post['id'] ?>"><?= View::e((string) $post['title']) ?></a>
                            <br><a class="muted" style="font-size:.8rem" href="/blog/<?= View::e((string) $post['slug']) ?>" target="_blank" rel="noopener">/blog/<?= View::e((string) $post['slug']) ?></a>
                        </td>
                        <td>
                            <?php if ((int) ($post['published'] ?? 0) === 1): ?>
                                <span class="pill pill--ok">Publikált</span>
                            <?php else: ?>
                                <span class="pill">Vázlat</span>
                            <?php endif; ?>
                        </td>
                        <td class="muted"><?= View::e(View::dateHu((string) ($post['created'] ?? ''))) ?></td>
                        <td class="ta-r">
                            <form method="post" action="/admin/blog/torles" onsubmit="return confirm('Biztosan törlöd?')" style="margin:0;display:inline">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= (int) $post['id'] ?>">
                                <button class="icon-btn" aria-label="Törlés">×</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
