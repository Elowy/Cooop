<?php

use App\Core\Markdown;
use App\Core\View;

/** @var array<string, mixed> $post */
/** @var array<int, array<string, mixed>> $recent */
/** @var array<string, mixed> $config */

$cover = trim((string) ($post['cover'] ?? ''));
$author = trim((string) ($post['author'] ?? ''));
$created = (string) ($post['created'] ?? '');
$bodyHtml = Markdown::toHtml((string) ($post['body'] ?? ''));

$base = rtrim((string) ($config['app']['url'] ?? ''), '/');
$articleLd = [
    '@context' => 'https://schema.org',
    '@type' => 'BlogPosting',
    'headline' => (string) $post['title'],
    'description' => (string) ($post['excerpt'] ?? ''),
    'url' => $base . '/blog/' . (string) $post['slug'],
    'datePublished' => $created,
    'author' => ['@type' => 'Organization', 'name' => $author !== '' ? $author : (string) $config['app']['name']],
    'publisher' => ['@type' => 'Organization', 'name' => (string) $config['app']['name'], 'logo' => ['@type' => 'ImageObject', 'url' => $base . '/assets/img/logo.svg']],
];
if ($cover !== '') {
    $articleLd['image'] = $base . '/uploads/blog/' . $cover;
}
?>
<section class="section section--clear-top">
    <div class="container narrow">
        <p class="breadcrumb"><a href="/blog">← Blog</a></p>

        <article class="blog-post">
            <header class="blog-post-head">
                <p class="blog-post-meta">
                    <?php if ($created !== ''): ?><time datetime="<?= View::e($created) ?>"><?= View::e(View::dateHu($created)) ?></time><?php endif; ?>
                    <?php if ($author !== ''): ?><?= $created !== '' ? ' · ' : '' ?><span><?= View::e($author) ?></span><?php endif; ?>
                </p>
                <h1 class="display"><?= View::e((string) $post['title']) ?></h1>
                <?php if (!empty($post['excerpt'])): ?>
                    <p class="blog-post-lead"><?= View::e((string) $post['excerpt']) ?></p>
                <?php endif; ?>
            </header>

            <?php if ($cover !== ''): ?>
                <figure class="blog-post-cover">
                    <img src="/uploads/blog/<?= View::e($cover) ?>" alt="<?= View::e((string) $post['title']) ?>">
                </figure>
            <?php endif; ?>

            <div class="legal-doc blog-post-body">
                <?= $bodyHtml !== '' ? $bodyHtml : '<p class="muted">Ehhez a bejegyzéshez még nincs tartalom.</p>' ?>
            </div>
        </article>

        <?php if (!empty($recent)): ?>
            <aside class="blog-recent">
                <h2 class="blog-recent-title">További bejegyzések</h2>
                <ul class="blog-recent-list">
                    <?php foreach ($recent as $r): ?>
                        <li>
                            <a href="/blog/<?= View::e((string) $r['slug']) ?>"><?= View::e((string) $r['title']) ?></a>
                            <?php if (!empty($r['created'])): ?><span class="muted"><?= View::e(View::dateHu((string) $r['created'])) ?></span><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </aside>
        <?php endif; ?>

        <p class="center-cta"><a href="/blog" class="btn btn--outline">Vissza a bloghoz</a></p>
    </div>
</section>
<script type="application/ld+json"><?= json_encode($articleLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?></script>
