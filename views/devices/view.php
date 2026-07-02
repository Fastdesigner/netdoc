<?php /** @var array $dev @var array $creds @var array $notes @var array $products */ ?>
<div class="pagehead">
    <h1><?= e($dev['name']) ?> <span class="tag"><?= e($dev['type']) ?></span></h1>
    <a class="btn" href="<?= url('device.edit', ['id' => $dev['id']]) ?>">Bearbeiten</a>
</div>

<div class="card">
    <dl class="detail">
        <div><dt>IP-Adresse</dt><dd class="mono"><?= e($dev['ip'] ?: '–') ?></dd></div>
        <div><dt>Hostname</dt><dd class="mono"><?= e($dev['hostname'] ?: '–') ?></dd></div>
        <div><dt>Standort</dt><dd><?= e($dev['location'] ?: '–') ?></dd></div>
        <div><dt>Status</dt><dd><?= e($dev['status']) ?></dd></div>
        <div><dt>Hersteller</dt><dd><?= e($dev['vendor'] ?: '–') ?></dd></div>
        <div><dt>Modell</dt><dd><?= e($dev['model'] ?: '–') ?></dd></div>
        <div><dt>Betriebssystem</dt><dd><?= e($dev['os'] ?: '–') ?></dd></div>
        <div><dt>Aktualisiert</dt><dd><?= fmt_date((int) $dev['updated_at']) ?></dd></div>
    </dl>
    <?php if ($dev['notes']): ?>
        <div class="notesblock"><?= nl2br(e($dev['notes'])) ?></div>
    <?php endif; ?>
</div>

<section class="card">
    <div class="cardhead">
        <h2>Zugänge</h2>
        <a class="btn small" href="<?= url('cred.edit', ['device_id' => $dev['id']]) ?>">+ Zugang</a>
    </div>
    <?php if (!$creds): ?>
        <p class="muted">Keine Zugänge hinterlegt.</p>
    <?php else: ?>
        <?php require VIEWS . '/credentials/_table.php'; ?>
    <?php endif; ?>
</section>

<section class="card">
    <div class="cardhead">
        <h2>Notizen</h2>
        <a class="btn small" href="<?= url('note.edit', ['device_id' => $dev['id']]) ?>">+ Notiz</a>
    </div>
    <?php if (!$notes): ?>
        <p class="muted">Keine Notizen.</p>
    <?php else: foreach ($notes as $n): ?>
        <div class="noteitem">
            <a href="<?= url('note.edit', ['id' => $n['id']]) ?>"><strong><?= e($n['title']) ?></strong></a>
            <div class="muted small"><?= fmt_date((int) $n['updated_at']) ?></div>
            <?php if ($n['body']): ?><div><?= nl2br(e($n['body'])) ?></div><?php endif; ?>
        </div>
    <?php endforeach; endif; ?>
</section>

<section class="card">
    <div class="cardhead">
        <h2>Dokumente</h2>
        <a class="btn small" href="<?= url('document.edit', ['device_id' => $dev['id']]) ?>">+ Hochladen</a>
    </div>
    <?php if (!$documents): ?>
        <p class="muted">Keine Dokumente.</p>
    <?php else: ?>
        <ul class="linklist">
            <?php foreach ($documents as $doc): ?>
                <li>
                    <a href="<?= url('document.download', ['id' => $doc['id']]) ?>"><?= e($doc['title']) ?></a>
                    <span class="muted small"><?= fmt_bytes((int) ($doc['size'] ?? 0)) ?></span>
                    <a class="muted small" href="<?= url('document.edit', ['id' => $doc['id']]) ?>">bearbeiten</a>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

<?php if ($products): ?>
<section class="card">
    <h2>Zugeordnete Produkte</h2>
    <ul class="linklist">
        <?php foreach ($products as $p): ?>
            <li><a href="<?= url('product.edit', ['id' => $p['id']]) ?>"><?= e($p['name']) ?></a></li>
        <?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>

<p><a class="btn ghost" href="<?= url('devices') ?>">← Zurück zur Liste</a></p>
