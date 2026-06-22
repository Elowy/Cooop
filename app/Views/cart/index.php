<?php

use App\Core\Csrf;
use App\Core\View;

/** @var array<int, array{product: \App\Integration\Product, qty: int, subtotal: int}> $lines */
/** @var int $total */
?>
<section class="section section--clear-top">
    <div class="container cart-wrap">
        <h1 class="display">Kosár</h1>

        <?php if (!$lines): ?>
            <div class="empty-cart">
                <p>A kosarad jelenleg üres.</p>
                <a href="/webshop" class="btn btn--gold">Irány a webshop</a>
            </div>
        <?php else: ?>
            <div class="cart-table">
                <?php foreach ($lines as $line): $p = $line['product']; ?>
                    <div class="cart-row">
                        <span class="cart-thumb" data-icon="<?= View::e($p->icon) ?>" aria-hidden="true"></span>
                        <div class="cart-info">
                            <a href="/termek/<?= View::e($p->slug) ?>" class="cart-name"><?= View::e($p->name) ?></a>
                            <small><?= View::huf($p->priceGross()) ?> / <?= View::e($p->unit) ?></small>
                        </div>
                        <form method="post" action="/kosar/frissit" class="cart-qty">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="sku" value="<?= View::e($p->sku) ?>">
                            <input type="number" name="qty" value="<?= (int) $line['qty'] ?>" min="1" max="<?= max(1, (int) $p->stock) ?>" aria-label="Mennyiség">
                            <button type="submit" class="btn btn--outline btn--sm">Frissít</button>
                        </form>
                        <span class="cart-sub"><?= View::huf($line['subtotal']) ?></span>
                        <form method="post" action="/kosar/torol" class="cart-del">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="sku" value="<?= View::e($p->sku) ?>">
                            <button type="submit" class="icon-btn" aria-label="Törlés">×</button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="cart-summary">
                <div class="cart-total">
                    <span>Végösszeg (bruttó)</span>
                    <strong class="display"><?= View::huf($total) ?></strong>
                </div>
                <button type="button" class="btn btn--gold btn--lg" disabled title="A fizetés és a számlázás a következő körben készül el">
                    Tovább a pénztárhoz
                </button>
                <p class="note">A pénztár (online fizetés + Axel Pro számlázás) a következő fejlesztési körben kerül be.</p>
            </div>
        <?php endif; ?>
    </div>
</section>
