<?php /** @var array $dev @var array $creds @var array $notes @var array $products @var array $documents */ ?>
<?= ui('page-header', [
    'title' => $dev['name'],
    'description' => trim(($dev['hostname'] ?: '') . ($dev['hostname'] && $dev['ip'] ? ' · ' : '') . ($dev['ip'] ?: '')) ?: 'Gerätedetails',
    'badge' => \NetDoc\device_type_label($dev['type']),
    'actions' => [['label' => 'Bearbeiten', 'icon' => 'pencil', 'href' => url('device.edit', ['id' => $dev['id']])]],
]) ?>

<section class="panel detail-panel">
    <?= ui('section-header', ['title' => 'Geräteinformationen', 'description' => 'Technische Daten und aktueller Zustand']) ?>
    <dl class="detail-grid">
        <div><dt>Status</dt><dd><span class="status status--<?= e($dev['status']) ?>"><span></span><?= e(\NetDoc\device_status_label($dev['status'])) ?></span></dd></div>
        <div><dt>Standort</dt><dd><?= e($dev['location'] ?: 'Nicht angegeben') ?></dd></div>
        <div><dt>IP-Adresse</dt><dd class="mono"><?= e($dev['ip'] ?: 'Nicht angegeben') ?></dd></div>
        <div><dt>Hostname</dt><dd class="mono"><?= e($dev['hostname'] ?: 'Nicht angegeben') ?></dd></div>
        <div><dt>Hersteller</dt><dd><?= e($dev['vendor'] ?: 'Nicht angegeben') ?></dd></div>
        <div><dt>Modell</dt><dd><?= e($dev['model'] ?: 'Nicht angegeben') ?></dd></div>
        <div><dt>Betriebssystem</dt><dd><?= e($dev['os'] ?: 'Nicht angegeben') ?></dd></div>
        <div><dt>Zuletzt geändert</dt><dd><?= fmt_date((int) $dev['updated_at']) ?></dd></div>
    </dl>
    <?php if ($dev['notes']): ?><div class="detail-note"><strong>Hinweis</strong><p><?= nl2br(e($dev['notes'])) ?></p></div><?php endif; ?>
</section>

<div class="related-grid">
    <section class="panel related-grid__wide">
        <?= ui('section-header', ['title' => 'Zugänge', 'description' => 'Anmeldedaten für dieses Gerät', 'action' => ['label' => 'Zugang hinzufügen', 'icon' => 'plus', 'href' => url('cred.edit', ['device_id' => $dev['id']]), 'size' => 'small']]) ?>
        <?php if (!$creds): ?>
            <?= ui('empty-state', ['title' => 'Keine Zugänge hinterlegt', 'text' => 'Hier erscheinen zugeordnete Anmeldedaten.', 'icon' => 'key-round', 'compact' => true]) ?>
        <?php else: ?>
            <?php require VIEWS . '/credentials/_table.php'; ?>
        <?php endif; ?>
    </section>

    <section class="panel">
        <?= ui('section-header', ['title' => 'Notizen', 'description' => 'Abläufe und wichtige Hinweise', 'action' => ['label' => 'Notiz hinzufügen', 'icon' => 'plus', 'href' => url('note.edit', ['device_id' => $dev['id']]), 'size' => 'small']]) ?>
        <?php if (!$notes): ?>
            <?= ui('empty-state', ['title' => 'Keine Notizen', 'icon' => 'notebook-pen', 'compact' => true]) ?>
        <?php else: ?>
            <ul class="record-list">
                <?php foreach ($notes as $n): ?>
                    <li><span class="record-list__icon"><?= ui('icon', ['name' => 'notebook-pen']) ?></span><div><a href="<?= url('note.edit', ['id' => $n['id']]) ?>"><strong><?= e($n['title']) ?></strong></a><span><?= e(mb_substr($n['body'] ?? '', 0, 90)) ?><?= mb_strlen($n['body'] ?? '') > 90 ? '…' : '' ?></span></div></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="panel">
        <?= ui('section-header', ['title' => 'Dokumente', 'description' => 'Pläne und Dateien', 'action' => ['label' => 'Hochladen', 'icon' => 'upload', 'href' => url('document.edit', ['device_id' => $dev['id']]), 'size' => 'small']]) ?>
        <?php if (!$documents): ?>
            <?= ui('empty-state', ['title' => 'Keine Dokumente', 'icon' => 'file-text', 'compact' => true]) ?>
        <?php else: ?>
            <ul class="record-list">
                <?php foreach ($documents as $doc): ?>
                    <li><span class="record-list__icon"><?= ui('icon', ['name' => 'file-text']) ?></span><div><a href="<?= url('document.download', ['id' => $doc['id']]) ?>"><strong><?= e($doc['title']) ?></strong></a><span><?= fmt_bytes((int) ($doc['size'] ?? 0)) ?></span></div><?= ui('button', ['label' => 'Bearbeiten', 'icon' => 'pencil', 'href' => url('document.edit', ['id' => $doc['id']]), 'variant' => 'quiet', 'size' => 'small', 'class' => 'button--icon-only', 'attributes' => ['title' => 'Dokument bearbeiten', 'aria-label' => 'Dokument bearbeiten']]) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <?php if ($products): ?>
    <section class="panel related-grid__wide">
        <?= ui('section-header', ['title' => 'Zugeordnete Produkte', 'description' => 'Lizenzen und Verträge für dieses Gerät']) ?>
        <ul class="record-list record-list--inline">
            <?php foreach ($products as $p): ?>
                <li><span class="record-list__icon"><?= ui('icon', ['name' => 'package']) ?></span><div><a href="<?= url('product.view', ['id' => $p['id']]) ?>"><strong><?= e($p['name']) ?></strong></a></div></li>
            <?php endforeach; ?>
        </ul>
    </section>
    <?php endif; ?>
</div>

<div class="page-footer-actions"><?= ui('button', ['label' => 'Zurück zu Geräten', 'icon' => 'arrow-left', 'href' => url('devices'), 'variant' => 'quiet']) ?></div>
