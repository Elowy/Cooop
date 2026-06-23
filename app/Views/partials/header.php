<?php

use App\Core\Cart;
use App\Core\View;

/** @var array<string, mixed> $config */
$nav = [
    '/webshop'      => 'Webshop',
    '/#kategoriak'  => 'Kategóriák',
    '/#rolunk'      => 'Rólunk',
    '/#terkep'      => 'Térkép',
    '/#referenciak' => 'Referenciák',
    '/#kapcsolat'   => 'Kapcsolat',
];
$cartCount = Cart::count();
?>
<header class="site-header" data-header>
    <div class="container header-inner">
        <a href="/" class="brand" aria-label="<?= View::e($config['app']['name']) ?>">
            <img class="brand-logo" src="/assets/img/logo.svg" alt="<?= View::e($config['app']['name']) ?> Kft." width="232" height="64">
        </a>

        <button class="nav-toggle" data-nav-toggle aria-label="Menü" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>

        <nav class="main-nav" data-nav>
            <?php foreach ($nav as $href => $label): ?>
                <a href="<?= View::e($href) ?>"><?= View::e($label) ?></a>
            <?php endforeach; ?>
            <a href="/kosar" class="cart-link<?= $cartCount > 0 ? ' has-items' : '' ?>" aria-label="Kosár">
                <svg class="cart-icon" width="18" height="18" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 8h12l-1 12H7L6 8z"/><path d="M9 8a3 3 0 0 1 6 0"/>
                </svg>
                <span>Kosár</span>
                <?php if ($cartCount > 0): ?><span class="cart-badge"><?= (int) $cartCount ?></span><?php endif; ?>
            </a>
        </nav>
    </div>
</header>
