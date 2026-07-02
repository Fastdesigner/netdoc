<?php /** @var array|null $p @var array $devices @var string $csrf */
$v = fn(string $k) => e($p[$k] ?? '');
$selDevice = (int) ($p['device_id'] ?? 0);
?>
<div class="pagehead">
    <h1><?= $p ? 'Produkt bearbeiten' : 'Neues Produkt' ?></h1>
</div>

<form class="card form" method="post" action="<?= url('product.save') ?>" autocomplete="off">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <?php if ($p): ?><input type="hidden" name="id" value="<?= (int) $p['id'] ?>"><?php endif; ?>

    <div class="grid2">
        <label>Name *<input type="text" name="name" value="<?= $v('name') ?>" required autofocus></label>
        <label>Hersteller<input type="text" name="vendor" value="<?= $v('vendor') ?>"></label>
        <label>Kategorie<input type="text" name="category" value="<?= $v('category') ?>" placeholder="Software, Hardware, Abo…"></label>
        <label>Seats / Anzahl<input type="number" name="seats" value="<?= $v('seats') ?>" min="0"></label>
        <label>Kaufdatum<input type="date" name="purchase_date" value="<?= $v('purchase_date') ?>"></label>
        <label>Ablaufdatum<input type="date" name="expiry_date" value="<?= $v('expiry_date') ?>"></label>
        <label>Kosten<input type="text" name="cost" value="<?= $v('cost') ?>" placeholder="z.B. 199,00 €/Jahr"></label>
        <label>Lieferant<input type="text" name="supplier" value="<?= $v('supplier') ?>"></label>
        <label>Zugeordnetes Gerät
            <select name="device_id">
                <option value="0">— keins —</option>
                <?php foreach ($devices as $d): ?>
                    <option value="<?= (int) $d['id'] ?>" <?= $selDevice === (int) $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Lizenzschlüssel <small class="muted">(verschlüsselt gespeichert)</small>
            <div class="pwfield">
                <input type="password" name="license" class="mono" autocomplete="off"
                       placeholder="<?= $p && $p['license_enc'] ? '•••• (leer lassen = unverändert)' : '' ?>">
                <button type="button" class="linkbtn togglepw">zeigen</button>
            </div>
        </label>
    </div>
    <label>Notizen<textarea name="notes" rows="3"><?= $v('notes') ?></textarea></label>

    <div class="formactions">
        <button type="submit" class="btn primary">Speichern</button>
        <a class="btn ghost" href="<?= url('products') ?>">Abbrechen</a>
        <?php if ($p): ?>
            <button type="submit" class="btn danger right" formaction="<?= url('product.delete', ['id' => $p['id']]) ?>"
                    data-confirm="Produkt „<?= e($p['name']) ?>“ wirklich löschen?">Löschen</button>
        <?php endif; ?>
    </div>
</form>
