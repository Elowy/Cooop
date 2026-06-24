<?php

use App\Core\View;

/** @var array<int, array<string, mixed>> $posts */
?>
<section class="page-hero">
    <div class="hero-glow" aria-hidden="true"></div>
    <div class="container">
        <p class="eyebrow"><span class="eyebrow-dot"></span> Blog</p>
        <h1 class="display">Hírek, tippek, szakmai cikkek</h1>
        <p class="section-sub">Csomagolásról, raklapgyártásról és logisztikáról – tapasztalatból.</p>
    </div>
</section>

<section class="section section--flush-top">
    <div class="container">
        <?php if (!$posts): ?>
            <p class="muted" style="text-align:center">Hamarosan érkeznek az első bejegyzések.</p>
        <?php else: ?>
            <div class="blog-grid">
                <?php foreach ($posts as $post):
                    $cover = trim((string) ($post['cover'] ?? '')); ?>
                    <article class="blog-card reveal">
                        <a class="blog-card-media" href="/blog/<?= View::e((string) $post['slug']) ?>" aria-hidden="true" tabindex="-1">
                            <?php if ($cover !== ''): ?>
                                <img src="/uploads/blog/<?= View::e($cover) ?>" alt="" loading="lazy">
                            <?php else: ?>
                                <span class="blog-card-mark"><?= View::e(mb_strtoupper(mb_substr((string) $post['title'], 0, 1))) ?></span>
                            <?php endif; ?>
                        </a>
                        <div class="blog-card-body">
                            <?php if (!empty($post['created'])): ?>
                                <time class="blog-card-date" datetime="<?= View::e((string) $post['created']) ?>"><?= View::e(View::dateHu((string) $post['created'])) ?></time>
                            <?php endif; ?>
                            <h2><a href="/blog/<?= View::e((string) $post['slug']) ?>"><?= View::e((string) $post['title']) ?></a></h2>
                            <?php if (!empty($post['excerpt'])): ?>
                                <p><?= View::e((string) $post['excerpt']) ?></p>
                            <?php endif; ?>
                            <a class="blog-card-link" href="/blog/<?= View::e((string) $post['slug']) ?>">Tovább olvasom →</a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
