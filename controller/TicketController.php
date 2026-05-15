<?php
class TicketController
{

    public function store(): void
    {
        $this->requireJson();
        $usuario = $_SESSION['usuario'];
        if (!in_array($usuario['rol_id'], [1, 2, 3, 6]))
            $this->jsonError('Sin permisos.', 403);

        $body = $this->jsonBody();
        foreach (['fecha', 'horario_id', 'tecnico_id', 'cliente', 'ticket_num', 'descripcion', 'telefono'] as $f) {
            if (empty($body[$f]))
                $this->jsonError("El campo {$f} es requerido.", 422);
        }
        if (strlen($body['telefono']) !== 10 || !ctype_digit($body['telefono']))
            $this->jsonError('El teléfono debe tener 10 dígitos numéricos.', 422);

        $dow = (int) date('w', strtotime($body['fecha']));
        $tecnicoPermiteDomingo = ((int) $body['tecnico_id'] === 11);
        if ($dow === 0 && !$tecnicoPermiteDomingo)
            $this->jsonError('No se pueden asignar tickets en domingo.', 422);

        $model = new TicketModel();
        if ($model->exists((int) $body['tecnico_id'], (int) $body['horario_id'], $body['fecha']))
            $this->jsonError('Ya existe un ticket para ese técnico en ese horario.', 409);

        // Validar el tipo de ticket asegurando que si no es el usuario 2, forzosamente sea 1.
        $tipo_ticket = isset($body['tipo_ticket']) ? (int) $body['tipo_ticket'] : 1;
        $puedeCrearTipo2 = ((int) $usuario['id'] === 2 || (int) $usuario['rol_id'] === 6);
        if ($tipo_ticket === 2 && !$puedeCrearTipo2) {
            $tipo_ticket = 1;
        }
        $caja_puerto = $body['caja_puerto'] ?? '';

        $id = $model->create([
            'usuario_id' => $usuario['id'],
            'fecha' => $body['fecha'],
            'horario_id' => (int) $body['horario_id'],
            'tecnico_id' => (int) $body['tecnico_id'],
            'cliente' => $body['cliente'],
            'colonia' => $body['colonia'] ?? '',
            'ticket_num' => $body['ticket_num'],
            'descripcion' => $body['descripcion'],
            'telefono' => $body['telefono'],
            'tipo_ticket' => $tipo_ticket,
            'caja_puerto' => $caja_puerto,
        ]);
        if ($tipo_ticket === 2) {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
            INSERT INTO tm_ticket_mesa (ticket_id, num_ticket, caja_puerto)
            VALUES (:tid, :nt, :cp)
            ON DUPLICATE KEY UPDATE num_ticket = VALUES(num_ticket), caja_puerto = VALUES(caja_puerto)
        ");
            $stmt->execute([
                ':tid' => $id,
                ':nt' => $body['ticket_num'],
                ':cp' => $body['caja_puerto'] ?? '',
            ]);
        }

        WsNotifier::send('ticket.changed', ['fecha' => $body['fecha']]);
        $this->jsonSuccess(['ticket_id' => $id, 'rol_id' => $usuario['rol_id']]);
    }

    public function show(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $model = new TicketModel();
        $ticket = $model->findById($id);
        if (!$ticket)
            $this->jsonError('Ticket no encontrado.', 404);

        $usuario = $_SESSION['usuario'];
        $ticket['can_edit'] = in_array($usuario['rol_id'], [3, 4]);

        $llamadaModel = new LlamadaModel();
        $ticket['llamadas'] = $llamadaModel->getByTicket($id);
        $this->jsonSuccess($ticket);
    }

    public function update(): void
    {
        $this->requireJson();
        $usuario = $_SESSION['usuario'];
        if (!in_array($usuario['rol_id'], [3, 4]))
            $this->jsonError('Sin permisos para editar tickets.', 403);

        $body = $this->jsonBody();
        $id = (int) ($body['ticket_id'] ?? 0);
        if (!$id)
            $this->jsonError('ID de ticket inválido.', 422);

        // Validar el tipo de ticket asegurando que si no es el usuario 2, forzosamente sea 1.
        $tipo_ticket = isset($body['tipo_ticket']) ? (int) $body['tipo_ticket'] : 1;
        if ($tipo_ticket === 2 && (int) $usuario['id'] !== 2) {
            $tipo_ticket = 1;
        }
        $caja_puerto = $body['caja_puerto'] ?? '';

        $model = new TicketModel();
        $ticket = $model->findById($id);
        if (!$ticket)
            $this->jsonError('Ticket no encontrado.', 404);

        foreach (['cliente', 'ticket_num', 'descripcion', 'telefono', 'horario_id', 'tecnico_id'] as $f) {
            if (empty($body[$f]))
                $this->jsonError("El campo {$f} es requerido.", 422);
        }
        $model->update($id, [
            'cliente' => $body['cliente'],
            'colonia' => $body['colonia'] ?? '',
            'ticket_num' => $body['ticket_num'],
            'descripcion' => $body['descripcion'],
            'telefono' => $body['telefono'],
            'horario_id' => (int) $body['horario_id'],
            'tecnico_id' => (int) $body['tecnico_id'],
            'tipo_ticket' => $tipo_ticket,
            'caja_puerto' => $caja_puerto,
        ]);
        if ($tipo_ticket === 2) {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("
            INSERT INTO tm_ticket_mesa (ticket_id, num_ticket, caja_puerto)
            VALUES (:tid, :nt, :cp)
            ON DUPLICATE KEY UPDATE num_ticket = VALUES(num_ticket), caja_puerto = VALUES(caja_puerto)
        ");
            $stmt->execute([
                ':tid' => $id,
                ':nt' => $body['ticket_num'],
                ':cp' => $body['caja_puerto'] ?? '',
            ]);
        }
        WsNotifier::send('ticket.changed', ['fecha' => $ticket['fecha']]);
        $this->jsonSuccess(['updated' => true]);
    }

    /**
     * POST ?action=ticket.setEstado
     * Disponible para todos los roles (excepto rol 5).
     * Marca el ticket como 'terminado' o revierte a null.
     */
    public function setEstado(): void
    {
        $this->requireJson();
        $usuario = $_SESSION['usuario'];
        if ($usuario['rol_id'] == 5)
            $this->jsonError('Sin permisos.', 403);

        $body = $this->jsonBody();
        $id = (int) ($body['ticket_id'] ?? 0);
        $estado = $body['estado'] ?? null;

        if (!$id)
            $this->jsonError('ID de ticket inválido.', 422);
        if ($estado !== null && $estado !== 'terminado')
            $this->jsonError('Estado inválido.', 422);

        $model = new TicketModel();
        $ticket = $model->findById($id); // <- guardar el resultado
        if (!$ticket)
            $this->jsonError('Ticket no encontrado.', 404);

        $model->setEstado($id, $estado);
        WsNotifier::send('ticket.changed', ['fecha' => $ticket['fecha']]); // <- ahora sí existe
        $this->jsonSuccess(['estado' => $estado]);
    }

    public function getSlots(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        $id = (int) ($_GET['id'] ?? 0);
        if (!$id)
            $this->jsonError('ID de ticket inválido.', 422);

        $model = new TicketModel();
        $ticket = $model->findById($id);
        if (!$ticket)
            $this->jsonError('Ticket no encontrado.', 404);

        $dias = min(14, max(1, (int) ($_GET['dias'] ?? 5)));
        $fromFecha = date('Y-m-d');
        $tecnicos = $model->getAvailableSlotsForReschedule($id, $fromFecha, $dias);
        $this->jsonSuccess(['tecnicos' => $tecnicos]);
    }

    public function reschedule(): void
    {
        $this->requireJson();
        $body = $this->jsonBody();
        $ticketId = (int) ($body['ticket_id'] ?? 0);
        if (!$ticketId)
            $this->jsonError('ID de ticket inválido.', 422);

        $model = new TicketModel();
        $ticket = $model->findById($ticketId);
        if (!$ticket)
            $this->jsonError('Ticket no encontrado.', 404);

        if (!empty($body['tecnico_id']) && !empty($body['horario_id']) && !empty($body['fecha'])) {
            $newTecnicoId = (int) $body['tecnico_id'];
            $newHorarioId = (int) $body['horario_id'];
            $newFecha = $body['fecha'];

            $dow = (int) date('w', strtotime($body['fecha']));
            $tecnicoPermiteDomingo = ((int) $body['tecnico_id'] === 11);
            if ($dow === 0 && !$tecnicoPermiteDomingo)
                $this->jsonError('No se pueden asignar tickets en domingo.', 422);

            $stmt = Database::getInstance()->getConnection()->prepare("
                SELECT COUNT(*) FROM tm_ticket
                WHERE tecnico_id = :t AND horario_id = :h AND fecha = :f AND ticket_id != :excl
            ");
            $stmt->execute([':t' => $newTecnicoId, ':h' => $newHorarioId, ':f' => $newFecha, ':excl' => $ticketId]);
            if ((int) $stmt->fetchColumn() > 0)
                $this->jsonError('Ese horario ya está ocupado.', 409);

            $model->reschedule($ticketId, $newFecha, $newHorarioId, $newTecnicoId);
            WsNotifier::send('ticket.changed', ['fecha' => $ticket['fecha']]);
            $horarioModel = new HorarioModel();
            $horario = $horarioModel->findById($newHorarioId);
            $this->jsonSuccess([
                'ticket_id' => $ticketId,
                'nueva_fecha' => $newFecha,
                'nueva_fecha_fmt' => date('d/m/Y', strtotime($newFecha)),
                'nueva_hora' => $horario['hora'] ?? '',
                'nuevo_horario_id' => $newHorarioId,
                'nuevo_tecnico_id' => $newTecnicoId,
            ]);
        }

        $slot = $model->getNextAvailableSlot((int) $ticket['tecnico_id'], (int) $ticket['horario_id'], $ticket['fecha']);
        if (!$slot)
            $this->jsonError('No hay horario disponible en los próximos 30 días laborables.', 409);

        $model->reschedule($ticketId, $slot['fecha'], $slot['horario_id']);
        $this->jsonSuccess([
            'ticket_id' => $ticketId,
            'nueva_fecha' => $slot['fecha'],
            'nueva_fecha_fmt' => date('d/m/Y', strtotime($slot['fecha'])),
            'nueva_hora' => $slot['hora'],
            'nuevo_horario_id' => $slot['horario_id'],
            'nuevo_tecnico_id' => (int) $ticket['tecnico_id'],
        ]);
    }

    public function updateCajaPuerto(): void
    {
        $this->requireJson();
        $usuario = $_SESSION['usuario'];

        // Solo el usuario con ID=2 puede usar este endpoint
        if ((int) $usuario['id'] !== 2) {
            $this->jsonError('Sin permisos.', 403);
        }

        $body = $this->jsonBody();
        $id = (int) ($body['ticket_id'] ?? 0);
        $cajaPuerto = trim($body['caja_puerto'] ?? '');

        if (!$id)
            $this->jsonError('ID de ticket inválido.', 422);

        $model = new TicketModel();
        $ticket = $model->findById($id);
        if (!$ticket)
            $this->jsonError('Ticket no encontrado.', 404);
        if ((int) $ticket['tipo_ticket'] !== 2)
            $this->jsonError('El ticket no es de tipo Retiro de equipo.', 422);

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("UPDATE tm_ticket SET caja_puerto = :cp WHERE ticket_id = :id");
        WsNotifier::send('ticket.changed', ['fecha' => date('Y-m-d')]);
        $stmt->execute([':cp' => $cajaPuerto, ':id' => $id]);

        $this->jsonSuccess(['updated' => true]);
    }

    public function upsertLlamada(): void
    {
        $this->requireJson();
        $usuario = $_SESSION['usuario'];
        if (!in_array($usuario['rol_id'], [1, 2, 3, 4, 6]))
            $this->jsonError('Sin permisos.', 403);

        $body = $this->jsonBody();
        $ticketId = (int) ($body['ticket_id'] ?? 0);
        $noLlamada = (int) ($body['no_llamada'] ?? 0);
        if (!$ticketId || $noLlamada < 1 || $noLlamada > 4)   // ← 4 ahora es válido
            $this->jsonError('Datos inválidos.', 422);

        $ticketModel = new TicketModel();
        if (!$ticketModel->findById($ticketId))
            $this->jsonError('Ticket no encontrado.', 404);

        $llamadaModel = new LlamadaModel();
        $llamadaModel->upsert(
            $ticketId,
            $noLlamada,
            trim($body['respuesta_tecnico'] ?? ''),
            trim($body['respuesta_cliente'] ?? ''),
            (int) ($body['es_calidad'] ?? 0)          // ← nuevo campo
        );
        WsNotifier::send('ticket.changed', ['fecha' => date('Y-m-d')]);
        $this->jsonSuccess(['saved' => true]);
    }

    public function delete(): void
    {
        $this->requireJson();
        $usuario = $_SESSION['usuario'];

        if (!in_array($usuario['rol_id'], [1, 2, 3, 4, 6])) {
            $this->jsonError('Sin permisos para eliminar tickets.', 403);
        }

        $body = $this->jsonBody();
        $id = (int) ($body['ticket_id'] ?? 0);
        if (!$id)
            $this->jsonError('ID de ticket inválido.', 422);

        $model = new TicketModel();
        $ticket = $model->findById($id);
        if (!$ticket)
            $this->jsonError('Ticket no encontrado.', 404);

        $model->delete($id);
        WsNotifier::send('ticket.changed', ['fecha' => $ticket['fecha']]);
        $this->jsonSuccess(['deleted' => true]);
    }

    public function search(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $usuario = $_SESSION['usuario'];
        if (!in_array($usuario['rol_id'], [1, 2, 3, 4, 5, 6]))
            $this->jsonError('Sin permisos.', 403);

        $q = trim($_GET['q'] ?? '');
        if ($q === '')
            $this->jsonError('Ingresa un número de ticket.', 422);

        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("
        SELECT tt.ticket_id, tt.Ticket, tt.fecha, tt.tecnico_id, tt.horario_id,
               tt.Cliente, tt.Descripcion,
               tec.TecnicoNombre AS tecnico_nombre,
               TIME_FORMAT(h.hora, '%H:%i') AS hora
        FROM tm_ticket tt
        JOIN tecnicos tec ON tec.TecnicoId = tt.tecnico_id
        JOIN horarios h   ON h.horario_id  = tt.horario_id
        WHERE tt.Ticket LIKE :q
        ORDER BY tt.fecha DESC
        LIMIT 10
    ");
        $stmt->execute([':q' => '%' . $q . '%']);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->jsonSuccess(['results' => $rows]);
    }

    /* ── Helpers ──────────────────────────────────────────────────── */


    private function requireJson(): void
    {
        header('Content-Type: application/json; charset=utf-8');
    }

    private function jsonBody(): array
    {
        $data = json_decode(file_get_contents('php://input'), true);
        return is_array($data) ? $data : [];
    }

    private function jsonSuccess(array $data): void
    {
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    private function jsonError(string $msg, int $code = 400): void
    {
        http_response_code($code);
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    }
}
