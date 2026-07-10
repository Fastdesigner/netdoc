<?php
/** @var string $title @var string|null $description @var string|null $badge @var array|null $actions */
?>
<header class="page-header">
    <div class="page-header__copy">
        <div class="page-header__title-row">
            <h1><?= e($title) ?></h1>
            <?php if (!empty($badge)): ?><span class="badge"><?= e($badge) ?></span><?php endif; ?>
        </div>
        <?php if (!empty($description)): ?><p><?= e($description) ?></p><?php endif; ?>
    </div>
    <?php if (!empty($actions)): ?>
        <div class="page-header__actions">
            <?php foreach ($actions as $action): ?><?= ui('button', $action) ?><?php endforeach; ?>
        </div>
    <?php endif; ?>
</header>
