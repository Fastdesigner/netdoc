<?php /** @var array $rows @var string $q */ ?>
<?= ui('page-header', [
    'title' => 'Dokumente',
    'description' => 'Pläne, Verträge und Anleitungen geschützt ablegen.',
    'actions' => [['label' => 'Dokument hochladen', 'icon' => 'upload', 'href' => url('document.edit'), 'variant' => 'primary']],
]) ?>
<?= ui('filter-bar', ['route' => 'documents', 'query' => $q, 'placeholder' => 'Titel oder Dateiname']) ?>

<?php if (!$rows): ?>
    <?= ui('empty-state', [
        'title' => $q ? 'Keine passenden Dokumente' : 'Noch keine Dokumente',
        'text' => $q ? 'Ändere oder lösche den Filter.' : 'Lade den ersten Plan, Vertrag oder eine Anleitung hoch.',
        'icon' => 'file-text',
        'action' => $q
            ? ['label' => 'Filter löschen', 'icon' => 'x', 'href' => url('documents')]
            : ['label' => 'Dokument hochladen', 'icon' => 'upload', 'href' => url('document.edit'), 'variant' => 'primary'],
    ]) ?>
<?php else: ?>
<div class="data-panel">
<table class="data-table">
    <thead><tr><th>Titel</th><th>Datei</th><th>Größe</th><th>Gerät</th><th>Hochgeladen</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $d): ?>
        <tr>
            <td data-label="Titel" class="data-table__primary"><a href="<?= url('document.download', ['id' => $d['id']]) ?>"><strong><?= e($d['title']) ?></strong></a></td>
            <td data-label="Datei" class="muted mono"><?= e($d['filename'] ?: '–') ?></td>
            <td data-label="Größe"><?= fmt_bytes((int) ($d['size'] ?? 0)) ?></td>
            <td data-label="Gerät"><?php if (!empty($d['device_name'])): ?><span class="badge"><?= e($d['device_name']) ?></span><?php else: ?>–<?php endif; ?></td>
            <td data-label="Hochgeladen" class="muted small"><?= fmt_date((int) $d['created_at']) ?><?php if (!empty($d['uploaded_by'])): ?><br><?= e($d['uploaded_by']) ?><?php endif; ?></td>
            <td data-label="Aktion" class="row-actions">
                <?= ui('button', ['label' => 'Herunterladen', 'icon' => 'download', 'href' => url('document.download', ['id' => $d['id']]), 'variant' => 'quiet', 'size' => 'small']) ?>
                <?= ui('button', ['label' => 'Bearbeiten', 'icon' => 'pencil', 'href' => url('document.edit', ['id' => $d['id']]), 'variant' => 'quiet', 'size' => 'small']) ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
