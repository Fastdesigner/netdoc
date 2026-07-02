<?php
declare(strict_types=1);

/**
 * Kleine, global genutzte Hilfsfunktionen.
 */

/** HTML-Escaping für die Ausgabe (XSS-Schutz). */
function e(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** URL zu einer internen Route bauen. */
function url(string $route, array $params = []): string
{
    $params = ['r' => $route] + $params;
    return 'index.php?' . http_build_query($params);
}

function redirect(string $route, array $params = []): never
{
    header('Location: ' . url($route, $params));
    exit;
}

/** Flash-Nachricht setzen (überlebt genau einen Redirect). */
function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function take_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/** GET/POST-Parameter als getrimmter String. */
function param(string $key, string $default = ''): string
{
    $v = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($v) ? trim($v) : $default;
}

/** Nullbarer String – leere Eingabe wird zu NULL in der DB. */
function param_null(string $key): ?string
{
    $v = param($key);
    return $v === '' ? null : $v;
}

function now(): int
{
    return time();
}

function fmt_date(?int $ts): string
{
    return $ts ? date('d.m.Y H:i', $ts) : '–';
}

/** Zeilen filtern: Treffer, wenn irgendeines der Felder $q (case-insensitiv) enthält. */
function arr_search(array $rows, array $fields, string $q): array
{
    if ($q === '') {
        return $rows;
    }
    return array_values(array_filter($rows, static function (array $r) use ($fields, $q): bool {
        foreach ($fields as $f) {
            if (isset($r[$f]) && $r[$f] !== null && stripos((string) $r[$f], $q) !== false) {
                return true;
            }
        }
        return false;
    }));
}

/** Nach einem Feld sortieren (Strings case-insensitiv). $desc kehrt die Reihenfolge um. */
function arr_sort(array $rows, string $field, bool $desc = false): array
{
    usort($rows, static function (array $a, array $b) use ($field): int {
        $va = $a[$field] ?? null;
        $vb = $b[$field] ?? null;
        if (is_numeric($va) && is_numeric($vb)) {
            return $va <=> $vb;
        }
        return strcasecmp((string) $va, (string) $vb);
    });
    return $desc ? array_reverse($rows) : $rows;
}

/** View rendern und als String zurückgeben. */
function view(string $name, array $data = []): string
{
    extract($data, EXTR_SKIP);
    ob_start();
    require VIEWS . '/' . $name . '.php';
    return (string) ob_get_clean();
}

/** View in das Layout einbetten und ausgeben. */
function render(string $name, string $title, array $data = []): void
{
    $content = view($name, $data);
    echo view('layout', ['content' => $content, 'title' => $title] + $data);
}
