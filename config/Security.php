<?php
/**
 * Security.php
 * Centraliza todas las utilidades de seguridad del sistema.
 */
class Security {

    // ── Configuración ─────────────────────────────────────────────────
    private const MAX_ATTEMPTS  = 5;       // intentos fallidos antes de bloqueo
    private const LOCKOUT_TIME  = 900;     // segundos de bloqueo (15 min)
    private const SESSION_LIFE  = 7200;    // tiempo máximo de sesión (2 h)
    private const IDLE_TIME     = 1800;    // tiempo máximo de inactividad (30 min)
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

    /**
     * Valida el token CSRF enviado.
     * Usa hash_equals para prevenir timing attacks.
     */
    public static function verifyCsrf(string $token): bool {
        $stored = $_SESSION['csrf_token'] ?? '';
        return $stored !== '' && hash_equals($stored, $token);
    }

    /** Campo oculto listo para insertar en formularios. */
    public static function csrfField(): string {
        return '<input type="hidden" name="csrf_token" value="'
            . htmlspecialchars(self::csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
    }

    // ══════════════════════════════════════════════════════════════════
    // RATE LIMITING (almacenado en sesión por IP)
    // ══════════════════════════════════════════════════════════════════

    /**
     * Registra un intento fallido de login para la IP dada.
     * Almacena los datos en un archivo temporal seguro por IP.
     */
    public static function recordFailedAttempt(string $ip): void {
        $data = self::readAttemptData($ip);
        $data['count']++;
        $data['last'] = time();
        if ($data['count'] >= self::MAX_ATTEMPTS) {
            $data['locked_until'] = time() + self::LOCKOUT_TIME;
        }
        self::writeAttemptData($ip, $data);
    }

    /** Limpia el contador de intentos tras un login exitoso. */
    public static function clearAttempts(string $ip): void {
        $file = self::attemptFile($ip);
        if (file_exists($file)) unlink($file);
    }

    /**
     * Retorna null si la IP puede intentar, o los segundos restantes
     * de bloqueo si está bloqueada.
     */
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

    /** Número de intentos fallidos actuales para una IP. */
    public static function getAttempts(string $ip): int {
        return self::readAttemptData($ip)['count'] ?? 0;
    }

    // ── Persistencia de intentos ─────────────────────────────────────

    private static function attemptFile(string $ip): string {
        $dir = sys_get_temp_dir() . '/alertas_attempts';
        if (!is_dir($dir)) mkdir($dir, 0700, true);
        // Hash de la IP para no exponer la IP en el nombre del archivo
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

    /**
     * Configura la sesión con parámetros de seguridad endurecidos.
     * Debe llamarse ANTES de session_start().
     */
    public static function configureSession(): void {
        // Cookie solo HTTP, sin acceso JS
        ini_set('session.cookie_httponly', '1');
        // Cookie solo sobre HTTPS en producción
        ini_set('session.cookie_secure',   self::isHttps() ? '1' : '0');
        // Previene que el session ID viaje en la URL
        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_trans_sid',    '0');
        // SameSite=Strict para prevenir CSRF via cookie
        ini_set('session.cookie_samesite', 'Strict');
        // Usa SHA-256 para el ID de sesión
        ini_set('session.sid_length',       '64');
        ini_set('session.sid_bits_per_character', '6');
        // Regenerar ID automáticamente cada N segundos (maneja en código)
        session_name('ALERTAS_SID');
    }

    /**
     * Inicializa la sesión del usuario autenticado con datos de huella digital.
     * Regenera el ID de sesión para prevenir session fixation.
     */
    public static function startAuthSession(array $userData, string $ip): void {
        // Regenerar ID elimina el ID anterior (previene session fixation)
        session_regenerate_id(true);

        $_SESSION['usuario']        = $userData;
        $_SESSION['csrf_token']     = bin2hex(random_bytes(self::TOKEN_BYTES));
        $_SESSION['created_at']     = time();
        $_SESSION['last_activity']  = time();
        $_SESSION['ip']             = hash('sha256', $ip);          // huella IP
        $_SESSION['ua']             = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? ''); // huella UA
    }

    /**
     * Valida que la sesión activa sea legítima.
     * Verifica: expiración absoluta, inactividad, huella IP/UA.
     * Retorna true si la sesión es válida, false si debe destruirse.
     */
    public static function validateSession(string $ip): bool {
        // Sin sesión iniciada
        if (empty($_SESSION['usuario'])) return false;

        // Expiración absoluta
        if ((time() - ($_SESSION['created_at'] ?? 0)) > self::SESSION_LIFE) {
            self::destroySession();
            return false;
        }

        // Inactividad
        if ((time() - ($_SESSION['last_activity'] ?? 0)) > self::IDLE_TIME) {
            self::destroySession();
            return false;
        }

        // Huella IP
        if (($_SESSION['ip'] ?? '') !== hash('sha256', $ip)) {
            self::destroySession();
            return false;
        }

        // Huella User-Agent
        if (($_SESSION['ua'] ?? '') !== hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '')) {
            self::destroySession();
            return false;
        }

        // Actualizar timestamp de actividad
        $_SESSION['last_activity'] = time();
        return true;
    }

    /** Destruye completamente la sesión y borra la cookie. */
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

    /** IP real del cliente (considera proxies confiables). */
    public static function clientIp(): string {
        // Solo confiar en X-Forwarded-For si estás detrás de un proxy conocido.
        // En producción, ajusta esto según tu infraestructura.
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /** Detecta si la conexión es HTTPS. */
    public static function isHttps(): bool {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
    }

    /**
     * Hashea una contraseña usando bcrypt con costo 12.
     * Úsalo al registrar o cambiar contraseñas.
     */
    public static function hashPassword(string $plain): string {
        return password_hash($plain, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /** Verifica contraseña y detecta si el hash necesita re-hasheo. */
    public static function verifyPassword(string $plain, string $hash): bool {
        return password_verify($plain, $hash);
    }

    public static function needsRehash(string $hash): bool {
        return password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /** Sanitiza una cadena para salida HTML. */
    public static function e(string $str): string {
        return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /** Minutos y segundos restantes de un bloqueo en texto legible. */
    public static function formatLockout(int $seconds): string {
        $m = intdiv($seconds, 60);
        $s = $seconds % 60;
        return $m > 0 ? "{$m} min {$s} seg" : "{$s} seg";
    }
}
