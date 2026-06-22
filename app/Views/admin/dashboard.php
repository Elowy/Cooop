<?php

use App\Catalog\Categories;
use App\Core\View;
use App\Integration\Product;

/** @var array{products:int,categories:int,inStock:int,out:int,stockValue:int} $kpi */
/** @var Product[] $lowStockItems */
/** @var int $lowStock */
/** @var Categories $cats */
?>
<div class="kpi-grid">
    <div class="kpi">
        <span class="kpi-l">Termékek</span>
        <span class="kpi-n display"><?= (int) $kpi['products'] ?></span>
    </div>
    <div class="kpi">
        <span class="kpi-l">Kategóriák (levél)</span>
        <span class="kpi-n display"><?= (int) $kpi['categories'] ?></span>
    </div>
    <div class="kpi">
        <span class="kpi-l">Raktáron</span>
        <span class="kpi-n display"><?= (int) $kpi['inStock'] ?></span>
    </div>
    <div class="kpi <?= $kpi['out'] > 0 ? 'kpi--warn' : '' ?>">
        <span class="kpi-l">Elfogyott</span>
        <span class="kpi-n display"><?= (int) $kpi['out'] ?></span>
    </div>
    <div class="kpi kpi--wide">
        <span class="kpi-l">Becsült készletérték (bruttó)</span>
        <span class="kpi-n display gold"><?= View::huf((int) $kpi['stockValue']) ?></span>
    </div>
</div>

<div class="admin-cols">
    <section class="panel">
        <header class="panel-head">
            <h2>Alacsony készlet (&lt; <?= (int) $lowStock ?>)</h2>
            <a href="/admin/termekek" class="panel-link">Összes termék →</a>
        </header>
        <?php if (!$lowStockItems): ?>
            <p class="muted">Nincs alacsony készletű termék. 👍</p>
        <?php else: ?>
            <ul class="mini-list">
                <?php foreach ($lowStockItems as $p): ?>
                    <li>
                        <span class="mini-name"><?= View::e($p->name) ?></span>
                        <span class="mini-cat"><?= View::e($cats->name($p->category)) ?></span>
                        <span class="stock-pill stock-low"><?= (int) $p->stock ?> <?= View::e($p->unit) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="panel">
        <header class="panel-head"><h2>Axel Pro integráció</h2></header>
        <div class="status-row">
            <span class="status-dot status-dot--off"></span>
            <div>
                <strong>Mock adapter aktív</strong>
                <p class="muted">A készlet és az árak placeholder forrásból jönnek; a számlázás még nincs bekötve.</p>
            </div>
        </div>
        <a href="/admin/integracio" class="btn btn--outline btn--sm">Integráció részletei</a>
    </section>
</div>
