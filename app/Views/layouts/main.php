<?php

use App\Core\Cart;
use App\Core\View;

/** @var array<string, mixed> $config */
/** @var string|null $title */
/** @var string $content */

$appName = $config['app']['name'];
$pageTitle = isset($title) && $title ? "{$title} – {$appName}" : "{$appName} – {$config['app']['tagline']}";
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= View::e($config['app']['tagline']) ?> – routerek, switchek, kábelek, kamerák és hálózati eszközök kedvező áron.">
    <title><?= View::e($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" type="image/svg+xml" href="/assets/img/favicon.svg">
</head>
<body>
<?php include dirname(__DIR__) . '/partials/header.php'; ?>

<main>
    <?= $content ?>
</main>

<?php include dirname(__DIR__) . '/partials/footer.php'; ?>

<script src="/assets/js/main.js" defer></script>
</body>
</html>
