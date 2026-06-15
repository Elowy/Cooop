<?php

use App\Core\View;

/** @var array<string, mixed> $config */
?>
<section class="page-head">
    <div class="container">
        <h1><?= View::e(t('nav.about')) ?></h1>
        <p><?= View::e(t('about.lead')) ?></p>
    </div>
</section>

<section class="section container">
    <div class="prose reveal">
        <p><?= View::e(t('about.intro')) ?></p>

        <h3><?= View::e(t('about.h_pack')) ?></h3>
        <p><?= View::e(t('about.p_pack')) ?></p>

        <h3><?= View::e(t('about.h_wood')) ?></h3>
        <p><?= View::e(t('about.p_wood')) ?></p>

        <h3><?= View::e(t('about.h_freight')) ?></h3>
        <p><?= View::e(t('about.p_freight')) ?></p>

        <h3><?= View::e(t('about.h_brick')) ?></h3>
        <p><?= View::e(t('about.p_brick')) ?></p>

        <h3><?= View::e(t('about.h_lumber')) ?></h3>
        <p><?= View::e(t('about.p_lumber')) ?></p>

        <p><?= View::e(t('about.closing')) ?></p>
    </div>

    <div class="stats-row">
        <div class="stat reveal"><strong>30 év</strong><span><?= View::e(t('stat.experience')) ?></span></div>
        <div class="stat reveal"><strong>2017</strong><span><?= View::e(t('stat.britterm')) ?></span></div>
        <div class="stat reveal"><strong>100%</strong><span><?= View::e(t('stat.quality')) ?></span></div>
        <div class="stat reveal"><strong>1–1,5 hét</strong><span><?= View::e(t('stat.delivery')) ?></span></div>
    </div>
</section>
