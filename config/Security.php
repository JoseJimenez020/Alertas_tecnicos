<?php
/**
 * Security.php
 * Centraliza todas las utilidades de seguridad del sistema.
 */
class Security {

    // ── Configuración ─────────────────────────────────────────────────
    private const MAX_ATTEMPTS  = 5;       // intentos fallidos antes de bloqueo
    private const LOCKOUT_TIME  = 900;     // segundos de bloqueo (15 min)
    private const SESSION_LIFE  = 14400;   // tiempo máximo de sesión (4 h)
    private const IDLE_TIME     = 14400;   // tiempo máximo de inactividad (4 h)
    private const TOKEN_BYTES   = 32;      // bytes para CSRF token

    // ══════════════════════════════════════════════════════════════════
    // CSRF
    // ══════════════════════════════════════════════════════════════════

    /** Genera (o reutiliza) el token CSRF de la sesión. */
    public static function csrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(self::TOKEN_BYTES));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrf(string $token): bool {
        $stored = $_SESSION['csrf_token'] ?? '';
        return $stored !== '' && hash_equals($stored, $token);
    }

    public static function csrfField(): string {
        return '<input type="hidden" name="csrf_token" value="'
            . htmlspecialchars(self::csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
    }

    // ══════════════════════════════════════════════════════════════════
    // RATE LIMITING
    // ══════════════════════════════════════════════════════════════════

    public static function recordFailedAttempt(string $ip): void {
        $data = self::readAttemptData($ip);
        $data['count']++;
        $data['last'] = time();
        if ($data['count'] >= self::MAX_ATTEMPTS) {
            $data['locked_until'] = time() + self::LOCKOUT_TIME;
        }
        self::writeAttemptData($ip, $data);
    }

    public static function clearAttempts(string $ip): void {
        $file = self::attemptFile($ip);
        if (file_exists($file)) unlink($file);
    }

    public static function checkLockout(string $ip): ?int {
        $data = self::readAttemptData($ip);
        if (!isset($data['locked_until'])) return null;
        $remaining = $data['locked_until'] - time();
        if ($remaining <= 0) {
            self::clearAttempts($ip);
            return null;
        }
        return $remaining;
    }

    public static function getAttempts(string $ip): int {
        return self::readAttemptData($ip)['count'] ?? 0;
    }

    private static function attemptFile(string $ip): string {
        $dir = sys_get_temp_dir() . '/alertas_attempts';
        if (!is_dir($dir)) mkdir($dir, 0700, true);
        return $dir . '/' . hash('sha256', $ip) . '.json';
    }

    private static function readAttemptData(string $ip): array {
        $file = self::attemptFile($ip);
        if (!file_exists($file)) return ['count' => 0];
        $data = json_decode(file_get_contents($file), true);
        return is_array($data) ? $data : ['count' => 0];
    }

    private static function writeAttemptData(string $ip, array $data): void {
        file_put_contents(
            self::attemptFile($ip),
            json_encode($data),
            LOCK_EX
        );
    }

    // ══════════════════════════════════════════════════════════════════
    // SESIÓN SEGURA
    // ══════════════════════════════════════════════════════════════════

    public static function configureSession(): void {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure',   self::isHttps() ? '1' : '0');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid',    '0');
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.sid_length',       '64');
        ini_set('session.sid_bits_per_character', '6');
        // Duración de la cookie de sesión alineada con SESSION_LIFE
        ini_set('session.cookie_lifetime', (string) self::SESSION_LIFE);
        session_name('ALERTAS_SID');
    }

    public static function startAuthSession(array $userData, string $ip): void {
        session_regenerate_id(true);
        $_SESSION['usuario']        = $userData;
        $_SESSION['csrf_token']     = bin2hex(random_bytes(self::TOKEN_BYTES));
        $_SESSION['created_at']     = time();
        $_SESSION['last_activity']  = time();
        $_SESSION['ip']             = hash('sha256', $ip);
        $_SESSION['ua']             = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
    }

    public static function validateSession(string $ip): bool {
        if (empty($_SESSION['usuario'])) return false;

        if ((time() - ($_SESSION['created_at'] ?? 0)) > self::SESSION_LIFE) {
            self::destroySession();
            return false;
        }

        if ((time() - ($_SESSION['last_activity'] ?? 0)) > self::IDLE_TIME) {
            self::destroySession();
            return false;
        }

        if (($_SESSION['ip'] ?? '') !== hash('sha256', $ip)) {
            self::destroySession();
            return false;
        }

        if (($_SESSION['ua'] ?? '') !== hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '')) {
            self::destroySession();
            return false;
        }

        $_SESSION['last_activity'] = time();
        return true;
    }

    public static function destroySession(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(
                session_name(), '', time() - 42000,
                $p['path'], $p['domain'],
                $p['secure'], $p['httponly']
            );
        }
        session_destroy();
    }

    // ══════════════════════════════════════════════════════════════════
    // HELPERS
    // ══════════════════════════════════════════════════════════════════

    public static function clientIp(): string {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public static function isHttps(): bool {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
    }

    public static function hashPassword(string $plain): string {
        return password_hash($plain, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function verifyPassword(string $plain, string $hash): bool {
        return password_verify($plain, $hash);
    }

    public static function needsRehash(string $hash): bool {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function e(string $str): string {
        return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public static function formatLockout(int $seconds): string {
        $m = intdiv($seconds, 60);
        $s = $seconds % 60;
        return $m > 0 ? "{$m} min {$s} seg" : "{$s} seg";
    }
}
