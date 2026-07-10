<?php /** @var array|null $doc @var array $devices @var string $csrf @var int $preselectDevice @var int $maxBytes */
$v = fn(string $k) => e($doc[$k] ?? '');
$selDevice = (int) ($doc['device_id'] ?? $preselectDevice);
?>
<?= ui('page-header', [
    'title' => $doc ? 'Dokument bearbeiten' : 'Dokument hochladen',
    'description' => $doc ? 'Titel, Zuordnung oder Datei aktualisieren.' : 'Pläne, Verträge und Anleitungen sicher ablegen.',
]) ?>

<form class="form-panel form-panel--narrow" method="post" action="<?= url('document.save') ?>" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <?php if ($doc): ?><input type="hidden" name="id" value="<?= (int) $doc['id'] ?>"><?php endif; ?>

    <?php if ($doc): ?>
        <div class="file-summary">
            <span><?= ui('icon', ['name' => 'file-text']) ?></span>
            <div><strong><?= e($doc['filename']) ?></strong><small><?= fmt_bytes((int) ($doc['size'] ?? 0)) ?></small></div>
            <?= ui('button', ['label' => 'Herunterladen', 'icon' => 'download', 'href' => url('document.download', ['id' => $doc['id']]), 'variant' => 'quiet', 'size' => 'small']) ?>
        </div>
    <?php endif; ?>

    <div class="form-grid">
        <label>Titel<input type="text" name="title" value="<?= $v('title') ?>" placeholder="Ohne Titel wird der Dateiname verwendet"></label>
        <label>Zugeordnetes Gerät
            <select name="device_id">
                <option value="0">Nicht zugeordnet</option>
                <?php foreach ($devices as $d): ?>
                    <option value="<?= (int) $d['id'] ?>" <?= $selDevice === (int) $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>

    <label><?= $doc ? 'Datei ersetzen' : 'Datei' ?><?= $doc ? ' <small>optional</small>' : ' <span class="required">Pflichtfeld</span>' ?>
        <input type="file" name="file" <?= $doc ? '' : 'required' ?>>
        <small class="field-hint">Maximal <?= fmt_bytes($maxBytes) ?>. Ausführbare Dateien sind aus Sicherheitsgründen ausgeschlossen.</small>
    </label>
    <label>Notizen<textarea name="notes" rows="3" placeholder="Inhalt, Version oder Gültigkeit des Dokuments"><?= $v('notes') ?></textarea></label>

    <div class="form-actions">
        <?= ui('button', ['label' => $doc ? 'Speichern' : 'Hochladen', 'icon' => $doc ? 'check' : 'upload', 'variant' => 'primary', 'type' => 'submit']) ?>
        <?= ui('button', ['label' => 'Abbrechen', 'icon' => 'x', 'href' => url('documents'), 'variant' => 'quiet']) ?>
        <?php if ($doc): ?>
            <?= ui('button', ['label' => 'Dokument löschen', 'icon' => 'trash-2', 'variant' => 'danger', 'type' => 'submit', 'class' => 'form-actions__danger', 'attributes' => ['formaction' => url('document.delete', ['id' => $doc['id']]), 'formnovalidate' => true, 'data-confirm' => 'Dokument „' . $doc['title'] . '“ wirklich löschen?']]) ?>
        <?php endif; ?>
    </div>
</form>
