<?php

use App\Core\Markdown;
use App\Core\View;

/** @var string $heading */
/** @var string $mdFile */

$path = dirname(__DIR__, 2) . '/config/legal/' . basename($mdFile);
$content = is_file($path) ? Markdown::toHtml((string) file_get_contents($path)) : '<p>A dokumentum jelenleg nem érhető el.</p>';
?>
<section class="page-hero">
    <div class="hero-glow" aria-hidden="true"></div>
    <div class="container">
        <p class="eyebrow"><span class="eyebrow-dot"></span> Jogi</p>
        <h1 class="display"><?= View::e($heading) ?></h1>
    </div>
</section>

<section class="section section--flush-top">
    <div class="container narrow">
        <article class="legal-doc reveal">
            <?= $content ?>
        </article>
    </div>
</section>
