<?php
declare(strict_types=1);

namespace NetDoc;

/**
 * Authentifizierung, Session-Handling, CSRF-Schutz und Login-Rate-Limit.
 */
final class Auth
{
    private const MAX_FAILED   = 5;      // Fehlversuche bis Sperre
    private const LOCK_SECONDS = 900;    // 15 Minuten Sperre

    public function __construct(private Store $store) {}

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

    public function createUser(string $username, string $password, string $role = 'admin'): void
    {
        $this->store->insert('users', [
            'username'      => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'role'          => $role,
            'failed_logins' => 0,
            'locked_until'  => null,
            'last_login'    => null,
            'created_at'    => now(),
        ]);
    }

    /** @return array{ok:bool,error?:string} */
    public function attempt(string $username, string $password): array
    {
        $u = $this->store->findBy('users', 'username', $username);

        if ($u && $u['locked_until'] && (int) $u['locked_until'] > now()) {
            $mins = (int) ceil(((int) $u['locked_until'] - now()) / 60);
            return ['ok' => false, 'error' => "Konto gesperrt. Erneut versuchen in {$mins} Min."];
        }

        // Timing-Angleichung, wenn User nicht existiert.
        $hash = $u['password_hash'] ?? '$2y$10$usesomesillystringforsalthashabcdefghijklmnopqrstuv';

        if (!$u || !password_verify($password, $hash)) {
            if ($u) {
                $failed = (int) $u['failed_logins'] + 1;
                $lock   = $failed >= self::MAX_FAILED ? now() + self::LOCK_SECONDS : null;
                $this->store->update('users', (int) $u['id'], [
                    'failed_logins' => $failed,
                    'locked_until'  => $lock,
                ]);
            }
            return ['ok' => false, 'error' => 'Benutzername oder Passwort falsch.'];
        }

        // Erfolg: Session-Fixation verhindern.
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id'       => (int) $u['id'],
            'username' => $u['username'],
            'role'     => $u['role'],
        ];
        $this->store->update('users', (int) $u['id'], [
            'failed_logins' => 0,
            'locked_until'  => null,
            'last_login'    => now(),
        ]);

        return ['ok' => true];
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
