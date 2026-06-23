<?php

use App\Core\View;
use App\Settings\SettingsStore;

/** @var array<string, mixed> $config */

$s = (new SettingsStore())->all();
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
if ($messenger !== '') {
    $channels[] = ['ch' => 'messenger', 'label' => 'Messenger', 'href' => $messenger, 'ext' => true];
}
if ($viber !== '') {
    $channels[] = ['ch' => 'viber', 'label' => 'Viber', 'href' => $viberHref($viber), 'ext' => true];
}
if ($email !== '') {
    $channels[] = ['ch' => 'email', 'label' => 'E-mail', 'href' => 'mailto:' . $email, 'ext' => false];
}
if ($phone !== '') {
    $channels[] = ['ch' => 'phone', 'label' => 'Telefon', 'href' => 'tel:' . str_replace(' ', '', $phone), 'ext' => false];
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
    <button class="fab-toggle" data-fab-toggle aria-label="Kapcsolat" aria-expanded="false">
        <span class="fab-ico-main" aria-hidden="true"></span>
        <span class="fab-ico-close" aria-hidden="true"></span>
    </button>
</div>
