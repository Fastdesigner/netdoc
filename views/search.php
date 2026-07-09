<?php /** @var string $q @var array $results */ ?>
<div class="pagehead"><h1>Suche</h1></div>

<form class="filterbar" method="get" action="index.php">
    <input type="hidden" name="r" value="search">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Suchbegriff…" autofocus>
    <button class="btn primary">Suchen</button>
</form>

<?php if ($q === ''): ?>
    <div class="card"><p class="muted">Suchbegriff eingeben, um Geräte, Zugänge, Produkte und Notizen zu durchsuchen.</p></div>
<?php else:
    $total = count($results['devices']) + count($results['creds']) + count($results['products']) + count($results['notes']);
?>
    <p class="muted"><?= $total ?> Treffer für „<?= e($q) ?>“</p>

    <?php if ($results['devices']): ?>
    <section class="card">
        <h2>Geräte</h2>
        <ul class="linklist">
            <?php foreach ($results['devices'] as $d): ?>
                <li><a href="<?= url('device.view', ['id' => $d['id']]) ?>"><?= e($d['name']) ?></a>
                    <span class="tag"><?= e($d['type']) ?></span> <span class="muted mono"><?= e($d['ip']) ?></span></li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>

    <?php if ($results['creds']): ?>
    <section class="card nopad">
        <div class="cardhead"><h2>Zugänge</h2></div>
        <?php $creds = $results['creds']; require VIEWS . '/credentials/_table.php'; ?>
    </section>
    <?php endif; ?>

    <?php if ($results['products']): ?>
    <section class="card">
        <h2>Produkte</h2>
        <ul class="linklist">
            <?php foreach ($results['products'] as $p): ?>
                <li><a href="<?= url('product.view', ['id' => $p['id']]) ?>"><?= e($p['name']) ?></a> <span class="muted"><?= e($p['vendor']) ?></span></li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>

    <?php if ($results['notes']): ?>
    <section class="card">
        <h2>Notizen</h2>
        <ul class="linklist">
            <?php foreach ($results['notes'] as $n): ?>
                <li><a href="<?= url('note.edit', ['id' => $n['id']]) ?>"><?= e($n['title']) ?></a></li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>
<?php endif; ?>
