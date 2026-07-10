<?php
/** @var string $title @var string|null $description @var array|null $action */
?>
<header class="section-header">
    <div>
        <h2><?= e($title) ?></h2>
        <?php if (!empty($description)): ?><p><?= e($description) ?></p><?php endif; ?>
    </div>
    <?php if (!empty($action)): ?><?= ui('button', $action) ?><?php endif; ?>
</header>
