<?php

use App\Core\View;

/** @var array<string, mixed> $config */
$contact = $config['contact'];
// Beállítások (a fő layoutból; social linkek adminból szerkeszthetők).
$seo = $seo ?? [];
$safeUrl = static fn (string $u): string => preg_match('#^https?://#i', trim($u)) === 1 ? trim($u) : '';
$facebook = $safeUrl((string) ($seo['social_facebook'] ?? ''));
$youtube = $safeUrl((string) ($seo['social_youtube'] ?? ''));
// Pályázati közzététel linkje csak akkor, ha van mit megjeleníteni.
$hasGrant = trim((string) ($seo['grant_image'] ?? '')) !== ''
    || trim((string) ($seo['grant_title'] ?? '')) !== ''
    || trim((string) ($seo['grant_body'] ?? '')) !== '';
?>
<footer class="site-footer">
    <div class="container footer-grid">
        <div class="footer-brand">
            <img class="brand-logo" src="/assets/img/logo.svg" alt="<?= View::e($config['app']['name']) ?> Kft." width="232" height="64">
            <div class="footer-brand-text">
            <p><?= View::e($config['app']['tagline']) ?></p>
            <?php if ($facebook !== '' || $youtube !== ''): ?>
                <div class="footer-social">
                    <?php if ($facebook !== ''): ?>
                        <a href="<?= View::e($facebook) ?>" target="_blank" rel="noopener" aria-label="Facebook">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M14 9h3V6h-3c-2.2 0-4 1.8-4 4v2H7v3h3v6h3v-6h3l1-3h-4v-2c0-.6.4-1 1-1z"/></svg>
                        </a>
                    <?php endif; ?>
                    <?php if ($youtube !== ''): ?>
                        <a href="<?= View::e($youtube) ?>" target="_blank" rel="noopener" aria-label="YouTube">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M22 8.2a3 3 0 0 0-2.1-2.1C18 5.6 12 5.6 12 5.6s-6 0-7.9.5A3 3 0 0 0 2 8.2 31 31 0 0 0 1.7 12 31 31 0 0 0 2 15.8a3 3 0 0 0 2.1 2.1c1.9.5 7.9.5 7.9.5s6 0 7.9-.5a3 3 0 0 0 2.1-2.1c.3-1.9.3-3.8.3-3.8s0-1.9-.3-3.8zM10 15V9l5 3z"/></svg>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            </div>
        </div>

        <div class="footer-col">
            <h4>Elérhetőség</h4>
            <ul>
                <li><a href="mailto:<?= View::e($contact['email']) ?>"><?= View::e($contact['email']) ?></a></li>
                <li><a href="tel:<?= View::e(str_replace(' ', '', $contact['phone'])) ?>"><?= View::e($contact['phone']) ?></a></li>
                <?php if (!empty($contact['person'])): ?><li><?= View::e($contact['person']) ?></li><?php endif; ?>
                <li><?= View::e($contact['address']) ?></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4>Oldal</h4>
            <ul>
                <li><a href="/webshop">Webshop</a></li>
                <li><a href="/szolgaltatasok">Tevékenységek</a></li>
                <li><a href="/bemutatkozas">Bemutatkozás</a></li>
                <li><a href="/blog">Blog</a></li>
                <li><a href="/#kapcsolat">Kapcsolat</a></li>
                <li><a href="/admin">Vezérlőpult</a></li>
            </ul>
        </div>

        <div class="footer-col">
            <h4>Vásárlás</h4>
            <ul>
                <li><a href="/szallitas">Szállítási információk</a></li>
                <li><a href="/#referenciak">Referenciák</a></li>
                <li><a href="/#hirlevel">Hírlevél</a></li>
                <li><a href="/oldalterkep">Oldaltérkép</a></li>
            </ul>
        </div>
    </div>

    <div class="container footer-bottom">
        <small>&copy; <?= date('Y') ?> <?= View::e($config['app']['name']) ?> Kft. — Minden jog fenntartva.</small>
        <nav class="footer-legal">
            <a href="/aszf">ÁSZF</a>
            <a href="/adatkezeles">Adatkezelési tájékoztató</a>
            <?php if ($hasGrant): ?><a href="/palyazat">Pályázati közzététel</a><?php endif; ?>
            <a href="/oldalterkep">Oldaltérkép</a>
            <a href="#" data-cookie-open>Cookie-beállítások</a>
        </nav>
    </div>

    <div class="container footer-credit">
        <a href="https://luiz-tech.hu" target="_blank" rel="noopener" class="credit" aria-label="Készítette: luiz-tech.hu">
            <span class="credit-label">Készítette</span>
            <span class="credit-spark" aria-hidden="true">&lt;/&gt;</span>
            <span class="credit-name">luiz-tech.hu</span>
            <span class="credit-heart" aria-hidden="true">❤️</span>
        </a>
    </div>
</footer>
