<?php /** @var array $rows @var string $q */ ?>
<?= ui('page-header', [
    'title' => 'Produkte und Lizenzen',
    'description' => 'Verträge, Laufzeiten und Kosten im Blick behalten.',
    'actions' => [['label' => 'Produkt hinzufügen', 'icon' => 'plus', 'href' => url('product.edit'), 'variant' => 'primary']],
]) ?>
<?= ui('filter-bar', ['route' => 'products', 'query' => $q, 'placeholder' => 'Name, Hersteller oder Kategorie']) ?>

<?php if (!$rows): ?>
    <?= ui('empty-state', [
        'title' => $q ? 'Keine passenden Produkte' : 'Noch keine Produkte',
        'text' => $q ? 'Ändere oder lösche den Filter.' : 'Erfasse dein erstes Produkt oder eine Lizenz mit Laufzeit und Kosten.',
        'icon' => 'package',
        'action' => $q
            ? ['label' => 'Filter löschen', 'icon' => 'x', 'href' => url('products')]
            : ['label' => 'Produkt hinzufügen', 'icon' => 'plus', 'href' => url('product.edit'), 'variant' => 'primary'],
    ]) ?>
<?php else: ?>
<div class="data-panel">
<table class="data-table">
    <thead><tr><th>Name</th><th>Hersteller</th><th>Kategorie</th><th>Anzahl</th><th>Ablauf</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $p): ?>
        <tr>
            <td data-label="Name" class="data-table__primary"><a href="<?= url('product.view', ['id' => $p['id']]) ?>"><strong><?= e($p['name']) ?></strong></a></td>
            <td data-label="Hersteller"><?= e($p['vendor'] ?: '–') ?></td>
            <td data-label="Kategorie"><?php if ($p['category']): ?><span class="badge"><?= e($p['category']) ?></span><?php else: ?>–<?php endif; ?></td>
            <td data-label="Anzahl"><?= $p['seats'] !== null ? (int) $p['seats'] : '–' ?></td>
            <td data-label="Ablauf"><?= e(fmt_day($p['expiry_date'])) ?></td>
            <td data-label="Aktion" class="row-actions"><?= ui('button', ['label' => 'Bearbeiten', 'icon' => 'pencil', 'href' => url('product.edit', ['id' => $p['id']]), 'variant' => 'quiet', 'size' => 'small']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
