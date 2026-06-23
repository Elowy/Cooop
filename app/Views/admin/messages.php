<?php

use App\Core\View;

/** @var array<int, array<string, mixed>> $messages */
?>
<?php if (!$messages): ?>
    <section class="panel">
        <div class="empty-state">
            <span class="adm-ico adm-ico--lg" data-aico="mail" aria-hidden="true"></span>
            <h3>Nincs üzenet</h3>
            <p class="muted">A kapcsolatfelvételi űrlapon érkező üzenetek itt jelennek meg.</p>
        </div>
    </section>
<?php else: ?>
    <p class="result-count"><?= count($messages) ?> üzenet</p>
    <div class="msg-list">
        <?php foreach ($messages as $m): ?>
            <article class="panel msg">
                <header class="msg-head">
                    <div>
                        <strong><?= View::e((string) ($m['name'] ?? '')) ?></strong>
                        <a href="mailto:<?= View::e((string) ($m['email'] ?? '')) ?>"><?= View::e((string) ($m['email'] ?? '')) ?></a>
                        <?php if (!empty($m['phone'])): ?><span class="muted">· <?= View::e((string) $m['phone']) ?></span><?php endif; ?>
                    </div>
                    <span class="muted"><?= View::e(date('Y-m-d H:i', strtotime((string) ($m['created'] ?? 'now')))) ?></span>
                </header>
                <?php if (!empty($m['subject'])): ?><p class="msg-subject"><?= View::e((string) $m['subject']) ?></p><?php endif; ?>
                <p class="msg-body"><?= nl2br(View::e((string) ($m['message'] ?? ''))) ?></p>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
