<?php
/**
 * NetDoc – Konfiguration.
 *
 * Diese Datei wird beim Setup automatisch als config.php erzeugt.
 * Manuell nur anfassen, wenn du weißt was du tust.
 *
 * WICHTIG: 'app_key' ist der Schlüssel zur Entschlüsselung aller Geheimnisse.
 * Geht er verloren, sind gespeicherte Passwörter/Lizenzen unwiederbringlich weg.
 * -> Bei Backups IMMER mitsichern (aber getrennt von der Datenbank aufbewahren!).
 */

return [
    // 32-Byte-Schlüssel, base64. Wird beim Setup generiert.
    'app_key' => '',

    // true erzwingt Secure-Cookies (nur über HTTPS). Auf Produktivsystemen an lassen.
    'https_only' => true,

    // Zeitzone für die Anzeige.
    'timezone' => 'Europe/Berlin',
];
