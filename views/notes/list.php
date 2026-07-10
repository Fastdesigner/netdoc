<?php /** @var array $rows */ ?>
<?= ui('page-header', [
    'title' => 'Notizen',
    'description' => 'Wissen und Abläufe dort festhalten, wo sie gebraucht werden.',
    'actions' => [['label' => 'Notiz hinzufügen', 'icon' => 'plus', 'href' => url('note.edit'), 'variant' => 'primary']],
]) ?>

<?php if (!$rows): ?>
    <?= ui('empty-state', ['title' => 'Noch keine Notizen', 'text' => 'Halte den ersten Ablauf oder wichtigen Hinweis fest.', 'icon' => 'notebook-pen', 'action' => ['label' => 'Notiz hinzufügen', 'icon' => 'plus', 'href' => url('note.edit'), 'variant' => 'primary']]) ?>
<?php else: ?>
    <div class="note-grid">
        <?php foreach ($rows as $n): ?>
            <a class="note-card" href="<?= url('note.edit', ['id' => $n['id']]) ?>">
                <span class="note-card__icon"><?= ui('icon', ['name' => 'notebook-pen']) ?></span>
                <div class="note-card__title"><strong><?= e($n['title']) ?></strong><?php if (!empty($n['device_name'])): ?><span class="badge"><?= e($n['device_name']) ?></span><?php endif; ?></div>
                <?php if ($n['body']): ?><p><?= e(mb_substr($n['body'], 0, 160)) ?><?= mb_strlen($n['body']) > 160 ? '…' : '' ?></p><?php endif; ?>
                <span class="note-card__meta">Geändert am <?= fmt_date((int) $n['updated_at']) ?></span>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
