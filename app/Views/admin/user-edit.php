<?php

use App\Core\Csrf;
use App\Core\View;
use App\User\UserRepository;

/** @var array<string, mixed>|null $user */
$user = $user ?? null;
?>
<p class="breadcrumb"><a href="/admin/felhasznalok">← Felhasználók</a></p>

<section class="panel" style="max-width:560px">
    <form method="post" action="/admin/felhasznalok/mentes" class="form-card" style="background:transparent;border:0;padding:0">
        <?= Csrf::field() ?>
        <?php if (!empty($user['id'])): ?><input type="hidden" name="id" value="<?= (int) $user['id'] ?>"><?php endif; ?>

        <div class="field"><label>Név *</label><input name="name" value="<?= View::e((string) ($user['name'] ?? '')) ?>" required></div>
        <div class="field"><label>E-mail *</label><input type="email" name="email" value="<?= View::e((string) ($user['email'] ?? '')) ?>" required></div>
        <div class="field">
            <label>Rang</label>
            <select name="role" class="adm-input" style="width:100%">
                <?php foreach (UserRepository::ROLES as $key => $label): ?>
                    <option value="<?= $key ?>"<?= ($user['role'] ?? 'customer') === $key ? ' selected' : '' ?>><?= View::e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="field">
            <label>Jelszó <?= $user ? '(csak ha módosítod)' : '(min. 6 karakter) *' ?></label>
            <input type="password" name="password"<?= $user ? '' : ' required' ?>>
        </div>

        <button type="submit" class="btn btn--gold">Mentés</button>
        <a href="/admin/felhasznalok" class="btn btn--outline">Mégse</a>
    </form>
</section>
