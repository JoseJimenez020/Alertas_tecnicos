<?php
class TableroController {

    public function index(): void {
        $fecha = $_GET['fecha'] ?? date('Y-m-d');

        $horarioModel = new HorarioModel();
        $tecnicoModel = new TecnicoModel();
        $ticketModel  = new TicketModel();
        $usuarioModel = new UsuarioModel();
        $bloqueModel  = new BloqueModel();

        $horarios    = $horarioModel->getAll();
        $tecnicosAll = $tecnicoModel->getAll();
        $tecnicosGroup = $tecnicoModel->getGroupedByZona();
        $tickets     = $ticketModel->getByDate($fecha);
        $usuario     = $_SESSION['usuario'];

        // Mapa dinámico de colores: usuario_id → clase CSS
        $todosUsuarios = $usuarioModel->getAll();
        $userColorMap  = [];
        foreach ($todosUsuarios as $u) {
            $userColorMap[(int) $u['usu_id']] = $u['color'];
        }

        // ── Bloqueos del día del tablero ──────────────────────────
        // Estructura: [tecnico_id][] = ['horas_ids' => [1,3,5]|null, ...]
        // null en horas_ids = día completo bloqueado
        $tecIds        = array_column($tecnicosAll, 'TecnicoId');
        $bloquesDia    = $bloqueModel->getActivosEnRango($tecIds, $fecha, $fecha);
        // bloqueosPorHora[tecnico_id][horario_id] = true   → celda bloqueada
        // bloqueosPorHora[tecnico_id]['_todo'] = true       → técnico completo bloqueado ese día
        $bloqueosCelda = [];
        foreach ($bloquesDia as $tecId => $lista) {
            foreach ($lista as $b) {
                if ($b['horas_ids'] === null) {
                    // Bloqueo total del día (vacaciones, apoyo, mecánico con "todas")
                    $bloqueosCelda[$tecId]['_todo'] = true;
                } else {
                    foreach ($b['horas_ids'] as $hid) {
                        $bloqueosCelda[$tecId][(int)$hid] = true;
                    }
                }
            }
        }

        require BASE_PATH . '/views/Home/index.php';
    }
}