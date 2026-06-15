<?php

use App\Core\View;

/** @var array<string, mixed> $config */
/** @var bool $sent */
/** @var string $name */
$contact = $config['contact'];
$sent = $sent ?? false;
?>
<section class="page-head">
    <div class="container">
        <h1>Kapcsolat</h1>
        <p>Kérdésed van? Írj nekünk, és kollégáink hamarosan válaszolnak.</p>
    </div>
</section>

<section class="section container">
    <div class="contact-layout">
        <div class="contact-info">
            <h2>Elérhetőségeink</h2>
            <ul class="contact-list">
                <li><strong>Cégnév</strong><span><?= View::e($config['app']['name']) ?> Kft.</span></li>
                <li><strong>E-mail</strong><a href="mailto:<?= View::e($contact['email']) ?>"><?= View::e($contact['email']) ?></a></li>
                <li><strong>Telefon</strong><a href="tel:<?= View::e(str_replace(' ', '', $contact['phone'])) ?>"><?= View::e($contact['phone']) ?></a></li>
                <li><strong>Cím</strong><span><?= View::e($contact['address']) ?></span></li>
                <li><strong>Nyitvatartás</strong><span><?= View::e($contact['hours'] ?? 'H–P: 8:00–16:00') ?></span></li>
            </ul>

            <h2 style="margin-top:32px;">Munkatársaink</h2>
            <ul class="contact-list">
                <li>
                    <strong>Nagy Kristóf – ügyvezető</strong>
                    <span>Vezetés, tervezés, gyártás, fuvarozás</span>
                    <a href="mailto:nagy.kristof@net-trade.hu">nagy.kristof@net-trade.hu</a>
                    <a href="tel:+36204152695">+36 20 415 2695</a>
                </li>
                <li>
                    <strong>Nagy László – projektmenedzser</strong>
                    <span>Kereskedelem, pénzügy, marketing, ügynöki üzletág</span>
                    <a href="mailto:nagy.laszlo@net-trade.hu">nagy.laszlo@net-trade.hu</a>
                    <a href="tel:+36203871450">+36 20 3871 450</a>
                </li>
                <li>
                    <strong>Nagy Lászlóné – ügyvezető</strong>
                    <span>Vezetés, oktatás, pályázatok</span>
                    <a href="mailto:info@net-trade.hu">info@net-trade.hu</a>
                </li>
            </ul>
        </div>

        <div class="contact-form-wrap">
            <?php if ($sent): ?>
                <div class="alert alert--success">
                    Köszönjük az üzenetet<?= !empty($name) ? ', ' . View::e($name) : '' ?>! Hamarosan válaszolunk.
                </div>
            <?php endif; ?>
            <form method="post" action="/kapcsolat" class="contact-form">
                <label>Név
                    <input type="text" name="name" required>
                </label>
                <label>E-mail
                    <input type="email" name="email" required>
                </label>
                <label>Tárgy
                    <input type="text" name="subject">
                </label>
                <label>Üzenet
                    <textarea name="message" rows="5" required></textarea>
                </label>
                <button type="submit" class="btn btn--primary">Üzenet küldése</button>
            </form>
        </div>
    </div>
</section>
