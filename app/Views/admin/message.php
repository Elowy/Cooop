<?php

use App\Core\View;

/** @var string $message */
?>
<div class="admin-message">
    <h1><?= View::e($title ?? 'Üzenet') ?></h1>
    <p><?= View::e($message ?? '') ?></p>
    <a href="/admin" class="btn btn--primary">Vissza a vezérlőpultra</a>
</div>
