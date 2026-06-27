<?php

use App\Core\Auth;
use App\Core\Cart;
use App\Core\Lang;
use App\Core\View;

/** @var array<string, mixed> $config */
/** @var array<int, array{slug: string, title: string}> $servicesNav */
$servicesNav = $servicesNav ?? [];
// A label-ek fordítási kulcsok (lásd config/lang/*.php).
$nav = [
    '/#kategoriak'  => 'nav.categories',
    '/#rolunk'      => 'nav.about',
    '/#terkep'      => 'nav.map',
    '/#referenciak' => 'nav.references',
    '/blog'         => 'nav.blog',
    '/#kapcsolat'   => 'nav.contact',
];
// Megjegyzés: a Webshop és a „Tevékenységek" legördülő külön renderelődik alább.
$cartCount = Cart::count();
$me = Auth::user();
?>
<header class="site-header" data-header>
    <div class="container header-inner">
        <a href="/" class="brand" aria-label="<?= View::e($config['app']['name']) ?>">
            <img class="brand-logo" src="/assets/img/logo.svg" alt="<?= View::e($config['app']['name']) ?> Kft." width="232" height="64">
        </a>

        <button class="nav-toggle" data-nav-toggle aria-label="<?= View::e(t('nav.menu')) ?>" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>

        <nav class="main-nav" data-nav>
            <a href="/webshop"><?= View::e(t('nav.shop')) ?></a>
            <?php if (!empty($servicesNav)): ?>
                <div class="nav-group">
                    <a href="/szolgaltatasok" class="nav-group-trigger" aria-haspopup="true">
                        <?= View::e(t('nav.services')) ?> <span class="nav-caret" aria-hidden="true">▾</span>
                    </a>
                    <div class="nav-dropdown">
                        <?php foreach ($servicesNav as $sv): ?>
                            <a href="/szolgaltatasok/<?= View::e((string) $sv['slug']) ?>"><?= View::e((string) $sv['title']) ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <a href="/szolgaltatasok"><?= View::e(t('nav.services')) ?></a>
            <?php endif; ?>
            <?php foreach ($nav as $href => $labelKey): ?>
                <a href="<?= View::e($href) ?>"><?= View::e(t($labelKey)) ?></a>
            <?php endforeach; ?>
            <a href="<?= $me !== null ? '/fiokom' : '/belepes' ?>" class="account-link" aria-label="<?= View::e($me !== null ? t('nav.account') : t('nav.login')) ?>">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>
                </svg>
                <span><?= View::e($me !== null ? t('nav.account') : t('nav.login')) ?></span>
            </a>
            <a href="/kosar" class="cart-link<?= $cartCount > 0 ? ' has-items' : '' ?>" data-cart-link aria-label="<?= View::e(t('nav.cart')) ?>">
                <svg class="cart-icon" width="18" height="18" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 8h12l-1 12H7L6 8z"/><path d="M9 8a3 3 0 0 1 6 0"/>
                </svg>
                <span><?= View::e(t('nav.cart')) ?></span>
                <span class="cart-badge" data-cart-badge<?= $cartCount > 0 ? '' : ' hidden' ?>><?= (int) $cartCount ?></span>
            </a>
            <div class="lang-switch" role="group" aria-label="<?= View::e(t('lang.switch')) ?>">
                <?php foreach (Lang::available() as $code => $name): ?>
                    <a href="/nyelv/<?= $code ?>" class="lang-opt<?= Lang::locale() === $code ? ' is-active' : '' ?>"
                       hreflang="<?= $code ?>" lang="<?= $code ?>" title="<?= View::e($name) ?>"<?= Lang::locale() === $code ? ' aria-current="true"' : '' ?>><?= strtoupper($code) ?></a>
                <?php endforeach; ?>
            </div>
        </nav>
    </div>
</header>
