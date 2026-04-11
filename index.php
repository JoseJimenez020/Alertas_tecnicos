<?php
define('BASE_PATH', __DIR__);
define('BASE_URL', 'http://alertas.local.com/');

require_once BASE_PATH . '/config/Security.php';
Security::configureSession();
session_start();

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'");

// ── Autoloads ────────────────────────────────────────────────────────
require_once BASE_PATH . '/config/database.php';
require_once BASE_PATH . '/models/TicketModel.php';
require_once BASE_PATH . '/models/TecnicoModel.php';
require_once BASE_PATH . '/models/HorarioModel.php';
require_once BASE_PATH . '/models/UsuarioModel.php';
require_once BASE_PATH . '/models/LlamadaModel.php';
require_once BASE_PATH . '/controller/AuthController.php';
require_once BASE_PATH . '/controller/TicketController.php';
require_once BASE_PATH . '/controller/TableroController.php';
require_once BASE_PATH . '/controller/AdminController.php';
require_once BASE_PATH . '/controller/TecnicoController.php';
require_once BASE_PATH . '/controller/HorarioController.php';
require_once BASE_PATH . '/controller/NotifController.php';

$action = $_GET['action'] ?? 'tablero';
$ip     = Security::clientIp();

// ── Rutas públicas ───────────────────────────────────────────────────
if ($action === 'login')  { (new AuthController())->login();  exit; }
if ($action === 'logout') { (new AuthController())->logout(); exit; }

// ── Validar sesión ───────────────────────────────────────────────────
if (!Security::validateSession($ip)) {
    $msg = isset($_SESSION['usuario']) ? '?action=login&msg=expired' : '?action=login';
    Security::destroySession();
    session_start();
    header('Location: ' . BASE_URL . $msg);
    exit;
}

// ── Enrutador ────────────────────────────────────────────────────────
switch ($action) {

    case 'tablero':
        (new TableroController())->index(); break;

    // Tickets
    case 'ticket.store':  (new TicketController())->store();  break;
    case 'ticket.show':   (new TicketController())->show();   break;
    case 'ticket.update': (new TicketController())->update(); break;

    // Llamadas
    case 'llamada.upsert': (new TicketController())->upsertLlamada(); break;

    // Notificaciones (polling JS)
    case 'notif.tickets': (new NotifController())->tickets(); break;

    // Admin: Usuarios (rol 4)
    case 'admin.usuarios':       (new AdminController())->usuarios();       break;
    case 'admin.usuario.store':  (new AdminController())->storeUsuario();   break;
    case 'admin.usuario.update': (new AdminController())->updateUsuario();  break;
    case 'admin.usuario.delete': (new AdminController())->deleteUsuario();  break;

    // Técnicos (rol 2)
    case 'tecnicos.panel':  (new TecnicoController())->panel();     break;
    case 'tecnico.store':   (new TecnicoController())->store();     break;
    case 'tecnico.update':  (new TecnicoController())->update();    break;
    case 'tecnico.status':  (new TecnicoController())->setStatus(); break;
    case 'tecnico.delete':  (new TecnicoController())->delete();    break;

    // Horarios (rol 2 y 4)
    case 'horarios.panel':  (new HorarioController())->panel();  break;
    case 'horario.store':   (new HorarioController())->store();  break;
    case 'horario.delete':  (new HorarioController())->delete(); break;

    default:
        http_response_code(404);
        echo 'Página no encontrada';
}
