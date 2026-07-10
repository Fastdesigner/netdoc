<?php /** @var array|null $u @var array $roles @var string $csrf */
$val = fn(string $k) => e($u[$k] ?? '');
$me = $GLOBALS['auth']->user();
$isSelf = $u && (int) $u['id'] === (int) $me['id'];
?>
<?= ui('page-header', [
    'title' => $u ? 'Benutzer bearbeiten' : 'Benutzer hinzufügen',
    'description' => $u ? 'Kontaktdaten und Berechtigungen aktualisieren.' : 'Der neue Benutzer kann sich anschließend direkt per E-Mail-Code anmelden.',
]) ?>

<form class="form-panel form-panel--narrow" method="post" action="<?= url('user.save') ?>" autocomplete="off">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <?php if ($u): ?><input type="hidden" name="id" value="<?= (int) $u['id'] ?>"><?php endif; ?>

    <div class="form-grid">
        <label>Benutzername <span class="required">Pflichtfeld</span><input type="text" name="username" value="<?= $val('username') ?>" required minlength="3" autofocus></label>
        <label>E-Mail-Adresse <span class="required">Pflichtfeld</span><input type="email" name="email" value="<?= $val('email') ?>" required placeholder="name@unternehmen.de"></label>
        <label>Rolle
            <select name="role">
                <?php foreach ($roles as $role): ?>
                    <option value="<?= e($role) ?>" <?= ($u['role'] ?? 'user') === $role ? 'selected' : '' ?>><?= e(\NetDoc\role_label($role)) ?><?= $role === 'systemadmin' ? ' – alle Zugänge' : ($role === 'admin' ? ' – Benutzerverwaltung' : '') ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>

    <?php if ($isSelf): ?><div class="inline-alert"><?= ui('icon', ['name' => 'shield-check']) ?><span>Das ist dein eigenes Konto. Es kann hier nicht gelöscht werden.</span></div><?php endif; ?>

    <div class="form-actions">
        <?= ui('button', ['label' => 'Speichern', 'icon' => 'check', 'variant' => 'primary', 'type' => 'submit']) ?>
        <?= ui('button', ['label' => 'Abbrechen', 'icon' => 'x', 'href' => url('users'), 'variant' => 'quiet']) ?>
        <?php if ($u && !$isSelf): ?>
            <?= ui('button', ['label' => 'Benutzer löschen', 'icon' => 'trash-2', 'variant' => 'danger', 'type' => 'submit', 'class' => 'form-actions__danger', 'attributes' => ['formaction' => url('user.delete', ['id' => $u['id']]), 'formnovalidate' => true, 'data-confirm' => 'Benutzer „' . $u['username'] . '“ wirklich löschen?']]) ?>
        <?php endif; ?>
    </div>
</form>
