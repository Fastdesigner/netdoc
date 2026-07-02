<?php
declare(strict_types=1);

namespace NetDoc;

/**
 * Dateibasierter JSON-Speicher – KEINE Datenbank-Erweiterung nötig (nur ext/json).
 *
 * Jede "Collection" ist eine Datei data/<name>.json mit der Struktur
 *   { "seq": <letzte ID>, "rows": [ { "id": 1, ... }, ... ] }
 *
 * Schreibzugriffe laufen unter exklusivem flock (LOCK_EX), Lesezugriffe unter
 * LOCK_SH – damit sind parallele Requests sicher. Geschrieben wird komplett
 * (truncate + write) innerhalb des Locks.
 */
final class Store
{
    public function __construct(private string $dir)
    {
        if (!is_dir($dir)) {
            @mkdir($dir, 0700, true);
        }
    }

    private function path(string $collection): string
    {
        // Nur erlaubte Zeichen – Schutz gegen Pfad-Tricks.
        if (!preg_match('/^[a-z_]+$/', $collection)) {
            throw new \InvalidArgumentException('Ungültige Collection.');
        }
        return $this->dir . '/' . $collection . '.json';
    }

    /** Alle Datensätze einer Collection (Liste). */
    public function all(string $collection): array
    {
        return $this->read($collection)['rows'];
    }

    public function find(string $collection, int $id): ?array
    {
        foreach ($this->read($collection)['rows'] as $row) {
            if ((int) $row['id'] === $id) {
                return $row;
            }
        }
        return null;
    }

    /** Erster Treffer nach Feld-Wert (z.B. username). */
    public function findBy(string $collection, string $field, $value): ?array
    {
        foreach ($this->read($collection)['rows'] as $row) {
            if (($row[$field] ?? null) === $value) {
                return $row;
            }
        }
        return null;
    }

    public function count(string $collection): int
    {
        return count($this->read($collection)['rows']);
    }

    /** Neuen Datensatz anlegen, vergibt fortlaufende ID und gibt sie zurück. */
    public function insert(string $collection, array $row): int
    {
        return $this->mutate($collection, function (array &$data) use ($row): int {
            $id = (int) $data['seq'] + 1;
            $data['seq'] = $id;
            $row = ['id' => $id] + $row;
            $data['rows'][] = $row;
            return $id;
        });
    }

    /** Vorhandenen Datensatz teilweise aktualisieren. */
    public function update(string $collection, int $id, array $patch): void
    {
        $this->mutate($collection, function (array &$data) use ($id, $patch): void {
            foreach ($data['rows'] as &$row) {
                if ((int) $row['id'] === $id) {
                    $row = array_merge($row, $patch);
                    return;
                }
            }
        });
    }

    public function delete(string $collection, int $id): void
    {
        $this->mutate($collection, function (array &$data) use ($id): void {
            $data['rows'] = array_values(array_filter(
                $data['rows'],
                static fn(array $r): bool => (int) $r['id'] !== $id
            ));
        });
    }

    /**
     * Setzt in allen Zeilen $field von $oldValue auf null.
     * Ersetzt die ON DELETE SET NULL-Logik (z.B. Gerät gelöscht -> Verknüpfung lösen).
     */
    public function nullifyReferences(string $collection, string $field, int $value): void
    {
        $this->mutate($collection, function (array &$data) use ($field, $value): void {
            foreach ($data['rows'] as &$row) {
                if ((int) ($row[$field] ?? 0) === $value) {
                    $row[$field] = null;
                }
            }
        });
    }

    // --- intern ------------------------------------------------------------

    /** Lesen unter Shared-Lock. */
    private function read(string $collection): array
    {
        $path = $this->path($collection);
        $fh = @fopen($path, 'r');
        if ($fh === false) {
            return ['seq' => 0, 'rows' => []];
        }
        flock($fh, LOCK_SH);
        $content = stream_get_contents($fh);
        flock($fh, LOCK_UN);
        fclose($fh);
        return $this->decode($content);
    }

    /**
     * Read-modify-write unter exklusivem Lock (atomar für parallele Requests).
     * @return mixed Rückgabewert des Callbacks
     */
    private function mutate(string $collection, callable $fn)
    {
        $path = $this->path($collection);
        $isNew = !file_exists($path);
        $fh = fopen($path, 'c+');
        if ($fh === false) {
            throw new \RuntimeException("Kann {$collection}.json nicht öffnen (Schreibrechte im data/-Ordner?).");
        }
        if ($isNew) {
            @chmod($path, 0600);
        }
        flock($fh, LOCK_EX);
        $data = $this->decode(stream_get_contents($fh));

        $result = $fn($data);

        rewind($fh);
        ftruncate($fh, 0);
        fwrite($fh, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        fflush($fh);
        flock($fh, LOCK_UN);
        fclose($fh);

        return $result;
    }

    private function decode(string $content): array
    {
        if (trim($content) === '') {
            return ['seq' => 0, 'rows' => []];
        }
        $data = json_decode($content, true);
        if (!is_array($data) || !isset($data['rows']) || !is_array($data['rows'])) {
            return ['seq' => 0, 'rows' => []];
        }
        $data['seq'] = (int) ($data['seq'] ?? 0);
        return $data;
    }
}
