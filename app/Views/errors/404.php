<?php

use App\Core\View;
?>
<section class="section container">
    <div class="success-box">
        <div class="error-code">404</div>
        <h1><?= View::e(t('e404.title')) ?></h1>
        <p><?= View::e(t('e404.text')) ?></p>
        <a href="/" class="btn btn--primary"><?= View::e(t('e404.back')) ?></a>
    </div>
</section>
