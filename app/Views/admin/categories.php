<?php

use App\Core\View;

/** @var array<int, array<string, mixed>> $catsTree */
/** @var array<string, int> $counts */

$render = function (array $nodes) use (&$render, $counts): void {
    echo '<ul class="adm-tree">';
    foreach ($nodes as $node) {
        $key = $node['key'];
        $children = $node['children'] ?? [];
        $count = $counts[$key] ?? 0;
        echo '<li>';
        printf(
            '<div class="adm-tree-row"><a href="/admin/termekek?kat=%s" class="adm-tree-name">%s</a><span class="adm-tree-count">%d termék</span></div>',
            urlencode($key),
            View::e($node['name']),
            $count
        );
        if ($children) {
            $render($children);
        }
        echo '</li>';
    }
    echo '</ul>';
};
?>
<section class="panel">
    <header class="panel-head">
        <h2>Kategóriastruktúra</h2>
        <span class="panel-link">A megadott termékstruktúra alapján</span>
    </header>
    <div class="adm-tree-wrap">
        <?php $render($catsTree); ?>
    </div>
    <p class="note">A számok a kategória teljes ágára (leszármazottakkal együtt) vonatkoznak.
    A kategóriafa a <code>config/categories.php</code> fájlból jön.</p>
</section>
