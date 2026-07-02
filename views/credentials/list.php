<?php /** @var array $rows @var string $q */
$creds = $rows; // Partial erwartet $creds
?>
<div class="pagehead">
    <h1>Zugänge &amp; Verbindungen</h1>
    <a class="btn primary" href="<?= url('cred.edit') ?>">+ Neuer Zugang</a>
</div>

<form class="filterbar" method="get" action="index.php">
    <input type="hidden" name="r" value="creds">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Nach Titel, Benutzer, URL…">
    <button class="btn">Filtern</button>
    <?php if ($q !== ''): ?><a class="btn ghost" href="<?= url('creds') ?>">×</a><?php endif; ?>
</form>

<?php if (!$rows): ?>
    <div class="card"><p class="muted">Keine Zugänge gefunden.</p></div>
<?php else: ?>
    <div class="card nopad"><?php require VIEWS . '/credentials/_table.php'; ?></div>
<?php endif; ?>
