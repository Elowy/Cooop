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
        <h1 class="display">Pénztár</h1>

        <?php if (isset($errors['stock'])): ?>
            <div class="form-alert"><?= View::e($errors['stock']) ?></div>
        <?php endif; ?>

        <?php if ($payError): ?>
            <div class="form-alert">A bankkártyás fizetést most nem sikerült elindítani. Kérjük, próbáld újra, vagy válassz banki átutalást.</div>
        <?php endif; ?>

        <form method="post" action="/penztar" class="checkout-grid" novalidate>
            <?= Csrf::field() ?>

            <div class="checkout-form">
                <fieldset class="form-card">
                    <legend>Számlázási adatok</legend>
                    <div class="field"><label for="co-name">Név *</label><input id="co-name" name="name" autocomplete="name" value="<?= $v('name') ?>"><?= $err('name') ?></div>
                    <div class="field-row">
                        <div class="field"><label for="co-email">E-mail *</label><input id="co-email" type="email" name="email" autocomplete="email" value="<?= $v('email') ?>"><?= $err('email') ?></div>
                        <div class="field"><label for="co-phone">Telefon *</label><input id="co-phone" name="phone" type="tel" autocomplete="tel" inputmode="tel" value="<?= $v('phone') ?>"><?= $err('phone') ?></div>
                    </div>
                    <div class="field-row">
                        <div class="field"><label for="co-company">Cégnév</label><input id="co-company" name="company" autocomplete="organization" value="<?= $v('company') ?>"></div>
                        <div class="field"><label for="co-tax">Adószám</label><input id="co-tax" name="tax_number" autocomplete="off" value="<?= $v('tax_number') ?>"></div>
                    </div>
                    <div class="field-row">
                        <div class="field field--zip"><label for="co-bzip">Irányítószám *</label><input id="co-bzip" name="billing_zip" autocomplete="postal-code" inputmode="numeric" value="<?= $v('billing_zip') ?>"><?= $err('billing_zip') ?></div>
                        <div class="field"><label for="co-bcity">Város *</label><input id="co-bcity" name="billing_city" autocomplete="address-level2" value="<?= $v('billing_city') ?>"><?= $err('billing_city') ?></div>
                    </div>
                    <div class="field"><label for="co-baddr">Cím (utca, házszám) *</label><input id="co-baddr" name="billing_address" autocomplete="address-line1" value="<?= $v('billing_address') ?>"><?= $err('billing_address') ?></div>
                </fieldset>

                <fieldset class="form-card">
                    <legend>Szállítás</legend>
                    <label class="check"><input type="checkbox" name="shipping_diff" data-ship-toggle<?= $checked('shipping_diff') ?>> A szállítási cím eltér a számlázásitól</label>
                    <div class="ship-fields" data-ship-fields<?= isset($old['shipping_diff']) ? '' : ' hidden' ?>>
                        <div class="field-row">
                            <div class="field field--zip"><label for="co-szip">Irányítószám</label><input id="co-szip" name="shipping_zip" autocomplete="shipping postal-code" inputmode="numeric" value="<?= $v('shipping_zip') ?>"><?= $err('shipping_zip') ?></div>
                            <div class="field"><label for="co-scity">Város</label><input id="co-scity" name="shipping_city" autocomplete="shipping address-level2" value="<?= $v('shipping_city') ?>"><?= $err('shipping_city') ?></div>
                        </div>
                        <div class="field"><label for="co-saddr">Cím</label><input id="co-saddr" name="shipping_address" autocomplete="shipping address-line1" value="<?= $v('shipping_address') ?>"><?= $err('shipping_address') ?></div>
                    </div>
                    <div class="field"><label for="co-note">Megjegyzés</label><textarea id="co-note" name="note" rows="3"><?= $v('note') ?></textarea></div>
                </fieldset>

                <fieldset class="form-card">
                    <legend>Fizetési mód</legend>
                    <label class="radio"><input type="radio" name="payment_method" value="card"<?= $pm === 'card' ? ' checked' : '' ?>> <?= View::e($paymentLabel) ?></label>
                    <label class="radio"><input type="radio" name="payment_method" value="transfer"<?= $pm === 'transfer' ? ' checked' : '' ?>> Banki átutalás</label>
                    <?= $err('payment_method') ?>
                </fieldset>

                <label class="check check--terms"><input type="checkbox" name="terms"<?= $checked('terms') ?>> Elfogadom az ÁSZF-et és az adatkezelési tájékoztatót. *</label>
                <?= $err('terms') ?>
            </div>

            <aside class="checkout-summary">
                <div class="summary-card">
                    <h2>Összegzés</h2>
                    <ul class="summary-items">
                        <?php foreach ($cart['items'] as $it): ?>
                            <li>
                                <span><?= View::e($it['name']) ?> <small>× <?= (int) $it['qty'] ?> <?= View::e($it['unit']) ?></small></span>
                                <span><?= View::huf((int) $it['subtotal']) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <div class="summary-subtotals">
                        <div class="cart-line"><span>Nettó összesen</span><span><?= View::huf($netTotal) ?></span></div>
                        <div class="cart-line"><span>ÁFA</span><span><?= View::huf($vatTotal) ?></span></div>
                    </div>
                    <div class="summary-total">
                        <span>Végösszeg (bruttó)</span>
                        <strong class="display"><?= View::huf((int) $cart['total']) ?></strong>
                    </div>
                    <button type="submit" class="btn btn--gold btn--lg btn--block">Megrendelés elküldése</button>
                    <p class="secure-note">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                        Biztonságos, titkosított kapcsolat
                    </p>
                    <a href="/kosar" class="summary-back">← Vissza a kosárhoz</a>
                </div>
            </aside>
        </form>
    </div>
</section>
