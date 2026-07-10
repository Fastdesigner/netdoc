<?php /** @var array|null $p @var array $devices @var string $csrf */
$v = fn(string $k) => e($p[$k] ?? '');
$selDevice = (int) ($p['device_id'] ?? 0);
?>
<?= ui('page-header', [
    'title' => $p ? 'Produkt bearbeiten' : 'Produkt hinzufügen',
    'description' => 'Laufzeit, Kosten und Zuordnung übersichtlich dokumentieren.',
]) ?>

<form class="form-panel" method="post" action="<?= url('product.save') ?>" autocomplete="off">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <?php if ($p): ?><input type="hidden" name="id" value="<?= (int) $p['id'] ?>"><?php endif; ?>

    <div class="form-grid">
        <label>Name <span class="required">Pflichtfeld</span><input type="text" name="name" value="<?= $v('name') ?>" required autofocus></label>
        <label>Hersteller<input type="text" name="vendor" value="<?= $v('vendor') ?>"></label>
        <label>Kategorie<input type="text" name="category" value="<?= $v('category') ?>" placeholder="Software, Hardware oder Abonnement"></label>
        <label>Anzahl<input type="number" name="seats" value="<?= $v('seats') ?>" min="0"></label>
        <label>Kaufdatum<input type="date" name="purchase_date" value="<?= $v('purchase_date') ?>"></label>
        <label>Ablaufdatum<input type="date" name="expiry_date" value="<?= $v('expiry_date') ?>"></label>
        <label>Kosten<input type="text" name="cost" value="<?= $v('cost') ?>" placeholder="Zum Beispiel 199 EUR pro Jahr"></label>
        <label>Lieferant<input type="text" name="supplier" value="<?= $v('supplier') ?>"></label>
        <label>Zugeordnetes Gerät
            <select name="device_id">
                <option value="0">Nicht zugeordnet</option>
                <?php foreach ($devices as $d): ?>
                    <option value="<?= (int) $d['id'] ?>" <?= $selDevice === (int) $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Lizenzschlüssel <small>wird verschlüsselt gespeichert</small>
            <div class="password-field">
                <input type="password" name="license" class="mono" autocomplete="off" placeholder="<?= $p && $p['license_enc'] ? 'Leer lassen, um den Schlüssel beizubehalten' : '' ?>">
                <?= ui('button', ['label' => 'Anzeigen', 'icon' => 'eye', 'size' => 'small', 'class' => 'toggle-password']) ?>
            </div>
        </label>
    </div>
    <label>Notizen<textarea name="notes" rows="3" placeholder="Kündigungsfrist, Ansprechpartner oder weitere Hinweise"><?= $v('notes') ?></textarea></label>

    <div class="form-actions">
        <?= ui('button', ['label' => 'Speichern', 'icon' => 'check', 'variant' => 'primary', 'type' => 'submit']) ?>
        <?= ui('button', ['label' => 'Abbrechen', 'icon' => 'x', 'href' => $p ? url('product.view', ['id' => $p['id']]) : url('products'), 'variant' => 'quiet']) ?>
        <?php if ($p): ?>
            <?= ui('button', ['label' => 'Produkt löschen', 'icon' => 'trash-2', 'variant' => 'danger', 'type' => 'submit', 'class' => 'form-actions__danger', 'attributes' => ['formaction' => url('product.delete', ['id' => $p['id']]), 'data-confirm' => 'Produkt „' . $p['name'] . '“ wirklich löschen?']]) ?>
        <?php endif; ?>
    </div>
</form>
