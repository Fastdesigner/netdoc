<?php /** @var array $p @var array $creds */ ?>
<?= ui('page-header', [
    'title' => $p['name'],
    'description' => $p['vendor'] ?: 'Produktdetails',
    'badge' => $p['category'] ?: null,
    'actions' => [['label' => 'Bearbeiten', 'icon' => 'pencil', 'href' => url('product.edit', ['id' => $p['id']])]],
]) ?>

<section class="panel detail-panel">
    <?= ui('section-header', ['title' => 'Produktinformationen', 'description' => 'Vertrag, Laufzeit und Zuordnung']) ?>
    <dl class="detail-grid">
        <div><dt>Hersteller</dt><dd><?= e($p['vendor'] ?: 'Nicht angegeben') ?></dd></div>
        <div><dt>Kategorie</dt><dd><?= e($p['category'] ?: 'Nicht angegeben') ?></dd></div>
        <div><dt>Anzahl</dt><dd><?= $p['seats'] !== null ? (int) $p['seats'] : 'Nicht angegeben' ?></dd></div>
        <div><dt>Zugeordnetes Gerät</dt><dd><?= e($p['device_name'] ?: 'Nicht zugeordnet') ?></dd></div>
        <div><dt>Kaufdatum</dt><dd><?= e(fmt_day($p['purchase_date'])) ?></dd></div>
        <div><dt>Ablaufdatum</dt><dd><?= e(fmt_day($p['expiry_date'])) ?></dd></div>
        <div><dt>Kosten</dt><dd><?= e($p['cost'] ?: 'Nicht angegeben') ?></dd></div>
        <div><dt>Lieferant</dt><dd><?= e($p['supplier'] ?: 'Nicht angegeben') ?></dd></div>
    </dl>
    <?php if ($p['notes']): ?><div class="detail-note"><strong>Hinweis</strong><p><?= nl2br(e($p['notes'])) ?></p></div><?php endif; ?>
</section>

<section class="panel">
    <?= ui('section-header', ['title' => 'Zugänge', 'description' => 'Anmeldedaten für dieses Produkt', 'action' => ['label' => 'Zugang hinzufügen', 'icon' => 'plus', 'href' => url('cred.edit', ['product_id' => $p['id'], 'back' => 'product']), 'size' => 'small']]) ?>
    <?php if (!$creds): ?>
        <?= ui('empty-state', ['title' => 'Keine Zugänge hinterlegt', 'text' => 'Hier erscheinen zugeordnete Anmeldedaten.', 'icon' => 'key-round', 'compact' => true]) ?>
    <?php else: ?>
        <?php require VIEWS . '/credentials/_table.php'; ?>
    <?php endif; ?>
</section>

<div class="page-footer-actions"><?= ui('button', ['label' => 'Zurück zu Produkten', 'icon' => 'arrow-left', 'href' => url('products'), 'variant' => 'quiet']) ?></div>
