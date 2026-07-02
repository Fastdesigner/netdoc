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

// --- Datenspeicher (dateibasiert, JSON – keine DB-Erweiterung nötig) --------
$store = new Store(DATA);

// Der app_key dient auch als Pepper zum Hashen der Login-Codes/-Tokens.
$auth   = new Auth($store, (string) ($config['app_key'] ?? ''));
$mailer = new Mailer($config['mail'] ?? []);

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
function audit(Store $store, ?array $user, string $action, ?string $entity = null, ?int $entityId = null): void
{
    $store->insert('audit_log', [
        'user_id'    => $user['id'] ?? null,
        'username'   => $user['username'] ?? null,
        'action'     => $action,
        'entity'     => $entity,
        'entity_id'  => $entityId,
        'ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
        'created_at' => now(),
    ]);
}
