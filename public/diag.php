<?php
/**
 * TEMPORÄRE Diagnose – nach Gebrauch WIEDER LÖSCHEN.
 * Gibt Fehler selbst aus, auch wenn der Host display_errors gesperrt hat.
 */
header('Content-Type: text/plain; charset=utf-8');

echo "PHP-Version:      " . PHP_VERSION . "\n";
echo "SAPI:             " . PHP_SAPI . "\n";
echo "json:             " . (extension_loaded('json')    ? 'ja' : 'NEIN !!!') . "\n";
echo "openssl:          " . (extension_loaded('openssl') ? 'ja' : 'NEIN !!!') . "\n";
echo "session:          " . (extension_loaded('session') ? 'ja' : 'NEIN !!!') . "\n";
echo "display_errors:   " . var_export(ini_get('display_errors'), true) . "\n";
echo "open_basedir:     " . (ini_get('open_basedir') ?: '(keins)') . "\n";
echo "error_log-Datei:  " . (ini_get('error_log') ?: '(Standard/Panel)') . "\n";

$data   = __DIR__ . '/../data';
$config = __DIR__ . '/../config';
echo "\nPfad data:        " . realpath($data) . "\n";
echo "  existiert:      " . (is_dir($data)   ? 'ja' : 'NEIN') . "  schreibbar: " . (is_writable($data)   ? 'ja' : 'NEIN') . "\n";
echo "Pfad config:      " . realpath($config) . "\n";
echo "  existiert:      " . (is_dir($config) ? 'ja' : 'NEIN') . "  schreibbar: " . (is_writable($config) ? 'ja' : 'NEIN') . "\n";

echo "\n--- Test 1: JSON-Datei schreiben/lesen ---\n";
try {
    $f = $data . '/_diag.json';
    file_put_contents($f, json_encode(['ok' => true]));
    $back = json_decode((string) file_get_contents($f), true);
    @unlink($f);
    echo ($back['ok'] ?? false) ? "OK – data/ ist beschreibbar.\n" : "FEHLER: Rücklesen misslungen.\n";
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
