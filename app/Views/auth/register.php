<?php

use App\Core\Csrf;
use App\Core\View;

/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */

$v = static fn (string $k): string => View::e((string) ($old[$k] ?? ''));
$err = static fn (string $k): string => isset($errors[$k])
    ? '<p class="field-err">' . View::e($errors[$k]) . '</p>' : '';
?>
<section class="section section--clear-top">
    <div class="container narrow auth-wrap">
        <h1 class="display">Regisztráció</h1>
        <p class="muted">Hozz létre fiókot a gyorsabb pénztárhoz és a rendeléstörténethez.</p>

        <form method="post" action="/regisztracio" class="form-card auth-form" novalidate>
            <?= Csrf::field() ?>
            <div class="field"><label for="rg-name">Név</label><input id="rg-name" name="name" autocomplete="name" value="<?= $v('name') ?>"><?= $err('name') ?></div>
            <div class="field"><label for="rg-email">E-mail</label><input id="rg-email" type="email" name="email" autocomplete="email" value="<?= $v('email') ?>"><?= $err('email') ?></div>
            <div class="field"><label for="rg-pw">Jelszó (min. 6 karakter)</label><input id="rg-pw" type="password" name="password" autocomplete="new-password"><?= $err('password') ?></div>
            <label class="check"><input type="checkbox" name="privacy"<?= isset($old['privacy']) ? ' checked' : '' ?>> Elfogadom az <a href="/adatkezeles" target="_blank" rel="noopener">adatkezelési tájékoztatót</a>.</label>
            <?= $err('privacy') ?>
            <button type="submit" class="btn btn--gold btn--lg btn--block">Fiók létrehozása</button>
        </form>

        <p class="auth-alt">Van már fiókod? <a href="/belepes">Lépj be</a></p>
    </div>
</section>
