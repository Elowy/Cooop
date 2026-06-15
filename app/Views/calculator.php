<?php

use App\Core\View;

/** @var array<string, mixed> $config */
/** @var array<int, array<string, mixed>> $products */
$currency = $config['app']['currency'];
?>
<section class="page-head">
    <div class="container">
        <h1><?= View::e(t('calc.title')) ?></h1>
        <p><?= View::e(t('calc.subtitle')) ?></p>
    </div>
</section>

<section class="section container">
    <div class="calc-layout">
        <form class="calc-form" data-calc autocomplete="off"
              data-currency="<?= View::e($currency) ?>">
            <label><?= View::e(t('calc.product')) ?>
                <select data-calc-product>
                    <?php foreach ($products as $p): $unit = $p['unit'] ?? 'db'; ?>
                        <option value="<?= (float) $p['price'] ?>"
                                data-unit="<?= View::e($unit) ?>">
                            <?= View::e($p['name']) ?> — <?= View::price((float) $p['price'], $currency) ?> / <?= View::e($unit) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label><?= View::e(t('calc.qty')) ?>
                <input type="number" data-calc-qty value="100" min="1" max="100000" step="1">
            </label>

            <label><?= View::e(t('calc.size')) ?>
                <select data-calc-size>
                    <option value="1"><?= View::e(t('calc.size_std')) ?></option>
                    <option value="1.3"><?= View::e(t('calc.size_large')) ?></option>
                    <option value="1.6"><?= View::e(t('calc.size_xl')) ?></option>
                </select>
            </label>
        </form>

        <aside class="calc-result">
            <p class="calc-result__label"><?= View::e(t('calc.unit_price')) ?></p>
            <p class="calc-result__unit" data-calc-unitprice>—</p>
            <p class="calc-result__label"><?= View::e(t('calc.result')) ?></p>
            <p class="calc-result__total" data-calc-total>—</p>
            <a href="/kapcsolat" class="btn btn--primary btn--block"><?= View::e(t('calc.request')) ?></a>
            <p class="calc-note"><?= View::e(t('calc.note')) ?></p>
        </aside>
    </div>
</section>
