<?php

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;

/** @var array<string, mixed> $config */
/** @var string|null $title */
/** @var string $content */

$appName = $config['app']['name'];
$pageTitle = isset($title) && $title ? "{$title} – Admin – {$appName}" : "Admin – {$appName}";
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= View::e($pageTitle) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <link rel="icon" type="image/svg+xml" href="/assets/img/favicon.svg">
</head>
<body class="admin">
<?php if (Auth::check()): ?>
    <header class="admin-bar">
        <div class="admin-bar__inner">
            <a class="admin-brand" href="/admin">
                <span class="brand-mark">NT</span>
                <span>Admin</span>
            </a>
            <nav class="admin-nav">
                <a href="/admin">Vezérlőpult</a>
                <a href="/admin/termekek">Termékek</a>
                <a href="/admin/kategoriak">Kategóriák</a>
                <a href="/" target="_blank" rel="noopener">Webshop ↗</a>
            </nav>
            <form method="post" action="/admin/kijelentkezes" class="admin-logout">
                <?= Csrf::field() ?>
                <button type="submit" class="btn btn--ghost btn--small">Kilépés</button>
            </form>
        </div>
    </header>
<?php endif; ?>

<main class="admin-main">
    <div class="container">
        <?= $content ?>
    </div>
</main>
</body>
</html>
