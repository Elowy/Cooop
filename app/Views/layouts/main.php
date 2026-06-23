<?php

use App\Core\View;
use App\Settings\SettingsStore;

/** @var array<string, mixed> $config */
/** @var string|null $title */
/** @var string $content */

$appName = $config['app']['name'];

// SEO beállítások (adminból szerkeszthető); DB ha telepítve, különben fájl.
$seoPdo = null;
if (!empty($config['installed'])) {
    try {
        $seoPdo = \App\Db\Database::instance($config['db']);
    } catch (\Throwable $e) {
        $seoPdo = null;
    }
}
$seo = (new SettingsStore($seoPdo))->all();
$meta = $meta ?? []; // oldal-specifikus felülírás (pl. termékoldal)

$defaultDesc = 'Net-Trade Hungary Kft. – egyedi raklapgyártás, ipari csomagolás, nemzetközi árufuvarozás és fűrészáru-nagykereskedelem.';
$baseTitle = trim((string) ($seo['seo_title'] ?? '')) ?: ($appName . ' — ' . $config['app']['tagline']);
$ovTitle = trim((string) ($meta['title'] ?? ''));
$pageTitle = $ovTitle !== '' ? $ovTitle : ((isset($title) && $title) ? "{$title} — {$appName}" : $baseTitle);
$metaDesc = trim((string) ($meta['description'] ?? '')) ?: (trim((string) ($seo['seo_description'] ?? '')) ?: $defaultDesc);
$metaKeywords = trim((string) ($meta['keywords'] ?? '')) ?: trim((string) ($seo['seo_keywords'] ?? ''));
$ogImage = trim((string) ($meta['og_image'] ?? '')) ?: trim((string) ($seo['seo_og_image'] ?? ''));
$ogUrl = rtrim((string) $config['app']['url'], '/') . ($_SERVER['REQUEST_URI'] ?? '/');
?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <script>document.documentElement.classList.add('js');</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0e0f0c">
    <title><?= View::e($pageTitle) ?></title>
    <meta name="description" content="<?= View::e($metaDesc) ?>">
    <?php if ($metaKeywords !== ''): ?><meta name="keywords" content="<?= View::e($metaKeywords) ?>">
    <?php endif; ?>
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= View::e($appName) ?>">
    <meta property="og:title" content="<?= View::e($pageTitle) ?>">
    <meta property="og:description" content="<?= View::e($metaDesc) ?>">
    <meta property="og:url" content="<?= View::e($ogUrl) ?>">
    <?php if ($ogImage !== ''): ?><meta property="og:image" content="<?= View::e($ogImage) ?>">
    <?php endif; ?>
    <meta name="twitter:card" content="<?= $ogImage !== '' ? 'summary_large_image' : 'summary' ?>">
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
