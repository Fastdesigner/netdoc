<?php /** @var array|null $u @var array $roles @var string $csrf */
$val = fn(string $k) => e($u[$k] ?? '');
$me  = $GLOBALS['auth']->user();
$isSelf = $u && (int) $u['id'] === (int) $me['id'];
?>
<div class="pagehead">
    <h1><?= $u ? 'Benutzer bearbeiten' : 'Neuer Benutzer' ?></h1>
</div>

<form class="card form" method="post" action="<?= url('user.save') ?>" autocomplete="off">
    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
    <?php if ($u): ?><input type="hidden" name="id" value="<?= (int) $u['id'] ?>"><?php endif; ?>

    <div class="grid2">
        <label>Benutzername *<input type="text" name="username" value="<?= $val('username') ?>" required minlength="3" autofocus></label>
        <label>E-Mail-Adresse *<input type="email" name="email" value="<?= $val('email') ?>" required placeholder="kollege@example.com"></label>
        <label>Rolle
            <select name="role">
                <?php foreach ($roles as $role): ?>
                    <option value="<?= e($role) ?>" <?= ($u['role'] ?? 'user') === $role ? 'selected' : '' ?>><?= e(\NetDoc\role_label($role)) ?><?= $role === 'systemadmin' ? ' (inkl. privater Zugänge)' : ($role === 'admin' ? ' (Benutzerverwaltung)' : '') ?></option>
                <?php endforeach; ?>
            </select>
        </label>
    </div>

    <div class="formactions">
        <button type="submit" class="btn primary">Speichern</button>
        <a class="btn ghost" href="<?= url('users') ?>">Abbrechen</a>
        <?php if ($u && !$isSelf): ?>
            <button type="submit" class="btn danger right" formaction="<?= url('user.delete', ['id' => $u['id']]) ?>"
                    formnovalidate data-confirm="Benutzer „<?= e($u['username']) ?>“ wirklich löschen?">Löschen</button>
        <?php endif; ?>
    </div>
    <?php if ($isSelf): ?><p class="muted small">Das ist dein eigenes Konto – Löschen ist hier nicht möglich.</p><?php endif; ?>
</form>
