<?php /** @var array $rows */ ?>
<div class="pagehead">
    <h1>Notizen</h1>
    <a class="btn primary" href="<?= url('note.edit') ?>">+ Neue Notiz</a>
</div>

<?php if (!$rows): ?>
    <div class="card"><p class="muted">Noch keine Notizen.</p></div>
<?php else: ?>
    <div class="notegrid">
        <?php foreach ($rows as $n): ?>
            <a class="card noteitem" href="<?= url('note.edit', ['id' => $n['id']]) ?>">
                <strong><?= e($n['title']) ?></strong>
                <?php if (!empty($n['device_name'])): ?><span class="tag"><?= e($n['device_name']) ?></span><?php endif; ?>
                <div class="muted small"><?= fmt_date((int) $n['updated_at']) ?></div>
                <?php if ($n['body']): ?><p class="notesnip"><?= e(mb_substr($n['body'], 0, 160)) ?><?= mb_strlen($n['body']) > 160 ? '…' : '' ?></p><?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
