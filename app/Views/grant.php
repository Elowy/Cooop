<?php

use App\Core\Markdown;
use App\Core\View;

/** @var array<string, string> $grant */

$rows = [
    'Projekt azonosító' => $grant['id'],
    'Alap / program' => $grant['fund'],
    'Támogatás összege' => $grant['amount'],
    'Támogatás intenzitása' => $grant['intensity'],
    'Megvalósítás kezdete' => $grant['from'],
    'Megvalósítás befejezése' => $grant['to'],
];
$rows = array_filter($rows, static fn ($v) => trim((string) $v) !== '');
$bodyHtml = trim($grant['body']) !== '' ? Markdown::toHtml($grant['body']) : '';
$heading = trim($grant['title']) !== '' ? $grant['title'] : 'Pályázati közzététel';
?>
<section class="page-hero">
    <div class="hero-glow" aria-hidden="true"></div>
    <div class="container">
        <p class="eyebrow"><span class="eyebrow-dot"></span> Pályázat</p>
        <h1 class="display"><?= View::e($heading) ?></h1>
    </div>
</section>

<section class="section section--flush-top">
    <div class="container narrow">
        <article class="legal-doc reveal">
            <?php if (trim($grant['image']) !== ''): ?>
                <img class="grant-infoblock" src="<?= View::e($grant['image']) ?>" alt="Pályázati infoblokk">
            <?php endif; ?>

            <?php if ($rows !== []): ?>
                <table class="grant-table">
                    <tbody>
                    <?php foreach ($rows as $label => $value): ?>
                        <tr>
                            <th scope="row"><?= View::e($label) ?></th>
                            <td><?= View::e((string) $value) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <?= $bodyHtml ?>
        </article>
    </div>
</section>
