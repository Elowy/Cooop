<?php

use App\Core\View;

/** @var bool $ok */
/** @var string $email */
?>
<section class="section">
    <div class="container" style="max-width:640px;text-align:center">
        <?php if ($ok): ?>
            <p class="eyebrow" style="justify-content:center"><span class="eyebrow-dot"></span> Hírlevél</p>
            <h1 class="display" style="font-size:clamp(2rem,4vw,2.6rem)">Leiratkoztál</h1>
            <p class="muted">A(z) <strong><?= View::e($email) ?></strong> címet eltávolítottuk a hírlevél-listáról.
                Több hírlevelet nem küldünk erre a címre. Ha meggondolnád magad, bármikor újra feliratkozhatsz az oldalunk alján.</p>
        <?php else: ?>
            <p class="eyebrow" style="justify-content:center"><span class="eyebrow-dot"></span> Hírlevél</p>
            <h1 class="display" style="font-size:clamp(2rem,4vw,2.6rem)">Érvénytelen hivatkozás</h1>
            <p class="muted">Ezt a leiratkozási linket nem sikerült feldolgozni — lehet, hogy már leiratkoztál, vagy a hivatkozás hibás.</p>
        <?php endif; ?>
        <p style="margin-top:28px"><a href="/" class="btn btn--outline">Vissza a főoldalra</a></p>
    </div>
</section>
