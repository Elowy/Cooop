<?php

use App\Core\Csrf;
use App\Core\View;

/** @var array{items: array<int, array<string, mixed>>, total: int} $cart */
/** @var string $paymentLabel */
/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */
$payError = $payError ?? false;

$v = static fn (string $k): string => View::e((string) ($old[$k] ?? ''));
$err = static fn (string $k): string => isset($errors[$k])
    ? '<p class="field-err">' . View::e($errors[$k]) . '</p>' : '';
$checked = static fn (string $k): string => isset($old[$k]) ? ' checked' : '';
$pm = (string) ($old['payment_method'] ?? 'card');

$netTotal = 0;
foreach ($cart['items'] as $it) {
    $netTotal += (int) ($it['price_net'] ?? 0) * (int) $it['qty'];
}
$vatTotal = (int) $cart['total'] - $netTotal;
?>
<section class="section section--clear-top">
    <div class="container">
        <h1 class="display"><?= View::e(t('shop.checkout_title')) ?></h1>

        <?php if (isset($errors['stock'])): ?>
            <div class="form-alert"><?= View::e($errors['stock']) ?></div>
        <?php endif; ?>

        <?php if ($payError): ?>
            <div class="form-alert"><?= View::e(t('shop.pay_error')) ?></div>
        <?php endif; ?>

        <form method="post" action="/penztar" class="checkout-grid" novalidate>
            <?= Csrf::field() ?>

            <div class="checkout-form">
                <fieldset class="form-card">
                    <legend><?= View::e(t('shop.billing')) ?></legend>
                    <div class="field"><label for="co-name"><?= View::e(t('shop.name')) ?> *</label><input id="co-name" name="name" autocomplete="name" value="<?= $v('name') ?>"><?= $err('name') ?></div>
                    <div class="field-row">
                        <div class="field"><label for="co-email"><?= View::e(t('shop.email')) ?> *</label><input id="co-email" type="email" name="email" autocomplete="email" value="<?= $v('email') ?>"><?= $err('email') ?></div>
                        <div class="field"><label for="co-phone"><?= View::e(t('shop.phone')) ?> *</label><input id="co-phone" name="phone" type="tel" autocomplete="tel" inputmode="tel" value="<?= $v('phone') ?>"><?= $err('phone') ?></div>
                    </div>
                    <div class="field-row">
                        <div class="field"><label for="co-company"><?= View::e(t('shop.company')) ?></label><input id="co-company" name="company" autocomplete="organization" value="<?= $v('company') ?>"></div>
                        <div class="field"><label for="co-tax"><?= View::e(t('shop.tax_number')) ?></label><input id="co-tax" name="tax_number" autocomplete="off" value="<?= $v('tax_number') ?>"></div>
                    </div>
                    <div class="field-row">
                        <div class="field field--zip"><label for="co-bzip"><?= View::e(t('shop.zip')) ?> *</label><input id="co-bzip" name="billing_zip" autocomplete="postal-code" inputmode="numeric" value="<?= $v('billing_zip') ?>"><?= $err('billing_zip') ?></div>
                        <div class="field"><label for="co-bcity"><?= View::e(t('shop.city')) ?> *</label><input id="co-bcity" name="billing_city" autocomplete="address-level2" value="<?= $v('billing_city') ?>"><?= $err('billing_city') ?></div>
                    </div>
                    <div class="field"><label for="co-baddr"><?= View::e(t('shop.address_full')) ?> *</label><input id="co-baddr" name="billing_address" autocomplete="address-line1" value="<?= $v('billing_address') ?>"><?= $err('billing_address') ?></div>
                </fieldset>

                <fieldset class="form-card">
                    <legend><?= View::e(t('shop.shipping')) ?></legend>
                    <label class="check"><input type="checkbox" name="shipping_diff" data-ship-toggle<?= $checked('shipping_diff') ?>> <?= View::e(t('shop.shipping_diff')) ?></label>
                    <div class="ship-fields" data-ship-fields<?= isset($old['shipping_diff']) ? '' : ' hidden' ?>>
                        <div class="field-row">
                            <div class="field field--zip"><label for="co-szip"><?= View::e(t('shop.zip')) ?></label><input id="co-szip" name="shipping_zip" autocomplete="shipping postal-code" inputmode="numeric" value="<?= $v('shipping_zip') ?>"><?= $err('shipping_zip') ?></div>
                            <div class="field"><label for="co-scity"><?= View::e(t('shop.city')) ?></label><input id="co-scity" name="shipping_city" autocomplete="shipping address-level2" value="<?= $v('shipping_city') ?>"><?= $err('shipping_city') ?></div>
                        </div>
                        <div class="field"><label for="co-saddr"><?= View::e(t('shop.address')) ?></label><input id="co-saddr" name="shipping_address" autocomplete="shipping address-line1" value="<?= $v('shipping_address') ?>"><?= $err('shipping_address') ?></div>
                    </div>
                    <div class="field"><label for="co-note"><?= View::e(t('shop.note')) ?></label><textarea id="co-note" name="note" rows="3"><?= $v('note') ?></textarea></div>
                </fieldset>

                <fieldset class="form-card">
                    <legend><?= View::e(t('shop.payment_method')) ?></legend>
                    <label class="radio"><input type="radio" name="payment_method" value="card"<?= $pm === 'card' ? ' checked' : '' ?>> <?= View::e($paymentLabel) ?></label>
                    <label class="radio"><input type="radio" name="payment_method" value="transfer"<?= $pm === 'transfer' ? ' checked' : '' ?>> <?= View::e(t('shop.bank_transfer')) ?></label>
                    <?= $err('payment_method') ?>
                </fieldset>

                <label class="check check--terms"><input type="checkbox" name="terms"<?= $checked('terms') ?>> <?= View::e(t('shop.terms')) ?> *</label>
                <?= $err('terms') ?>
            </div>

            <aside class="checkout-summary">
                <div class="summary-card">
                    <h2><?= View::e(t('shop.summary')) ?></h2>
                    <ul class="summary-items">
                        <?php foreach ($cart['items'] as $it): ?>
                            <li>
                                <span><?= View::e($it['name']) ?> <small>× <?= (int) $it['qty'] ?> <?= View::e($it['unit']) ?></small></span>
                                <span><?= View::huf((int) $it['subtotal']) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="summary-subtotals">
                        <div class="cart-line"><span><?= View::e(t('shop.net_total')) ?></span><span><?= View::huf($netTotal) ?></span></div>
                        <div class="cart-line"><span><?= View::e(t('shop.vat_label')) ?></span><span><?= View::huf($vatTotal) ?></span></div>
                    </div>
                    <div class="summary-total">
                        <span><?= View::e(t('shop.grand_total')) ?></span>
                        <strong class="display"><?= View::huf((int) $cart['total']) ?></strong>
                    </div>
                    <button type="submit" class="btn btn--gold btn--lg btn--block"><?= View::e(t('shop.place_order')) ?></button>
                    <p class="secure-note">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                        <?= View::e(t('shop.secure_note')) ?>
                    </p>
                    <a href="/kosar" class="summary-back">← <?= View::e(t('shop.back_to_cart')) ?></a>
                </div>
            </aside>
        </form>
    </div>
</section>
