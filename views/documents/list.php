<?php /** @var array $rows @var string $q */ ?>
<div class="pagehead">
    <h1>Dokumente</h1>
    <a class="btn primary" href="<?= url('document.edit') ?>">+ Dokument hochladen</a>
</div>

<form class="filterbar" method="get" action="index.php">
    <input type="hidden" name="r" value="documents">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Nach Titel oder Dateiname…">
    <button class="btn">Filtern</button>
    <?php if ($q !== ''): ?><a class="btn ghost" href="<?= url('documents') ?>">×</a><?php endif; ?>
</form>

<?php if (!$rows): ?>
    <div class="card"><p class="muted">Noch keine Dokumente.</p></div>
<?php else: ?>
<div class="card nopad">
<table class="table">
    <thead><tr><th>Titel</th><th>Datei</th><th>Größe</th><th>Gerät</th><th>Hochgeladen</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $d): ?>
        <tr>
            <td data-label="Titel"><a href="<?= url('document.download', ['id' => $d['id']]) ?>"><strong><?= e($d['title']) ?></strong></a></td>
            <td data-label="Datei" class="muted mono"><?= e($d['filename'] ?: '–') ?></td>
            <td data-label="Größe"><?= fmt_bytes((int) ($d['size'] ?? 0)) ?></td>
            <td data-label="Gerät"><?php if (!empty($d['device_name'])): ?><span class="tag"><?= e($d['device_name']) ?></span><?php else: ?>–<?php endif; ?></td>
            <td data-label="Hochgeladen" class="muted small"><?= fmt_date((int) $d['created_at']) ?><?php if (!empty($d['uploaded_by'])): ?><br><?= e($d['uploaded_by']) ?><?php endif; ?></td>
            <td data-label="Aktion" class="rowactions">
                <a href="<?= url('document.download', ['id' => $d['id']]) ?>">Download</a>
                <a href="<?= url('document.edit', ['id' => $d['id']]) ?>">Bearbeiten</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
