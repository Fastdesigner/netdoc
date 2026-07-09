# NetDoc – schlanke IT-Netzwerk-Dokumentation

Ein leichtgewichtiges Tool, um Ordnung in einen Netzwerk-„Saustall“ zu bringen:
Server & Geräte, Zugänge/Verbindungsdaten, Produkte/Lizenzen und Notizen –
verknüpfbar und durchsuchbar.

## Eigenschaften

- **Reines PHP, kein MySQL, keine DB-Erweiterung** – Daten liegen als **JSON-Dateien** im `data/`-Ordner (braucht nur `ext/json`, das in PHP immer dabei ist). Backup = Ordner kopieren.
- **Kein Composer, kein Build** – auf jeden 08/15-Webspace hochladen, fertig.
- Geräte · Zugänge · Produkte/Lizenzen · Notizen · Dokumente, alles miteinander verknüpfbar.
- Zugänge können als Team-Zugang oder privat „nur für mich“ gespeichert werden.
- **Dokumenten-Ablage** mit Datei-Upload (außerhalb des Webroots gespeichert, Download nur nach Login).
- **Benutzerverwaltung** (Admin): weitere Personen mit eigener E-Mail anlegen, passwortlose Anmeldung.
- Rollen: Benutzer, Admin und Systemadmin. Systemadmins können zusätzlich private Zugänge aller Benutzer einsehen.
- Volltextsuche über alle Bereiche.

## Sicherheit

| Schutz | Umsetzung |
|---|---|
| Zugriffsschutz | **Passwortlose Anmeldung**: 6-stelliger Code + Magic-Link per E-Mail. Kein Passwort gespeichert. Gehärtete Session-Cookies (HttpOnly, SameSite, Secure) |
| Login-Codes | 10 Min. gültig, einmalig, max. 5 Fehlversuche je Code. Code-/Token-Hashes zusätzlich mit `app_key` gepeppert |
| Enumeration | Login zeigt für existierende und nicht existierende Adressen dieselbe Reaktion |
| **Geheimnisse at-rest** | Passwörter & Lizenzschlüssel sind mit **AES-256-GCM** verschlüsselt; der Schlüssel liegt separat in `config/config.php`. Ein reiner Diebstahl der Datendateien gibt die Zugänge **nicht** preis. |
| SQL-Injection | ausschließlich Prepared Statements |
| XSS | konsequentes Output-Escaping + Content-Security-Policy |
| CSRF | Token-Pflicht auf allen schreibenden Requests |
| Datei-Schutz | DB/Config/Code liegen außerhalb des Docroots (`public/`) **und** haben zusätzlich `deny`-`.htaccess` |
| Nachvollziehbarkeit | Audit-Log (Login, Code-Versand, Änderungen, Passwort-Anzeigen) in `data/audit_log.json` |

### E-Mail-Versand

Die Anmeldung braucht funktionierenden Mailversand. Konfiguration in `config/config.php` unter `mail`:

- `transport: 'mail'` – nutzt PHP `mail()` (Standard, aktuell aktiv).
- `transport: 'smtp'` – für später vorgesehen (host/port/user/pass/encryption); wichtig, sobald SPF/DKIM sauber sein müssen.
- `from` leer lassen → `netdoc@<Hauptdomain>` wird automatisch verwendet.

## Deployment auf dem Kunden-Webhost

**Voraussetzungen:** PHP 8.1+ mit `openssl` (beides Standard). Es wird **keine** Datenbank-Erweiterung benötigt.

### Variante A – eigener Docroot (empfohlen)
Domain/Subdomain so einstellen, dass der Docroot auf den **`public/`**-Ordner zeigt.
Alles andere (DB, Config, Code) ist damit gar nicht erst über das Web erreichbar.

### Variante B – fester Docroot (z. B. `public_html`)
Kompletten Projektordner hochladen. Die mitgelieferten `deny`-`.htaccess` in
`config/`, `data/`, `src/`, `views/` sperren den Zugriff. Aufruf dann über
`https://…/netdoc/public/`.

### Schritte
1. Dateien per FTP/SFTP hochladen.
2. Sicherstellen, dass `config/` und `data/` vom PHP-Prozess **beschreibbar** sind
   (das Setup schreibt dorthin Key bzw. Datenbank).
3. Im Browser aufrufen → **Setup-Seite** legt Admin-Konto an und erzeugt automatisch
   den Verschlüsselungs-Schlüssel in `config/config.php`.
4. **HTTPS verwenden.** In `config/config.php` bleibt `https_only => true`.

## Backup & Wiederherstellung

- Daten: den kompletten `data/`-Ordner sichern (alle `*.json`).
- Schlüssel: `config/config.php` **getrennt** von der DB aufbewahren.
  > Ohne `app_key` sind verschlüsselte Passwörter/Lizenzen nicht wiederherstellbar!

## Projektstruktur

```
netdoc/
├─ public/          ← Docroot (index.php, assets, .htaccess)
├─ src/             ← Klassen: Store (JSON), Crypto, Auth + Bootstrap/Helpers
├─ views/           ← Templates
├─ config/          ← config.php (Key) – nicht im Web erreichbar
└─ data/            ← *.json (Datenbestand) – nicht im Web erreichbar
```
