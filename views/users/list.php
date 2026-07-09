<?php /** @var array $rows */
$me = $GLOBALS['auth']->user();
?>
<div class="pagehead">
    <h1>Benutzer</h1>
    <a class="btn primary" href="<?= url('user.edit') ?>">+ Neuer Benutzer</a>
</div>

<div class="card nopad">
<table class="table">
    <thead><tr><th>Benutzername</th><th>E-Mail</th><th>Rolle</th><th>Letzter Login</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $u): ?>
        <tr>
            <td data-label="Benutzername"><strong><?= e($u['username']) ?></strong><?php if ((int) $u['id'] === (int) $me['id']): ?> <span class="tag">du</span><?php endif; ?></td>
            <td data-label="E-Mail" class="mono"><?= e($u['email'] ?? '') ?></td>
            <td data-label="Rolle"><span class="tag <?= in_array($u['role'] ?? '', ['admin', 'systemadmin'], true) ? 'warn' : '' ?>"><?= e(\NetDoc\role_label($u['role'] ?? 'user')) ?></span></td>
            <td data-label="Letzter Login" class="muted small"><?= fmt_date(isset($u['last_login']) ? (int) $u['last_login'] : null) ?></td>
            <td data-label="Aktion" class="rowactions"><a href="<?= url('user.edit', ['id' => $u['id']]) ?>">Bearbeiten</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<p class="muted small">Die Anmeldung erfolgt passwortlos per E-Mail-Code. Neue Benutzer können sich sofort anmelden – ein Passwort wird nicht vergeben.</p>
