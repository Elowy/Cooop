<?php

use App\Core\Csrf;

/** @var bool $error */
?>
<section class="section section--clear-top">
    <div class="container narrow auth-wrap">
        <h1 class="display">Belépés</h1>
        <p class="muted">Lépj be a fiókodba a rendeléseid követéséhez és a gyorsabb pénztárhoz.</p>

        <?php if (!empty($error)): ?>
            <div class="form-alert">Hibás e-mail cím vagy jelszó.</div>
        <?php endif; ?>

        <form method="post" action="/belepes" class="form-card auth-form">
            <?= Csrf::field() ?>
            <div class="field"><label for="lg-email">E-mail</label><input id="lg-email" type="email" name="email" autocomplete="email" required></div>
            <div class="field"><label for="lg-pw">Jelszó</label><input id="lg-pw" type="password" name="password" autocomplete="current-password" required></div>
            <button type="submit" class="btn btn--gold btn--lg btn--block">Belépés</button>
        </form>

        <p class="auth-alt">Még nincs fiókod? <a href="/regisztracio">Regisztrálj</a></p>
    </div>
</section>
