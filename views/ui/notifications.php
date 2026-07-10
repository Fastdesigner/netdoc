<?php /** @var array $items */ ?>
<?php if ($items): ?>
<div class="notifications" aria-live="polite" aria-atomic="true">
    <?php foreach ($items as $item): ?>
        <div class="notification notification--<?= e($item['type']) ?>" role="<?= $item['type'] === 'error' ? 'alert' : 'status' ?>">
            <?= ui('icon', ['name' => $item['type'] === 'error' ? 'alert-circle' : 'check-circle']) ?>
            <span><?= e($item['msg']) ?></span>
            <button type="button" class="notification__dismiss" aria-label="Meldung schließen" title="Meldung schließen"><?= ui('icon', ['name' => 'x']) ?></button>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
