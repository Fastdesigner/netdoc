<?php
declare(strict_types=1);

namespace NetDoc;

use RuntimeException;

/**
 * Authentifizierte Verschlüsselung (AES-256-GCM) für Geheimnisse at-rest.
 *
 * Zweck: Wird die SQLite-Datei kopiert/geleakt, sind Passwörter & Lizenzschlüssel
 * ohne den separaten Key aus config/config.php nicht lesbar.
 *
 * openssl statt libsodium bewusst gewählt – auf Shared-Hosting nahezu immer vorhanden.
 */
final class Crypto
{
    private const CIPHER = 'aes-256-gcm';

    private string $key; // 32 Byte roh

    public function __construct(string $base64Key)
    {
        $key = base64_decode($base64Key, true);
        if ($key === false || strlen($key) !== 32) {
            throw new RuntimeException('Ungültiger Krypto-Schlüssel in der Konfiguration.');
        }
        $this->key = $key;
    }

    /** Erzeugt einen frischen 32-Byte-Schlüssel (base64) für die Config. */
    public static function generateKey(): string
    {
        return base64_encode(random_bytes(32));
    }

    /** Klartext -> transportierbarer String (nonce|tag|ciphertext, base64). */
    public function encrypt(string $plaintext): string
    {
        $iv  = random_bytes(12); // GCM Standard-Nonce-Länge
        $tag = '';
        $ct  = openssl_encrypt($plaintext, self::CIPHER, $this->key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($ct === false) {
            throw new RuntimeException('Verschlüsselung fehlgeschlagen.');
        }
        return base64_encode($iv . $tag . $ct);
    }

    /** Umkehrung von encrypt(). Gibt bei Manipulation/falschem Key null zurück. */
    public function decrypt(?string $stored): ?string
    {
        if ($stored === null || $stored === '') {
            return null;
        }
        $raw = base64_decode($stored, true);
        if ($raw === false || strlen($raw) < 28) {
            return null;
        }
        $iv  = substr($raw, 0, 12);
        $tag = substr($raw, 12, 16);
        $ct  = substr($raw, 28);

        $pt = openssl_decrypt($ct, self::CIPHER, $this->key, OPENSSL_RAW_DATA, $iv, $tag);
        return $pt === false ? null : $pt;
    }
}
