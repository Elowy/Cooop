<?php

use App\Core\Csrf;
use App\Core\View;

/** @var array<string, mixed> $config */
/** @var bool $sent */
/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */

$contact = $config['contact'];
$v = static fn (string $k): string => View::e((string) ($old[$k] ?? ''));
$err = static fn (string $k): string => isset($errors[$k])
    ? '<p class="field-err">' . View::e($errors[$k]) . '</p>' : '';
?>
<section class="page-hero">
    <div class="hero-glow" aria-hidden="true"></div>
    <div class="container">
        <p class="eyebrow"><span class="eyebrow-dot"></span> Kapcsolat</p>
        <h1 class="display">Írjon nekünk</h1>
        <p class="section-sub">Kérdése van vagy ajánlatot kérne? Töltse ki az űrlapot, hamarosan válaszolunk.</p>
    </div>
</section>

<section class="section section--flush-top">
    <div class="container contact-grid">
        <div class="contact-form-col">
            <?php if (!empty($sent)): ?>
                <div class="form-success">
                    <span class="confirm-check" aria-hidden="true">✓</span>
                    <h2>Köszönjük az üzenetet!</h2>
                    <p class="muted">Hamarosan felvesszük Önnel a kapcsolatot a megadott elérhetőségen.</p>
                    <a href="/" class="btn btn--outline">Vissza a főoldalra</a>
                </div>
            <?php else: ?>
                <form method="post" action="/kapcsolat" class="form-card" novalidate>
                    <?= Csrf::field() ?>
                    <div class="field-row">
                        <div class="field"><label>Név *</label><input name="name" value="<?= $v('name') ?>"><?= $err('name') ?></div>
                        <div class="field"><label>E-mail *</label><input type="email" name="email" value="<?= $v('email') ?>"><?= $err('email') ?></div>
                    </div>
                    <div class="field-row">
                        <div class="field"><label>Telefon</label><input name="phone" value="<?= $v('phone') ?>"></div>
                        <div class="field"><label>Tárgy</label><input name="subject" value="<?= $v('subject') ?>"></div>
                    </div>
                    <div class="field"><label>Üzenet *</label><textarea name="message" rows="6"><?= $v('message') ?></textarea><?= $err('message') ?></div>
                    <label class="check"><input type="checkbox" name="privacy"> Elfogadom az adatkezelési tájékoztatót. *</label>
                    <?= $err('privacy') ?>
                    <button type="submit" class="btn btn--gold btn--lg">Üzenet küldése</button>
                </form>
            <?php endif; ?>
        </div>

        <aside class="contact-info">
            <h2>Elérhetőség</h2>
            <ul class="contact-list">
                <li><span>E-mail</span><a href="mailto:<?= View::e($contact['email']) ?>"><?= View::e($contact['email']) ?></a></li>
                <li><span>Telefon</span><a href="tel:<?= View::e(str_replace(' ', '', $contact['phone'])) ?>"><?= View::e($contact['phone']) ?></a></li>
                <?php if (!empty($contact['person'])): ?>
                    <li><span>Kapcsolattartó</span><strong><?= View::e($contact['person']) ?></strong></li>
                <?php endif; ?>
                <li><span>Cím</span><strong><?= View::e($contact['address']) ?></strong></li>
            </ul>
            <p class="muted">Az ügyfél sikere a mi sikerünk!</p>
        </aside>
    </div>
</section>
