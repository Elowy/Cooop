<?php

use App\Core\Lang;
use App\Core\Markdown;
use App\Core\View;

/** @var string $eyebrow */
/** @var string $heading */
/** @var string $mdFile */
/** @var string|null $lead */

$path = Lang::file(dirname(__DIR__, 2) . '/config/pages', basename($mdFile));
$body = is_file($path) ? Markdown::toHtml((string) file_get_contents($path)) : '<p>A tartalom jelenleg nem érhető el.</p>';
?>
<section class="page-hero">
    <div class="hero-glow" aria-hidden="true"></div>
    <div class="container">
        <p class="eyebrow"><span class="eyebrow-dot"></span> <?= View::e($eyebrow) ?></p>
        <h1 class="display"><?= View::e($heading) ?></h1>
        <?php if (!empty($lead)): ?><p class="section-sub"><?= View::e($lead) ?></p><?php endif; ?>
    </div>
</section>

<section class="section section--flush-top">
    <div class="container narrow">
        <article class="legal-doc reveal">
            <?= $body ?>
        </article>
    </div>
</section>
