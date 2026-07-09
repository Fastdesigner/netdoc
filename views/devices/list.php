<?php /** @var array $rows @var string $q */ ?>
<div class="pagehead">
    <h1>Geräte &amp; Server</h1>
    <a class="btn primary" href="<?= url('device.edit') ?>">+ Neues Gerät</a>
</div>

<form class="filterbar" method="get" action="index.php">
    <input type="hidden" name="r" value="devices">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Nach Name, IP, Hostname, Standort…">
    <button class="btn">Filtern</button>
    <?php if ($q !== ''): ?><a class="btn ghost" href="<?= url('devices') ?>">×</a><?php endif; ?>
</form>

<?php if (!$rows): ?>
    <div class="card"><p class="muted">Keine Geräte gefunden.</p></div>
<?php else: ?>
<div class="card nopad">
<table class="table">
    <thead><tr><th>Name</th><th>Typ</th><th>IP / Hostname</th><th>Standort</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $d): ?>
        <tr>
            <td data-label="Name"><a href="<?= url('device.view', ['id' => $d['id']]) ?>"><strong><?= e($d['name']) ?></strong></a></td>
            <td data-label="Typ"><span class="tag"><?= e($d['type']) ?></span></td>
            <td data-label="IP / Hostname" class="mono"><?= e($d['ip'] ?: $d['hostname'] ?: '–') ?></td>
            <td data-label="Standort"><?= e($d['location'] ?: '–') ?></td>
            <td data-label="Status"><span class="dot <?= $d['status'] === 'active' ? 'ok' : 'off' ?>"></span><?= e($d['status']) ?></td>
            <td data-label="Aktion" class="rowactions"><a href="<?= url('device.edit', ['id' => $d['id']]) ?>">Bearbeiten</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
