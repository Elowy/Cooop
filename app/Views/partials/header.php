<?php

use App\Core\Auth;
use App\Core\Cart;
use App\Core\Csrf;
use App\Core\Lang;
use App\Core\View;

/** @var array<string, mixed> $config */
$count = Cart::count();
$current = Lang::code();
$user = Auth::user();
?>
<header class="site-header" data-header>
    <div class="container header-inner">
        <a href="/" class="brand" aria-label="<?= View::e($config['app']['name']) ?> főoldal">
            <span class="brand-mark">NT</span>
            <span class="brand-text">
                <strong><?= View::e($config['app']['name']) ?></strong>
                <small><?= View::e($config['app']['tagline']) ?></small>
            </span>
        </a>

        <button class="nav-toggle" data-nav-toggle aria-label="Menü" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>

        <nav class="main-nav" data-nav>
            <a href="/"><?= View::e(t('nav.home')) ?></a>
            <a href="/termekek"><?= View::e(t('nav.products')) ?></a>
            <a href="/galeria"><?= View::e(t('nav.gallery')) ?></a>
            <a href="/kalkulator"><?= View::e(t('nav.calculator')) ?></a>
            <a href="/rolunk"><?= View::e(t('nav.about')) ?></a>
            <a href="/kapcsolat"><?= View::e(t('nav.contact')) ?></a>

            <?php if ($user !== null): ?>
                <?php if (Auth::isAdmin()): ?>
                    <a href="/admin" class="nav-admin"><?= View::e(t('nav.admin')) ?></a>
                <?php endif; ?>
                <form method="post" action="/kilepes" class="logout-form">
                    <?= Csrf::field() ?>
                    <button type="submit" class="link-button"><?= View::e(t('nav.logout')) ?></button>
                </form>
            <?php else: ?>
                <a href="/belepes"><?= View::e(t('nav.login')) ?></a>
                <a href="/regisztracio"><?= View::e(t('nav.register')) ?></a>
            <?php endif; ?>

            <div class="nav-tools">
                <button type="button" class="theme-toggle" data-theme-toggle
                        aria-label="<?= View::e(t('nav.theme')) ?>" title="<?= View::e(t('nav.theme')) ?>"></button>

                <div class="lang-switch" role="group" aria-label="Nyelv / Language">
                    <?php foreach (Lang::available() as $code => $label): ?>
                        <a href="<?= View::e(Lang::switchUrl($code)) ?>"
                           class="<?= $code === $current ? 'is-active' : '' ?>"
                           hreflang="<?= View::e($code) ?>"><?= View::e($label) ?></a>
                    <?php endforeach; ?>
                </div>

                <a href="/kosar" class="cart-link">
                    <?= View::e(t('nav.cart')) ?>
                    <span class="cart-badge<?= $count ? '' : ' is-empty' ?>" data-cart-count><?= $count ?></span>
                </a>
            </div>
        </nav>
    </div>
</header>
