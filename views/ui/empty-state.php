<?php
/** @var string $title @var string|null $text @var string|null $icon @var array|null $action @var bool|null $compact */
?>
<div class="empty-state<?= !empty($compact) ? ' empty-state--compact' : '' ?>">
    <?php if (!empty($icon)): ?><span class="empty-state__icon"><?= ui('icon', ['name' => $icon]) ?></span><?php endif; ?>
    <div>
        <strong><?= e($title) ?></strong>
        <?php if (!empty($text)): ?><p><?= e($text) ?></p><?php endif; ?>
    </div>
    <?php if (!empty($action)): ?><?= ui('button', $action) ?><?php endif; ?>
</div>
