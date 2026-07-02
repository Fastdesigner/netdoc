<?php
/**
 * TEMPORÄRE Diagnose – nach Gebrauch WIEDER LÖSCHEN.
 * Gibt Fehler selbst aus, auch wenn der Host display_errors gesperrt hat.
 */
header('Content-Type: text/plain; charset=utf-8');

echo "PHP-Version:      " . PHP_VERSION . "\n";
echo "SAPI:             " . PHP_SAPI . "\n";
echo "pdo_sqlite:       " . (extension_loaded('pdo_sqlite') ? 'ja' : 'NEIN !!!') . "\n";
echo "openssl:          " . (extension_loaded('openssl')   ? 'ja' : 'NEIN !!!') . "\n";
echo "session:          " . (extension_loaded('session')   ? 'ja' : 'NEIN !!!') . "\n";
echo "display_errors:   " . var_export(ini_get('display_errors'), true) . "\n";
echo "open_basedir:     " . (ini_get('open_basedir') ?: '(keins)') . "\n";
echo "error_log-Datei:  " . (ini_get('error_log') ?: '(Standard/Panel)') . "\n";

$data   = __DIR__ . '/../data';
$config = __DIR__ . '/../config';
echo "\nPfad data:        " . realpath($data) . "\n";
echo "  existiert:      " . (is_dir($data)   ? 'ja' : 'NEIN') . "  schreibbar: " . (is_writable($data)   ? 'ja' : 'NEIN') . "\n";
echo "Pfad config:      " . realpath($config) . "\n";
echo "  existiert:      " . (is_dir($config) ? 'ja' : 'NEIN') . "  schreibbar: " . (is_writable($config) ? 'ja' : 'NEIN') . "\n";

echo "\n--- Test 1: SQLite-Datei öffnen/anlegen ---\n";
try {
    $pdo = new PDO('sqlite:' . $data . '/netdoc.sqlite');
    $pdo->exec('CREATE TABLE IF NOT EXISTS _diag (x INTEGER)');
    echo "OK – Datenbank ist beschreibbar.\n";
} catch (\Throwable $e) {
    echo "FEHLER: " . get_class($e) . ": " . $e->getMessage() . "\n";
}

echo "\n--- Test 2: Kompletten Bootstrap laden ---\n";
try {
    require __DIR__ . '/../src/bootstrap.php';
    echo "OK – Bootstrap lief durch.\n";
} catch (\Throwable $e) {
    echo "FEHLER: " . get_class($e) . ": " . $e->getMessage() . "\n";
    echo "  Datei: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
