<?php
declare(strict_types=1);

namespace NetDoc;

/**
 * Schlanker Mailversand für die passwortlose Anmeldung.
 *
 * Aktuell Transport 'mail' (PHP mail()). Der SMTP-Zweig ist vorbereitet, aber
 * noch nicht aktiv – sobald SMTP-Daten in der Config stehen, wird hier erweitert
 * (wichtig fürs spätere SPF/DKIM-Thema).
 */
final class Mailer
{
    public function __construct(private array $cfg) {}

    /** Login-Mail mit 6-stelligem Code und Magic-Link. */
    public function sendLoginCode(string $toEmail, string $toName, string $code, string $magicUrl): bool
    {
        $subject = 'Dein NetDoc-Anmeldecode: ' . $code;

        $body = "Hallo {$toName},\n\n"
            . "dein Anmeldecode für NetDoc lautet:\n\n"
            . "    {$code}\n\n"
            . "Der Code ist 10 Minuten gültig.\n\n"
            . "Oder direkt per Klick anmelden (ohne Code eintippen):\n"
            . "{$magicUrl}\n\n"
            . "Wenn du diese Anmeldung nicht angefordert hast, ignoriere diese Mail einfach.\n";

        return $this->send($toEmail, $subject, $body);
    }

    private function send(string $to, string $subject, string $body): bool
    {
        $transport = $this->cfg['transport'] ?? 'mail';

        // SMTP folgt später – bis dahin immer über mail().
        if ($transport === 'smtp') {
            // TODO: SMTP-Anbindung (host/port/user/pass/encryption aus $this->cfg).
            // Bewusst Fallback auf mail(), damit nichts stillschweigend nicht sendet.
        }

        $from     = $this->from();
        $fromName = $this->cfg['from_name'] ?? 'NetDoc';
        // RFC-2047-kodierter Anzeigename (Umlaute-sicher).
        $fromHeader = '=?UTF-8?B?' . base64_encode($fromName) . '?= <' . $from . '>';

        $headers = implode("\r\n", [
            'From: ' . $fromHeader,
            'Reply-To: ' . $from,
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            'MIME-Version: 1.0',
            'X-Mailer: NetDoc',
        ]);

        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

        // -f setzt den Envelope-Sender (hilft später bei SPF).
        return @mail($to, $encodedSubject, $body, $headers, '-f' . $from);
    }

    /** Absender bestimmen: konfiguriert, sonst netdoc@<Hauptdomain>. */
    private function from(): string
    {
        if (!empty($this->cfg['from'])) {
            return $this->cfg['from'];
        }
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $host = preg_replace('/:\d+$/', '', $host);          // Port entfernen
        $parts = explode('.', $host);
        if (count($parts) > 2) {
            $host = implode('.', array_slice($parts, -2));    // Subdomain weglassen
        }
        return 'netdoc@' . $host;
    }
}
