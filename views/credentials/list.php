<?php /** @var array $rows @var string $q */
$creds = $rows;
?>
<?= ui('page-header', [
    'title' => 'Zugänge',
    'description' => 'Anmeldedaten und Verbindungen sicher an einem Ort.',
    'actions' => [['label' => 'Zugang hinzufügen', 'icon' => 'plus', 'href' => url('cred.edit'), 'variant' => 'primary']],
]) ?>
<?= ui('filter-bar', ['route' => 'creds', 'query' => $q, 'placeholder' => 'Titel, Benutzername oder Adresse']) ?>

<?php if (!$rows): ?>
    <?= ui('empty-state', [
        'title' => $q ? 'Keine passenden Zugänge' : 'Noch keine Zugänge',
        'text' => $q ? 'Ändere oder lösche den Filter.' : 'Speichere den ersten Zugang und ordne ihn einem Gerät oder Produkt zu.',
        'icon' => 'key-round',
        'action' => $q
            ? ['label' => 'Filter löschen', 'icon' => 'x', 'href' => url('creds')]
            : ['label' => 'Zugang hinzufügen', 'icon' => 'plus', 'href' => url('cred.edit'), 'variant' => 'primary'],
    ]) ?>
<?php else: ?>
    <div class="data-panel"><?php require VIEWS . '/credentials/_table.php'; ?></div>
<?php endif; ?>
