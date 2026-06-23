<?php

use App\Core\View;

/** @var array<int, array<string, mixed>> $orders */

$statusTag = static function (string $status): string {
    return match ($status) {
        'paid' => '<span class="tag tag--ok">Fizetve</span>',
        'placed' => '<span class="tag tag--low">Leadva</span>',
        'pending' => '<span class="tag tag--low">Fizetésre vár</span>',
        'failed' => '<span class="tag tag--out">Sikertelen</span>',
        default => '<span class="tag tag--out">' . View::e($status) . '</span>',
    };
};
$methodLabel = static fn (string $m): string => match ($m) {
    'card' => 'Bankkártya', 'transfer' => 'Átutalás', default => $m,
};
?>
<?php if (!$orders): ?>
    <section class="panel">
        <div class="empty-state">
            <span class="adm-ico adm-ico--lg" data-aico="cart" aria-hidden="true"></span>
            <h3>Még nincsenek rendelések</h3>
            <p class="muted">Amint érkezik egy rendelés a pénztáron keresztül, itt jelenik meg.</p>
        </div>
    </section>
<?php else: ?>
    <p class="result-count"><?= count($orders) ?> rendelés</p>
    <div class="table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Szám</th><th>Dátum</th><th>Vevő</th>
                    <th class="ta-r">Tételek</th><th class="ta-r">Végösszeg</th>
                    <th>Fizetés</th><th>Állapot</th><th>Számla</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $o): ?>
                    <tr>
                        <td class="mono"><a href="/admin/rendeles/<?= View::e((string) $o['token']) ?>"><?= View::e((string) $o['number']) ?></a></td>
                        <td class="muted"><?= View::e(date('Y-m-d H:i', strtotime((string) ($o['created'] ?? 'now')))) ?></td>
                        <td><?= View::e((string) ($o['customer']['name'] ?? '')) ?></td>
                        <td class="ta-r"><?= count($o['items'] ?? []) ?></td>
                        <td class="ta-r"><?= View::huf((int) ($o['totals']['gross'] ?? 0)) ?></td>
                        <td class="muted"><?= View::e($methodLabel((string) ($o['payment']['method'] ?? ''))) ?></td>
                        <td><?= $statusTag((string) ($o['status'] ?? '')) ?></td>
                        <td><?= !empty($o['invoice']['ok']) ? View::e((string) $o['invoice']['number']) : '<span class="muted">—</span>' ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
