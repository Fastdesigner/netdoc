<?php
/** @var string $route @var string $query @var string $placeholder */
?>
<form class="filter-bar" method="get" action="index.php" role="search">
    <input type="hidden" name="r" value="<?= e($route) ?>">
    <label class="filter-bar__field">
        <span class="sr-only">Liste durchsuchen</span>
        <?= ui('icon', ['name' => 'search']) ?>
        <input type="search" name="q" value="<?= e($query) ?>" placeholder="<?= e($placeholder) ?>">
    </label>
    <?= ui('button', ['label' => 'Filtern', 'icon' => 'filter', 'type' => 'submit']) ?>
    <?php if ($query !== ''): ?><?= ui('button', ['label' => 'Filter löschen', 'icon' => 'x', 'href' => url($route), 'variant' => 'quiet', 'class' => 'filter-bar__clear']) ?><?php endif; ?>
</form>
