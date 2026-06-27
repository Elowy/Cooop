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
            <p class="eyebrow"><span class="eyebrow-dot"></span> <?= View::e(t('newsletter.eyebrow')) ?></p>
            <h2 class="display"><?= View::e(t('newsletter.title')) ?></h2>
            <p class="newsletter-sub"><?= View::e(t('newsletter.sub')) ?></p>
        </div>

        <div class="newsletter-form-col">
            <?php if ($ok): ?>
                <div class="newsletter-success">
                    <span class="confirm-check" aria-hidden="true">✓</span>
                    <p><?= View::e((string) ($flash['text'] ?? t('newsletter.success'))) ?></p>
                </div>
            <?php else: ?>
                <form method="post" action="/hirlevel" class="newsletter-form" novalidate>
                    <?= Csrf::field() ?>
                    <div class="newsletter-fields">
                        <input type="text" name="name" placeholder="<?= View::e(t('newsletter.name_ph')) ?>" aria-label="<?= View::e(t('newsletter.name_aria')) ?>" class="newsletter-input">
                        <input type="email" name="email" placeholder="<?= View::e(t('newsletter.email_ph')) ?>" aria-label="<?= View::e(t('newsletter.email_aria')) ?>" required class="newsletter-input">
                        <button type="submit" class="btn btn--gold"><?= View::e(t('newsletter.submit')) ?></button>
                    </div>
                    <?php if (is_array($flash) && ($flash['type'] ?? '') === 'error'): ?>
                        <p class="newsletter-err"><?= View::e((string) ($flash['text'] ?? '')) ?></p>
                    <?php endif; ?>
                    <label class="newsletter-consent">
                        <input type="checkbox" name="privacy" value="1">
                        <span><?= View::e(t('newsletter.consent_pre')) ?> <a href="/adatkezeles" target="_blank" rel="noopener"><?= View::e(t('newsletter.consent_link')) ?></a>.</span>
                    </label>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>
