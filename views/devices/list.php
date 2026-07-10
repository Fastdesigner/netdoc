<?php /** @var array $rows @var string $q */ ?>
<?= ui('page-header', [
    'title' => 'Geräte',
    'description' => 'Server, Netzwerkkomponenten und Arbeitsplätze.',
    'actions' => [['label' => 'Gerät hinzufügen', 'icon' => 'plus', 'href' => url('device.edit'), 'variant' => 'primary']],
]) ?>
<?= ui('filter-bar', ['route' => 'devices', 'query' => $q, 'placeholder' => 'Name, IP-Adresse, Hostname oder Standort']) ?>

<?php if (!$rows): ?>
    <?= ui('empty-state', [
        'title' => $q ? 'Keine passenden Geräte' : 'Noch keine Geräte',
        'text' => $q ? 'Ändere oder lösche den Filter.' : 'Lege das erste Gerät an und ordne ihm Zugänge, Notizen und Dokumente zu.',
        'icon' => 'server',
        'action' => $q
            ? ['label' => 'Filter löschen', 'icon' => 'x', 'href' => url('devices')]
            : ['label' => 'Gerät hinzufügen', 'icon' => 'plus', 'href' => url('device.edit'), 'variant' => 'primary'],
    ]) ?>
<?php else: ?>
<div class="data-panel">
<table class="data-table">
    <thead><tr><th>Name</th><th>Typ</th><th>IP / Hostname</th><th>Standort</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $d): ?>
        <tr>
            <td data-label="Name" class="data-table__primary"><a href="<?= url('device.view', ['id' => $d['id']]) ?>"><strong><?= e($d['name']) ?></strong></a></td>
            <td data-label="Typ"><span class="badge"><?= e(\NetDoc\device_type_label($d['type'])) ?></span></td>
            <td data-label="IP / Hostname" class="mono"><?= e($d['ip'] ?: $d['hostname'] ?: '–') ?></td>
            <td data-label="Standort"><?= e($d['location'] ?: '–') ?></td>
            <td data-label="Status"><span class="status status--<?= e($d['status']) ?>"><span></span><?= e(\NetDoc\device_status_label($d['status'])) ?></span></td>
            <td data-label="Aktion" class="row-actions"><?= ui('button', ['label' => 'Bearbeiten', 'icon' => 'pencil', 'href' => url('device.edit', ['id' => $d['id']]), 'variant' => 'quiet', 'size' => 'small']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
