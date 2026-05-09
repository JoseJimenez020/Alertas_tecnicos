<?php
class TableroController
{

    public function index(): void
    {
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        $fechaHoy = date('Y-m-d');

        $horarioModel = new HorarioModel();
        $tecnicoModel = new TecnicoModel();
        $ticketModel = new TicketModel();
        $usuarioModel = new UsuarioModel();
        $bloqueModel = new BloqueModel();
        $horarios = $horarioModel->getAll();
        $tecnicosAll = $tecnicoModel->getAllActive(); // ← Solo activos
        $tecnicosGroup = $tecnicoModel->getGroupedByZona();
        $tickets = $ticketModel->getByDate($fecha);
        $usuario = $_SESSION['usuario'];

        $todosUsuarios = $usuarioModel->getAll();
        $userColorMap = [];
        foreach ($todosUsuarios as $u) {
            $userColorMap[(int) $u['usu_id']] = $u['color'];
        }

        $tecIds = array_column($tecnicosAll, 'TecnicoId');

        $bloquesDia = $bloqueModel->getActivosEnRango($tecIds, $fecha, $fecha);
        $bloqueosCelda = [];
        foreach ($bloquesDia as $tecId => $lista) {
            foreach ($lista as $b) {
                if ($b['horas_ids'] === null) {
                    $bloqueosCelda[$tecId]['_todo'] = true;
                } else {
                    foreach ($b['horas_ids'] as $hid) {
                        $bloqueosCelda[$tecId][(int) $hid] = true;
                    }
                }
            }
        }

        $bloquesDiaHoy = $bloqueModel->getActivosEnRango($tecIds, $fechaHoy, $fechaHoy);
        $bloqueosCeldaHoy = [];
        foreach ($bloquesDiaHoy as $tecId => $lista) {
            foreach ($lista as $b) {
                if ($b['horas_ids'] === null) {
                    $bloqueosCeldaHoy[$tecId]['_todo'] = true;
                } else {
                    foreach ($b['horas_ids'] as $hid) {
                        $bloqueosCeldaHoy[$tecId][(int) $hid] = true;
                    }
                }
            }
        }

        require BASE_PATH . '/views/Home/index.php';
    }

    // ══════════════════════════════════════════════════════════════════
    // ENDPOINT DE POLLING — GET ?action=tablero.estado&fecha=YYYY-MM-DD
    //
    // Devuelve el estado actual de todos los tickets de una fecha.
    // El cliente compara el hash con el anterior; solo procesa si cambió.
    // ══════════════════════════════════════════════════════════════════
    public function estado(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');

        $fecha = $_GET['fecha'] ?? date('Y-m-d');

        // Validar formato de fecha para evitar inyecciones
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'Fecha inválida.']);
            exit;
        }

        $db = Database::getInstance()->getConnection();

        // ── Traer todos los tickets de la fecha con sus datos de color ──
        $stmt = $db->prepare("
            SELECT
                tt.ticket_id,
                tt.tecnico_id,
                tt.horario_id,
                tt.usuario_id,
                tt.estado,
                tt.tipo_ticket,
                tt.caja_puerto,
                tt.Cliente,
                tt.colonia,
                tt.Ticket,
                tt.Descripcion,
                tt.Telefono,
                u.rol_id   AS agente_rol,
                u.nombre   AS agente_nombre,
                u.color    AS agente_color,
                COUNT(tl.llamada_id) AS total_llamadas,
                MAX(tl.es_calidad)   AS calidad_hecha
            FROM tm_ticket tt
            JOIN tm_usuarios u  ON u.usu_id    = tt.usuario_id
            LEFT JOIN tm_llamadas tl ON tl.ticket_id = tt.ticket_id
            WHERE tt.fecha = :fecha
            GROUP BY tt.ticket_id
        ");
        $stmt->execute([':fecha' => $fecha]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // ── Indexar por "tecnico_id_horario_id" para búsqueda rápida en JS ──
        $tickets = [];
        foreach ($rows as $r) {
            $key = $r['tecnico_id'] . '_' . $r['horario_id'];
            $tickets[$key] = [
                'ticket_id' => (int) $r['ticket_id'],
                'tecnico_id' => (int) $r['tecnico_id'],
                'horario_id' => (int) $r['horario_id'],
                'usuario_id' => (int) $r['usuario_id'],
                'agente_rol' => (int) $r['agente_rol'],
                'agente_color' => $r['agente_color'],
                'agente_nombre' => $r['agente_nombre'],
                'estado' => $r['estado'],
                'tipo_ticket' => (int) ($r['tipo_ticket'] ?? 1),
                'total_llamadas' => (int) $r['total_llamadas'],
                'calidad_hecha'  => (int) ($r['calidad_hecha'] ?? 0),
            ];
        }

        // ── Hash para detección de cambios eficiente ──────────────────
        // md5 del JSON serializado: si cualquier campo cambia, el hash cambia.
        $hash = md5(json_encode($tickets));

        // ── Si el cliente ya tiene este hash, responder mínimo ─────────
        $clientHash = $_GET['hash'] ?? '';
        if ($clientHash !== '' && $clientHash === $hash) {
            echo json_encode(['success' => true, 'changed' => false, 'hash' => $hash]);
            exit;
        }

        echo json_encode([
            'success' => true,
            'changed' => true,
            'hash' => $hash,
            'fecha' => $fecha,
            'tickets' => $tickets,
        ]);
        exit;
    }
}
