<?php

use App\Core\View;

/** @var string $name */
?>
<section class="section container">
    <div class="success-box">
        <div class="success-icon">✓</div>
        <h1>Köszönjük a rendelést<?= !empty($name) ? ', ' . View::e($name) : '' ?>!</h1>
        <p>Rendelésedet rögzítettük. Hamarosan e-mailben küldjük a visszaigazolást a részletekkel.</p>
        <a href="/termekek" class="btn btn--primary">Vásárlás folytatása</a>
    </div>
</section>
