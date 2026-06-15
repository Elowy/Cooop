<?php

use App\Core\Csrf;
use App\Core\View;

/** @var array<string, mixed> $config */
/** @var string|null $error */
/** @var array<string, string> $old */
$error = $error ?? null;
$old = $old ?? [];
?>
<section class="page-head">
    <div class="container"><h1><?= View::e(t('auth.login_title')) ?></h1></div>
</section>

<section class="section container">
    <div class="auth-card reveal">
        <?php if ($error): ?>
            <div class="alert alert--error"><?= View::e(t($error)) ?></div>
        <?php endif; ?>
        <form method="post" action="/belepes" class="contact-form">
            <?= Csrf::field() ?>
            <label><?= View::e(t('auth.email')) ?>
                <input type="email" name="email" value="<?= View::e($old['email'] ?? '') ?>" required autocomplete="email">
            </label>
            <label><?= View::e(t('auth.password')) ?>
                <input type="password" name="password" required autocomplete="current-password">
            </label>
            <button type="submit" class="btn btn--primary btn--block"><?= View::e(t('auth.login_btn')) ?></button>
        </form>
        <p class="auth-alt"><?= View::e(t('auth.no_account')) ?>
            <a href="/regisztracio"><?= View::e(t('auth.register_link')) ?></a>
        </p>
    </div>
</section>
