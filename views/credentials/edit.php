<?php /** @var array|null $cred @var array $devices @var array $products @var array $categories @var string $csrf @var int $preselectDevice @var int $preselectProduct */
$v = fn(string $k) => e($cred[$k] ?? '');
$selDevice = (int) ($cred['device_id'] ?? $preselectDevice);
$selProduct = (int) ($cred['product_id'] ?? $preselectProduct);
$visibility = $cred['visibility'] ?? 'team';
$cancelUrl = param('back') === 'product' && $selProduct ? url('product.view', ['id' => $selProduct]) : url('creds');
?>
<?= ui('page-header', [
    'title' => $cred ? 'Zugang bearbeiten' : 'Zugang hinzufügen',
    'description' => 'Anmeldedaten verschlüsselt speichern und passend zuordnen.',
]) ?>

<form class="form-panel" method="post" action="<?= url('cred.save') ?>" autocomplete="off">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <?php if ($cred): ?><input type="hidden" name="id" value="<?= (int) $cred['id'] ?>"><?php endif; ?>
    <?php if (param('back') === 'product'): ?><input type="hidden" name="back" value="product"><?php endif; ?>

    <div class="form-grid">
        <label>Titel <span class="required">Pflichtfeld</span><input type="text" name="title" value="<?= $v('title') ?>" required autofocus placeholder="Zum Beispiel Firewall-Administration"></label>
        <label>Kategorie
            <select name="category">
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= e($cat) ?>" <?= ($cred['category'] ?? 'login') === $cat ? 'selected' : '' ?>><?= e(\NetDoc\credential_category_label($cat)) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Gerät
            <select name="device_id">
                <option value="0">Nicht zugeordnet</option>
                <?php foreach ($devices as $d): ?>
                    <option value="<?= (int) $d['id'] ?>" <?= $selDevice === (int) $d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Produkt oder Lizenz
            <select name="product_id">
                <option value="0">Nicht zugeordnet</option>
                <?php foreach ($products as $p): ?>
                    <option value="<?= (int) $p['id'] ?>" <?= $selProduct === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Sichtbarkeit
            <select name="visibility">
                <option value="team" <?= $visibility !== 'private' ? 'selected' : '' ?>>Für das Team</option>
                <option value="private" <?= $visibility === 'private' ? 'selected' : '' ?>>Nur für mich</option>
            </select>
        </label>
        <label>Benutzername<input type="text" name="username" value="<?= $v('username') ?>" class="mono" autocomplete="off"></label>
        <label>Passwort oder Secret
            <div class="password-field">
                <input type="password" name="secret" class="mono" autocomplete="new-password" placeholder="<?= $cred && $cred['secret_enc'] ? 'Leer lassen, um das Passwort beizubehalten' : '' ?>">
                <?= ui('button', ['label' => 'Anzeigen', 'icon' => 'eye', 'size' => 'small', 'class' => 'toggle-password']) ?>
            </div>
        </label>
        <label>Adresse oder Ziel<input type="text" name="url" value="<?= $v('url') ?>" class="mono" placeholder="https://… oder ssh://host"></label>
        <label>Port<input type="text" name="port" value="<?= $v('port') ?>" class="mono" placeholder="443"></label>
    </div>
    <label>Notizen<textarea name="notes" rows="3" placeholder="Zusätzliche Hinweise für dein Team"><?= $v('notes') ?></textarea></label>

    <div class="form-actions">
        <?= ui('button', ['label' => 'Speichern', 'icon' => 'check', 'variant' => 'primary', 'type' => 'submit']) ?>
        <?= ui('button', ['label' => 'Abbrechen', 'icon' => 'x', 'href' => $cancelUrl, 'variant' => 'quiet']) ?>
        <?php if ($cred): ?>
            <?= ui('button', ['label' => 'Zugang löschen', 'icon' => 'trash-2', 'variant' => 'danger', 'type' => 'submit', 'class' => 'form-actions__danger', 'attributes' => ['formaction' => url('cred.delete', ['id' => $cred['id']]), 'data-confirm' => 'Zugang „' . $cred['title'] . '“ wirklich löschen?']]) ?>
        <?php endif; ?>
    </div>
</form>
