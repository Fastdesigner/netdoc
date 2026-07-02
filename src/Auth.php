<?php
declare(strict_types=1);

namespace NetDoc;

/**
 * Passwortlose Authentifizierung (6-stelliger Code + Magic-Link),
 * Session-Handling und CSRF-Schutz.
 */
final class Auth
{
    public const CODE_TTL     = 600;  // Code/Link 10 Minuten gültig
    public const MAX_ATTEMPTS = 5;    // Falsche Code-Eingaben je Challenge

    /**
     * @param string $pepper Serverseitiges Geheimnis (app_key) zum Hashen der Codes/Tokens.
     *                       Liegt in config/ – getrennt von den Datendateien in data/.
     */
    public function __construct(private Store $store, private string $pepper) {}

    /** Session gehärtet starten. Einmal pro Request aufrufen. */
    public static function startSession(bool $secure): void
    {
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'httponly' => true,
            'secure'   => $secure,
            'samesite' => 'Lax',
        ]);
        session_name('netdocsid');
        session_start();
    }

    public function isSetupNeeded(): bool
    {
        return $this->store->count('users') === 0;
    }

    public function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public function check(): bool
    {
        return isset($_SESSION['user']);
    }

    public function createUser(string $username, string $email, string $role = 'admin'): void
    {
        $this->store->insert('users', [
            'username'   => $username,
            'email'      => strtolower(trim($email)),
            'role'       => $role,
            'last_login' => null,
            'created_at' => now(),
        ]);
    }

    // --- Passwortlose Anmeldung --------------------------------------------

    /**
     * Login-Code + Magic-Link-Token für eine Email erzeugen.
     * Gibt bei unbekannter Email null zurück (der Aufrufer zeigt trotzdem
     * dieselbe Meldung -> keine Rückschlüsse auf existierende Konten).
     *
     * @return array{challenge_id:int,code:string,token:string,user:array}|null
     */
    public function requestLogin(string $email): ?array
    {
        $this->purgeExpiredCodes();
        $user = $this->store->findBy('users', 'email', strtolower(trim($email)));
        if (!$user) {
            return null;
        }

        $code  = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $token = bin2hex(random_bytes(32));

        $id = $this->store->insert('login_codes', [
            'user_id'    => (int) $user['id'],
            'code_hash'  => $this->peppered($code),
            'token_hash' => $this->peppered($token),
            'expires_at' => now() + self::CODE_TTL,
            'attempts'   => 0,
            'created_at' => now(),
        ]);

        return ['challenge_id' => $id, 'code' => $code, 'token' => $token, 'user' => $user];
    }

    /** 6-stelligen Code zu einer laufenden Challenge prüfen. */
    public function verifyCode(int $challengeId, string $code): array
    {
        $c = $challengeId ? $this->store->find('login_codes', $challengeId) : null;
        if (!$c || (int) $c['expires_at'] < now()) {
            if ($c) $this->store->delete('login_codes', $challengeId);
            return ['ok' => false, 'error' => 'Code abgelaufen. Bitte neu anfordern.'];
        }
        if ((int) $c['attempts'] >= self::MAX_ATTEMPTS) {
            $this->store->delete('login_codes', $challengeId);
            return ['ok' => false, 'error' => 'Zu viele Fehlversuche. Bitte neuen Code anfordern.'];
        }
        if (!hash_equals((string) $c['code_hash'], $this->peppered(preg_replace('/\s+/', '', $code)))) {
            $this->store->update('login_codes', $challengeId, ['attempts' => (int) $c['attempts'] + 1]);
            return ['ok' => false, 'error' => 'Code falsch.'];
        }
        $this->completeLogin((int) $c['user_id'], $challengeId);
        return ['ok' => true];
    }

    /** Magic-Link-Token prüfen (identifiziert die Challenge selbst, ohne Session). */
    public function verifyMagic(string $token): bool
    {
        $this->purgeExpiredCodes();
        $hash = $this->peppered(trim($token));
        foreach ($this->store->all('login_codes') as $c) {
            if (hash_equals((string) $c['token_hash'], $hash)) {
                if ((int) $c['expires_at'] < now()) {
                    $this->store->delete('login_codes', (int) $c['id']);
                    return false;
                }
                $this->completeLogin((int) $c['user_id'], (int) $c['id']);
                return true;
            }
        }
        return false;
    }

    private function completeLogin(int $userId, int $challengeId): void
    {
        $u = $this->store->find('users', $userId);
        if (!$u) {
            return;
        }
        // Session-Fixation verhindern.
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'       => (int) $u['id'],
            'username' => $u['username'],
            'role'     => $u['role'],
        ];
        unset($_SESSION['login_challenge'], $_SESSION['login_email'], $_SESSION['login_sent_at']);
        $this->store->update('users', $userId, ['last_login' => now()]);
        $this->store->delete('login_codes', $challengeId);
    }

    private function peppered(string $value): string
    {
        return hash_hmac('sha256', $value, $this->pepper);
    }

    private function purgeExpiredCodes(): void
    {
        foreach ($this->store->all('login_codes') as $c) {
            if ((int) $c['expires_at'] < now()) {
                $this->store->delete('login_codes', (int) $c['id']);
            }
        }
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    // --- CSRF ---------------------------------------------------------------

    public function csrfToken(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    public function checkCsrf(): void
    {
        $sent = $_POST['csrf'] ?? '';
        if (!is_string($sent) || !hash_equals($_SESSION['csrf'] ?? '', $sent)) {
            http_response_code(419);
            exit('CSRF-Token ungültig. Bitte Formular neu laden.');
        }
    }
}
