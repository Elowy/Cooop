<?php

use App\Core\View;

/** @var array<string, mixed>|null $order */

if ($order === null) {
    echo '<section class="panel"><p class="muted">A rendelés nem található.</p>'
        . '<a href="/admin/rendelesek" class="btn btn--outline btn--sm">Vissza a listához</a></section>';
    return;
}

$methodLabel = static fn (string $m): string => match ($m) {
    'card' => 'Bankkártya', 'transfer' => 'Átutalás', default => $m,
};
$payLabel = static fn (string $s): string => match ($s) {
    'paid' => 'Fizetve', 'pending' => 'Folyamatban', 'failed' => 'Sikertelen',
    'awaiting_transfer' => 'Átutalásra vár', default => $s,
};
$c = $order['customer'];
$b = $order['billing'];
$s = $order['shipping'] ?? null;
?>
<p class="breadcrumb"><a href="/admin/rendelesek">← Rendelések</a></p>

<div class="admin-cols">
    <section class="panel">
        <header class="panel-head">
            <h2><?= View::e((string) $order['number']) ?></h2>
            <span class="muted"><?= View::e(date('Y-m-d H:i', strtotime((string) $order['created']))) ?></span>
        </header>
        <div class="table-wrap">
            <table class="admin-table">
                <thead><tr><th>Termék</th><th class="ta-r">Egységár</th><th class="ta-r">Db</th><th class="ta-r">Összesen</th></tr></thead>
                <tbody>
                    <?php foreach ($order['items'] as $it): ?>
                        <tr>
                            <td><?= View::e((string) $it['name']) ?> <span class="muted mono"><?= View::e((string) $it['sku']) ?></span></td>
                            <td class="ta-r"><?= View::huf((int) $it['price_gross']) ?></td>
                            <td class="ta-r"><?= (int) $it['qty'] ?> <?= View::e((string) $it['unit']) ?></td>
                            <td class="ta-r"><?= View::huf((int) $it['subtotal']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot><tr><td colspan="3" class="ta-r"><strong>Végösszeg (bruttó)</strong></td><td class="ta-r"><strong class="gold"><?= View::huf((int) $order['totals']['gross']) ?></strong></td></tr></tfoot>
            </table>
        </div>
        <?php if (!empty($c['note'])): ?>
            <p class="note"><strong>Megjegyzés:</strong> <?= View::e((string) $c['note']) ?></p>
        <?php endif; ?>
    </section>

    <section class="panel">
        <header class="panel-head"><h2>Adatok</h2></header>
        <ul class="kv-list">
            <li><span>Állapot</span><strong><?= View::e((string) $order['status']) ?></strong></li>
            <li><span>Fizetési mód</span><strong><?= View::e($methodLabel((string) $order['payment']['method'])) ?></strong></li>
            <li><span>Fizetés állapota</span><strong><?= View::e($payLabel((string) $order['payment']['status'])) ?></strong></li>
            <li><span>Számla</span><strong><?= !empty($order['invoice']['ok']) ? View::e((string) $order['invoice']['number']) : 'nincs (mock)' ?></strong></li>
        </ul>

        <h3 class="sub-h">Vevő</h3>
        <p class="addr">
            <?= View::e((string) $c['name']) ?><br>
            <?php if (!empty($c['company'])): ?><?= View::e((string) $c['company']) ?><?php if (!empty($c['tax_number'])): ?> · <?= View::e((string) $c['tax_number']) ?><?php endif; ?><br><?php endif; ?>
            <a href="mailto:<?= View::e((string) $c['email']) ?>"><?= View::e((string) $c['email']) ?></a><br>
            <?= View::e((string) $c['phone']) ?>
        </p>

        <h3 class="sub-h">Számlázási cím</h3>
        <p class="addr"><?= View::e($b['zip'] . ' ' . $b['city']) ?><br><?= View::e((string) $b['address']) ?></p>

        <?php if ($s !== null): ?>
            <h3 class="sub-h">Szállítási cím</h3>
            <p class="addr"><?= View::e($s['zip'] . ' ' . $s['city']) ?><br><?= View::e((string) $s['address']) ?></p>
        <?php endif; ?>
    </section>
</div>
