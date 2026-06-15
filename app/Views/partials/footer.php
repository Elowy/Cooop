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
            <h4><?= View::e(t('footer.products')) ?></h4>
            <ul>
                <li><a href="/termekek?kategoria=raklapok">Raklapok</a></li>
                <li><a href="/termekek?kategoria=fureszaru">Fűrészáru</a></li>
                <li><a href="/termekek?kategoria=tuzifa">Tűzifa</a></li>
                <li><a href="/termekek?kategoria=teglak">BRITTERM téglák</a></li>
            </ul>
        </div>

        <div>
            <h4><?= View::e(t('footer.info')) ?></h4>
            <ul>
                <li><a href="/rolunk"><?= View::e(t('nav.about')) ?></a></li>
                <li><a href="/galeria"><?= View::e(t('nav.gallery')) ?></a></li>
                <li><a href="/kalkulator"><?= View::e(t('nav.calculator')) ?></a></li>
                <li><a href="/kapcsolat"><?= View::e(t('nav.contact')) ?></a></li>
            </ul>
        </div>

        <div>
            <h4><?= View::e(t('footer.contact')) ?></h4>
            <ul class="footer-contact">
                <li><a href="mailto:<?= View::e($contact['email']) ?>"><?= View::e($contact['email']) ?></a></li>
                <li><a href="tel:<?= View::e(str_replace(' ', '', $contact['phone'])) ?>"><?= View::e($contact['phone']) ?></a></li>
                <li><?= View::e($contact['address']) ?></li>
            </ul>
        </div>
    </div>
    <div class="container footer-bottom">
        <small>&copy; <?= date('Y') ?> <?= View::e($config['app']['name']) ?>. <?= View::e(t('footer.rights')) ?></small>
    </div>
</footer>
