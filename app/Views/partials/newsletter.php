<?php

use App\Core\Csrf;
use App\Core\View;

// Hírlevél-sáv a lábléc fölött (minden publikus oldalon). A feliratkozás
// PRG-mintával működik: a /hirlevel route session-flash-t tölt vissza.
$flash = $_SESSION['_flash_newsletter'] ?? null;
unset($_SESSION['_flash_newsletter']);
$ok = is_array($flash) && ($flash['type'] ?? '') === 'ok';
?>
<section class="newsletter" id="hirlevel">
    <div class="newsletter-glow" aria-hidden="true"></div>
    <div class="container newsletter-inner reveal">
        <div class="newsletter-copy">
            <p class="eyebrow"><span class="eyebrow-dot"></span> Hírlevél</p>
            <h2 class="display">Ne maradj le semmiről</h2>
            <p class="newsletter-sub">Iratkozz fel, és elsőként értesülsz újdonságainkról, akcióinkról és hasznos tippjeinkről. Bármikor leiratkozhatsz.</p>
        </div>

        <div class="newsletter-form-col">
            <?php if ($ok): ?>
                <div class="newsletter-success">
                    <span class="confirm-check" aria-hidden="true">✓</span>
                    <p><?= View::e((string) ($flash['text'] ?? 'Sikeres feliratkozás!')) ?></p>
                </div>
            <?php else: ?>
                <form method="post" action="/hirlevel" class="newsletter-form" novalidate>
                    <?= Csrf::field() ?>
                    <div class="newsletter-fields">
                        <input type="text" name="name" placeholder="Neved (nem kötelező)" aria-label="Név" class="newsletter-input">
                        <input type="email" name="email" placeholder="E-mail címed" aria-label="E-mail cím" required class="newsletter-input">
                        <button type="submit" class="btn btn--gold">Feliratkozom</button>
                    </div>
                    <?php if (is_array($flash) && ($flash['type'] ?? '') === 'error'): ?>
                        <p class="newsletter-err"><?= View::e((string) ($flash['text'] ?? '')) ?></p>
                    <?php endif; ?>
                    <label class="newsletter-consent">
                        <input type="checkbox" name="privacy" value="1">
                        <span>Elfogadom az <a href="/adatkezeles" target="_blank" rel="noopener">adatkezelési tájékoztatót</a>.</span>
                    </label>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>
