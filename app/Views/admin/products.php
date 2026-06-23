<?php

use App\Catalog\Categories;
use App\Core\View;
use App\Integration\Product;

/** @var Product[] $products */
/** @var Categories $cats */
/** @var string $kat */
/** @var string $q */
/** @var int $lowStock */
?>
<form method="get" action="/admin/termekek" class="admin-filters">
    <input type="search" name="q" value="<?= View::e($q) ?>" placeholder="Keresés név vagy cikkszám…" class="adm-input">
    <select name="kat" class="adm-input">
        <option value="">Minden kategória</option>
        <?php foreach ($cats->all() as $key => $node): ?>
            <option value="<?= View::e($key) ?>"<?= $kat === $key ? ' selected' : '' ?>>
                <?= str_repeat('— ', (int) $node['depth']) . View::e($node['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn--gold btn--sm">Szűrés</button>
    <?php if ($kat !== '' || $q !== ''): ?>
        <a href="/admin/termekek" class="btn btn--outline btn--sm">Törlés</a>
    <?php endif; ?>
</form>

<p class="result-count"><?= count($products) ?> termék</p>

<div class="table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Cikkszám</th><th>Termék</th><th>Kategória</th>
                <th class="ta-r">Nettó</th><th class="ta-r">Bruttó</th>
                <th class="ta-r">Készlet</th><th>Státusz</th><th></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$products): ?>
                <tr><td colspan="8" class="muted ta-c">Nincs a szűrésnek megfelelő termék.</td></tr>
            <?php else: foreach ($products as $p):
                $low = $p->stock > 0 && $p->stock < $lowStock; ?>
                <tr>
                    <td class="mono"><?= View::e($p->sku) ?></td>
                    <td><a href="/termek/<?= View::e($p->slug) ?>" target="_blank" rel="noopener"><?= View::e($p->name) ?></a></td>
                    <td class="muted"><?= View::e($cats->name($p->category)) ?></td>
                    <td class="ta-r"><?= View::huf($p->priceNet) ?></td>
                    <td class="ta-r"><?= View::huf($p->priceGross()) ?></td>
                    <td class="ta-r <?= $low ? 'stock-low' : '' ?>"><?= (int) $p->stock ?> <?= View::e($p->unit) ?></td>
                    <td>
                        <?php if (!$p->inStock()): ?><span class="tag tag--out">Elfogyott</span>
                        <?php elseif ($low): ?><span class="tag tag--low">Alacsony</span>
                        <?php else: ?><span class="tag tag--ok">Raktáron</span><?php endif; ?>
                    </td>
                    <td class="ta-r"><a class="btn btn--outline btn--sm" href="/admin/termek-seo?sku=<?= View::e($p->sku) ?>">SEO</a></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>
</div>

<p class="note">A készlet és az árak az Axel Pro-ból frissülnek (jelenleg mock). A szerkesztés
az Axelben történik — a vezérlőpult mutatja az aktuális állapotot.</p>
