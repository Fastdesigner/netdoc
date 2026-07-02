<?php /** @var array|null $dev @var array $types @var string $csrf */
$v = fn(string $k) => e($dev[$k] ?? '');
?>
<div class="pagehead">
    <h1><?= $dev ? 'Gerät bearbeiten' : 'Neues Gerät' ?></h1>
</div>

<form class="card form" method="post" action="<?= url('device.save') ?>">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <?php if ($dev): ?><input type="hidden" name="id" value="<?= (int) $dev['id'] ?>"><?php endif; ?>

    <div class="grid2">
        <label>Name *<input type="text" name="name" value="<?= $v('name') ?>" required autofocus></label>
        <label>Typ
            <select name="type">
                <?php foreach ($types as $t): ?>
                    <option value="<?= e($t) ?>" <?= ($dev['type'] ?? 'server') === $t ? 'selected' : '' ?>><?= e($t) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>IP-Adresse<input type="text" name="ip" value="<?= $v('ip') ?>" class="mono" placeholder="192.168.0.10"></label>
        <label>Hostname<input type="text" name="hostname" value="<?= $v('hostname') ?>" class="mono"></label>
        <label>Standort<input type="text" name="location" value="<?= $v('location') ?>" placeholder="Serverraum, Rack 3"></label>
        <label>Status
            <select name="status">
                <?php foreach (['active' => 'Aktiv', 'inactive' => 'Inaktiv', 'maintenance' => 'Wartung', 'retired' => 'Ausgemustert'] as $k => $lbl): ?>
                    <option value="<?= $k ?>" <?= ($dev['status'] ?? 'active') === $k ? 'selected' : '' ?>><?= $lbl ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Hersteller<input type="text" name="vendor" value="<?= $v('vendor') ?>"></label>
        <label>Modell<input type="text" name="model" value="<?= $v('model') ?>"></label>
        <label>Betriebssystem<input type="text" name="os" value="<?= $v('os') ?>"></label>
    </div>
    <label>Notizen<textarea name="notes" rows="4"><?= $v('notes') ?></textarea></label>

    <div class="formactions">
        <button type="submit" class="btn primary">Speichern</button>
        <a class="btn ghost" href="<?= $dev ? url('device.view', ['id' => $dev['id']]) : url('devices') ?>">Abbrechen</a>
        <?php if ($dev): ?>
            <button type="submit" class="btn danger right" formaction="<?= url('device.delete', ['id' => $dev['id']]) ?>"
                    data-confirm="Gerät „<?= e($dev['name']) ?>“ wirklich löschen?">Löschen</button>
        <?php endif; ?>
    </div>
</form>
