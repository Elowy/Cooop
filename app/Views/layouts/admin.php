<?php

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;

/** @var array<string, mixed> $config */
/** @var string $content */
/** @var string $title */
/** @var string $active */

$nav = [
    'dashboard'   => ['/admin', 'Áttekintés', 'chart'],
    'products'    => ['/admin/termekek', 'Termékek', 'box'],
    'categories'  => ['/admin/kategoriak', 'Kategóriák', 'tree'],
    'orders'      => ['/admin/rendelesek', 'Rendelések', 'cart'],
    'messages'    => ['/admin/uzenetek', 'Üzenetek', 'mail'],
    'newsletter'  => ['/admin/hirlevel', 'Hírlevél', 'send'],
    'blog'        => ['/admin/blog', 'Blog', 'post'],
    'services_admin' => ['/admin/szolgaltatasok', 'Tevékenységek', 'layers'],
    'references'  => ['/admin/referenciak', 'Referenciák', 'star'],
    'leaders'     => ['/admin/vezetok', 'Vezetők', 'person'],
    'map'         => ['/admin/terkep', 'Térkép', 'pin'],
    'integration' => ['/admin/integracio', 'Axel integráció', 'plug'],
    'users'       => ['/admin/felhasznalok', 'Felhasználók', 'users'],
    'settings'    => ['/admin/beallitasok', 'Beállítások', 'gear'],
    'seo'         => ['/admin/seo', 'SEO', 'search'],
    'grant'       => ['/admin/palyazat', 'Pályázat', 'post'],
];
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title><?= View::e($title) ?> — Vezérlőpult</title>
    <link rel="stylesheet" href="<?= View::e(View::asset('/assets/css/style.css')) ?>">
    <link rel="icon" type="image/svg+xml" href="/assets/img/favicon.svg">
</head>
<body class="admin">
<div class="admin-shell">
    <aside class="admin-aside">
        <a href="/admin" class="admin-brand">
            <span class="brand-mark"><?= View::e($config['app']['short']) ?></span>
            <span>Vezérlőpult</span>
        </a>
        <nav class="admin-nav">
            <?php foreach ($nav as $key => [$href, $label, $icon]): ?>
                <?php if ($key === 'users' && !Auth::isAdmin()) { continue; } ?>
                <a href="<?= View::e($href) ?>" class="<?= $active === $key ? 'is-active' : '' ?>">
                    <span class="adm-ico" data-aico="<?= $icon ?>" aria-hidden="true"></span>
                    <?= View::e($label) ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="admin-aside-foot">
            <a href="/" class="admin-back">← Vissza az oldalra</a>
        </div>
    </aside>

    <div class="admin-main">
        <header class="admin-topbar">
            <h1><?= View::e($title) ?></h1>
            <form method="post" action="/admin/logout" class="logout-form">
                <?= Csrf::field() ?>
                <button type="submit" class="btn btn--outline btn--sm">Kilépés</button>
            </form>
        </header>

        <div class="admin-content">
            <?php $flash = $_SESSION['_flash_admin'] ?? null; unset($_SESSION['_flash_admin']); ?>
            <?php if ($flash): ?>
                <div class="admin-flash admin-flash--<?= ($flash['type'] ?? '') === 'ok' ? 'ok' : 'error' ?>">
                    <?= View::e((string) ($flash['text'] ?? '')) ?>
                </div>
            <?php endif; ?>
            <?= $content ?>
        </div>
    </div>
</div>
</body>
</html>
