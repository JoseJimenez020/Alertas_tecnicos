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
$ip = Security::clientIp();

if ($action === 'login') {
    (new AuthController())->login();
    exit;
}
if ($action === 'logout') {
    (new AuthController())->logout();
    exit;
}

if (!Security::validateSession($ip)) {
    $msg = isset($_SESSION['usuario']) ? '?action=login&msg=expired' : '?action=login';
    Security::destroySession();
    session_start();
    header('Location: ' . BASE_URL . $msg);
    exit;
}

switch ($action) {
    case 'tablero':
        (new TableroController())->index();
        break;

    case 'ticket.store':
        (new TicketController())->store();
        break;
    case 'ticket.show':
        (new TicketController())->show();
        break;
    case 'ticket.update':
        (new TicketController())->update();
        break;
    case 'ticket.delete':
        (new TicketController())->delete();
    case 'ticket.reschedule':
        (new TicketController())->reschedule();
        break;
    case 'ticket.getSlots':
        (new TicketController())->getSlots();
        break;
    case 'ticket.setEstado':
        (new TicketController())->setEstado();
        break;

    case 'llamada.upsert':
        (new TicketController())->upsertLlamada();
        break;

    case 'notif.tickets':
        (new NotifController())->tickets();
        break;

    case 'admin.usuarios':
        (new AdminController())->usuarios();
        break;
    case 'admin.reporte':
        (new AdminController())->reporte();
        break;
    case 'admin.usuario.store':
        (new AdminController())->storeUsuario();
        break;
    case 'admin.usuario.update':
        (new AdminController())->updateUsuario();
        break;
    case 'admin.usuario.delete':
        (new AdminController())->deleteUsuario();
        break;

    case 'tecnicos.panel':
        (new TecnicoController())->panel();
        break;
    case 'tecnico.store':
        (new TecnicoController())->store();
        break;
    case 'tecnico.update':
        (new TecnicoController())->update();
        break;
    case 'tecnico.status':
        (new TecnicoController())->setStatus();
        break;
    case 'tecnico.delete':
        (new TecnicoController())->delete();
        break;

    case 'horarios.panel':
        (new HorarioController())->panel();
        break;
    case 'horario.store':
        (new HorarioController())->store();
        break;
    case 'horario.delete':
        (new HorarioController())->delete();
        break;

    default:
        http_response_code(404);
        echo 'Página no encontrada';
}
