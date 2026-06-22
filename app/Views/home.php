<?php

use App\Core\View;
use App\Integration\Product;

/** @var array<string, mixed> $config */
/** @var Product[] $featured */
/** @var array<int, array<string, mixed>> $topCats */

// Megjelenítési adatok a fő kategóriákhoz (ikon + rövid leírás).
$catMeta = [
    'epitoanyagok'     => ['icon' => 'brick', 'text' => 'Tégla, áthidaló, homok és faanyag.'],
    'kert-szabadido'   => ['icon' => 'leaf',  'text' => 'Kert, háziállat, játszótér és növény.'],
    'fahazak'          => ['icon' => 'house', 'text' => 'Nyaraló, lombház, pavilon, úszóház.'],
    'design-dekoracio' => ['icon' => 'deco',  'text' => 'Lakás- és üzletdekoráció, cégtáblák.'],
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
                <a href="#kategoriak" class="btn btn--outline">Kategóriák</a>
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

<section class="section" id="kategoriak">
    <div class="container">
        <header class="section-head reveal">
            <p class="eyebrow"><span class="eyebrow-dot"></span> Kategóriák</p>
            <h2 class="display">Fő kategóriáink</h2>
            <p class="section-sub">Négy fő terület — a webshopban tovább böngészhető alkategóriákkal.</p>
        </header>

        <div class="card-grid">
            <?php foreach ($topCats as $cat): $meta = $catMeta[$cat['key']] ?? ['icon' => 'packaging', 'text' => '']; ?>
                <a class="card cat-card reveal" href="/webshop?kat=<?= urlencode($cat['key']) ?>">
                    <span class="card-icon" data-icon="<?= View::e($meta['icon']) ?>"></span>
                    <h3><?= View::e($cat['name']) ?></h3>
                    <p><?= View::e($meta['text']) ?></p>
                    <span class="card-link"><?= count($cat['children']) ?> alkategória →</span>
                </a>
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
