<?php
class TableroController {

    public function index(): void {
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        $fechaHoy = date('Y-m-d'); // Siempre la fecha real del servidor

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

        $tecIds = array_column($tecnicosAll, 'TecnicoId');

        // ── Bloqueos para la fecha del TABLERO (colorear celdas y cabeceras) ──
        $bloquesDia    = $bloqueModel->getActivosEnRango($tecIds, $fecha, $fecha);
        // bloqueosCelda[tecnico_id][horario_id] = true  → celda bloqueada
        // bloqueosCelda[tecnico_id]['_todo']    = true  → técnico completo bloqueado ese día
        $bloqueosCelda = [];
        foreach ($bloquesDia as $tecId => $lista) {
            foreach ($lista as $b) {
                if ($b['horas_ids'] === null) {
                    $bloqueosCelda[$tecId]['_todo'] = true;
                } else {
                    foreach ($b['horas_ids'] as $hid) {
                        $bloqueosCelda[$tecId][(int)$hid] = true;
                    }
                }
            }
        }

        // ── Bloqueos para HOY (lista inferior de técnicos — siempre fecha actual) ──
        // Son independientes de la fecha del tablero para que la lista muestre
        // el estado real de disponibilidad de hoy, no el de la fecha consultada.
        $bloquesDiaHoy    = $bloqueModel->getActivosEnRango($tecIds, $fechaHoy, $fechaHoy);
        $bloqueosCeldaHoy = [];
        foreach ($bloquesDiaHoy as $tecId => $lista) {
            foreach ($lista as $b) {
                if ($b['horas_ids'] === null) {
                    $bloqueosCeldaHoy[$tecId]['_todo'] = true;
                } else {
                    foreach ($b['horas_ids'] as $hid) {
                        $bloqueosCeldaHoy[$tecId][(int)$hid] = true;
                    }
                }
            }
        }

        require BASE_PATH . '/views/Home/index.php';
    }
}
