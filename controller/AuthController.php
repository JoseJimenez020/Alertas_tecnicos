<?php
class AuthController {

    public function login(): void {
        $ip = Security::clientIp();

        // ── Verificar bloqueo por intentos fallidos ──────────────────
        $lockout = Security::checkLockout($ip);
        if ($lockout !== null) {
            $error = 'Demasiados intentos fallidos. Intenta de nuevo en '
                . Security::formatLockout($lockout) . '.';
            require BASE_PATH . '/views/login/index.php';
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            require BASE_PATH . '/views/login/index.php';
            return;
        }

        // ── Validar CSRF ─────────────────────────────────────────────
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!Security::verifyCsrf($csrfToken)) {
            $error = 'Token de seguridad inválido. Recarga la página e intenta de nuevo.';
            require BASE_PATH . '/views/login/index.php';
            return;
        }

        // ── Sanitizar entradas ───────────────────────────────────────
        $usuario  = trim($_POST['usuario']  ?? '');
        $password = $_POST['password']      ?? '';   // NO trim en contraseñas

        // Longitud máxima para prevenir ataques de DoS en bcrypt
        if (strlen($password) > 72 || strlen($usuario) > 255) {
            $error = 'Datos de acceso inválidos.';
            Security::recordFailedAttempt($ip);
            require BASE_PATH . '/views/login/index.php';
            return;
        }

        if ($usuario === '' || $password === '') {
            $error = 'Ingresa tu usuario y contraseña.';
            require BASE_PATH . '/views/login/index.php';
            return;
        }

        // ── Consultar usuario ────────────────────────────────────────
        $model = new UsuarioModel();
        $user  = $model->findByUsuario($usuario);

        /*
         * Verificamos la contraseña SIEMPRE (incluso si el usuario no existe)
         * contra un hash dummy para que el tiempo de respuesta sea constante
         * y no revele si el usuario existe (timing attack mitigation).
         */
        $dummyHash  = '$2y$12$invalidsaltXXXXXXXXXXXXXXuhash0000000000000000000000000';
        $hashToCheck = $user ? $user['password'] : $dummyHash;
        $passwordOk  = Security::verifyPassword($password, $hashToCheck);

        if (!$user || !$passwordOk) {
            Security::recordFailedAttempt($ip);
            $attempts  = Security::getAttempts($ip);
            $remaining = max(0, 5 - $attempts);

            // Mensaje genérico — no revela si el usuario existe
            $error = 'Credenciales incorrectas.';
            if ($remaining > 0 && $remaining <= 2) {
                $error .= " Te quedan {$remaining} intento(s) antes del bloqueo temporal.";
            }

            require BASE_PATH . '/views/Login/index.php';
            return;
        }

        // ── Login exitoso ────────────────────────────────────────────

        // Re-hashear si el costo de bcrypt cambió
        if (Security::needsRehash($user['password'])) {
            $model->updatePassword((int) $user['usu_id'], Security::hashPassword($password));
        }

        Security::clearAttempts($ip);

        Security::startAuthSession([
            'id'      => (int) $user['usu_id'],
            'nombre'  => $user['nombre'],
            'rol_id'  => (int) $user['rol_id'],
            'usuario' => $user['usuario'],
        ], $ip);

        header('Location: ' . BASE_URL . '?action=tablero');
        exit;
    }

    public function logout(): void {
        Security::destroySession();
        header('Location: ' . BASE_URL . '?action=login&msg=logout');
        exit;
    }
}
