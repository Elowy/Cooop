<?php
// Axel Pro integráció állapota és a nyitott kérdések.
?>
<div class="admin-cols">
    <section class="panel">
        <header class="panel-head"><h2>Jelenlegi állapot</h2></header>
        <ul class="kv-list">
            <li><span>Adapter</span><strong>MockAxelGateway</strong></li>
            <li><span>Készlet forrása</span><strong>Placeholder katalógus</strong></li>
            <li><span>Számlázás</span><strong class="tag tag--out">Nincs bekötve</strong></li>
            <li><span>NAV Online Számla</span><strong>Az Axel Pro oldalán</strong></li>
        </ul>
        <p class="note">
            A webshop az Axel felé egyetlen interfészen (<code>App\Integration\AxelGateway</code>)
            keresztül kommunikál. Éles bekötéskor a <code>MockAxelGateway</code> helyére egy
            XML- vagy REST-alapú adapter kerül — a shop többi része változatlan marad.
        </p>
    </section>

    <section class="panel">
        <header class="panel-head"><h2>Tisztázandó az Axelnél</h2></header>
        <ol class="num-list">
            <li>Van-e REST API a licenszhez? (dokumentáció, API-kulcs, végpontok)</li>
            <li>Ha nincs: XML import-/exportformátum a rendelésekhez és a készlethez.</li>
            <li>Ütemezett, automatikus import/export a szerveren (figyelt mappa / parancssor)?</li>
            <li>A kiállított számla száma/PDF-je visszakérdezhető-e?</li>
        </ol>
        <button type="button" class="btn btn--gold btn--sm" disabled title="Az éles bekötés után lesz aktív">
            Szinkron most
        </button>
    </section>
</div>
