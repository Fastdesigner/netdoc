<?php /** @var array|null $dev @var array $types @var string $csrf */
$v = fn(string $k) => e($dev[$k] ?? '');
?>
<?= ui('page-header', [
    'title' => $dev ? 'Gerät bearbeiten' : 'Gerät hinzufügen',
    'description' => $dev ? 'Technische Daten und Standort aktualisieren.' : 'Erfasse die wichtigsten Daten. Weitere Inhalte kannst du danach zuordnen.',
]) ?>

<form class="form-panel" method="post" action="<?= url('device.save') ?>">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <?php if ($dev): ?><input type="hidden" name="id" value="<?= (int) $dev['id'] ?>"><?php endif; ?>

    <div class="form-grid">
        <label>Name <span class="required">Pflichtfeld</span><input type="text" name="name" value="<?= $v('name') ?>" required autofocus></label>
        <label>Typ
            <select name="type">
                <?php foreach ($types as $t): ?>
                    <option value="<?= e($t) ?>" <?= ($dev['type'] ?? 'server') === $t ? 'selected' : '' ?>><?= e(\NetDoc\device_type_label($t)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>IP-Adresse<input type="text" name="ip" value="<?= $v('ip') ?>" class="mono" placeholder="192.168.0.10"></label>
        <label>Hostname<input type="text" name="hostname" value="<?= $v('hostname') ?>" class="mono" placeholder="server-01"></label>
        <label>Standort<input type="text" name="location" value="<?= $v('location') ?>" placeholder="Serverraum, Büro oder Rechenzentrum"></label>
        <label>Status
            <select name="status">
                <?php foreach (['active', 'inactive', 'maintenance', 'retired'] as $status): ?>
                    <option value="<?= e($status) ?>" <?= ($dev['status'] ?? 'active') === $status ? 'selected' : '' ?>><?= e(\NetDoc\device_status_label($status)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Hersteller<input type="text" name="vendor" value="<?= $v('vendor') ?>"></label>
        <label>Modell<input type="text" name="model" value="<?= $v('model') ?>"></label>
        <label>Betriebssystem<input type="text" name="os" value="<?= $v('os') ?>"></label>
    </div>
    <label>Notizen<textarea name="notes" rows="4" placeholder="Besonderheiten, Wartungshinweise oder Zuständigkeiten"><?= $v('notes') ?></textarea></label>

    <div class="form-actions">
        <?= ui('button', ['label' => 'Speichern', 'icon' => 'check', 'variant' => 'primary', 'type' => 'submit']) ?>
        <?= ui('button', ['label' => 'Abbrechen', 'icon' => 'x', 'href' => $dev ? url('device.view', ['id' => $dev['id']]) : url('devices'), 'variant' => 'quiet']) ?>
        <?php if ($dev): ?>
            <?= ui('button', ['label' => 'Gerät löschen', 'icon' => 'trash-2', 'variant' => 'danger', 'type' => 'submit', 'class' => 'form-actions__danger', 'attributes' => ['formaction' => url('device.delete', ['id' => $dev['id']]), 'data-confirm' => 'Gerät „' . $dev['name'] . '“ wirklich löschen?']]) ?>
        <?php endif; ?>
    </div>
</form>
