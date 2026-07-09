<?php /** @var array $p @var array $creds */ ?>
<div class="pagehead">
    <h1><?= e($p['name']) ?></h1>
    <a class="btn" href="<?= url('product.edit', ['id' => $p['id']]) ?>">Bearbeiten</a>
</div>

<div class="card">
    <dl class="detail">
        <div><dt>Hersteller</dt><dd><?= e($p['vendor'] ?: '–') ?></dd></div>
        <div><dt>Kategorie</dt><dd><?= e($p['category'] ?: '–') ?></dd></div>
        <div><dt>Seats / Anzahl</dt><dd><?= $p['seats'] !== null ? (int) $p['seats'] : '–' ?></dd></div>
        <div><dt>Zugeordnetes Gerät</dt><dd><?= e($p['device_name'] ?: '–') ?></dd></div>
        <div><dt>Kaufdatum</dt><dd><?= e($p['purchase_date'] ?: '–') ?></dd></div>
        <div><dt>Ablaufdatum</dt><dd><?= e($p['expiry_date'] ?: '–') ?></dd></div>
        <div><dt>Kosten</dt><dd><?= e($p['cost'] ?: '–') ?></dd></div>
        <div><dt>Lieferant</dt><dd><?= e($p['supplier'] ?: '–') ?></dd></div>
    </dl>
    <?php if ($p['notes']): ?>
        <div class="notesblock"><?= nl2br(e($p['notes'])) ?></div>
    <?php endif; ?>
</div>

<section class="card">
    <div class="cardhead">
        <h2>Zugänge</h2>
        <a class="btn small" href="<?= url('cred.edit', ['product_id' => $p['id'], 'back' => 'product']) ?>">+ Zugang</a>
    </div>
    <?php if (!$creds): ?>
        <p class="muted">Keine Zugänge hinterlegt.</p>
    <?php else: ?>
        <?php require VIEWS . '/credentials/_table.php'; ?>
    <?php endif; ?>
</section>

<p><a class="btn ghost" href="<?= url('products') ?>">← Zurück zur Liste</a></p>
