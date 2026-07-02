<?php
declare(strict_types=1);

namespace NetDoc;

use PDO;

/**
 * SQLite-Datenbank inkl. selbstlaufender Migrationen.
 * Es wird KEIN MySQL benötigt – die komplette DB liegt in einer Datei.
 */
final class Database
{
    private PDO $pdo;

    public function __construct(string $path)
    {
        $isNew = !file_exists($path);

        $this->pdo = new PDO('sqlite:' . $path, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        // Fremdschlüssel + WAL für parallele Zugriffe.
        $this->pdo->exec('PRAGMA foreign_keys = ON');
        $this->pdo->exec('PRAGMA journal_mode = WAL');

        if ($isNew) {
            @chmod($path, 0600);
        }

        $this->migrate();
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }

    /** Bequemer Prepared-Statement-Wrapper. */
    public function run(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function one(string $sql, array $params = []): ?array
    {
        $row = $this->run($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    public function all(string $sql, array $params = []): array
    {
        return $this->run($sql, $params)->fetchAll();
    }

    public function lastId(): int
    {
        return (int) $this->pdo->lastInsertId();
    }

    private function migrate(): void
    {
        $this->pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS users (
                id            INTEGER PRIMARY KEY AUTOINCREMENT,
                username      TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                role          TEXT NOT NULL DEFAULT 'admin',
                failed_logins INTEGER NOT NULL DEFAULT 0,
                locked_until  INTEGER,
                last_login    INTEGER,
                created_at    INTEGER NOT NULL
            );

            CREATE TABLE IF NOT EXISTS devices (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                name       TEXT NOT NULL,
                type       TEXT NOT NULL DEFAULT 'server',
                hostname   TEXT,
                ip         TEXT,
                location   TEXT,
                vendor     TEXT,
                model      TEXT,
                os         TEXT,
                status     TEXT NOT NULL DEFAULT 'active',
                notes      TEXT,
                created_at INTEGER NOT NULL,
                updated_at INTEGER NOT NULL
            );

            CREATE TABLE IF NOT EXISTS credentials (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                device_id  INTEGER REFERENCES devices(id) ON DELETE SET NULL,
                title      TEXT NOT NULL,
                category   TEXT NOT NULL DEFAULT 'login',
                username   TEXT,
                secret_enc TEXT,
                url        TEXT,
                port       TEXT,
                notes      TEXT,
                created_at INTEGER NOT NULL,
                updated_at INTEGER NOT NULL
            );

            CREATE TABLE IF NOT EXISTS products (
                id            INTEGER PRIMARY KEY AUTOINCREMENT,
                name          TEXT NOT NULL,
                vendor        TEXT,
                category      TEXT,
                license_enc   TEXT,
                seats         INTEGER,
                purchase_date TEXT,
                expiry_date   TEXT,
                cost          TEXT,
                supplier      TEXT,
                device_id     INTEGER REFERENCES devices(id) ON DELETE SET NULL,
                notes         TEXT,
                created_at    INTEGER NOT NULL,
                updated_at    INTEGER NOT NULL
            );

            CREATE TABLE IF NOT EXISTS notes (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                title      TEXT NOT NULL,
                body       TEXT,
                device_id  INTEGER REFERENCES devices(id) ON DELETE SET NULL,
                created_at INTEGER NOT NULL,
                updated_at INTEGER NOT NULL
            );

            CREATE TABLE IF NOT EXISTS audit_log (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id    INTEGER,
                username   TEXT,
                action     TEXT NOT NULL,
                entity     TEXT,
                entity_id  INTEGER,
                ip         TEXT,
                created_at INTEGER NOT NULL
            );

            CREATE INDEX IF NOT EXISTS idx_cred_device ON credentials(device_id);
            CREATE INDEX IF NOT EXISTS idx_note_device ON notes(device_id);
            CREATE INDEX IF NOT EXISTS idx_prod_device ON products(device_id);
        SQL);
    }
}
