<?php
define('BASE_PATH', __DIR__);
define('BASE_URL', 'http://alertas.local.com/');

// ── Cargar helpers de seguridad ANTES de session_start ───────────────
require_once BASE_PATH . '/config/Security.php';
Security::configureSession();
session_start();

// ── Headers de seguridad HTTP ────────────────────────────────────────
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
// Content-Security-Policy básico (ajusta si usas CDNs externos)
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'");

// ── Autoloads ────────────────────────────────────────────────────────
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/models/TicketModel.php';
require_once BASE_PATH . '/models/TecnicoModel.php';
require_once BASE_PATH . '/models/HorarioModel.php';
require_once BASE_PATH . '/models/UsuarioModel.php';
require_once BASE_PATH . '/controller/AuthController.php';
require_once BASE_PATH . '/controller/TicketController.php';
require_once BASE_PATH . '/controller/TableroController.php';

$action = $_GET['action'] ?? 'tablero';
$ip     = Security::clientIp();

// ── Rutas públicas (sin sesión requerida) ────────────────────────────
if ($action === 'login') {
    $ctrl = new AuthController();
    $ctrl->login();
    exit;
}
if ($action === 'logout') {
    $ctrl = new AuthController();
    $ctrl->logout();
    exit;
}

// ── Validar sesión activa en TODAS las rutas protegidas ──────────────
if (!Security::validateSession($ip)) {
    // Distinguir entre sesión expirada y nunca autenticado
    $msg = isset($_SESSION['usuario']) ? '?action=login&msg=expired' : '?action=login';
    Security::destroySession();
    session_start(); // reiniciar sesión limpia para token CSRF del login
    header('Location: ' . BASE_URL . $msg);
    exit;
}

// ── Enrutador principal ──────────────────────────────────────────────
switch ($action) {
    case 'tablero':
        $ctrl = new TableroController();
        $ctrl->index();
        break;

    case 'ticket.store':
        $ctrl = new TicketController();
        $ctrl->store();
        break;

    case 'ticket.show':
        $ctrl = new TicketController();
        $ctrl->show();
        break;

    case 'ticket.update':
        $ctrl = new TicketController();
        $ctrl->update();
        break;

    default:
        http_response_code(404);
        echo 'Página no encontrada';
}
