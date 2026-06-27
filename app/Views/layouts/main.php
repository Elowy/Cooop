<?php

use App\Core\Lang;
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

// Tevékenységek a fejléc legördülő menüjéhez (publikált, sorrendben).
$servicesNav = [];
try {
    $servicesNav = (new \App\Service\ServiceStore($seoPdo))->menu();
} catch (\Throwable $e) {
    $servicesNav = [];
}

$defaultDesc = 'Net-Trade Hungary Kft. – egyedi raklapgyártás, ipari csomagolás, nemzetközi árufuvarozás és fűrészáru-nagykereskedelem.';
$baseTitle = trim((string) ($seo['seo_title'] ?? '')) ?: ($appName . ' — ' . $config['app']['tagline']);
$ovTitle = trim((string) ($meta['title'] ?? ''));
$pageTitle = $ovTitle !== '' ? $ovTitle : ((isset($title) && $title) ? "{$title} — {$appName}" : $baseTitle);
$metaDesc = trim((string) ($meta['description'] ?? '')) ?: (trim((string) ($seo['seo_description'] ?? '')) ?: $defaultDesc);
$metaKeywords = trim((string) ($meta['keywords'] ?? '')) ?: trim((string) ($seo['seo_keywords'] ?? ''));
$ogImage = trim((string) ($meta['og_image'] ?? '')) ?: trim((string) ($seo['seo_og_image'] ?? ''));
$ogUrl = rtrim((string) $config['app']['url'], '/') . ($_SERVER['REQUEST_URI'] ?? '/');
$ogType = trim((string) ($meta['og_type'] ?? '')) ?: 'website';

$gaId = trim((string) ($seo['ga_id'] ?? ''));
$fbPixel = trim((string) ($seo['fb_pixel'] ?? ''));
$consent = \App\Core\Consent::parse($_COOKIE['nt_consent'] ?? '');
?>
<!DOCTYPE html>
<html lang="<?= Lang::locale() ?>">
<head>
    <script>document.documentElement.classList.add('js');</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0e0f0c">
    <title><?= View::e($pageTitle) ?></title>
    <meta name="description" content="<?= View::e($metaDesc) ?>">
    <?php if ($metaKeywords !== ''): ?><meta name="keywords" content="<?= View::e($metaKeywords) ?>">
    <?php endif; ?>
    <meta property="og:type" content="<?= View::e($ogType) ?>">
    <meta property="og:site_name" content="<?= View::e($appName) ?>">
    <meta property="og:title" content="<?= View::e($pageTitle) ?>">
    <meta property="og:description" content="<?= View::e($metaDesc) ?>">
    <meta property="og:url" content="<?= View::e($ogUrl) ?>">
    <?php if ($ogImage !== ''): ?><meta property="og:image" content="<?= View::e($ogImage) ?>">
    <?php endif; ?>
    <meta name="twitter:card" content="<?= $ogImage !== '' ? 'summary_large_image' : 'summary' ?>">
    <?php if ($ogType === 'product'): ?>
    <meta property="product:price:amount" content="<?= View::e((string) ($meta['product_price'] ?? '')) ?>">
    <meta property="product:price:currency" content="<?= View::e((string) ($meta['product_currency'] ?? 'HUF')) ?>">
    <meta property="og:availability" content="<?= View::e((string) ($meta['product_availability'] ?? '')) ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="<?= View::e(View::asset('/assets/css/style.css')) ?>">
    <link rel="icon" type="image/svg+xml" href="/assets/img/favicon.svg">
    <?php if ($gaId !== '' && $consent['analytics']): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= View::e($gaId) ?>"></script>
    <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?= View::e($gaId) ?>');</script>
    <?php endif; ?>
    <?php if ($fbPixel !== '' && $consent['marketing']): ?>
    <script>!function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,document,'script','https://connect.facebook.net/en_US/fbevents.js');fbq('init','<?= View::e($fbPixel) ?>');fbq('track','PageView');</script>
    <?php endif; ?>
    <?php if ($gaId !== ''): ?><script>window.NT_GA=<?= json_encode($gaId, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;</script><?php endif; ?>
    <?php if ($fbPixel !== ''): ?><script>window.NT_FBQ=<?= json_encode($fbPixel, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;</script><?php endif; ?>
</head>
<body>
<div class="scroll-progress" aria-hidden="true"><span class="scroll-progress-bar" data-scroll-progress></span></div>
<?php include dirname(__DIR__) . '/partials/header.php'; ?>

<main>
    <?= $content ?>
</main>

<button class="to-top" data-to-top aria-label="Vissza a tetejére">↑ Vissza a tetejére</button>

<?php include dirname(__DIR__) . '/partials/contact-widget.php'; ?>
<?php include dirname(__DIR__) . '/partials/cookie-wall.php'; ?>
<?php include dirname(__DIR__) . '/partials/newsletter.php'; ?>
<?php include dirname(__DIR__) . '/partials/footer.php'; ?>

<script src="<?= View::e(View::asset('/assets/js/main.js')) ?>" defer></script>
</body>
</html>
