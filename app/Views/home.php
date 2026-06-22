<?php

use App\Core\View;
use App\Integration\Product;

/** @var array<string, mixed> $config */
/** @var Product[] $featured */

$activities = [
    ['icon' => 'packaging', 'title' => 'Ipari csomagolás',  'text' => 'Gépek és nagy értékű áruk feladatra szabott csomagolása.'],
    ['icon' => 'wood',      'title' => 'Faipari gyártás',    'text' => 'Egyedi raklapok, exportládák és faszerkezetek.'],
    ['icon' => 'truck',     'title' => 'Nemzetközi fuvarozás', 'text' => 'Saját nyergesvontatóval, megbízható kiszolgálással.'],
    ['icon' => 'lumber',    'title' => 'Fűrészáru & tűzifa', 'text' => 'Minőségi fenyő fűrészáru és tűzifa nagykereskedés.'],
    ['icon' => 'brick',     'title' => 'BRITTERM tégla',     'text' => 'A szlovák BRITTERM hivatalos magyar képviselete.'],
];

$stats = [
    ['n' => '30', 'l' => 'év tapasztalat'],
    ['n' => '100%', 'l' => 'minőségi garancia'],
    ['n' => '5+', 'l' => 'fő tevékenység'],
    ['n' => '1', 'l' => 'megbízható partner'],
];
?>
<section class="hero" id="top">
    <div class="hero-glow" aria-hidden="true"></div>
    <div class="container hero-inner">
        <div class="hero-copy reveal">
            <p class="eyebrow"><span class="eyebrow-dot"></span> <?= View::e($config['app']['tagline']) ?></p>
            <h1 class="display">
                Amit ránk bíznak,<br><span class="gold">azt biztonságban</span> szállítjuk.
            </h1>
            <p class="hero-lead">
                Placeholder bevezető szöveg a Net-Trade Hungary Kft.-ről — ipari csomagolás,
                faipari gyártás és logisztika egy kézből. (A végleges szöveg később.)
            </p>
            <div class="hero-actions">
                <a href="/webshop" class="btn btn--gold">Irány a webshop</a>
                <a href="#tevekenysegek" class="btn btn--outline">Tevékenységeink</a>
            </div>
        </div>
        <div class="hero-visual reveal" aria-hidden="true">
            <img src="/assets/img/hero.svg" alt="" width="560" height="520">
        </div>
    </div>

    <div class="container">
        <div class="stats-bar reveal">
            <?php foreach ($stats as $s): ?>
                <div class="stat">
                    <span class="stat-n display"><?= View::e($s['n']) ?></span>
                    <span class="stat-l"><?= View::e($s['l']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php if (!empty($featured)): ?>
<section class="section section--alt" id="kiemelt">
    <div class="container">
        <header class="section-head reveal">
            <p class="eyebrow"><span class="eyebrow-dot"></span> Webshop</p>
            <h2 class="display">Kiemelt termékek</h2>
            <p class="section-sub">Néhány cikk a kínálatból — placeholder ár és készlet.</p>
        </header>

        <div class="card-grid product-grid">
            <?php foreach ($featured as $p): ?>
                <article class="card product-card reveal">
                    <a class="product-media" href="/termek/<?= View::e($p->slug) ?>" data-icon="<?= View::e($p->icon) ?>" aria-label="<?= View::e($p->name) ?>">
                        <span class="badge">Raktáron</span>
                    </a>
                    <h3><a href="/termek/<?= View::e($p->slug) ?>"><?= View::e($p->name) ?></a></h3>
                    <p><?= View::e($p->short) ?></p>
                    <div class="price-row">
                        <span class="price"><?= View::huf($p->priceGross()) ?></span>
                        <span class="price-unit">/ <?= View::e($p->unit) ?> · bruttó</span>
                    </div>
                    <a href="/termek/<?= View::e($p->slug) ?>" class="card-link">Megnézem →</a>
                </article>
            <?php endforeach; ?>
        </div>

        <p class="center-cta reveal"><a href="/webshop" class="btn btn--outline">Összes termék</a></p>
    </div>
</section>
<?php endif; ?>

<section class="section" id="tevekenysegek">
    <div class="container">
        <header class="section-head reveal">
            <p class="eyebrow"><span class="eyebrow-dot"></span> Tevékenységek</p>
            <h2 class="display">Amivel foglalkozunk</h2>
            <p class="section-sub">Rövid placeholder leírás a fő szolgáltatási területekről.</p>
        </header>

        <div class="card-grid">
            <?php foreach ($activities as $a): ?>
                <article class="card reveal">
                    <span class="card-icon" data-icon="<?= View::e($a['icon']) ?>"></span>
                    <h3><?= View::e($a['title']) ?></h3>
                    <p><?= View::e($a['text']) ?></p>
                    <span class="card-link">Részletek →</span>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section--alt" id="rolunk">
    <div class="container about-grid">
        <div class="about-visual reveal" aria-hidden="true">
            <img src="/assets/img/about.svg" alt="" width="520" height="440">
        </div>
        <div class="about-copy reveal">
            <p class="eyebrow"><span class="eyebrow-dot"></span> Rólunk</p>
            <h2 class="display">Családi vállalkozás, <span class="gold">megbízható kézből</span></h2>
            <p>
                Placeholder bekezdés a cég bemutatkozásához. Ide kerül majd a történet,
                az értékek és a „miért minket” üzenet. Nagy képek, kevés szöveg.
            </p>
            <ul class="ticks">
                <li>Feladatra szabott megoldások</li>
                <li>Fenntartható alapanyag</li>
                <li>Saját logisztika</li>
            </ul>
            <a href="#kapcsolat" class="btn btn--outline">Kapcsolatfelvétel</a>
        </div>
    </div>
</section>

<section class="cta">
    <div class="container cta-inner reveal">
        <h2 class="display">Dolgozzunk együtt</h2>
        <p>Mondja el, mire van szüksége — visszajelzünk egy ajánlattal.</p>
        <a href="#kapcsolat" class="btn btn--gold btn--lg">Ajánlatkérés</a>
    </div>
</section>
