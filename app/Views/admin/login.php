<?php

use App\Core\Csrf;
use App\Core\View;

/** @var array<string, mixed> $config */
/** @var bool $error */
/** @var bool $locked */
/** @var bool $installed */
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>Belépés — Vezérlőpult</title>
    <link rel="stylesheet" href="<?= View::e(View::asset('/assets/css/style.css')) ?>">
    <link rel="icon" type="image/svg+xml" href="/assets/img/favicon.svg">
</head>
<body class="admin">
<div class="login-wrap">
    <div class="login-card">
        <a href="/" class="login-brand">
            <span class="brand-mark"><?= View::e($config['app']['short']) ?></span>
            <strong>Vezérlőpult</strong>
        </a>
        <p class="login-sub">Jelentkezz be a folytatáshoz.</p>

        <?php if (!empty($locked)): ?>
            <div class="login-error">Túl sok sikertelen próbálkozás. Várj néhány percet, mielőtt újra próbálkozol.</div>
        <?php elseif (!empty($error)): ?>
            <div class="login-error">Hibás jelszó.</div>
        <?php endif; ?>

        <form method="post" action="/admin/login">
            <?= Csrf::field() ?>
            <?php if (!empty($installed)): ?>
                <label class="login-field">
                    <span>E-mail</span>
                    <input type="email" name="email" autocomplete="username" autofocus required>
                </label>
            <?php endif; ?>
            <label class="login-field">
                <span>Jelszó</span>
                <input type="password" name="password" autocomplete="current-password"<?= empty($installed) ? ' autofocus' : '' ?> required>
            </label>
            <button type="submit" class="btn btn--gold btn--block">Belépés</button>
        </form>

        <a href="/" class="login-home">← Vissza a weboldalra</a>
    </div>
</div>
</body>
</html>
