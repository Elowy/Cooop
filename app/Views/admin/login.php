<?php

use App\Core\Csrf;
use App\Core\View;

/** @var string|null $error */
?>
<div class="admin-auth">
    <div class="admin-auth__card">
        <h1>Admin belépés</h1>
        <p class="admin-auth__sub">Net-Trade Hungary – webshop kezelőfelület</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert--error"><?= View::e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="/admin/bejelentkezes" class="admin-form">
            <?= Csrf::field() ?>
            <label>Felhasználónév
                <input type="text" name="user" required autofocus autocomplete="username">
            </label>
            <label>Jelszó
                <input type="password" name="password" required autocomplete="current-password">
            </label>
            <button type="submit" class="btn btn--primary btn--block">Belépés</button>
        </form>

        <p class="admin-auth__hint">← <a href="/">Vissza a webshopba</a></p>
    </div>
</div>
