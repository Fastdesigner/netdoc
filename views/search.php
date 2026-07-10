<?php /** @var string $q @var array $results */
$total = $q === '' ? 0 : count($results['devices']) + count($results['creds']) + count($results['products']) + count($results['notes']);
?>
<?= ui('page-header', [
    'title' => 'Suche',
    'description' => $q === '' ? 'Durchsuche Geräte, Zugänge, Produkte und Notizen.' : $total . ' Treffer für „' . $q . '“',
]) ?>

<form class="search-page-form" method="get" action="index.php" role="search">
    <input type="hidden" name="r" value="search">
    <label><span class="sr-only">Suchbegriff</span><?= ui('icon', ['name' => 'search']) ?><input type="search" name="q" value="<?= e($q) ?>" placeholder="Wonach suchst du?" autofocus></label>
    <?= ui('button', ['label' => 'Suchen', 'icon' => 'search', 'variant' => 'primary', 'type' => 'submit']) ?>
</form>

<?php if ($q === ''): ?>
    <?= ui('empty-state', ['title' => 'Bereit für deine Suche', 'text' => 'Gib einen Namen, eine IP-Adresse, einen Benutzernamen oder einen Begriff ein.', 'icon' => 'search']) ?>
<?php elseif ($total === 0): ?>
    <?= ui('empty-state', ['title' => 'Keine Treffer', 'text' => 'Prüfe die Schreibweise oder versuche einen allgemeineren Begriff.', 'icon' => 'search']) ?>
<?php else: ?>
    <div class="search-results">
        <?php if ($results['devices']): ?>
        <section class="panel">
            <?= ui('section-header', ['title' => 'Geräte', 'description' => count($results['devices']) . ' Treffer']) ?>
            <ul class="record-list">
                <?php foreach ($results['devices'] as $d): ?>
                    <li><span class="record-list__icon"><?= ui('icon', ['name' => 'server']) ?></span><div><a href="<?= url('device.view', ['id' => $d['id']]) ?>"><strong><?= e($d['name']) ?></strong></a><span><?= e(\NetDoc\device_type_label($d['type'])) ?><?= $d['ip'] ? ' · ' . e($d['ip']) : '' ?></span></div></li>
                <?php endforeach; ?>
            </ul>
        </section>
        <?php endif; ?>

        <?php if ($results['creds']): ?>
        <section class="panel search-results__wide">
            <?= ui('section-header', ['title' => 'Zugänge', 'description' => count($results['creds']) . ' Treffer']) ?>
            <?php $creds = $results['creds']; require VIEWS . '/credentials/_table.php'; ?>
        </section>
        <?php endif; ?>

        <?php if ($results['products']): ?>
        <section class="panel">
            <?= ui('section-header', ['title' => 'Produkte', 'description' => count($results['products']) . ' Treffer']) ?>
            <ul class="record-list">
                <?php foreach ($results['products'] as $p): ?>
                    <li><span class="record-list__icon"><?= ui('icon', ['name' => 'package']) ?></span><div><a href="<?= url('product.view', ['id' => $p['id']]) ?>"><strong><?= e($p['name']) ?></strong></a><span><?= e($p['vendor'] ?: 'Kein Hersteller angegeben') ?></span></div></li>
                <?php endforeach; ?>
            </ul>
        </section>
        <?php endif; ?>

        <?php if ($results['notes']): ?>
        <section class="panel">
            <?= ui('section-header', ['title' => 'Notizen', 'description' => count($results['notes']) . ' Treffer']) ?>
            <ul class="record-list">
                <?php foreach ($results['notes'] as $n): ?>
                    <li><span class="record-list__icon"><?= ui('icon', ['name' => 'notebook-pen']) ?></span><div><a href="<?= url('note.edit', ['id' => $n['id']]) ?>"><strong><?= e($n['title']) ?></strong></a></div></li>
                <?php endforeach; ?>
            </ul>
        </section>
        <?php endif; ?>
    </div>
<?php endif; ?>
