<?php

use App\Core\View;

/** @var array<string, mixed> $config */
$contact = $config['contact'];
?>
<footer class="site-footer" id="kapcsolat">
    <div class="container footer-grid">
        <div class="footer-brand">
            <span class="brand-mark"><?= View::e($config['app']['short']) ?></span>
            <div>
                <strong><?= View::e($config['app']['name']) ?> Kft.</strong>
                <p><?= View::e($config['app']['tagline']) ?></p>
            </div>
        </div>

        <div class="footer-col">
            <h4>Elérhetőség</h4>
            <ul>
                <li><a href="mailto:<?= View::e($contact['email']) ?>"><?= View::e($contact['email']) ?></a></li>
                <li><a href="tel:<?= View::e(str_replace(' ', '', $contact['phone'])) ?>"><?= View::e($contact['phone']) ?></a></li>
                <li><?= View::e($contact['address']) ?></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4>Oldal</h4>
            <ul>
                <li><a href="/webshop">Webshop</a></li>
                <li><a href="/#kategoriak">Kategóriák</a></li>
                <li><a href="#rolunk">Rólunk</a></li>
                <li><a href="#top">Vissza fel</a></li>
            </ul>
        </div>
    </div>

    <div class="container footer-bottom">
        <small>&copy; <?= date('Y') ?> <?= View::e($config['app']['name']) ?> Kft. — Minden jog fenntartva.</small>
        <small class="footer-note">Vázlat / placeholder tartalom</small>
    </div>
</footer>
