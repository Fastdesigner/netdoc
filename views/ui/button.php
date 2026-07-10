<?php
/** @var string $label @var string|null $href @var string|null $icon @var string|null $variant @var string|null $size @var string|null $type @var array|null $attributes @var string|null $class */
$isLink = isset($href) && $href !== '';
$classes = trim('button button--' . ($variant ?? 'secondary') . (!empty($size) ? ' button--' . $size : '') . (!empty($class) ? ' ' . $class : ''));
$attrs = ($attributes ?? []) + ['class' => $classes];
if ($isLink) {
    $attrs = ['href' => $href] + $attrs;
} else {
    $attrs = ['type' => $type ?? 'button'] + $attrs;
}
?>
<<?= $isLink ? 'a' : 'button' ?><?= ui_attrs($attrs) ?>><?php if (!empty($icon)): ?><?= ui('icon', ['name' => $icon]) ?><?php endif; ?><span class="button__label"><?= e($label) ?></span></<?= $isLink ? 'a' : 'button' ?>>
