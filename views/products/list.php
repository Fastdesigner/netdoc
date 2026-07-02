<?php /** @var array $rows @var string $q */ ?>
<div class="pagehead">
    <h1>Produkte &amp; Lizenzen</h1>
    <a class="btn primary" href="<?= url('product.edit') ?>">+ Neues Produkt</a>
</div>

<form class="filterbar" method="get" action="index.php">
    <input type="hidden" name="r" value="products">
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Nach Name, Hersteller, Kategorie…">
    <button class="btn">Filtern</button>
    <?php if ($q !== ''): ?><a class="btn ghost" href="<?= url('products') ?>">×</a><?php endif; ?>
</form>

<?php if (!$rows): ?>
    <div class="card"><p class="muted">Keine Produkte gefunden.</p></div>
<?php else: ?>
<div class="card nopad">
<table class="table">
    <thead><tr><th>Name</th><th>Hersteller</th><th>Kategorie</th><th>Seats</th><th>Ablauf</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $p): ?>
        <tr>
            <td><a href="<?= url('product.edit', ['id' => $p['id']]) ?>"><strong><?= e($p['name']) ?></strong></a></td>
            <td><?= e($p['vendor'] ?: '–') ?></td>
            <td><?php if ($p['category']): ?><span class="tag"><?= e($p['category']) ?></span><?php else: ?>–<?php endif; ?></td>
            <td><?= $p['seats'] !== null ? (int) $p['seats'] : '–' ?></td>
            <td><?= e($p['expiry_date'] ?: '–') ?></td>
            <td class="rowactions"><a href="<?= url('product.edit', ['id' => $p['id']]) ?>">Bearbeiten</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
