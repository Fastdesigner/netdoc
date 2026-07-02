<?php /** @var array|null $doc @var array $devices @var string $csrf @var int $preselectDevice @var int $maxBytes */
$v = fn(string $k) => e($doc[$k] ?? '');
$selDevice = (int) ($doc['device_id'] ?? $preselectDevice);
?>
<div class="pagehead">
    <h1><?= $doc ? 'Dokument bearbeiten' : 'Dokument hochladen' ?></h1>
</div>

<form class="card form" method="post" action="<?= url('document.save') ?>" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <?php if ($doc): ?><input type="hidden" name="id" value="<?= (int) $doc['id'] ?>"><?php endif; ?>

    <?php if ($doc): ?>
        <p class="muted">Aktuelle Datei: <strong class="mono"><?= e($doc['filename']) ?></strong>
            (<?= fmt_bytes((int) ($doc['size'] ?? 0)) ?>) ·
            <a href="<?= url('document.download', ['id' => $doc['id']]) ?>">herunterladen</a></p>
    <?php endif; ?>

    <div class="grid2">
        <label>Titel<input type="text" name="title" value="<?= $v('title') ?>" placeholder="leer = Dateiname"></label>
        <label>Gerät / Server
            <select name="device_id">
                <option value="0">— keins —</option>
                <?php foreach ($devices as $d): ?>
                    <option value="<?= (int) $d['id'] ?>" <?= $selDevice === (int) $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>

    <label><?= $doc ? 'Datei ersetzen (optional)' : 'Datei *' ?>
        <input type="file" name="file" <?= $doc ? '' : 'required' ?>>
    </label>
    <p class="muted small">Max. <?= fmt_bytes($maxBytes) ?> pro Datei. Ausführbare Dateitypen (php, exe, …) sind gesperrt. Dateien liegen außerhalb des Web-Verzeichnisses und sind nur nach Login abrufbar.</p>

    <label>Notizen<textarea name="notes" rows="3"><?= $v('notes') ?></textarea></label>

    <div class="formactions">
        <button type="submit" class="btn primary"><?= $doc ? 'Speichern' : 'Hochladen' ?></button>
        <a class="btn ghost" href="<?= url('documents') ?>">Abbrechen</a>
        <?php if ($doc): ?>
            <button type="submit" class="btn danger right" formaction="<?= url('document.delete', ['id' => $doc['id']]) ?>"
                    formnovalidate data-confirm="Dokument „<?= e($doc['title']) ?>“ wirklich löschen?">Löschen</button>
        <?php endif; ?>
    </div>
</form>
