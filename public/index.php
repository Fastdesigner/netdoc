<?php
declare(strict_types=1);

/**
 * NetDoc – Front-Controller.
 * Query-basiertes Routing (index.php?r=...) – funktioniert ohne mod_rewrite auf jedem Host.
 */

namespace NetDoc;

require dirname(__DIR__) . '/src/bootstrap.php';

/** @var Database $db */
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
        handle_setup($db, $auth);
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
            audit($db, $auth->user(), 'login');
            redirect('home');
        }
        audit($db, ['id' => null, 'username' => param('username')], 'login_failed');
        flash('error', $res['error'] ?? 'Login fehlgeschlagen.');
        redirect('login');
    }
    render('login', 'Anmelden', ['csrf' => $csrf]);
    exit;
}

if ($r === 'logout') {
    audit($db, $auth->user(), 'logout');
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
    case 'home':          route_home($db); break;

    case 'devices':       route_device_list($db); break;
    case 'device.edit':   route_device_edit($db); break;
    case 'device.save':   route_device_save($db, $user); break;
    case 'device.view':   route_device_view($db, $crypto); break;
    case 'device.delete': route_device_delete($db, $user); break;

    case 'creds':         route_cred_list($db); break;
    case 'cred.edit':     route_cred_edit($db); break;
    case 'cred.save':     route_cred_save($db, $crypto, $user); break;
    case 'cred.reveal':   route_cred_reveal($db, $crypto, $user); break;
    case 'cred.delete':   route_cred_delete($db, $user); break;

    case 'products':      route_product_list($db); break;
    case 'product.edit':  route_product_edit($db); break;
    case 'product.save':  route_product_save($db, $crypto, $user); break;
    case 'product.delete':route_product_delete($db, $user); break;

    case 'notes':         route_note_list($db); break;
    case 'note.edit':     route_note_edit($db); break;
    case 'note.save':     route_note_save($db, $user); break;
    case 'note.delete':   route_note_delete($db, $user); break;

    case 'search':        route_search($db); break;

    default:
        http_response_code(404);
        render('error', 'Nicht gefunden', ['message' => 'Seite nicht gefunden.']);
}

// ============================================================================
//  Setup
// ============================================================================

function handle_setup(Database $db, Auth $auth): void
{
    $user = param('username');
    $pass = param('password');
    $pass2 = param('password2');
    $errors = [];

    if (strlen($user) < 3)          $errors[] = 'Benutzername muss mind. 3 Zeichen haben.';
    if (strlen($pass) < 10)         $errors[] = 'Passwort muss mind. 10 Zeichen haben.';
    if ($pass !== $pass2)           $errors[] = 'Passwörter stimmen nicht überein.';

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

    $auth->createUser($user, $pass);
    flash('success', 'Einrichtung abgeschlossen. Bitte anmelden.');
    redirect('login');
}

function render_setup(Auth $auth, bool $isInstalled): void
{
    render('setup', 'Einrichtung', [
        'csrf'        => $auth->csrfToken(),
        'configExists'=> $isInstalled,
    ]);
}

// ============================================================================
//  Dashboard
// ============================================================================

function route_home(Database $db): void
{
    $counts = [
        'devices'  => (int) $db->one('SELECT COUNT(*) c FROM devices')['c'],
        'creds'    => (int) $db->one('SELECT COUNT(*) c FROM credentials')['c'],
        'products' => (int) $db->one('SELECT COUNT(*) c FROM products')['c'],
        'notes'    => (int) $db->one('SELECT COUNT(*) c FROM notes')['c'],
    ];
    $recent = $db->all('SELECT * FROM devices ORDER BY updated_at DESC LIMIT 8');
    // Bald ablaufende Produkte/Lizenzen.
    $expiring = $db->all(
        "SELECT * FROM products WHERE expiry_date IS NOT NULL AND expiry_date != '' ORDER BY expiry_date ASC LIMIT 8"
    );
    render('dashboard', 'Übersicht', compact('counts', 'recent', 'expiring'));
}

// ============================================================================
//  Geräte / Server
// ============================================================================

function route_device_list(Database $db): void
{
    $q = param('q');
    if ($q !== '') {
        $like = '%' . $q . '%';
        $rows = $db->all(
            'SELECT * FROM devices WHERE name LIKE ? OR ip LIKE ? OR hostname LIKE ? OR location LIKE ? ORDER BY name',
            [$like, $like, $like, $like]
        );
    } else {
        $rows = $db->all('SELECT * FROM devices ORDER BY name');
    }
    render('devices/list', 'Geräte & Server', ['rows' => $rows, 'q' => $q]);
}

function route_device_edit(Database $db): void
{
    $id  = (int) param('id', '0');
    $dev = $id ? $db->one('SELECT * FROM devices WHERE id = ?', [$id]) : null;
    render('devices/edit', $id ? 'Gerät bearbeiten' : 'Neues Gerät',
        ['dev' => $dev, 'types' => DEVICE_TYPES, 'csrf' => $GLOBALS['csrf']]);
}

function route_device_save(Database $db, array $user): void
{
    $id   = (int) param('id', '0');
    $name = param('name');
    if ($name === '') {
        flash('error', 'Name ist Pflicht.');
        redirect('device.edit', $id ? ['id' => $id] : []);
    }
    $type = in_array(param('type'), DEVICE_TYPES, true) ? param('type') : 'other';
    $data = [
        $name, $type, param_null('hostname'), param_null('ip'), param_null('location'),
        param_null('vendor'), param_null('model'), param_null('os'),
        param('status') ?: 'active', param_null('notes'),
    ];

    if ($id) {
        $db->run('UPDATE devices SET name=?,type=?,hostname=?,ip=?,location=?,vendor=?,model=?,os=?,status=?,notes=?,updated_at=? WHERE id=?',
            [...$data, now(), $id]);
        audit($db, $user, 'update', 'device', $id);
        flash('success', 'Gerät aktualisiert.');
    } else {
        $db->run('INSERT INTO devices (name,type,hostname,ip,location,vendor,model,os,status,notes,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)',
            [...$data, now(), now()]);
        $id = $db->lastId();
        audit($db, $user, 'create', 'device', $id);
        flash('success', 'Gerät angelegt.');
    }
    redirect('device.view', ['id' => $id]);
}

function route_device_view(Database $db, ?Crypto $crypto): void
{
    $id  = (int) param('id', '0');
    $dev = $db->one('SELECT * FROM devices WHERE id = ?', [$id]);
    if (!$dev) {
        http_response_code(404);
        render('error', 'Nicht gefunden', ['message' => 'Gerät existiert nicht.']);
        return;
    }
    $creds    = $db->all('SELECT * FROM credentials WHERE device_id = ? ORDER BY title', [$id]);
    $notes    = $db->all('SELECT * FROM notes WHERE device_id = ? ORDER BY updated_at DESC', [$id]);
    $products = $db->all('SELECT * FROM products WHERE device_id = ? ORDER BY name', [$id]);
    render('devices/view', $dev['name'], compact('dev', 'creds', 'notes', 'products'));
}

function route_device_delete(Database $db, array $user): void
{
    $id = (int) param('id', '0');
    $db->run('DELETE FROM devices WHERE id = ?', [$id]);
    audit($db, $user, 'delete', 'device', $id);
    flash('success', 'Gerät gelöscht.');
    redirect('devices');
}

// ============================================================================
//  Zugänge / Verbindungsdaten (verschlüsselt)
// ============================================================================

function route_cred_list(Database $db): void
{
    $q = param('q');
    $sql = 'SELECT c.*, d.name AS device_name FROM credentials c LEFT JOIN devices d ON d.id = c.device_id';
    if ($q !== '') {
        $like = '%' . $q . '%';
        $rows = $db->all($sql . ' WHERE c.title LIKE ? OR c.username LIKE ? OR c.url LIKE ? ORDER BY c.title',
            [$like, $like, $like]);
    } else {
        $rows = $db->all($sql . ' ORDER BY c.title');
    }
    render('credentials/list', 'Zugänge & Verbindungen', ['rows' => $rows, 'q' => $q]);
}

function route_cred_edit(Database $db): void
{
    $id   = (int) param('id', '0');
    $cred = $id ? $db->one('SELECT * FROM credentials WHERE id = ?', [$id]) : null;
    $devices = $db->all('SELECT id, name FROM devices ORDER BY name');
    render('credentials/edit', $id ? 'Zugang bearbeiten' : 'Neuer Zugang', [
        'cred' => $cred, 'devices' => $devices,
        'categories' => CRED_CATEGORIES, 'csrf' => $GLOBALS['csrf'],
        // Vorbelegung Geräte-Zuordnung, wenn von Geräteansicht kommend.
        'preselectDevice' => (int) param('device_id', '0'),
    ]);
}

function route_cred_save(Database $db, ?Crypto $crypto, array $user): void
{
    $id    = (int) param('id', '0');
    $title = param('title');
    if ($title === '') {
        flash('error', 'Titel ist Pflicht.');
        redirect('cred.edit', $id ? ['id' => $id] : []);
    }
    $category = in_array(param('category'), CRED_CATEGORIES, true) ? param('category') : 'other';
    $deviceId = (int) param('device_id', '0') ?: null;

    // Passwort nur neu verschlüsseln, wenn im Formular ein neues eingegeben wurde.
    $secretInput = param('secret');
    if ($id) {
        $existing = $db->one('SELECT secret_enc FROM credentials WHERE id = ?', [$id]);
        $secretEnc = $existing['secret_enc'] ?? null;
        if ($secretInput !== '') {
            $secretEnc = $crypto->encrypt($secretInput);
        }
    } else {
        $secretEnc = $secretInput !== '' ? $crypto->encrypt($secretInput) : null;
    }

    $data = [
        $deviceId, $title, $category, param_null('username'), $secretEnc,
        param_null('url'), param_null('port'), param_null('notes'),
    ];

    if ($id) {
        $db->run('UPDATE credentials SET device_id=?,title=?,category=?,username=?,secret_enc=?,url=?,port=?,notes=?,updated_at=? WHERE id=?',
            [...$data, now(), $id]);
        audit($db, $user, 'update', 'credential', $id);
        flash('success', 'Zugang aktualisiert.');
    } else {
        $db->run('INSERT INTO credentials (device_id,title,category,username,secret_enc,url,port,notes,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?)',
            [...$data, now(), now()]);
        $id = $db->lastId();
        audit($db, $user, 'create', 'credential', $id);
        flash('success', 'Zugang angelegt.');
    }
    redirect('creds');
}

/** Passwort-Klartext per XHR liefern – wird geloggt, damit Zugriffe nachvollziehbar sind. */
function route_cred_reveal(Database $db, ?Crypto $crypto, array $user): void
{
    header('Content-Type: application/json');
    $id   = (int) param('id', '0');
    $cred = $db->one('SELECT secret_enc FROM credentials WHERE id = ?', [$id]);
    if (!$cred) {
        http_response_code(404);
        echo json_encode(['error' => 'not found']);
        return;
    }
    audit($db, $user, 'reveal', 'credential', $id);
    echo json_encode(['secret' => $crypto->decrypt($cred['secret_enc']) ?? '']);
}

function route_cred_delete(Database $db, array $user): void
{
    $id = (int) param('id', '0');
    $db->run('DELETE FROM credentials WHERE id = ?', [$id]);
    audit($db, $user, 'delete', 'credential', $id);
    flash('success', 'Zugang gelöscht.');
    redirect('creds');
}

// ============================================================================
//  Produkte / Lizenzen
// ============================================================================

function route_product_list(Database $db): void
{
    $q = param('q');
    if ($q !== '') {
        $like = '%' . $q . '%';
        $rows = $db->all('SELECT * FROM products WHERE name LIKE ? OR vendor LIKE ? OR category LIKE ? ORDER BY name',
            [$like, $like, $like]);
    } else {
        $rows = $db->all('SELECT * FROM products ORDER BY name');
    }
    render('products/list', 'Produkte & Lizenzen', ['rows' => $rows, 'q' => $q]);
}

function route_product_edit(Database $db): void
{
    $id  = (int) param('id', '0');
    $p   = $id ? $db->one('SELECT * FROM products WHERE id = ?', [$id]) : null;
    $devices = $db->all('SELECT id, name FROM devices ORDER BY name');
    render('products/edit', $id ? 'Produkt bearbeiten' : 'Neues Produkt',
        ['p' => $p, 'devices' => $devices, 'csrf' => $GLOBALS['csrf']]);
}

function route_product_save(Database $db, ?Crypto $crypto, array $user): void
{
    $id   = (int) param('id', '0');
    $name = param('name');
    if ($name === '') {
        flash('error', 'Name ist Pflicht.');
        redirect('product.edit', $id ? ['id' => $id] : []);
    }
    $deviceId = (int) param('device_id', '0') ?: null;

    // Lizenzschlüssel ist ebenfalls ein Geheimnis -> verschlüsseln.
    $licInput = param('license');
    if ($id) {
        $existing = $db->one('SELECT license_enc FROM products WHERE id = ?', [$id]);
        $licEnc = $existing['license_enc'] ?? null;
        if ($licInput !== '') $licEnc = $crypto->encrypt($licInput);
    } else {
        $licEnc = $licInput !== '' ? $crypto->encrypt($licInput) : null;
    }

    $seats = param('seats');
    $data = [
        $name, param_null('vendor'), param_null('category'), $licEnc,
        $seats === '' ? null : (int) $seats,
        param_null('purchase_date'), param_null('expiry_date'),
        param_null('cost'), param_null('supplier'), $deviceId, param_null('notes'),
    ];

    if ($id) {
        $db->run('UPDATE products SET name=?,vendor=?,category=?,license_enc=?,seats=?,purchase_date=?,expiry_date=?,cost=?,supplier=?,device_id=?,notes=?,updated_at=? WHERE id=?',
            [...$data, now(), $id]);
        audit($db, $user, 'update', 'product', $id);
        flash('success', 'Produkt aktualisiert.');
    } else {
        $db->run('INSERT INTO products (name,vendor,category,license_enc,seats,purchase_date,expiry_date,cost,supplier,device_id,notes,created_at,updated_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)',
            [...$data, now(), now()]);
        $id = $db->lastId();
        audit($db, $user, 'create', 'product', $id);
        flash('success', 'Produkt angelegt.');
    }
    redirect('products');
}

function route_product_delete(Database $db, array $user): void
{
    $id = (int) param('id', '0');
    $db->run('DELETE FROM products WHERE id = ?', [$id]);
    audit($db, $user, 'delete', 'product', $id);
    flash('success', 'Produkt gelöscht.');
    redirect('products');
}

// ============================================================================
//  Notizen
// ============================================================================

function route_note_list(Database $db): void
{
    $rows = $db->all('SELECT n.*, d.name AS device_name FROM notes n LEFT JOIN devices d ON d.id = n.device_id ORDER BY n.updated_at DESC');
    render('notes/list', 'Notizen', ['rows' => $rows]);
}

function route_note_edit(Database $db): void
{
    $id   = (int) param('id', '0');
    $note = $id ? $db->one('SELECT * FROM notes WHERE id = ?', [$id]) : null;
    $devices = $db->all('SELECT id, name FROM devices ORDER BY name');
    render('notes/edit', $id ? 'Notiz bearbeiten' : 'Neue Notiz', [
        'note' => $note, 'devices' => $devices, 'csrf' => $GLOBALS['csrf'],
        'preselectDevice' => (int) param('device_id', '0'),
    ]);
}

function route_note_save(Database $db, array $user): void
{
    $id    = (int) param('id', '0');
    $title = param('title');
    if ($title === '') {
        flash('error', 'Titel ist Pflicht.');
        redirect('note.edit', $id ? ['id' => $id] : []);
    }
    $deviceId = (int) param('device_id', '0') ?: null;
    $data = [$title, param_null('body'), $deviceId];

    if ($id) {
        $db->run('UPDATE notes SET title=?,body=?,device_id=?,updated_at=? WHERE id=?', [...$data, now(), $id]);
        audit($db, $user, 'update', 'note', $id);
        flash('success', 'Notiz aktualisiert.');
    } else {
        $db->run('INSERT INTO notes (title,body,device_id,created_at,updated_at) VALUES (?,?,?,?,?)', [...$data, now(), now()]);
        audit($db, $user, 'create', 'note', $db->lastId());
        flash('success', 'Notiz angelegt.');
    }
    redirect('notes');
}

function route_note_delete(Database $db, array $user): void
{
    $id = (int) param('id', '0');
    $db->run('DELETE FROM notes WHERE id = ?', [$id]);
    audit($db, $user, 'delete', 'note', $id);
    flash('success', 'Notiz gelöscht.');
    redirect('notes');
}

// ============================================================================
//  Globale Suche
// ============================================================================

function route_search(Database $db): void
{
    $q = param('q');
    $results = ['devices' => [], 'creds' => [], 'products' => [], 'notes' => []];
    if ($q !== '') {
        $like = '%' . $q . '%';
        $results['devices']  = $db->all('SELECT * FROM devices WHERE name LIKE ? OR ip LIKE ? OR hostname LIKE ? OR notes LIKE ? ORDER BY name LIMIT 25', [$like, $like, $like, $like]);
        $results['creds']    = $db->all('SELECT c.*, d.name AS device_name FROM credentials c LEFT JOIN devices d ON d.id=c.device_id WHERE c.title LIKE ? OR c.username LIKE ? OR c.url LIKE ? ORDER BY c.title LIMIT 25', [$like, $like, $like]);
        $results['products'] = $db->all('SELECT * FROM products WHERE name LIKE ? OR vendor LIKE ? ORDER BY name LIMIT 25', [$like, $like]);
        $results['notes']    = $db->all('SELECT * FROM notes WHERE title LIKE ? OR body LIKE ? ORDER BY updated_at DESC LIMIT 25', [$like, $like]);
    }
    render('search', 'Suche', ['q' => $q, 'results' => $results]);
}
