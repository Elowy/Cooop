<?php

use App\Core\Csrf;
use App\Core\View;

/** @var array<string, string> $values */
?>
<div class="admin-cols">
    <section class="panel">
        <header class="panel-head"><h2>Axel Pro kapcsolat</h2></header>
        <p class="muted" style="margin-top:0">Itt állítható be, hogyan kapcsolódik a webshop az Axel Pro-hoz. Jelenleg a <strong>mock</strong> (teszt) adapter aktív; az <code>xml</code> és <code>rest</code> az éles bekötéskor lesz elérhető.</p>

        <form method="post" action="/admin/integracio" class="form-card" style="background:transparent;border:0;padding:0">
            <?= Csrf::field() ?>
            <div class="field">
                <label>Kapcsolat módja</label>
                <select name="gateway" class="adm-input" style="width:100%">
                    <?php foreach (['mock' => 'Mock (teszt)', 'xml' => 'XML fájl-csere (helyi mappa)', 'rest' => 'REST API'] as $k => $label): ?>
                        <option value="<?= $k ?>"<?= $values['gateway'] === $k ? ' selected' : '' ?>><?= View::e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label>XML adatcsere mappa (közös az Axellel)</label>
                <input name="exchange_dir" value="<?= View::e($values['exchange_dir']) ?>" placeholder="C:\axel-exchange">
            </div>
            <div class="field">
                <label>REST API URL</label>
                <input name="api_url" value="<?= View::e($values['api_url']) ?>" placeholder="https://api.axelszamlazo.hu/...">
            </div>
            <div class="field">
                <label>REST API kulcs</label>
                <input name="api_key" value="<?= View::e($values['api_key']) ?>" placeholder="(az Axeltől)">
            </div>
            <button type="submit" class="btn btn--gold">Mentés</button>
        </form>
    </section>

    <section class="panel">
        <header class="panel-head"><h2>Tisztázandó az Axelnél</h2></header>
        <ol class="num-list">
            <li>Van-e REST API a licenszhez? (dokumentáció, API-kulcs, végpontok)</li>
            <li>Ha nincs: XML import-/exportformátum a rendelésekhez és a készlethez.</li>
            <li>Ütemezett, automatikus import/export a szerveren (figyelt mappa / parancssor)?</li>
            <li>A kiállított számla száma/PDF-je visszakérdezhető-e?</li>
        </ol>
        <p class="note">A beállítások mentésre kerülnek; az éles XML/REST adapter ezeket fogja használni, amint elkészül.</p>
    </section>
</div>
