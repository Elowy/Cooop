<?php

use App\Core\View;

/** @var array<string, mixed> $config */
/** @var array<int, array<string, mixed>> $categories */
/** @var array<int, array<string, mixed>> $featured */

$services = [
    [
        'icon'  => 'packaging',
        'title' => 'Ipari csomagolás',
        'text'  => 'Gépek, gyártósorok és nagy értékű áruk feladatra szabott csomagolása légi, tengeri és közúti szállításhoz – igény szerint RAPID expressz kivitelezéssel.',
    ],
    [
        'icon'  => 'pallet',
        'title' => 'Faipari gyártás',
        'text'  => 'Egyedi raklapok, exportládák, ömlesztett fatermékek és speciális faszerkezetek gyártása – kizárólag fenntartható forrásból származó alapanyagból.',
    ],
    [
        'icon'  => 'truck',
        'title' => 'Nemzetközi fuvarozás',
        'text'  => '2021 óta saját nyergesvontatóval végzünk közúti fuvarozást – akár a saját logisztikánkhoz, akár megbízásból, több országba.',
    ],
    [
        'icon'  => 'brick',
        'title' => 'BRITTERM tégla képviselet',
        'text'  => '2017 óta a szlovák BRITTERM téglagyártó magyarországi képviselője vagyunk – minőségi falazó-, kémény- és blokktéglák kedvező áron.',
    ],
    [
        'icon'  => 'lumber',
        'title' => 'Fűrészáru és tűzifa',
        'text'  => 'Közel 30 év tapasztalattal kínálunk minőségi szlovák fenyő fűrészárut és tűzifát nagykereskedelmi mennyiségben, megbízható, folyamatos forrásból.',
    ],
    [
        'icon'  => 'education',
        'title' => 'Oktatás és képzés',
        'text'  => 'Családi vállalkozásként a tudás átadása is fontos számunkra: szakmai oktatással és képzésekkel is foglalkozunk.',
    ],
];
?>
<section class="hero">
    <div class="container hero-inner">
        <div class="hero-copy">
            <p class="eyebrow">Net-Trade Hungary Kft.</p>
            <h1>Ipari csomagolás, <span>raklap és fűrészáru</span> egy kézből</h1>
            <p class="lead">Családi vállalkozásként a csomagolástól a faipari gyártáson át a fuvarozásig kísérjük végig partnereinket. „Az ügyfél sikere a mi sikerünk.”</p>
            <div class="hero-actions">
                <a href="/termekek" class="btn btn--primary">Termékek böngészése</a>
                <a href="/kapcsolat" class="btn btn--ghost">Ajánlatot kérek</a>
            </div>
            <ul class="hero-usps">
                <li>✓ 100% minőségi garancia</li>
                <li>✓ Közel 30 év tapasztalat</li>
                <li>✓ Saját fuvarozás</li>
            </ul>
        </div>
        <div class="hero-art" aria-hidden="true">
            <img src="/assets/img/hero.svg" alt="" width="480" height="380">
        </div>
    </div>
</section>

<section class="section container">
    <div class="section-head">
        <h2>Tevékenységeink</h2>
        <a href="/rolunk" class="link-arrow">Rólunk →</a>
    </div>
    <div class="feature-row feature-row--services">
        <?php foreach ($services as $s): ?>
            <div class="feature feature--service">
                <span class="feature__icon" data-icon="<?= View::e($s['icon']) ?>"></span>
                <h3><?= View::e($s['title']) ?></h3>
                <p><?= View::e($s['text']) ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="section section--alt">
    <div class="container">
        <div class="section-head">
            <h2>Termékkínálatunk</h2>
            <a href="/termekek" class="link-arrow">Összes termék →</a>
        </div>
        <div class="category-grid">
            <?php foreach ($categories as $cat): ?>
                <a href="/termekek?kategoria=<?= View::e($cat['slug']) ?>" class="category-card">
                    <span class="category-card__icon" data-icon="<?= View::e($cat['icon'] ?? '') ?>"></span>
                    <span class="category-card__name"><?= View::e($cat['name']) ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="section-head" style="margin-top:48px;">
            <h2>Kiemelt termékek</h2>
            <a href="/termekek" class="link-arrow">Tovább →</a>
        </div>
        <div class="product-grid">
            <?php foreach ($featured as $product): ?>
                <?php include dirname(__DIR__) . '/Views/partials/product-card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section container">
    <div class="feature-row">
        <div class="feature">
            <h3>100% minőségi garancia</h3>
            <p>Minőségi munkát adunk ki a kezünkből; ha mégis hiba lenne, cseréljük vagy javítjuk. Az ügyfél elégedettsége az első.</p>
        </div>
        <div class="feature">
            <h3>Fenntartható alapanyag</h3>
            <p>Csak felelős, fenntartható forrásból származó faanyaggal dolgozunk, és minden faforgácsot igyekszünk hasznosítani.</p>
        </div>
        <div class="feature">
            <h3>Gyors, megbízható kiszolgálás</h3>
            <p>Viszonteladói és projektrendeléseket jellemzően 1–1,5 héten belül teljesítünk, saját fuvareszközzel is.</p>
        </div>
    </div>
</section>
