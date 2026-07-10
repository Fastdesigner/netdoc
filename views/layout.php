<?php
/** @var string $content @var string $title */
$auth = $GLOBALS['auth'];
$u = $auth->user();
$active = param('r', 'home');
$nav = [
    'home'      => ['Übersicht', '▚'],
    'devices'   => ['Geräte', '🖥'],
    'creds'     => ['Zugänge', '🔑'],
    'products'  => ['Produkte', '🧾'],
    'notes'     => ['Notizen', '📝'],
    'documents' => ['Dokumente', '📎'],
];
if ($active === 'login' && param('diagnose') === '1') {
    var_dump('[NETDOC_LOGIN_500] Layout user type: ' . get_debug_type($u) . '; management navigation: ' . ($u ? 'evaluate role' : 'skip'));
}
if ($u && \NetDoc\can_manage_users($u)) {
    $nav['users'] = ['Benutzer', '👥'];
}
function nav_active(string $key, string $active): string {
    // Unterrouten wie device.view der Hauptgruppe zuordnen.
    $group = explode('.', $active)[0];
    $map = ['device' => 'devices', 'cred' => 'creds', 'creds' => 'creds', 'product' => 'products',
            'products' => 'products', 'note' => 'notes', 'notes' => 'notes',
            'document' => 'documents', 'documents' => 'documents', 'user' => 'users', 'users' => 'users'];
    $current = $map[$group] ?? $active;
    return $current === $key ? ' class="active"' : '';
}
?>
<!doctype html>
<html lang="de">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> · NetDoc</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php if ($u): ?>
<header class="topbar">
    <a class="brand" href="<?= url('home') ?>">Net<span>Doc</span></a>
    <form class="globalsearch" method="get" action="index.php">
        <input type="hidden" name="r" value="search">
        <input type="search" name="q" placeholder="Alles durchsuchen…" value="<?= e(param('q')) ?>">
    </form>
    <div class="userbox">
        <span><?= e($u['username']) ?></span>
        <a href="<?= url('logout') ?>">Abmelden</a>
    </div>
</header>
<div class="shell">
    <nav class="sidebar">
        <?php foreach ($nav as $key => [$label, $icon]): ?>
            <a href="<?= url($key) ?>"<?= nav_active($key, $active) ?>>
                <span class="ico"><?= $icon ?></span><?= e($label) ?>
            </a>
        <?php endforeach; ?>
    </nav>
    <main class="content">
        <?php foreach (take_flashes() as $f): ?>
            <div class="flash <?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
        <?php endforeach; ?>
        <?= $content ?>
    </main>
</div>
<script src="assets/app.js"></script>
<?php else: ?>
    <main class="authwrap">
        <?php foreach (take_flashes() as $f): ?>
            <div class="flash <?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
        <?php endforeach; ?>
        <?= $content ?>
    </main>
<?php endif; ?>
</body>
</html>
