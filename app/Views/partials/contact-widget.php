<?php

use App\Core\View;
use App\Settings\SettingsStore;

/** @var array<string, mixed> $config */

$widgetPdo = null;
if (!empty($config['installed'])) {
    try {
        $widgetPdo = \App\Db\Database::instance($config['db']);
    } catch (\Throwable $e) {
        $widgetPdo = null;
    }
}
$s = (new SettingsStore($widgetPdo))->all();
$cfg = $config['contact'];

// Nincs beállítva még → config alapérték; üres string → szándékosan elrejtve.
$email = array_key_exists('contact_email', $s) ? (string) $s['contact_email'] : (string) $cfg['email'];
$phone = array_key_exists('contact_phone', $s) ? (string) $s['contact_phone'] : (string) $cfg['phone'];
$messenger = (string) ($s['contact_messenger'] ?? '');
$viber = (string) ($s['contact_viber'] ?? '');

$viberHref = static function (string $v): string {
    return (str_starts_with($v, 'http') || str_starts_with($v, 'viber:'))
        ? $v : 'viber://chat?number=' . rawurlencode($v);
};

$channels = [];
if ($messenger !== '' && preg_match('#^https?://#i', $messenger)) {
    $channels[] = ['ch' => 'messenger', 'label' => 'Messenger', 'href' => $messenger, 'ext' => true];
}
if ($viber !== '') {
    $channels[] = ['ch' => 'viber', 'label' => 'Viber', 'href' => $viberHref($viber), 'ext' => true];
}
if ($email !== '') {
    $channels[] = ['ch' => 'email', 'label' => 'E-mail', 'href' => 'mailto:' . $email, 'ext' => false];
}
if ($phone !== '') {
    $channels[] = ['ch' => 'phone', 'label' => t('contact.phone'), 'href' => 'tel:' . str_replace(' ', '', $phone), 'ext' => false];
}

if (!$channels) {
    return;
}
?>
<div class="contact-fab" data-fab>
    <div class="fab-menu" data-fab-menu>
        <?php foreach ($channels as $c): ?>
            <a class="fab-item fab-item--<?= $c['ch'] ?>" href="<?= View::e($c['href']) ?>"
               <?= $c['ext'] ? 'target="_blank" rel="noopener"' : '' ?>>
                <span class="fab-ico" data-ch="<?= $c['ch'] ?>" aria-hidden="true"></span>
                <span><?= View::e($c['label']) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
    <button class="fab-toggle" data-fab-toggle aria-label="<?= View::e(t('nav.contact')) ?>" aria-expanded="false">
        <span class="fab-saw" aria-hidden="true">
            <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
                <g class="saw-log">
                    <rect x="23" y="40" width="18" height="12" rx="3" fill="#5b3f1a"/>
                    <ellipse cx="23" cy="46" rx="3" ry="6" fill="#7a5626"/>
                    <ellipse cx="23" cy="46" rx="1.3" ry="3" fill="#5b3f1a"/>
                    <ellipse cx="41" cy="46" rx="3" ry="6" fill="#6b4a1e"/>
                </g>
                <g class="saw-tool">
                    <path d="M12 38 H52" stroke="#16110a" stroke-width="2.4" stroke-linecap="round"/>
                    <path d="M14 39.5 l3 -2.5 l3 2.5 l3 -2.5 l3 2.5 l3 -2.5 l3 2.5 l3 -2.5 l3 2.5 l3 -2.5 l3 2.5" fill="none" stroke="#16110a" stroke-width="1"/>
                    <rect x="8" y="36" width="5" height="4" rx="1.5" fill="#16110a"/>
                    <rect x="51" y="36" width="5" height="4" rx="1.5" fill="#16110a"/>
                </g>
                <g class="saw-fig saw-fig--l">
                    <circle cx="10" cy="22" r="3.4" fill="#16110a"/>
                    <path d="M10 25 L10 36 M10 28 L7 37 M10 36 L7 50 M10 36 L13 50" stroke="#16110a" stroke-width="2" stroke-linecap="round" fill="none"/>
                </g>
                <g class="saw-fig saw-fig--r">
                    <circle cx="54" cy="22" r="3.4" fill="#16110a"/>
                    <path d="M54 25 L54 36 M54 28 L57 37 M54 36 L57 50 M54 36 L51 50" stroke="#16110a" stroke-width="2" stroke-linecap="round" fill="none"/>
                </g>
            </svg>
        </span>
        <span class="fab-ico-close" aria-hidden="true"></span>
    </button>
</div>
