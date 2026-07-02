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

// --- 2) Login / Logout ------------------------------------------------------
if ($r === 'login') {
    if ($isPost) {
        $auth->checkCsrf();
        $res = $auth->attempt(param('username'), param('password'));
        if ($res['ok']) {
            audit($store, $auth->user(), 'login');
            redirect('home');
        }
        audit($store, ['id' => null, 'username' => param('username')], 'login_failed');
        flash('error', $res['error'] ?? 'Login fehlgeschlagen.');
        redirect('login');
    }
    render('login', 'Anmelden', ['csrf' => $csrf]);
    exit;
}

if ($r === 'logout') {
    audit($store, $auth->user(), 'logout');
    $auth->logout();
    redirect('login');
}

// --- 3) Ab hier: Login-Pflicht ---------------------------------------------
if (!$auth->check()) {
    flash('error', 'Bitte zuerst anmelden.');
    redirect('login');
}

// CSRF-Prüfung für ALLE schreibenden Requests zentral.
if ($isPost) {
    $auth->checkCsrf();
}

$user = $auth->user();

// --- 4) Routing -------------------------------------------------------------
switch ($r) {
    case 'home':          route_home($store); break;

    case 'devices':       route_device_list($store); break;
    case 'device.edit':   route_device_edit($store); break;
    case 'device.save':   route_device_save($store, $user); break;
    case 'device.view':   route_device_view($store); break;
    case 'device.delete': route_device_delete($store, $user); break;

    case 'creds':         route_cred_list($store); break;
    case 'cred.edit':     route_cred_edit($store); break;
    case 'cred.save':     route_cred_save($store, $crypto, $user); break;
    case 'cred.reveal':   route_cred_reveal($store, $crypto, $user); break;
    case 'cred.delete':   route_cred_delete($store, $user); break;

    case 'products':      route_product_list($store); break;
    case 'product.edit':  route_product_edit($store); break;
    case 'product.save':  route_product_save($store, $crypto, $user); break;
    case 'product.delete':route_product_delete($store, $user); break;

    case 'notes':         route_note_list($store); break;
    case 'note.edit':     route_note_edit($store); break;
    case 'note.save':     route_note_save($store, $user); break;
    case 'note.delete':   route_note_delete($store, $user); break;

    case 'search':        route_search($store); break;

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

// ============================================================================
//  Setup
// ============================================================================

function handle_setup(Auth $auth): void
{
    $username = param('username');
    $pass     = param('password');
    $pass2    = param('password2');
    $errors   = [];

    if (strlen($username) < 3) $errors[] = 'Benutzername muss mind. 3 Zeichen haben.';
    if (strlen($pass) < 10)    $errors[] = 'Passwort muss mind. 10 Zeichen haben.';
    if ($pass !== $pass2)      $errors[] = 'Passwörter stimmen nicht überein.';

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

    $auth->createUser($username, $pass);
    flash('success', 'Einrichtung abgeschlossen. Bitte anmelden.');
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

function route_home(Store $store): void
{
    $counts = [
        'devices'  => $store->count('devices'),
        'creds'    => $store->count('credentials'),
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

function route_device_view(Store $store): void
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

    $creds    = arr_sort($byDevice($store->all('credentials')), 'title');
    $notes    = arr_sort($byDevice($store->all('notes')), 'updated_at', true);
    $products = arr_sort($byDevice($store->all('products')), 'name');
    render('devices/view', $dev['name'], compact('dev', 'creds', 'notes', 'products'));
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

function route_cred_list(Store $store): void
{
    $q     = param('q');
    $map   = device_map($store);
    $rows  = arr_search($store->all('credentials'), ['title', 'username', 'url'], $q);
    foreach ($rows as &$c) {
        $c['device_name'] = $map[(int) ($c['device_id'] ?? 0)] ?? null;
    }
    unset($c);
    $rows = arr_sort($rows, 'title');
    render('credentials/list', 'Zugänge & Verbindungen', ['rows' => $rows, 'q' => $q]);
}

function route_cred_edit(Store $store): void
{
    $id   = (int) param('id', '0');
    $cred = $id ? $store->find('credentials', $id) : null;
    render('credentials/edit', $id ? 'Zugang bearbeiten' : 'Neuer Zugang', [
        'cred' => $cred, 'devices' => device_options($store),
        'categories' => CRED_CATEGORIES, 'csrf' => $GLOBALS['csrf'],
        'preselectDevice' => (int) param('device_id', '0'),
    ]);
}

function route_cred_save(Store $store, ?Crypto $crypto, array $user): void
{
    $id    = (int) param('id', '0');
    $title = param('title');
    if ($title === '') {
        flash('error', 'Titel ist Pflicht.');
        redirect('cred.edit', $id ? ['id' => $id] : []);
    }

    // Passwort nur neu verschlüsseln, wenn im Formular ein neues eingegeben wurde.
    $secretInput = param('secret');
    if ($id) {
        $existing  = $store->find('credentials', $id);
        $secretEnc = $existing['secret_enc'] ?? null;
        if ($secretInput !== '') {
            $secretEnc = $crypto->encrypt($secretInput);
        }
    } else {
        $secretEnc = $secretInput !== '' ? $crypto->encrypt($secretInput) : null;
    }

    $row = [
        'device_id'  => (int) param('device_id', '0') ?: null,
        'title'      => $title,
        'category'   => in_array(param('category'), CRED_CATEGORIES, true) ? param('category') : 'other',
        'username'   => param_null('username'),
        'secret_enc' => $secretEnc,
        'url'        => param_null('url'),
        'port'       => param_null('port'),
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
    redirect('creds');
}

/** Passwort-Klartext per XHR liefern – wird geloggt, damit Zugriffe nachvollziehbar sind. */
function route_cred_reveal(Store $store, ?Crypto $crypto, array $user): void
{
    header('Content-Type: application/json');
    $id   = (int) param('id', '0');
    $cred = $store->find('credentials', $id);
    if (!$cred) {
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
    redirect('products');
}

function route_product_delete(Store $store, array $user): void
{
    $id = (int) param('id', '0');
    $store->delete('products', $id);
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

function route_search(Store $store): void
{
    $q = param('q');
    $results = ['devices' => [], 'creds' => [], 'products' => [], 'notes' => []];
    if ($q !== '') {
        $map = device_map($store);

        $results['devices'] = array_slice(
            arr_sort(arr_search($store->all('devices'), ['name', 'ip', 'hostname', 'notes'], $q), 'name'), 0, 25);

        $creds = arr_search($store->all('credentials'), ['title', 'username', 'url'], $q);
        foreach ($creds as &$c) { $c['device_name'] = $map[(int) ($c['device_id'] ?? 0)] ?? null; }
        unset($c);
        $results['creds'] = array_slice(arr_sort($creds, 'title'), 0, 25);

        $results['products'] = array_slice(
            arr_sort(arr_search($store->all('products'), ['name', 'vendor'], $q), 'name'), 0, 25);

        $results['notes'] = array_slice(
            arr_sort(arr_search($store->all('notes'), ['title', 'body'], $q), 'updated_at', true), 0, 25);
    }
    render('search', 'Suche', ['q' => $q, 'results' => $results]);
}
