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
        <h1><?= View::e(t('nav.contact')) ?></h1>
        <p><?= View::e(t('contact.subtitle')) ?></p>
    </div>
</section>

<section class="section container">
    <div class="contact-layout">
        <div class="contact-info reveal">
            <h2><?= View::e(t('contact.details')) ?></h2>
            <ul class="contact-list">
                <li><strong><?= View::e(t('contact.company')) ?></strong><span><?= View::e($config['app']['name']) ?> Kft.</span></li>
                <li><strong><?= View::e(t('contact.email')) ?></strong><a href="mailto:<?= View::e($contact['email']) ?>"><?= View::e($contact['email']) ?></a></li>
                <li><strong><?= View::e(t('contact.phone')) ?></strong><a href="tel:<?= View::e(str_replace(' ', '', $contact['phone'])) ?>"><?= View::e($contact['phone']) ?></a></li>
                <li><strong><?= View::e(t('contact.address')) ?></strong><span><?= View::e($contact['address']) ?></span></li>
                <li><strong><?= View::e(t('contact.hours')) ?></strong><span><?= View::e($contact['hours'] ?? 'H–P: 8:00–16:00') ?></span></li>
            </ul>

            <h2 style="margin-top:32px;"><?= View::e(t('contact.staff')) ?></h2>
            <ul class="contact-list">
                <li>
                    <strong>Nagy Kristóf – ügyvezető</strong>
                    <span><?= View::e(t('contact.role_kristof')) ?></span>
                    <a href="mailto:nagy.kristof@net-trade.hu">nagy.kristof@net-trade.hu</a>
                    <a href="tel:+36204152695">+36 20 415 2695</a>
                </li>
                <li>
                    <strong>Nagy László – projektmenedzser</strong>
                    <span><?= View::e(t('contact.role_laszlo')) ?></span>
                    <a href="mailto:nagy.laszlo@net-trade.hu">nagy.laszlo@net-trade.hu</a>
                    <a href="tel:+36203871450">+36 20 3871 450</a>
                </li>
                <li>
                    <strong>Nagy Lászlóné – ügyvezető</strong>
                    <span><?= View::e(t('contact.role_laszlone')) ?></span>
                    <a href="mailto:info@net-trade.hu">info@net-trade.hu</a>
                </li>
            </ul>
        </div>

        <div class="contact-form-wrap reveal">
            <?php if ($sent): ?>
                <div class="alert alert--success">
                    <?= View::e(t('contact.sent')) ?><?= !empty($name) ? ', ' . View::e($name) : '' ?><?= View::e(t('contact.sent_after')) ?>
                </div>
            <?php endif; ?>
            <form method="post" action="/kapcsolat" class="contact-form">
                <label><?= View::e(t('form.name')) ?>
                    <input type="text" name="name" required>
                </label>
                <label><?= View::e(t('form.email')) ?>
                    <input type="email" name="email" required>
                </label>
                <label><?= View::e(t('form.subject')) ?>
                    <input type="text" name="subject">
                </label>
                <label><?= View::e(t('form.message')) ?>
                    <textarea name="message" rows="5" required></textarea>
                </label>
                <button type="submit" class="btn btn--primary"><?= View::e(t('form.send')) ?></button>
            </form>
        </div>
    </div>
</section>
