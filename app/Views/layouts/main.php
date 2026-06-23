<?php

use App\Core\View;

/** @var array<string, mixed> $config */
/** @var string|null $title */
/** @var string $content */

$appName = $config['app']['name'];
$pageTitle = isset($title) && $title ? "{$title} — {$appName}" : "{$appName} — {$config['app']['tagline']}";
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <script>document.documentElement.classList.add('js');</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Net-Trade Hungary Kft. – ipari csomagolás, faipari gyártás, fűrészáru és nemzetközi fuvarozás egy kézből.">
    <meta name="theme-color" content="#0e0f0c">
    <title><?= View::e($pageTitle) ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" type="image/svg+xml" href="/assets/img/favicon.svg">
</head>
<body>
<?php include dirname(__DIR__) . '/partials/header.php'; ?>

<main>
    <?= $content ?>
</main>

<?php include dirname(__DIR__) . '/partials/contact-widget.php'; ?>
<?php include dirname(__DIR__) . '/partials/footer.php'; ?>

<script src="/assets/js/main.js" defer></script>
</body>
</html>
