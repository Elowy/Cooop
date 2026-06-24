<?php

use App\Core\Csrf;
use App\Core\View;

/** @var array<string, mixed>|null $post */
$post = $post ?? [];
?>
<p class="breadcrumb"><a href="/admin/blog">← Blog</a></p>

<section class="panel" style="max-width:760px">
    <form method="post" action="/admin/blog/mentes" enctype="multipart/form-data" class="form-card" style="background:transparent;border:0;padding:0">
        <?= Csrf::field() ?>
        <?php if (!empty($post['id'])): ?><input type="hidden" name="id" value="<?= (int) $post['id'] ?>"><?php endif; ?>

        <div class="field">
            <label>Cím *</label>
            <input name="title" value="<?= View::e((string) ($post['title'] ?? '')) ?>" required>
        </div>

        <div class="field">
            <label>Egyedi URL-rész (slug) — üresen hagyva a címből generálódik</label>
            <input name="slug" value="<?= View::e((string) ($post['slug'] ?? '')) ?>" placeholder="pl. export-csomagolas-tippek">
            <?php if (!empty($post['slug'])): ?><p class="note" style="margin-top:6px">Jelenlegi cím: <code>/blog/<?= View::e((string) $post['slug']) ?></code></p><?php endif; ?>
        </div>

        <div class="field">
            <label>Borítókép (JPG, PNG vagy WEBP, max 16 MB)</label>
            <?php if (!empty($post['cover'])): ?>
                <div style="margin-bottom:10px"><img class="ref-thumb" src="/uploads/blog/<?= View::e((string) $post['cover']) ?>" alt=""></div>
            <?php endif; ?>
            <input type="file" name="cover" accept="image/jpeg,image/png,image/webp">
            <?php if (!empty($post['cover'])): ?><p class="note" style="margin-top:6px">Új fájl feltöltése felülírja a mostanit.</p><?php endif; ?>
        </div>

        <div class="field">
            <label>Szerző (nem kötelező)</label>
            <input name="author" value="<?= View::e((string) ($post['author'] ?? '')) ?>" placeholder="Net-Trade Hungary">
        </div>

        <div class="field">
            <label>Rövid összefoglaló (a kártyán és a keresőkben látszik)</label>
            <textarea name="excerpt" rows="2"><?= View::e((string) ($post['excerpt'] ?? '')) ?></textarea>
        </div>

        <div class="field">
            <label>Tartalom (Markdown: # címsor, **félkövér**, - lista, üres sor = új bekezdés)</label>
            <textarea name="body" rows="16" style="font-family:var(--sans)"><?= View::e((string) ($post['body'] ?? '')) ?></textarea>
        </div>

        <label class="check" style="display:flex;align-items:center;gap:9px;margin-bottom:16px">
            <input type="checkbox" name="published" value="1"<?= (int) ($post['published'] ?? 1) === 1 ? ' checked' : '' ?>>
            <span>Publikált (látszik a nyilvános blogon)</span>
        </label>

        <button type="submit" class="btn btn--gold">Mentés</button>
        <a href="/admin/blog" class="btn btn--outline">Mégse</a>
    </form>
</section>
