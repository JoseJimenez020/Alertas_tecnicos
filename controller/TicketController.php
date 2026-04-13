<?php
class TicketController
{

    /** POST ?action=ticket.store — Crear ticket (rol 1, 2, 3) */
    public function store(): void
    {
        $this->requireJson();
        $usuario = $_SESSION['usuario'];

        if (!in_array($usuario['rol_id'], [1, 2, 3])) {
            $this->jsonError('Sin permisos.', 403);
        }

        $body = $this->jsonBody();

        $required = ['fecha', 'horario_id', 'tecnico_id', 'cliente', 'ticket_num', 'descripcion', 'telefono'];
        foreach ($required as $field) {
            if (empty($body[$field])) {
                $this->jsonError("El campo {$field} es requerido.", 422);
            }
        }

        if (strlen($body['telefono']) !== 10 || !ctype_digit($body['telefono'])) {
            $this->jsonError('El teléfono debe tener 10 dígitos numéricos.', 422);
        }

        // Validar que la fecha no sea sábado (6) ni domingo (0)
        $dow = (int) date('w', strtotime($body['fecha']));
        if ($dow === 0 || $dow === 6) {
            $this->jsonError('No se pueden crear tickets en sábado ni domingo.', 422);
        }

        $model = new TicketModel();

        if ($model->exists((int) $body['tecnico_id'], (int) $body['horario_id'], $body['fecha'])) {
            $this->jsonError('Ya existe un ticket para ese técnico en ese horario.', 409);
        }

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
        ]);

        $this->jsonSuccess(['ticket_id' => $id, 'rol_id' => $usuario['rol_id']]);
    }

    /** GET ?action=ticket.show&id=X — Ver ticket + llamadas */
    public function show(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        $model = new TicketModel();
        $ticket = $model->findById($id);

        if (!$ticket) {
            $this->jsonError('Ticket no encontrado.', 404);
        }

        $usuario = $_SESSION['usuario'];
        $ticket['can_edit'] = in_array($usuario['rol_id'], [3, 4]);

        $llamadaModel = new LlamadaModel();
        $ticket['llamadas'] = $llamadaModel->getByTicket($id);

        $this->jsonSuccess($ticket);
    }

    /** POST ?action=ticket.update — Editar ticket (rol 3 y 4) */
    public function update(): void
    {
        $this->requireJson();
        $usuario = $_SESSION['usuario'];

        if (!in_array($usuario['rol_id'], [3, 4])) {
            $this->jsonError('Sin permisos para editar tickets.', 403);
        }

        $body = $this->jsonBody();
        $id = (int) ($body['ticket_id'] ?? 0);

        if (!$id)
            $this->jsonError('ID de ticket inválido.', 422);

        $model = new TicketModel();
        $ticket = $model->findById($id);
        if (!$ticket)
            $this->jsonError('Ticket no encontrado.', 404);

        $required = ['cliente', 'ticket_num', 'descripcion', 'telefono', 'horario_id', 'tecnico_id'];
        foreach ($required as $field) {
            if (empty($body[$field])) {
                $this->jsonError("El campo {$field} es requerido.", 422);
            }
        }

        $model->update($id, [
            'cliente' => $body['cliente'],
            'colonia' => $body['colonia'] ?? '',
            'ticket_num' => $body['ticket_num'],
            'descripcion' => $body['descripcion'],
            'telefono' => $body['telefono'],
            'horario_id' => (int) $body['horario_id'],
            'tecnico_id' => (int) $body['tecnico_id'],
        ]);

        $this->jsonSuccess(['updated' => true]);
    }

    /**
     * GET ?action=ticket.getSlots&id=X
     *
     * Devuelve los slots disponibles (técnicos × horarios × días) para
     * reagendar un ticket. Disponible para TODOS los roles.
     *
     * Parámetros GET opcionales:
     *  - dias: cuántos días laborables hacia adelante buscar (default 5, máx 14)
     *
     * Respuesta:
     * {
     *   "success": true,
     *   "data": {
     *     "tecnicos": [
     *       { "tecnico_id": N, "nombre": "...", "zona_nombre": "...",
     *         "slots": [{ "horario_id": N, "hora": "HH:MM",
     *                     "fecha": "YYYY-MM-DD", "fecha_fmt": "DD/MM/YYYY",
     *                     "label": "DD/MM/YYYY — HH:MM" }, ...] },
     *       ...
     *     ]
     *   }
     * }
     */
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

        // Buscar desde HOY (para incluir slots del día actual aún no pasados)
        $fromFecha = date('Y-m-d');

        $tecnicos = $model->getAvailableSlotsForReschedule($id, $fromFecha, $dias);

        $this->jsonSuccess(['tecnicos' => $tecnicos]);
    }

    /**
     * POST ?action=ticket.reschedule
     * Disponible para TODOS los roles autenticados.
     *
     * Modos:
     *  A) Automático — sin parámetros adicionales: busca el siguiente slot
     *     libre para el MISMO técnico.
     *
     *  B) Manual — con { tecnico_id, horario_id, fecha }:
     *     asigna el slot elegido por el usuario. Valida que esté libre
     *     y que no sea fin de semana.
     */
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

        // ── Modo manual: se recibe tecnico_id + horario_id + fecha ────
        if (!empty($body['tecnico_id']) && !empty($body['horario_id']) && !empty($body['fecha'])) {
            $newTecnicoId = (int) $body['tecnico_id'];
            $newHorarioId = (int) $body['horario_id'];
            $newFecha = $body['fecha'];

            // Validar día laborable
            $dow = (int) date('w', strtotime($newFecha));
            if ($dow === 0 || $dow === 6) {
                $this->jsonError('No se pueden asignar tickets en sábado ni domingo.', 422);
            }

            // Validar que el slot esté libre (excluyendo el propio ticket)
            $stmt = Database::getInstance()->getConnection()->prepare("
                SELECT COUNT(*) FROM tm_ticket
                WHERE tecnico_id = :t AND horario_id = :h AND fecha = :f AND ticket_id != :excl
            ");
            $stmt->execute([
                ':t' => $newTecnicoId,
                ':h' => $newHorarioId,
                ':f' => $newFecha,
                ':excl' => $ticketId,
            ]);
            if ((int) $stmt->fetchColumn() > 0) {
                $this->jsonError('Ese horario ya está ocupado para el técnico seleccionado en esa fecha.', 409);
            }

            $model->reschedule($ticketId, $newFecha, $newHorarioId, $newTecnicoId);

            // Devolver datos formateados para la UI
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

        // ── Modo automático: buscar siguiente slot del mismo técnico ──
        $slot = $model->getNextAvailableSlot(
            (int) $ticket['tecnico_id'],
            (int) $ticket['horario_id'],
            $ticket['fecha']
        );

        if (!$slot) {
            $this->jsonError('No hay horario disponible en los próximos 30 días laborables.', 409);
        }

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

    /** POST ?action=llamada.upsert — Guardar/actualizar una llamada */
    public function upsertLlamada(): void
    {
        $this->requireJson();
        $usuario = $_SESSION['usuario'];

        if (!in_array($usuario['rol_id'], [1, 2, 3, 4])) {
            $this->jsonError('Sin permisos.', 403);
        }

        $body = $this->jsonBody();
        $ticketId = (int) ($body['ticket_id'] ?? 0);
        $noLlamada = (int) ($body['no_llamada'] ?? 0);

        if (!$ticketId || $noLlamada < 1 || $noLlamada > 3) {
            $this->jsonError('Datos inválidos.', 422);
        }

        $ticketModel = new TicketModel();
        if (!$ticketModel->findById($ticketId)) {
            $this->jsonError('Ticket no encontrado.', 404);
        }

        $llamadaModel = new LlamadaModel();
        $llamadaModel->upsert(
            $ticketId,
            $noLlamada,
            trim($body['respuesta_tecnico'] ?? ''),
            trim($body['respuesta_cliente'] ?? '')
        );

        $this->jsonSuccess(['saved' => true]);
    }

    /** POST ?action=ticket.delete — Eliminar ticket */
    public function delete(): void
    {
        $this->requireJson();
        $usuario = $_SESSION['usuario'];

        if (!in_array($usuario['rol_id'], [1, 2, 3, 4])) {
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
        $this->jsonSuccess(['deleted' => true]);
    }
    /* ── Helpers ──────────────────────────────────────────────────── */

    private function requireJson(): void
    {
        header('Content-Type: application/json; charset=utf-8');
    }

    private function jsonBody(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
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
