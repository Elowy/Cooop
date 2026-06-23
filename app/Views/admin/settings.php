<?php

use App\Core\Csrf;
use App\Core\View;

/** @var array<string, string> $values */
/** @var bool $saved */
?>
<section class="panel" style="max-width:680px">
    <header class="panel-head">
        <h2>Kapcsolati csatornák</h2>
    </header>
    <p class="muted" style="margin-top:0">Ezek a lebegő kapcsolati gombban jelennek meg (jobb alul). Amelyik mezőt üresen hagyod, az nem jelenik meg.</p>

    <?php if (!empty($saved)): ?>
        <div class="form-success" style="padding:14px 18px;text-align:left;margin-bottom:18px">Beállítások mentve.</div>
    <?php endif; ?>

    <form method="post" action="/admin/beallitasok" class="form-card" style="background:transparent;border:0;padding:0">
        <?= Csrf::field() ?>
        <div class="field">
            <label>Messenger link</label>
            <input name="contact_messenger" value="<?= View::e($values['contact_messenger']) ?>" placeholder="https://m.me/oldalad">
        </div>
        <div class="field">
            <label>Viber (telefonszám vagy link)</label>
            <input name="contact_viber" value="<?= View::e($values['contact_viber']) ?>" placeholder="+36201234567">
        </div>
        <div class="field">
            <label>E-mail</label>
            <input name="contact_email" value="<?= View::e($values['contact_email']) ?>" placeholder="info@net-trade.hu">
        </div>
        <div class="field">
            <label>Telefon</label>
            <input name="contact_phone" value="<?= View::e($values['contact_phone']) ?>" placeholder="+36 20 387 1450">
        </div>
        <button type="submit" class="btn btn--gold">Mentés</button>
    </form>
</section>
