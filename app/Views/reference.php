<?php

use App\Core\View;

/** @var array<string, mixed> $ref */

$long = trim((string) ($ref['long'] ?? ''));
$short = trim((string) ($ref['short'] ?? ''));
?>
<section class="section section--clear-top">
    <div class="container narrow">
        <p class="breadcrumb"><a href="/#referenciak">← Referenciák</a></p>

        <div class="reference-detail">
            <div class="reference-detail-logo">
                <?php if (!empty($ref['logo'])): ?>
                    <img src="/uploads/references/<?= View::e((string) $ref['logo']) ?>" alt="<?= View::e((string) $ref['name']) ?>">
                <?php else: ?>
                    <span class="reference-monogram reference-monogram--lg"><?= View::e(mb_strtoupper(mb_substr((string) $ref['name'], 0, 1))) ?></span>
                <?php endif; ?>
            </div>
            <div>
                <h1 class="display"><?= View::e((string) $ref['name']) ?></h1>
                <?php if ($short !== ''): ?><p class="reference-detail-short"><?= View::e($short) ?></p><?php endif; ?>
            </div>
        </div>

        <?php if ($long !== ''): ?>
            <div class="reference-detail-body">
                <?= nl2br(View::e($long)) ?>
            </div>
        <?php elseif ($short === ''): ?>
            <p class="muted">Ehhez a referenciához még nincs bővebb leírás.</p>
        <?php endif; ?>

        <p class="center-cta"><a href="/#referenciak" class="btn btn--outline">Vissza a referenciákhoz</a></p>
    </div>
</section>
