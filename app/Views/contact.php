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
                <?php if (!empty($contact['person'])): ?>
                    <li><strong>Kapcsolattartó</strong><span><?= View::e($contact['person']) ?></span></li>
                <?php endif; ?>
                <li><strong>E-mail</strong><a href="mailto:<?= View::e($contact['email']) ?>"><?= View::e($contact['email']) ?></a></li>
                <li><strong>Telefon</strong><a href="tel:<?= View::e(str_replace(' ', '', $contact['phone'])) ?>"><?= View::e($contact['phone']) ?></a></li>
                <li><strong>Cím</strong><span><?= View::e($contact['address']) ?></span></li>
                <li><strong>Nyitvatartás</strong><span>H–P: 9:00–17:00</span></li>
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
