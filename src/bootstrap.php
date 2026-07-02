<?php
declare(strict_types=1);

/**
 * Zentrales Bootstrapping: Pfade, Autoload, Config, DB, Krypto, Session.
 */

namespace NetDoc;

// --- Pfade (public/ ist Docroot, alles andere liegt darüber) ---------------
define('ROOT',   dirname(__DIR__));
define('SRC',    ROOT . '/src');
define('VIEWS',  ROOT . '/views');
define('CONFIG', ROOT . '/config');
define('DATA',   ROOT . '/data');

require SRC . '/helpers.php';

// Simpler Autoloader für die Klassen in src/.
spl_autoload_register(static function (string $class): void {
    $short = str_replace('NetDoc\\', '', $class);
    $file  = SRC . '/' . $short . '.php';
    if (is_file($file)) {
        require $file;
    }
});

// --- Config laden -----------------------------------------------------------
$configFile = CONFIG . '/config.php';
$isInstalled = is_file($configFile);
$config = $isInstalled
    ? require $configFile
    : ['app_key' => '', 'https_only' => false, 'timezone' => 'Europe/Berlin'];

date_default_timezone_set($config['timezone'] ?? 'Europe/Berlin');

// Hinter Reverse-Proxy/HTTPS-Terminierung erkennen.
$secure = ($config['https_only'] ?? false)
    || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

// --- Datenbank --------------------------------------------------------------
if (!is_dir(DATA)) {
    @mkdir(DATA, 0700, true);
}
$db   = new Database(DATA . '/netdoc.sqlite');
$auth = new Auth($db);

// Krypto steht erst nach der Installation bereit (Key aus Config).
$crypto = ($isInstalled && !empty($config['app_key']))
    ? new Crypto($config['app_key'])
    : null;

Auth::startSession((bool) $secure);

// Sicherheits-Header, die auf jedem Host funktionieren.
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; object-src 'none'; base-uri 'self'; form-action 'self'");

/** Audit-Eintrag schreiben. */
function audit(Database $db, ?array $user, string $action, ?string $entity = null, ?int $entityId = null): void
{
    $db->run(
        'INSERT INTO audit_log (user_id, username, action, entity, entity_id, ip, created_at) VALUES (?,?,?,?,?,?,?)',
        [$user['id'] ?? null, $user['username'] ?? null, $action, $entity, $entityId,
         $_SERVER['REMOTE_ADDR'] ?? null, now()]
    );
}
