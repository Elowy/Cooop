<?php

use App\Core\View;

/** @var array<string, mixed> $config */
$contact = $config['contact'];
?>
<footer class="site-footer">
    <div class="container footer-grid">
        <div>
            <div class="brand brand--footer">
                <span class="brand-mark">NT</span>
                <strong><?= View::e($config['app']['name']) ?></strong>
            </div>
            <p class="footer-tagline"><?= View::e($config['app']['tagline']) ?></p>
        </div>

        <div>
            <h4>Vásárlás</h4>
            <ul>
                <li><a href="/termekek">Összes termék</a></li>
                <li><a href="/termekek?kategoria=routerek">Routerek</a></li>
                <li><a href="/termekek?kategoria=switchek">Switchek</a></li>
                <li><a href="/kosar">Kosár</a></li>
            </ul>
        </div>

        <div>
            <h4>Információ</h4>
            <ul>
                <li><a href="/rolunk">Rólunk</a></li>
                <li><a href="/kapcsolat">Kapcsolat</a></li>
            </ul>
        </div>

        <div>
            <h4>Elérhetőség</h4>
            <ul class="footer-contact">
                <li><a href="mailto:<?= View::e($contact['email']) ?>"><?= View::e($contact['email']) ?></a></li>
                <li><a href="tel:<?= View::e(str_replace(' ', '', $contact['phone'])) ?>"><?= View::e($contact['phone']) ?></a></li>
                <li><?= View::e($contact['address']) ?></li>
            </ul>
        </div>
    </div>
    <div class="container footer-bottom">
        <small>&copy; <?= date('Y') ?> <?= View::e($config['app']['name']) ?>. Minden jog fenntartva.</small>
    </div>
</footer>
