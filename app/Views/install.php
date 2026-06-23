<?php

use App\Core\Csrf;
use App\Core\View;

/** @var array<string, mixed> $config */
/** @var bool $done */
/** @var array<int, string> $errors */
/** @var array<string, mixed> $old */

$v = static fn (string $k, string $def = '') => View::e((string) ($old[$k] ?? $def));
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>Telepítő — <?= View::e($config['app']['name']) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" type="image/svg+xml" href="/assets/img/favicon.svg">
</head>
<body class="admin">
<div class="login-wrap">
    <div class="login-card" style="max-width:520px">
        <a href="/" class="login-brand"><span class="brand-mark"><?= View::e($config['app']['short']) ?></span> Telepítő</a>

        <?php if (!empty($done)): ?>
            <p class="login-sub">A weboldal már telepítve van.</p>
            <a href="/admin" class="btn btn--gold btn--block">Tovább a vezérlőpultra</a>
        <?php else: ?>
            <p class="login-sub">Add meg az adatbázis adatait és az első admin fiókot.</p>

            <?php if (!empty($errors)): ?>
                <div class="login-error" style="text-align:left">
                    <?php foreach ($errors as $e): ?><div><?= View::e($e) ?></div><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" action="/telepito" style="text-align:left">
                <?= Csrf::field() ?>
                <h3 class="sub-h">Adatbázis</h3>
                <label class="login-field">
                    <span>Típus</span>
                    <select name="driver" class="adm-input" style="width:100%">
                        <option value="mysql"<?= ($old['driver'] ?? 'mysql') === 'mysql' ? ' selected' : '' ?>>MySQL / MariaDB</option>
                        <option value="sqlite"<?= ($old['driver'] ?? '') === 'sqlite' ? ' selected' : '' ?>>SQLite (fájl)</option>
                    </select>
                </label>
                <div class="field-row">
                    <label class="login-field"><span>Host</span><input name="host" value="<?= $v('host', 'localhost') ?>"></label>
                    <label class="login-field"><span>Port</span><input name="port" value="<?= $v('port', '3306') ?>"></label>
                </div>
                <label class="login-field"><span>Adatbázis neve</span><input name="name" value="<?= $v('name') ?>"></label>
                <div class="field-row">
                    <label class="login-field"><span>Felhasználó</span><input name="user" value="<?= $v('user') ?>"></label>
                    <label class="login-field"><span>Jelszó</span><input type="password" name="pass"></label>
                </div>

                <h3 class="sub-h">Első admin fiók</h3>
                <label class="login-field"><span>Név</span><input name="admin_name" value="<?= $v('admin_name') ?>"></label>
                <label class="login-field"><span>E-mail</span><input type="email" name="admin_email" value="<?= $v('admin_email') ?>"></label>
                <label class="login-field"><span>Jelszó (min. 6 karakter)</span><input type="password" name="admin_password"></label>

                <button type="submit" class="btn btn--gold btn--block">Telepítés</button>
            </form>
        <?php endif; ?>

        <a href="/" class="login-home">← Vissza a weboldalra</a>
    </div>
</div>
</body>
</html>
