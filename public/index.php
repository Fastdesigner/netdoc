<?php
declare(strict_types=1);

/**
 * NetDoc – Front-Controller.
 * Query-basiertes Routing (index.php?r=...) – funktioniert ohne mod_rewrite auf jedem Host.
 */

namespace NetDoc;

require dirname(__DIR__) . '/src/bootstrap.php';

/** @var Store $store */
/** @var Auth $auth */
/** @var Crypto|null $crypto */
/** @var bool $isInstalled */

// Erlaubte Werte (top-level const werden nicht hoisted -> vor dem Routing deklarieren).
const DEVICE_TYPES     = ['server', 'switch', 'router', 'firewall', 'accesspoint', 'nas', 'printer', 'client', 'vm', 'other'];
const CRED_CATEGORIES  = ['login', 'ssh', 'rdp', 'vpn', 'web', 'database', 'wifi', 'api', 'other'];
const ROLES            = ['systemadmin', 'admin', 'user'];

// Datei-Upload: Limit und blockierte (ausführbare) Endungen.
const MAX_UPLOAD_BYTES = 20 * 1024 * 1024; // 20 MB
const BLOCKED_UPLOAD_EXT = ['php', 'php3', 'php4', 'php5', 'php7', 'phtml', 'phar', 'pht',
    'cgi', 'pl', 'py', 'sh', 'exe', 'bat', 'cmd', 'com', 'htaccess', 'js', 'mjs'];

$r        = param('r', 'home');
$isPost   = $_SERVER['REQUEST_METHOD'] === 'POST';
$csrf     = $auth->csrfToken();

// --- 1) Installation erzwingen, solange nicht eingerichtet ------------------
if (!$isInstalled || $auth->isSetupNeeded()) {
    if ($r === 'setup' && $isPost) {
        $auth->checkCsrf();
        handle_setup($auth);
    }
    render_setup($auth, $isInstalled);
    exit;
}

// --- 2) Passwortlose Anmeldung (Code + Magic-Link) -------------------------

// Magic-Link aus der E-Mail: Token prüfen -> direkt einloggen.
if ($r === 'login.magic') {
    if ($auth->verifyMagic(param('token'))) {
        audit($store, $auth->user(), 'login', 'magic');
        flash('success', 'Willkommen zurück!');
        redirect('home');
    }
    flash('error', 'Link ungültig oder abgelaufen. Bitte neu anmelden.');
    redirect('login');
}

if ($r === 'login') {
    // "Andere Adresse" / Schritt zurücksetzen.
    if (param('reset') === '1') {
        unset($_SESSION['login_challenge'], $_SESSION['login_email'], $_SESSION['login_sent_at']);
        redirect('login');
    }

    if ($isPost) {
        $auth->checkCsrf();
        $email = param('email');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Bitte eine gültige E-Mail-Adresse eingeben.');
            redirect('login');
        }
        // Resend-Schutz: höchstens alle 30 Sekunden ein neuer Code.
        $lastSent = (int) ($_SESSION['login_sent_at'] ?? 0);
        if ($lastSent && now() - $lastSent < 30 && !empty($_SESSION['login_challenge'])) {
            flash('error', 'Bitte kurz warten, bevor du einen neuen Code anforderst.');
            redirect('login');
        }

        if ($req = $auth->requestLogin($email)) {
            $magic = absolute_url('login.magic', ['token' => $req['token']]);
            $mailer->sendLoginCode($req['user']['email'], $req['user']['username'], $req['code'], $magic);
            $_SESSION['login_challenge'] = $req['challenge_id'];
            audit($store, ['id' => $req['user']['id'], 'username' => $req['user']['username']], 'login_code_sent');
        }
        // Immer identische Reaktion – keine Rückschlüsse auf existierende Konten.
        $_SESSION['login_email']   = $email;
        $_SESSION['login_sent_at'] = now();
        flash('success', 'Falls die Adresse hinterlegt ist, haben wir dir einen Code geschickt.');
        redirect('login');
    }

    $pending = !empty($_SESSION['login_challenge']) || !empty($_SESSION['login_email']);
    render('login', 'Anmelden', [
        'csrf'  => $csrf,
        'step'  => $pending ? 'code' : 'email',
        'email' => $_SESSION['login_email'] ?? '',
    ]);
    exit;
}

// Code-Eingabe prüfen.
if ($r === 'login.verify') {
    if ($isPost) {
        $auth->checkCsrf();
        $res = $auth->verifyCode((int) ($_SESSION['login_challenge'] ?? 0), param('code'));
        if ($res['ok']) {
            audit($store, $auth->user(), 'login', 'code');
            flash('success', 'Willkommen zurück!');
            redirect('home');
        }
        flash('error', $res['error'] ?? 'Code ungültig.');
    }
    redirect('login');
}

if ($r === 'logout') {
    audit($store, $auth->user(), 'logout');
    $auth->logout();
    redirect('login');
}

// --- 3) Ab hier: Login-Pflicht ---------------------------------------------
if (!$auth->check()) {
    redirect('login');
}

// CSRF-Prüfung für ALLE schreibenden Requests zentral.
if ($isPost) {
    $auth->checkCsrf();
}

$user = $auth->user();

// --- 4) Routing -------------------------------------------------------------
switch ($r) {
    case 'home':          route_home($store, $user); break;

    case 'devices':       route_device_list($store); break;
    case 'device.edit':   route_device_edit($store); break;
    case 'device.save':   route_device_save($store, $user); break;
    case 'device.view':   route_device_view($store, $user); break;
    case 'device.delete': route_device_delete($store, $user); break;

    case 'creds':         route_cred_list($store, $user); break;
    case 'cred.edit':     route_cred_edit($store, $user); break;
    case 'cred.save':     route_cred_save($store, $crypto, $user); break;
    case 'cred.reveal':   route_cred_reveal($store, $crypto, $user); break;
    case 'cred.delete':   route_cred_delete($store, $user); break;

    case 'products':      route_product_list($store); break;
    case 'product.edit':  route_product_edit($store); break;
    case 'product.view':  route_product_view($store, $user); break;
    case 'product.save':  route_product_save($store, $crypto, $user); break;
    case 'product.delete':route_product_delete($store, $user); break;

    case 'notes':         route_note_list($store); break;
    case 'note.edit':     route_note_edit($store); break;
    case 'note.save':     route_note_save($store, $user); break;
    case 'note.delete':   route_note_delete($store, $user); break;

    case 'documents':        route_document_list($store); break;
    case 'document.edit':    route_document_edit($store); break;
    case 'document.save':    route_document_save($store, $user); break;
    case 'document.download':route_document_download($store, $user); break;
    case 'document.delete':  route_document_delete($store, $user); break;

    case 'users':         require_admin($user); route_user_list($store); break;
    case 'user.edit':     require_admin($user); route_user_edit($store); break;
    case 'user.save':     require_admin($user); route_user_save($store, $user); break;
    case 'user.delete':   require_admin($user); route_user_delete($store, $user); break;

    case 'search':        route_search($store, $user); break;

    default:
        http_response_code(404);
        render('error', 'Nicht gefunden', ['message' => 'Seite nicht gefunden.']);
}

// ============================================================================
//  Helfer
// ============================================================================

/** Map [Geräte-ID => Name] zum Anreichern von Verknüpfungen. */
function device_map(Store $store): array
{
    $map = [];
    foreach ($store->all('devices') as $d) {
        $map[(int) $d['id']] = $d['name'];
    }
    return $map;
}

/** Geräteliste (id/name) für Auswahlfelder, nach Name sortiert. */
function device_options(Store $store): array
{
    return arr_sort($store->all('devices'), 'name');
}

function product_map(Store $store): array
{
    $map = [];
    foreach ($store->all('products') as $p) {
        $map[(int) $p['id']] = $p['name'];
    }
    return $map;
}

function product_options(Store $store): array
{
    return arr_sort($store->all('products'), 'name');
}

function user_map(Store $store): array
{
    $map = [];
    foreach ($store->all('users') as $u) {
        $map[(int) $u['id']] = $u['username'];
    }
    return $map;
}

function role_label(string $role): string
{
    return match ($role) {
        'systemadmin' => 'Systemadmin',
        'admin' => 'Admin',
        default => 'Benutzer',
    };
}

function can_manage_users(array $user): bool
{
    return in_array($user['role'] ?? '', ['admin', 'systemadmin'], true);
}

function can_view_credential(array $cred, array $user): bool
{
    if (($cred['visibility'] ?? 'team') !== 'private') {
        return true;
    }
    if (($user['role'] ?? '') === 'systemadmin') {
        return true;
    }
    return (int) ($cred['owner_user_id'] ?? 0) === (int) $user['id'];
}

function credential_rows(Store $store, array $user): array
{
    return array_values(array_filter($store->all('credentials'),
        static fn(array $c): bool => can_view_credential($c, $user)));
}

function require_credential(Store $store, int $id, array $user): array
{
    $cred = $id ? $store->find('credentials', $id) : null;
    if (!$cred || !can_view_credential($cred, $user)) {
        flash('error', 'Zugang nicht gefunden oder nicht freigegeben.');
        redirect('creds');
    }
    return $cred;
}

function enrich_credentials(Store $store, array $rows): array
{
    $devices = device_map($store);
    $products = product_map($store);
    $users = user_map($store);
    foreach ($rows as &$c) {
        $c['device_name'] = $devices[(int) ($c['device_id'] ?? 0)] ?? null;
        $c['product_name'] = $products[(int) ($c['product_id'] ?? 0)] ?? null;
        $c['owner_name'] = $users[(int) ($c['owner_user_id'] ?? 0)] ?? null;
    }
    unset($c);
    return $rows;
}

/** Admins/Systemadmins zulassen – sonst zurück zur Übersicht. */
function require_admin(array $user): void
{
    if (!can_manage_users($user)) {
        flash('error', 'Dafür fehlen dir die Rechte.');
        redirect('home');
    }
}

function management_user_count(Store $store): int
{
    return count(array_filter($store->all('users'), static fn($u) => can_manage_users($u)));
}

/** Upload-Verzeichnis (außerhalb des Webroots, unter data/). */
function uploads_dir(): string
{
    $dir = DATA . '/uploads';
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    return $dir;
}

// ============================================================================
//  Setup
// ============================================================================

function handle_setup(Auth $auth): void
{
    $username = param('username');
    $email    = param('email');
    $errors   = [];

    if (strlen($username) < 3)                              $errors[] = 'Benutzername muss mind. 3 Zeichen haben.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))         $errors[] = 'Bitte eine gültige E-Mail-Adresse eingeben.';

    if ($errors) {
        foreach ($errors as $e) flash('error', $e);
        redirect('setup');
    }

    // Config schreiben. Vorhandenen Key NIEMALS überschreiben (sonst wären bereits
    // verschlüsselte Daten verloren) – nur beim allerersten Setup neu erzeugen.
    $configFile = CONFIG . '/config.php';
    $sample = is_file($configFile) ? (require $configFile) : (require CONFIG . '/config.sample.php');
    if (empty($sample['app_key'])) {
        $sample['app_key'] = Crypto::generateKey();
    }
    $php = "<?php\n// Automatisch generiert beim Setup – NICHT versionieren.\nreturn " . var_export($sample, true) . ";\n";

    if (@file_put_contents($configFile, $php, LOCK_EX) === false) {
        flash('error', 'Konnte config/config.php nicht schreiben. Schreibrechte prüfen.');
        redirect('setup');
    }
    @chmod($configFile, 0600);

    $auth->createUser($username, $email);
    flash('success', 'Einrichtung abgeschlossen. Melde dich jetzt mit deiner E-Mail an.');
    redirect('login');
}

function render_setup(Auth $auth, bool $isInstalled): void
{
    render('setup', 'Einrichtung', [
        'csrf'         => $auth->csrfToken(),
        'configExists' => $isInstalled,
    ]);
}

// ============================================================================
//  Dashboard
// ============================================================================

function route_home(Store $store, array $user): void
{
    $counts = [
        'devices'  => $store->count('devices'),
        'creds'    => count(credential_rows($store, $user)),
        'products' => $store->count('products'),
        'notes'    => $store->count('notes'),
    ];
    $recent = array_slice(arr_sort($store->all('devices'), 'updated_at', true), 0, 8);

    // Bald ablaufende Produkte/Lizenzen (mit gesetztem Ablaufdatum, aufsteigend).
    $withExpiry = array_filter($store->all('products'), static fn($p) => !empty($p['expiry_date']));
    $expiring   = array_slice(arr_sort($withExpiry, 'expiry_date'), 0, 8);

    render('dashboard', 'Übersicht', compact('counts', 'recent', 'expiring'));
}

// ============================================================================
//  Geräte / Server
// ============================================================================

function route_device_list(Store $store): void
{
    $q    = param('q');
    $rows = arr_search($store->all('devices'), ['name', 'ip', 'hostname', 'location'], $q);
    $rows = arr_sort($rows, 'name');
    render('devices/list', 'Geräte & Server', ['rows' => $rows, 'q' => $q]);
}

function route_device_edit(Store $store): void
{
    $id  = (int) param('id', '0');
    $dev = $id ? $store->find('devices', $id) : null;
    render('devices/edit', $id ? 'Gerät bearbeiten' : 'Neues Gerät',
        ['dev' => $dev, 'types' => DEVICE_TYPES, 'csrf' => $GLOBALS['csrf']]);
}

function route_device_save(Store $store, array $user): void
{
    $id   = (int) param('id', '0');
    $name = param('name');
    if ($name === '') {
        flash('error', 'Name ist Pflicht.');
        redirect('device.edit', $id ? ['id' => $id] : []);
    }
    $row = [
        'name'     => $name,
        'type'     => in_array(param('type'), DEVICE_TYPES, true) ? param('type') : 'other',
        'hostname' => param_null('hostname'),
        'ip'       => param_null('ip'),
        'location' => param_null('location'),
        'vendor'   => param_null('vendor'),
        'model'    => param_null('model'),
        'os'       => param_null('os'),
        'status'   => param('status') ?: 'active',
        'notes'    => param_null('notes'),
    ];

    if ($id) {
        $store->update('devices', $id, $row + ['updated_at' => now()]);
        audit($store, $user, 'update', 'device', $id);
        flash('success', 'Gerät aktualisiert.');
    } else {
        $id = $store->insert('devices', $row + ['created_at' => now(), 'updated_at' => now()]);
        audit($store, $user, 'create', 'device', $id);
        flash('success', 'Gerät angelegt.');
    }
    redirect('device.view', ['id' => $id]);
}

function route_device_view(Store $store, array $user): void
{
    $id  = (int) param('id', '0');
    $dev = $store->find('devices', $id);
    if (!$dev) {
        http_response_code(404);
        render('error', 'Nicht gefunden', ['message' => 'Gerät existiert nicht.']);
        return;
    }
    $byDevice = static fn(array $rows) => array_values(array_filter($rows,
        static fn($x) => (int) ($x['device_id'] ?? 0) === $id));

    $creds     = enrich_credentials($store, arr_sort($byDevice(credential_rows($store, $user)), 'title'));
    $notes     = arr_sort($byDevice($store->all('notes')), 'updated_at', true);
    $products  = arr_sort($byDevice($store->all('products')), 'name');
    $documents = arr_sort($byDevice($store->all('documents')), 'created_at', true);
    render('devices/view', $dev['name'], compact('dev', 'creds', 'notes', 'products', 'documents'));
}

function route_device_delete(Store $store, array $user): void
{
    $id = (int) param('id', '0');
    $store->delete('devices', $id);
    // Verknüpfungen lösen (Ersatz für ON DELETE SET NULL).
    foreach (['credentials', 'notes', 'products'] as $coll) {
        $store->nullifyReferences($coll, 'device_id', $id);
    }
    audit($store, $user, 'delete', 'device', $id);
    flash('success', 'Gerät gelöscht.');
    redirect('devices');
}

// ============================================================================
//  Zugänge / Verbindungsdaten (verschlüsselt)
// ============================================================================

function route_cred_list(Store $store, array $user): void
{
    $q     = param('q');
    $rows  = enrich_credentials($store, arr_search(credential_rows($store, $user), ['title', 'username', 'url'], $q));
    $rows = arr_sort($rows, 'title');
    render('credentials/list', 'Zugänge & Verbindungen', ['rows' => $rows, 'q' => $q]);
}

function route_cred_edit(Store $store, array $user): void
{
    $id   = (int) param('id', '0');
    $cred = $id ? require_credential($store, $id, $user) : null;
    render('credentials/edit', $id ? 'Zugang bearbeiten' : 'Neuer Zugang', [
        'cred' => $cred, 'devices' => device_options($store), 'products' => product_options($store),
        'categories' => CRED_CATEGORIES, 'csrf' => $GLOBALS['csrf'],
        'preselectDevice' => (int) param('device_id', '0'),
        'preselectProduct' => (int) param('product_id', '0'),
    ]);
}

function route_cred_save(Store $store, ?Crypto $crypto, array $user): void
{
    $id    = (int) param('id', '0');
    $title = param('title');
    $existing = null;
    if ($title === '') {
        flash('error', 'Titel ist Pflicht.');
        $params = $id ? ['id' => $id] : [];
        if (!$id && (int) param('device_id', '0')) {
            $params['device_id'] = (int) param('device_id', '0');
        }
        if (!$id && (int) param('product_id', '0')) {
            $params['product_id'] = (int) param('product_id', '0');
        }
        if (!$id && param('back') === 'product') {
            $params['back'] = 'product';
        }
        redirect('cred.edit', $params);
    }

    // Passwort nur neu verschlüsseln, wenn im Formular ein neues eingegeben wurde.
    $secretInput = param('secret');
    if ($id) {
        $existing  = require_credential($store, $id, $user);
        $secretEnc = $existing['secret_enc'] ?? null;
        if ($secretInput !== '') {
            $secretEnc = $crypto->encrypt($secretInput);
        }
    } else {
        $secretEnc = $secretInput !== '' ? $crypto->encrypt($secretInput) : null;
    }

    $visibility = param('visibility') === 'private' ? 'private' : 'team';
    $ownerUserId = $visibility === 'private'
        ? (int) ($existing['owner_user_id'] ?? 0) ?: (int) $user['id']
        : null;
    $deviceId = (int) param('device_id', '0') ?: null;
    $productId = (int) param('product_id', '0') ?: null;
    $row = [
        'device_id'  => $deviceId,
        'product_id' => $productId,
        'title'      => $title,
        'category'   => in_array(param('category'), CRED_CATEGORIES, true) ? param('category') : 'other',
        'username'   => param_null('username'),
        'secret_enc' => $secretEnc,
        'url'        => param_null('url'),
        'port'       => param_null('port'),
        'visibility' => $visibility,
        'owner_user_id' => $ownerUserId,
        'notes'      => param_null('notes'),
    ];

    if ($id) {
        $store->update('credentials', $id, $row + ['updated_at' => now()]);
        audit($store, $user, 'update', 'credential', $id);
        flash('success', 'Zugang aktualisiert.');
    } else {
        $id = $store->insert('credentials', $row + ['created_at' => now(), 'updated_at' => now()]);
        audit($store, $user, 'create', 'credential', $id);
        flash('success', 'Zugang angelegt.');
    }
    if (param('back') === 'product' && $productId) {
        redirect('product.view', ['id' => $productId]);
    }
    redirect('creds');
}

/** Passwort-Klartext per XHR liefern – wird geloggt, damit Zugriffe nachvollziehbar sind. */
function route_cred_reveal(Store $store, ?Crypto $crypto, array $user): void
{
    header('Content-Type: application/json');
    $id   = (int) param('id', '0');
    $cred = $id ? $store->find('credentials', $id) : null;
    if (!$cred || !can_view_credential($cred, $user)) {
        http_response_code(404);
        echo json_encode(['error' => 'not found']);
        return;
    }
    audit($store, $user, 'reveal', 'credential', $id);
    echo json_encode(['secret' => $crypto->decrypt($cred['secret_enc'] ?? null) ?? '']);
}

function route_cred_delete(Store $store, array $user): void
{
    $id = (int) param('id', '0');
    require_credential($store, $id, $user);
    $store->delete('credentials', $id);
    audit($store, $user, 'delete', 'credential', $id);
    flash('success', 'Zugang gelöscht.');
    redirect('creds');
}

// ============================================================================
//  Produkte / Lizenzen
// ============================================================================

function route_product_list(Store $store): void
{
    $q    = param('q');
    $rows = arr_search($store->all('products'), ['name', 'vendor', 'category'], $q);
    $rows = arr_sort($rows, 'name');
    render('products/list', 'Produkte & Lizenzen', ['rows' => $rows, 'q' => $q]);
}

function route_product_edit(Store $store): void
{
    $id = (int) param('id', '0');
    $p  = $id ? $store->find('products', $id) : null;
    render('products/edit', $id ? 'Produkt bearbeiten' : 'Neues Produkt',
        ['p' => $p, 'devices' => device_options($store), 'csrf' => $GLOBALS['csrf']]);
}

function route_product_view(Store $store, array $user): void
{
    $id = (int) param('id', '0');
    $p  = $store->find('products', $id);
    if (!$p) {
        http_response_code(404);
        render('error', 'Nicht gefunden', ['message' => 'Produkt existiert nicht.']);
        return;
    }
    $devices = device_map($store);
    $p['device_name'] = $devices[(int) ($p['device_id'] ?? 0)] ?? null;
    $creds = enrich_credentials($store, arr_sort(array_values(array_filter(credential_rows($store, $user),
        static fn(array $c): bool => (int) ($c['product_id'] ?? 0) === $id)), 'title'));
    render('products/view', $p['name'], compact('p', 'creds'));
}

function route_product_save(Store $store, ?Crypto $crypto, array $user): void
{
    $id   = (int) param('id', '0');
    $name = param('name');
    if ($name === '') {
        flash('error', 'Name ist Pflicht.');
        redirect('product.edit', $id ? ['id' => $id] : []);
    }

    // Lizenzschlüssel ist ebenfalls ein Geheimnis -> verschlüsseln.
    $licInput = param('license');
    if ($id) {
        $existing = $store->find('products', $id);
        $licEnc   = $existing['license_enc'] ?? null;
        if ($licInput !== '') $licEnc = $crypto->encrypt($licInput);
    } else {
        $licEnc = $licInput !== '' ? $crypto->encrypt($licInput) : null;
    }

    $seats = param('seats');
    $row = [
        'name'          => $name,
        'vendor'        => param_null('vendor'),
        'category'      => param_null('category'),
        'license_enc'   => $licEnc,
        'seats'         => $seats === '' ? null : (int) $seats,
        'purchase_date' => param_null('purchase_date'),
        'expiry_date'   => param_null('expiry_date'),
        'cost'          => param_null('cost'),
        'supplier'      => param_null('supplier'),
        'device_id'     => (int) param('device_id', '0') ?: null,
        'notes'         => param_null('notes'),
    ];

    if ($id) {
        $store->update('products', $id, $row + ['updated_at' => now()]);
        audit($store, $user, 'update', 'product', $id);
        flash('success', 'Produkt aktualisiert.');
    } else {
        $id = $store->insert('products', $row + ['created_at' => now(), 'updated_at' => now()]);
        audit($store, $user, 'create', 'product', $id);
        flash('success', 'Produkt angelegt.');
    }
    redirect('product.view', ['id' => $id]);
}

function route_product_delete(Store $store, array $user): void
{
    $id = (int) param('id', '0');
    $store->delete('products', $id);
    $store->nullifyReferences('credentials', 'product_id', $id);
    audit($store, $user, 'delete', 'product', $id);
    flash('success', 'Produkt gelöscht.');
    redirect('products');
}

// ============================================================================
//  Notizen
// ============================================================================

function route_note_list(Store $store): void
{
    $map  = device_map($store);
    $rows = arr_sort($store->all('notes'), 'updated_at', true);
    foreach ($rows as &$n) {
        $n['device_name'] = $map[(int) ($n['device_id'] ?? 0)] ?? null;
    }
    unset($n);
    render('notes/list', 'Notizen', ['rows' => $rows]);
}

function route_note_edit(Store $store): void
{
    $id   = (int) param('id', '0');
    $note = $id ? $store->find('notes', $id) : null;
    render('notes/edit', $id ? 'Notiz bearbeiten' : 'Neue Notiz', [
        'note' => $note, 'devices' => device_options($store), 'csrf' => $GLOBALS['csrf'],
        'preselectDevice' => (int) param('device_id', '0'),
    ]);
}

function route_note_save(Store $store, array $user): void
{
    $id    = (int) param('id', '0');
    $title = param('title');
    if ($title === '') {
        flash('error', 'Titel ist Pflicht.');
        redirect('note.edit', $id ? ['id' => $id] : []);
    }
    $row = [
        'title'     => $title,
        'body'      => param_null('body'),
        'device_id' => (int) param('device_id', '0') ?: null,
    ];

    if ($id) {
        $store->update('notes', $id, $row + ['updated_at' => now()]);
        audit($store, $user, 'update', 'note', $id);
        flash('success', 'Notiz aktualisiert.');
    } else {
        $id = $store->insert('notes', $row + ['created_at' => now(), 'updated_at' => now()]);
        audit($store, $user, 'create', 'note', $id);
        flash('success', 'Notiz angelegt.');
    }
    redirect('notes');
}

function route_note_delete(Store $store, array $user): void
{
    $id = (int) param('id', '0');
    $store->delete('notes', $id);
    audit($store, $user, 'delete', 'note', $id);
    flash('success', 'Notiz gelöscht.');
    redirect('notes');
}

// ============================================================================
//  Globale Suche
// ============================================================================

function route_search(Store $store, array $user): void
{
    $q = param('q');
    $results = ['devices' => [], 'creds' => [], 'products' => [], 'notes' => []];
    if ($q !== '') {
        $results['devices'] = array_slice(
            arr_sort(arr_search($store->all('devices'), ['name', 'ip', 'hostname', 'notes'], $q), 'name'), 0, 25);

        $creds = enrich_credentials($store, arr_search(credential_rows($store, $user), ['title', 'username', 'url'], $q));
        $results['creds'] = array_slice(arr_sort($creds, 'title'), 0, 25);

        $results['products'] = array_slice(
            arr_sort(arr_search($store->all('products'), ['name', 'vendor'], $q), 'name'), 0, 25);

        $results['notes'] = array_slice(
            arr_sort(arr_search($store->all('notes'), ['title', 'body'], $q), 'updated_at', true), 0, 25);
    }
    render('search', 'Suche', ['q' => $q, 'results' => $results]);
}

// ============================================================================
//  Dokumente (Upload/Download, außerhalb des Webroots gespeichert)
// ============================================================================

function route_document_list(Store $store): void
{
    $q    = param('q');
    $map  = device_map($store);
    $rows = arr_search($store->all('documents'), ['title', 'filename'], $q);
    foreach ($rows as &$d) {
        $d['device_name'] = $map[(int) ($d['device_id'] ?? 0)] ?? null;
    }
    unset($d);
    $rows = arr_sort($rows, 'created_at', true);
    render('documents/list', 'Dokumente', ['rows' => $rows, 'q' => $q]);
}

function route_document_edit(Store $store): void
{
    $id  = (int) param('id', '0');
    $doc = $id ? $store->find('documents', $id) : null;
    render('documents/edit', $id ? 'Dokument bearbeiten' : 'Dokument hochladen', [
        'doc' => $doc, 'devices' => device_options($store), 'csrf' => $GLOBALS['csrf'],
        'preselectDevice' => (int) param('device_id', '0'),
        'maxBytes' => MAX_UPLOAD_BYTES,
    ]);
}

function route_document_save(Store $store, array $user): void
{
    $id       = (int) param('id', '0');
    $backTo   = $id ? ['id' => $id] : [];
    $deviceId = (int) param('device_id', '0') ?: null;
    $title    = param('title');

    $file     = $_FILES['file'] ?? null;
    $hasUpload = $file && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

    if (!$id && !$hasUpload) {
        flash('error', 'Bitte eine Datei auswählen.');
        redirect('document.edit', $backTo);
    }

    $stored = $mime = $origName = null;
    $size = 0;

    if ($hasUpload) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $msg = $file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE
                ? 'Datei zu groß (Server-Limit).'
                : 'Upload fehlgeschlagen (Fehlercode ' . (int) $file['error'] . ').';
            flash('error', $msg);
            redirect('document.edit', $backTo);
        }
        if ($file['size'] > MAX_UPLOAD_BYTES) {
            flash('error', 'Datei überschreitet ' . fmt_bytes(MAX_UPLOAD_BYTES) . '.');
            redirect('document.edit', $backTo);
        }
        $origName = basename((string) $file['name']);
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if (in_array($ext, BLOCKED_UPLOAD_EXT, true)) {
            flash('error', 'Dieser Dateityp ist aus Sicherheitsgründen nicht erlaubt.');
            redirect('document.edit', $backTo);
        }
        $stored = bin2hex(random_bytes(16));
        if (!move_uploaded_file($file['tmp_name'], uploads_dir() . '/' . $stored)) {
            flash('error', 'Datei konnte nicht gespeichert werden (Schreibrechte in data/uploads?).');
            redirect('document.edit', $backTo);
        }
        @chmod(uploads_dir() . '/' . $stored, 0600);
        $mime = function_exists('mime_content_type')
            ? (mime_content_type(uploads_dir() . '/' . $stored) ?: (string) $file['type'])
            : (string) $file['type'];
        $size = (int) $file['size'];
    }

    if ($id) {
        $doc = $store->find('documents', $id);
        if (!$doc) {
            flash('error', 'Dokument nicht gefunden.');
            redirect('documents');
        }
        $patch = [
            'title'     => $title !== '' ? $title : $doc['title'],
            'device_id' => $deviceId,
            'notes'     => param_null('notes'),
        ];
        if ($hasUpload) {
            @unlink(uploads_dir() . '/' . $doc['stored']); // alte Datei ersetzen
            $patch += ['stored' => $stored, 'filename' => $origName, 'mime' => $mime, 'size' => $size];
        }
        $store->update('documents', $id, $patch + ['updated_at' => now()]);
        audit($store, $user, 'update', 'document', $id);
        flash('success', 'Dokument aktualisiert.');
    } else {
        $id = $store->insert('documents', [
            'title'       => $title !== '' ? $title : $origName,
            'filename'    => $origName,
            'stored'      => $stored,
            'mime'        => $mime,
            'size'        => $size,
            'device_id'   => $deviceId,
            'notes'       => param_null('notes'),
            'uploaded_by' => $user['username'],
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);
        audit($store, $user, 'create', 'document', $id);
        flash('success', 'Dokument hochgeladen.');
    }
    redirect('documents');
}

function route_document_download(Store $store, array $user): void
{
    $id  = (int) param('id', '0');
    $doc = $store->find('documents', $id);
    $path = $doc ? uploads_dir() . '/' . $doc['stored'] : '';
    if (!$doc || !is_file($path)) {
        http_response_code(404);
        render('error', 'Nicht gefunden', ['message' => 'Datei nicht vorhanden.']);
        return;
    }
    audit($store, $user, 'download', 'document', $id);

    $name  = $doc['filename'] ?: ('dokument-' . $id);
    $ascii = preg_replace('/[^\x20-\x7E]/', '_', $name); // ASCII-Fallback

    header('Content-Type: ' . ($doc['mime'] ?: 'application/octet-stream'));
    header('Content-Length: ' . filesize($path));
    header("Content-Disposition: attachment; filename=\"{$ascii}\"; filename*=UTF-8''" . rawurlencode($name));
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
}

function route_document_delete(Store $store, array $user): void
{
    $id  = (int) param('id', '0');
    $doc = $store->find('documents', $id);
    if ($doc && !empty($doc['stored'])) {
        @unlink(uploads_dir() . '/' . $doc['stored']);
    }
    $store->delete('documents', $id);
    audit($store, $user, 'delete', 'document', $id);
    flash('success', 'Dokument gelöscht.');
    redirect('documents');
}

// ============================================================================
//  Benutzerverwaltung (nur Admin)
// ============================================================================

function route_user_list(Store $store): void
{
    $rows = arr_sort($store->all('users'), 'username');
    render('users/list', 'Benutzer', ['rows' => $rows]);
}

function route_user_edit(Store $store): void
{
    $id = (int) param('id', '0');
    $u  = $id ? $store->find('users', $id) : null;
    render('users/edit', $id ? 'Benutzer bearbeiten' : 'Neuer Benutzer',
        ['u' => $u, 'roles' => ROLES, 'csrf' => $GLOBALS['csrf']]);
}

function route_user_save(Store $store, array $current): void
{
    $id       = (int) param('id', '0');
    $username = param('username');
    $email    = strtolower(param('email'));
    $role     = in_array(param('role'), ROLES, true) ? param('role') : 'user';
    $backTo   = $id ? ['id' => $id] : [];

    $errors = [];
    if (strlen($username) < 3)                       $errors[] = 'Benutzername muss mind. 3 Zeichen haben.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))  $errors[] = 'Bitte eine gültige E-Mail-Adresse eingeben.';

    // E-Mail muss eindeutig sein (Login erfolgt darüber).
    $dupe = $store->findBy('users', 'email', $email);
    if ($dupe && (int) $dupe['id'] !== $id)          $errors[] = 'Diese E-Mail wird bereits verwendet.';

    // Letzten Admin/Systemadmin nicht herabstufen.
    if ($id && !can_manage_users(['role' => $role])) {
        $existing = $store->find('users', $id);
        if ($existing && can_manage_users($existing) && management_user_count($store) <= 1) {
            $errors[] = 'Der letzte Admin/Systemadmin kann nicht herabgestuft werden.';
        }
    }

    if ($errors) {
        foreach ($errors as $e) flash('error', $e);
        redirect('user.edit', $backTo);
    }

    if ($id) {
        $store->update('users', $id, ['username' => $username, 'email' => $email, 'role' => $role]);
        audit($store, $current, 'update', 'user', $id);
        flash('success', 'Benutzer aktualisiert.');
    } else {
        $id = $store->insert('users', [
            'username'   => $username,
            'email'      => $email,
            'role'       => $role,
            'last_login' => null,
            'created_at' => now(),
        ]);
        audit($store, $current, 'create', 'user', $id);
        flash('success', 'Benutzer angelegt – er kann sich jetzt per E-Mail anmelden.');
    }
    redirect('users');
}

function route_user_delete(Store $store, array $current): void
{
    $id = (int) param('id', '0');
    if ($id === (int) $current['id']) {
        flash('error', 'Du kannst dein eigenes Konto nicht löschen.');
        redirect('users');
    }
    $target = $store->find('users', $id);
    if ($target && can_manage_users($target) && management_user_count($store) <= 1) {
        flash('error', 'Der letzte Admin/Systemadmin kann nicht gelöscht werden.');
        redirect('users');
    }
    $store->delete('users', $id);
    audit($store, $current, 'delete', 'user', $id);
    flash('success', 'Benutzer gelöscht.');
    redirect('users');
}
