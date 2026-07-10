<?php /** @var array $rows */
$me = $GLOBALS['auth']->user();
?>
<?= ui('page-header', [
    'title' => 'Benutzer',
    'description' => 'Zugriff und Verantwortlichkeiten im Team verwalten.',
    'actions' => [['label' => 'Benutzer hinzufügen', 'icon' => 'plus', 'href' => url('user.edit'), 'variant' => 'primary']],
]) ?>

<div class="data-panel">
<table class="data-table">
    <thead><tr><th>Benutzername</th><th>E-Mail</th><th>Rolle</th><th>Letzter Login</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $u): ?>
        <tr>
            <td data-label="Benutzername" class="data-table__primary"><strong><?= e($u['username']) ?></strong><?php if ((int) $u['id'] === (int) $me['id']): ?> <span class="badge">Du</span><?php endif; ?></td>
            <td data-label="E-Mail" class="mono"><?= e($u['email'] ?? '') ?></td>
            <td data-label="Rolle"><span class="badge<?= in_array($u['role'] ?? '', ['admin', 'systemadmin'], true) ? ' badge--warning' : '' ?>"><?= e(\NetDoc\role_label($u['role'] ?? 'user')) ?></span></td>
            <td data-label="Letzter Login" class="muted small"><?= fmt_date(isset($u['last_login']) ? (int) $u['last_login'] : null) ?></td>
            <td data-label="Aktion" class="row-actions"><?= ui('button', ['label' => 'Bearbeiten', 'icon' => 'pencil', 'href' => url('user.edit', ['id' => $u['id']]), 'variant' => 'quiet', 'size' => 'small']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<p class="page-note">Neue Benutzer melden sich direkt mit ihrer E-Mail-Adresse und einem einmaligen Code an.</p>
