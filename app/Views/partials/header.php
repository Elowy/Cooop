<?php

use App\Core\View;

/** @var array<string, mixed> $config */
$nav = [
    '#tevekenysegek' => 'Tevékenységek',
    '#rolunk'        => 'Rólunk',
    '#kapcsolat'     => 'Kapcsolat',
];
?>
<header class="site-header" data-header>
    <div class="container header-inner">
        <a href="#top" class="brand" aria-label="<?= View::e($config['app']['name']) ?>">
            <span class="brand-mark"><?= View::e($config['app']['short']) ?></span>
            <span class="brand-text">
                <strong><?= View::e($config['app']['name']) ?></strong>
                <small><?= View::e($config['app']['tagline']) ?></small>
            </span>
        </a>

        <button class="nav-toggle" data-nav-toggle aria-label="Menü" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>

        <nav class="main-nav" data-nav>
            <?php foreach ($nav as $href => $label): ?>
                <a href="<?= View::e($href) ?>"><?= View::e($label) ?></a>
            <?php endforeach; ?>
            <a href="#kapcsolat" class="btn btn--gold btn--sm">Ajánlatkérés</a>
        </nav>
    </div>
</header>
