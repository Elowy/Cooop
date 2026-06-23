<?php

use App\Core\Csrf;
use App\Core\View;

/** @var array<int, array<string, mixed>> $subscribers */
/** @var int $activeCount */
/** @var array<int, array<string, mixed>> $templates */
/** @var array<string, string> $values */
/** @var string $lastSent */

// Utolsó küldés kibontása ("ISO|elküldött/összes" formátum).
$lastInfo = '';
if ($lastSent !== '' && str_contains($lastSent, '|')) {
    [$when, $cnt] = explode('|', $lastSent, 2);
    $lastInfo = 'Utolsó küldés: ' . date('Y-m-d H:i', strtotime($when)) . ' · ' . $cnt . ' címzett';
}

// Sablonok JSON-ban a küldő űrlap automatikus kitöltéséhez.
$tplJson = array_map(static fn ($t) => [
    'id' => (int) $t['id'], 'subject' => (string) $t['subject'], 'body' => (string) $t['body'],
], $templates);
?>
<div class="admin-cols">
    <section class="panel">
        <header class="panel-head"><h2>Hírlevél küldése</h2></header>
        <p class="muted" style="margin-top:0">
            <strong><?= $activeCount ?></strong> aktív feliratkozó.
            <?php if ($lastInfo !== ''): ?><br><span class="note"><?= View::e($lastInfo) ?></span><?php endif; ?>
        </p>

        <?php if ($activeCount === 0): ?>
            <p class="note">Még nincs aktív feliratkozó, akinek küldhetnél.</p>
        <?php endif; ?>

        <form method="post" action="/admin/hirlevel/kuldes" class="form-card" style="background:transparent;border:0;padding:0"
              onsubmit="return confirm('Biztosan kiküldöd a hírlevelet <?= $activeCount ?> feliratkozónak?')">
            <?= Csrf::field() ?>
            <?php if ($templates): ?>
            <div class="field">
                <label>Sablon betöltése (nem kötelező)</label>
                <select name="template_id" id="nl-template" class="adm-input" style="width:100%">
                    <option value="0">— Üres / saját tartalom —</option>
                    <?php foreach ($templates as $t): ?>
                        <option value="<?= (int) $t['id'] ?>"><?= View::e((string) $t['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="field">
                <label>Tárgy *</label>
                <input name="subject" id="nl-subject" placeholder="Pl. Tavaszi akcióink">
            </div>
            <div class="field">
                <label>Tartalom (HTML megengedett) *</label>
                <textarea name="body" id="nl-body" rows="10" placeholder="A hírlevél szövege…"></textarea>
            </div>
            <button type="submit" class="btn btn--gold"<?= $activeCount === 0 ? ' disabled' : '' ?>>Hírlevél kiküldése</button>
            <p class="note" style="margin-top:10px">Minden levél aljára automatikusan bekerül egy leiratkozási link.</p>
        </form>
    </section>

    <section class="panel">
        <header class="panel-head"><h2>Feladó beállítások</h2></header>
        <p class="muted" style="margin-top:0">Ezzel a névvel és e-mail címmel mennek ki a hírlevelek.</p>
        <form method="post" action="/admin/hirlevel/beallitasok" class="form-card" style="background:transparent;border:0;padding:0">
            <?= Csrf::field() ?>
            <div class="field">
                <label>Feladó neve</label>
                <input name="from_name" value="<?= View::e($values['from_name']) ?>" placeholder="Net-Trade Hungary">
            </div>
            <div class="field">
                <label>Feladó e-mail címe</label>
                <input name="from" value="<?= View::e($values['from']) ?>" placeholder="hirlevel@net-trade.hu">
            </div>
            <button type="submit" class="btn btn--gold">Mentés</button>
        </form>
    </section>
</div>

<section class="panel" style="margin-top:24px">
    <header class="panel-head" style="display:flex;justify-content:space-between;align-items:center">
        <h2>Sablonok</h2>
        <a href="/admin/hirlevel/sablon" class="btn btn--gold btn--sm">+ Új sablon</a>
    </header>
    <?php if (!$templates): ?>
        <p class="muted">Még nincs sablon. Hozz létre egyet, hogy gyorsan újra tudd használni a tartalmat.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead><tr><th>Név</th><th>Tárgy</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($templates as $t): ?>
                        <tr>
                            <td><a href="/admin/hirlevel/sablon?id=<?= (int) $t['id'] ?>"><?= View::e((string) $t['name']) ?></a></td>
                            <td class="muted"><?= View::e((string) $t['subject']) ?></td>
                            <td class="ta-r">
                                <form method="post" action="/admin/hirlevel/sablon/torles" onsubmit="return confirm('Biztosan törlöd a sablont?')" style="margin:0;display:inline">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                                    <button class="icon-btn" aria-label="Törlés">×</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<section class="panel" style="margin-top:24px">
    <header class="panel-head"><h2>Feliratkozók (<?= count($subscribers) ?>)</h2></header>
    <?php if (!$subscribers): ?>
        <p class="muted">Még senki nem iratkozott fel a hírlevélre.</p>
    <?php else: ?>
        <div class="table-wrap">
            <table class="admin-table">
                <thead><tr><th>E-mail</th><th>Név</th><th>Állapot</th><th>Feliratkozott</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($subscribers as $s): ?>
                        <tr>
                            <td><a href="mailto:<?= View::e((string) $s['email']) ?>"><?= View::e((string) $s['email']) ?></a></td>
                            <td class="muted"><?= View::e((string) ($s['name'] ?? '')) ?></td>
                            <td>
                                <?php if (!empty($s['active'])): ?>
                                    <span class="pill pill--ok">Aktív</span>
                                <?php else: ?>
                                    <span class="pill">Leiratkozott</span>
                                <?php endif; ?>
                            </td>
                            <td class="muted"><?= View::e($s['created'] ? date('Y-m-d', strtotime((string) $s['created'])) : '') ?></td>
                            <td class="ta-r">
                                <form method="post" action="/admin/hirlevel/feliratkozo/torles" onsubmit="return confirm('Biztosan törlöd ezt a feliratkozót?')" style="margin:0;display:inline">
                                    <?= Csrf::field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">
                                    <button class="icon-btn" aria-label="Törlés">×</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php if ($templates): ?>
<script>
(function () {
    var data = <?= json_encode(array_values($tplJson), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_APOS) ?>;
    var sel = document.getElementById('nl-template');
    var subj = document.getElementById('nl-subject');
    var body = document.getElementById('nl-body');
    if (!sel || !subj || !body) { return; }
    sel.addEventListener('change', function () {
        var id = parseInt(sel.value, 10);
        if (!id) { return; }
        var t = data.filter(function (x) { return x.id === id; })[0];
        if (!t) { return; }
        if (!subj.value.trim() || confirm('Felülírod a jelenlegi tárgyat és tartalmat a sablonnal?')) {
            subj.value = t.subject;
            body.value = t.body;
        }
    });
})();
</script>
<?php endif; ?>
