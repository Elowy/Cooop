<?php

use App\Core\Cart;
use App\Core\View;

/** @var array<string, mixed> $config */
$count = Cart::count();
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
            <a href="/">Főoldal</a>
            <a href="/termekek">Termékek</a>
            <a href="/rolunk">Rólunk</a>
            <a href="/kapcsolat">Kapcsolat</a>
            <a href="/kosar" class="cart-link">
                Kosár
                <span class="cart-badge<?= $count ? '' : ' is-empty' ?>" data-cart-count><?= $count ?></span>
            </a>
        </nav>
    </div>
</header>
