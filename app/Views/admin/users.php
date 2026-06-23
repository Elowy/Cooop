<?php

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\User\UserRepository;

/** @var array<int, array<string, mixed>> $users */
$me = Auth::user();
$meId = $me ? (int) $me['id'] : 0;
?>
<div class="admin-toolbar">
    <p class="result-count" style="margin:0"><?= count($users) ?> felhasználó</p>
    <a href="/admin/felhasznalok/szerkesztes" class="btn btn--gold btn--sm">+ Új felhasználó</a>
</div>

<div class="table-wrap">
    <table class="admin-table">
        <thead><tr><th>Név</th><th>E-mail</th><th>Rang</th><th>Létrehozva</th><th></th></tr></thead>
        <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td><a href="/admin/felhasznalok/szerkesztes?id=<?= (int) $u['id'] ?>"><?= View::e((string) $u['name']) ?></a><?= (int) $u['id'] === $meId ? ' <span class="muted">(te)</span>' : '' ?></td>
                    <td class="muted"><?= View::e((string) $u['email']) ?></td>
                    <td><span class="tag tag--ok"><?= View::e(UserRepository::roleLabel((string) $u['role'])) ?></span></td>
                    <td class="muted"><?= View::e(date('Y-m-d', strtotime((string) ($u['created_at'] ?? 'now')))) ?></td>
                    <td class="ta-r">
                        <?php if ((int) $u['id'] !== $meId): ?>
                            <form method="post" action="/admin/felhasznalok/torles" onsubmit="return confirm('Biztosan törlöd?')" style="margin:0;display:inline">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                <button class="icon-btn" aria-label="Törlés">×</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
