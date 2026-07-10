<?php
/** @var string $content @var string $title */
$auth = $GLOBALS['auth'];
$u = $auth->user();
$active = param('r', 'home');
$nav = [
    'home'      => ['Übersicht', 'layout-dashboard'],
    'devices'   => ['Geräte', 'server'],
    'creds'     => ['Zugänge', 'key-round'],
    'products'  => ['Produkte', 'package'],
    'notes'     => ['Notizen', 'notebook-pen'],
    'documents' => ['Dokumente', 'file-text'],
];
if ($u && \NetDoc\can_manage_users($u)) {
    $nav['users'] = ['Benutzer', 'users'];
}
function nav_active(string $key, string $active): bool {
    $group = explode('.', $active)[0];
    $map = ['device' => 'devices', 'cred' => 'creds', 'creds' => 'creds', 'product' => 'products',
            'products' => 'products', 'note' => 'notes', 'notes' => 'notes',
            'document' => 'documents', 'documents' => 'documents', 'user' => 'users', 'users' => 'users'];
    return ($map[$group] ?? $active) === $key;
}
$flashes = take_flashes();
?>
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="theme-color" content="#131714">
<title><?= e($title) ?> · NetDoc</title>
<link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="<?= $u ? 'app-page' : 'auth-page' ?>">
<?= ui('notifications', ['items' => $flashes]) ?>
<?php if ($u): ?>
<div class="app-shell">
    <aside class="sidebar">
        <a class="brand" href="<?= url('home') ?>" aria-label="NetDoc Übersicht">Net<span>Doc</span></a>
        <nav class="primary-nav" aria-label="Hauptnavigation">
            <?php foreach ($nav as $key => [$label, $icon]): ?>
                <a href="<?= url($key) ?>" class="primary-nav__item<?= nav_active($key, $active) ? ' is-active' : '' ?>"<?= nav_active($key, $active) ? ' aria-current="page"' : '' ?>>
                    <?= ui('icon', ['name' => $icon]) ?><span><?= e($label) ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar__account">
            <span class="avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr($u['username'], 0, 1))) ?></span>
            <div><strong><?= e($u['username']) ?></strong><span><?= e(\NetDoc\role_label($u['role'] ?? 'user')) ?></span></div>
        </div>
    </aside>
    <div class="workspace">
        <header class="topbar">
            <a class="brand brand--mobile" href="<?= url('home') ?>">Net<span>Doc</span></a>
            <form class="global-search" method="get" action="index.php" role="search">
                <input type="hidden" name="r" value="search">
                <label>
                    <span class="sr-only">NetDoc durchsuchen</span>
                    <?= ui('icon', ['name' => 'search']) ?>
                    <input type="search" name="q" placeholder="Geräte, Zugänge und Notizen durchsuchen" value="<?= e(param('q')) ?>">
                </label>
            </form>
            <?= ui('button', ['label' => 'Abmelden', 'icon' => 'log-out', 'href' => url('logout'), 'variant' => 'quiet', 'size' => 'small', 'class' => 'logout-button']) ?>
            <details class="mobile-nav">
                <summary><?= ui('icon', ['name' => 'menu']) ?><span>Bereiche</span></summary>
                <nav class="mobile-nav__panel" aria-label="Mobile Navigation">
                    <?php foreach ($nav as $key => [$label, $icon]): ?>
                        <a href="<?= url($key) ?>" class="mobile-nav__item<?= nav_active($key, $active) ? ' is-active' : '' ?>"<?= nav_active($key, $active) ? ' aria-current="page"' : '' ?>>
                            <?= ui('icon', ['name' => $icon]) ?><span><?= e($label) ?></span>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </details>
        </header>
        <main class="content"><?= $content ?></main>
    </div>
</div>
<?php else: ?>
    <main class="auth-shell"><div class="auth-shell__content"><?= $content ?></div></main>
<?php endif; ?>
<script src="assets/app.js"></script>
</body>
</html>
