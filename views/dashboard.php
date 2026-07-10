<?php /** @var array $counts @var array $recent @var array $expiring */ ?>
<?= ui('page-header', [
    'title' => 'Übersicht',
    'description' => 'Dein aktueller Stand auf einen Blick.',
    'actions' => [[
        'label' => 'Gerät hinzufügen',
        'icon' => 'plus',
        'href' => url('device.edit'),
        'variant' => 'primary',
    ]],
]) ?>

<div class="metric-grid">
    <?php foreach ([
        ['devices', 'Geräte', 'server'],
        ['creds', 'Zugänge', 'key-round'],
        ['products', 'Produkte', 'package'],
        ['notes', 'Notizen', 'notebook-pen'],
    ] as [$route, $label, $icon]): ?>
        <a class="metric" href="<?= url($route) ?>">
            <span class="metric__icon"><?= ui('icon', ['name' => $icon]) ?></span>
            <span class="metric__value"><?= (int) $counts[$route] ?></span>
            <span class="metric__label"><?= e($label) ?></span>
        </a>
    <?php endforeach; ?>
</div>

<div class="dashboard-grid">
    <section class="panel">
        <?= ui('section-header', ['title' => 'Zuletzt geändert', 'description' => 'Geräte mit den neuesten Änderungen']) ?>
        <?php if (!$recent): ?>
            <?= ui('empty-state', ['title' => 'Noch keine Geräte', 'text' => 'Lege dein erstes Gerät an.', 'icon' => 'server', 'compact' => true]) ?>
        <?php else: ?>
            <ul class="record-list">
                <?php foreach ($recent as $d): ?>
                    <li>
                        <span class="record-list__icon"><?= ui('icon', ['name' => 'server']) ?></span>
                        <div>
                            <a href="<?= url('device.view', ['id' => $d['id']]) ?>"><strong><?= e($d['name']) ?></strong></a>
                            <span><?= e(\NetDoc\device_type_label($d['type'])) ?><?= $d['ip'] ? ' · ' . e($d['ip']) : '' ?></span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="panel">
        <?= ui('section-header', ['title' => 'Anstehende Verlängerungen', 'description' => 'Produkte und Lizenzen nach Ablaufdatum']) ?>
        <?php if (!$expiring): ?>
            <?= ui('empty-state', ['title' => 'Keine Fristen offen', 'text' => 'Aktuell sind keine Ablaufdaten hinterlegt.', 'icon' => 'check-circle', 'compact' => true]) ?>
        <?php else: ?>
            <ul class="record-list">
                <?php foreach ($expiring as $p): ?>
                    <li>
                        <span class="record-list__icon record-list__icon--warning"><?= ui('icon', ['name' => 'package']) ?></span>
                        <div>
                            <a href="<?= url('product.view', ['id' => $p['id']]) ?>"><strong><?= e($p['name']) ?></strong></a>
                            <span>Fällig am <?= e(fmt_day($p['expiry_date'])) ?></span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>
