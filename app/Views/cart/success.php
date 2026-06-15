<?php

use App\Core\View;

/** @var string $name */
?>
<section class="section container">
    <div class="success-box">
        <div class="success-icon">✓</div>
        <h1><?= View::e(t('success.title')) ?><?= !empty($name) ? ', ' . View::e($name) : '' ?>!</h1>
        <p><?= View::e(t('success.text')) ?></p>
        <a href="/termekek" class="btn btn--primary"><?= View::e(t('success.continue')) ?></a>
    </div>
</section>
