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
<html lang="<?= View::e(lang()) ?>">
<head>
    <script>
    /* JS-jelző + téma azonnali beállítása villanás (FOUC) nélkül */
    (function () {
        document.documentElement.classList.add('js');
        try {
            var t = localStorage.getItem('theme');
            if (t === 'dark' || (!t && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        } catch (e) {}
    })();
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Net-Trade Hungary Kft. – ipari csomagolás, egyedi raklapok és faládák, fenyő fűrészáru, tűzifa és BRITTERM tégla, saját fuvarozással.">
    <title><?= View::e($pageTitle) ?></title>
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
