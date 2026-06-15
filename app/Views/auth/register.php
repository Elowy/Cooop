<?php

use App\Core\Csrf;
use App\Core\View;
use App\Models\User;

/** @var array<string, mixed> $config */
/** @var string|null $error */
/** @var array<string, string> $old */
$error = $error ?? null;
$old = $old ?? [];
$firstUser = User::count() === 0;
?>
<section class="page-head">
    <div class="container"><h1><?= View::e(t('auth.register_title')) ?></h1></div>
</section>

<section class="section container">
    <div class="auth-card reveal">
        <?php if ($error): ?>
            <div class="alert alert--error"><?= View::e(t($error)) ?></div>
        <?php elseif ($firstUser): ?>
            <div class="alert alert--success"><?= View::e(t('auth.first_admin')) ?></div>
        <?php endif; ?>
        <form method="post" action="/regisztracio" class="contact-form">
            <?= Csrf::field() ?>
            <label><?= View::e(t('auth.name')) ?>
                <input type="text" name="name" value="<?= View::e($old['name'] ?? '') ?>" required autocomplete="name">
            </label>
            <label><?= View::e(t('auth.email')) ?>
                <input type="email" name="email" value="<?= View::e($old['email'] ?? '') ?>" required autocomplete="email">
            </label>
            <label><?= View::e(t('auth.password')) ?>
                <input type="password" name="password" required minlength="6" autocomplete="new-password">
            </label>
            <label><?= View::e(t('auth.password2')) ?>
                <input type="password" name="password2" required minlength="6" autocomplete="new-password">
            </label>
            <button type="submit" class="btn btn--primary btn--block"><?= View::e(t('auth.register_btn')) ?></button>
        </form>
        <p class="auth-alt"><?= View::e(t('auth.have_account')) ?>
            <a href="/belepes"><?= View::e(t('auth.login_link')) ?></a>
        </p>
    </div>
</section>
