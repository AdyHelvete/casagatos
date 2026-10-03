<?php
declare(strict_types=1);

require_once __DIR__ . '/PageRegistry.php';

class ControlAuth
{
    private const SESSION_KEY = 'control_user';
    private const CSRF_KEY = 'control_csrf';
    public const USERS_FILE = 'admin-users.json';
    private const LOGIN_ATTEMPTS_FILE = 'control-login-attempts.json';
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOGIN_WINDOW_SECONDS = 900;

    public static function ensureDefaultAdmin(): void
    {
        $path = SiteStorage::path(self::USERS_FILE);
        if (is_file($path)) {
            return;
        }

        $password = bin2hex(random_bytes(16));
        SiteStorage::write(self::USERS_FILE, [
            'users' => [[
                'username' => 'CasaGatosAdmin',
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'admin',
            ]],
        ]);

        $setupPath = SiteStorage::path('.initial-control-password');
        $message = "Usuario: CasaGatosAdmin\nContraseña temporal: {$password}\n\nCámbiala en Mi cuenta y elimina este archivo.\n";
        file_put_contents($setupPath, $message, LOCK_EX);
        @chmod($setupPath, 0600);
    }

    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => PageRegistry::url('/control/'),
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);

        session_name('LCG_CONTROL');
        session_start();
    }

    public static function csrfToken(): string
    {
        self::startSession();

        if (empty($_SESSION[self::CSRF_KEY])) {
            $_SESSION[self::CSRF_KEY] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION[self::CSRF_KEY];
    }

    public static function verifyCsrf(?string $token): bool
    {
        self::startSession();
        $expected = (string) ($_SESSION[self::CSRF_KEY] ?? '');

        return $expected !== '' && is_string($token) && hash_equals($expected, $token);
    }

    public static function requireCsrf(): void
    {
        $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        if (!self::verifyCsrf(is_string($token) ? $token : null)) {
            http_response_code(403);
            exit('Token de seguridad inválido. Recarga la página e intenta de nuevo.');
        }
    }

    public static function isLoggedIn(): bool
    {
        self::startSession();
        return !empty($_SESSION[self::SESSION_KEY]);
    }

    public static function requireLogin(): void
    {
        if (!self::isLoggedIn()) {
            header('Location: ' . PageRegistry::url('/control/login.php'));
            exit;
        }
    }

    public static function login(string $username, string $password): bool
    {
        if (self::isLoginBlocked()) {
            return false;
        }

        self::ensureDefaultAdmin();
        $data = SiteStorage::read(self::USERS_FILE, ['users' => []]);
        $username = trim($username);
        $success = false;

        foreach ($data['users'] as $user) {
            if (($user['username'] ?? '') !== $username) {
                continue;
            }
            if (!password_verify($password, (string) ($user['password_hash'] ?? ''))) {
                self::recordLoginAttempt(false);
                return false;
            }

            self::startSession();
            session_regenerate_id(true);
            $_SESSION[self::SESSION_KEY] = [
                'username' => $username,
                'role' => $user['role'] ?? 'admin',
            ];
            self::recordLoginAttempt(true);
            $success = true;
            break;
        }

        if (!$success) {
            self::recordLoginAttempt(false);
        }

        return $success;
    }

    public static function logout(): void
    {
        self::startSession();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function currentUser(): ?array
    {
        self::startSession();
        return $_SESSION[self::SESSION_KEY] ?? null;
    }

    public static function loginBlockedMessage(): string
    {
        return 'Demasiados intentos fallidos. Espera 15 minutos e inténtalo de nuevo.';
    }

    public static function isLoginBlocked(): bool
    {
        $ip = self::clientIp();
        $attempts = self::readLoginAttempts();
        $recent = array_filter(
            $attempts[$ip] ?? [],
            fn(int $timestamp): bool => (time() - $timestamp) < self::LOGIN_WINDOW_SECONDS
        );

        return count($recent) >= self::MAX_LOGIN_ATTEMPTS;
    }

    private static function recordLoginAttempt(bool $success): void
    {
        if ($success) {
            $attempts = self::readLoginAttempts();
            unset($attempts[self::clientIp()]);
            self::writeLoginAttempts($attempts);
            return;
        }

        $ip = self::clientIp();
        $attempts = self::readLoginAttempts();
        $attempts[$ip] ??= [];
        $attempts[$ip][] = time();
        $attempts[$ip] = array_values(array_filter(
            $attempts[$ip],
            fn(int $timestamp): bool => (time() - $timestamp) < self::LOGIN_WINDOW_SECONDS
        ));

        self::writeLoginAttempts($attempts);

        require_once __DIR__ . '/SecurityLog.php';
        if (count($attempts[$ip] ?? []) >= self::MAX_LOGIN_ATTEMPTS) {
            SecurityLog::record('control_login_blocked', 'IP bloqueada tras intentos fallidos', $ip);
        } else {
            SecurityLog::record('control_login_failed', 'Credenciales incorrectas', $ip);
        }
    }

    private static function readLoginAttempts(): array
    {
        $data = SiteStorage::read(self::LOGIN_ATTEMPTS_FILE, ['attempts' => []]);
        return is_array($data['attempts'] ?? null) ? $data['attempts'] : [];
    }

    private static function writeLoginAttempts(array $attempts): void
    {
        SiteStorage::write(self::LOGIN_ATTEMPTS_FILE, ['attempts' => $attempts]);
    }

    private static function clientIp(): string
    {
        $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        return $ip !== '' ? $ip : 'unknown';
    }

    public static function changePassword(string $currentPassword, string $newPassword): array
    {
        $user = self::currentUser();
        if (!$user) {
            return ['success' => false, 'message' => 'Sesión no válida'];
        }

        if (strlen($newPassword) < 8) {
            return ['success' => false, 'message' => 'La nueva contraseña debe tener al menos 8 caracteres'];
        }

        $data = SiteStorage::read(self::USERS_FILE, ['users' => []]);
        $updated = false;

        foreach ($data['users'] as &$entry) {
            if (($entry['username'] ?? '') !== ($user['username'] ?? '')) {
                continue;
            }
            if (!password_verify($currentPassword, (string) ($entry['password_hash'] ?? ''))) {
                return ['success' => false, 'message' => 'La contraseña actual no es correcta'];
            }
            $entry['password_hash'] = password_hash($newPassword, PASSWORD_DEFAULT);
            $updated = true;
            break;
        }
        unset($entry);

        if (!$updated) {
            return ['success' => false, 'message' => 'Usuario no encontrado'];
        }

        if (!SiteStorage::write(self::USERS_FILE, $data)) {
            return ['success' => false, 'message' => 'No se pudo guardar la nueva contraseña'];
        }

        return ['success' => true, 'message' => 'Contraseña actualizada'];
    }

}
