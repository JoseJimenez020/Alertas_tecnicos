<?php
class TicketController {

    /** POST ?action=ticket.store — Crear ticket (rol 1, 2, 3) */
    public function store(): void {
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

        $model = new TicketModel();

        if ($model->exists((int)$body['tecnico_id'], (int)$body['horario_id'], $body['fecha'])) {
            $this->jsonError('Ya existe un ticket para ese técnico en ese horario.', 409);
        }

        $id = $model->create([
            'usuario_id'  => $usuario['id'],
            'fecha'       => $body['fecha'],
            'horario_id'  => (int) $body['horario_id'],
            'tecnico_id'  => (int) $body['tecnico_id'],
            'cliente'     => $body['cliente'],
            'colonia'     => $body['colonia'] ?? '',
            'ticket_num'  => $body['ticket_num'],
            'descripcion' => $body['descripcion'],
            'telefono'    => $body['telefono'],
        ]);

        $this->jsonSuccess(['ticket_id' => $id, 'rol_id' => $usuario['rol_id']]);
    }

    /** GET ?action=ticket.show&id=X — Ver ticket + llamadas */
    public function show(): void {
        $id     = (int) ($_GET['id'] ?? 0);
        $model  = new TicketModel();
        $ticket = $model->findById($id);

        if (!$ticket) {
            $this->jsonError('Ticket no encontrado.', 404);
        }

        $usuario = $_SESSION['usuario'];
        $ticket['can_edit'] = in_array($usuario['rol_id'], [3, 4]);

        $llamadaModel       = new LlamadaModel();
        $ticket['llamadas'] = $llamadaModel->getByTicket($id);

        $this->jsonSuccess($ticket);
    }

    /** POST ?action=ticket.update — Editar ticket (rol 3 y 4) */
    public function update(): void {
        $this->requireJson();
        $usuario = $_SESSION['usuario'];

        if (!in_array($usuario['rol_id'], [3, 4])) {
            $this->jsonError('Sin permisos para editar tickets.', 403);
        }

        $body = $this->jsonBody();
        $id   = (int) ($body['ticket_id'] ?? 0);

        if (!$id) $this->jsonError('ID de ticket inválido.', 422);

        $model  = new TicketModel();
        $ticket = $model->findById($id);
        if (!$ticket) $this->jsonError('Ticket no encontrado.', 404);

        $required = ['cliente', 'ticket_num', 'descripcion', 'telefono', 'horario_id', 'tecnico_id'];
        foreach ($required as $field) {
            if (empty($body[$field])) {
                $this->jsonError("El campo {$field} es requerido.", 422);
            }
        }

        $model->update($id, [
            'cliente'     => $body['cliente'],
            'colonia'     => $body['colonia'] ?? '',
            'ticket_num'  => $body['ticket_num'],
            'descripcion' => $body['descripcion'],
            'telefono'    => $body['telefono'],
            'horario_id'  => (int) $body['horario_id'],
            'tecnico_id'  => (int) $body['tecnico_id'],
        ]);

        $this->jsonSuccess(['updated' => true]);
    }

    /**
     * POST ?action=ticket.reschedule
     * Disponible para TODOS los roles autenticados (1, 2, 3, 4).
     *
     * Busca el siguiente slot libre para el mismo técnico del ticket:
     *   1. Intenta los horarios posteriores del mismo día.
     *   2. Si no hay, avanza día a día (hasta 30) hasta encontrar un hueco.
     *
     * Devuelve la nueva fecha y hora para actualizarla en el modal sin recargar.
     */
    public function reschedule(): void {
        $this->requireJson();

        $body     = $this->jsonBody();
        $ticketId = (int) ($body['ticket_id'] ?? 0);

        if (!$ticketId) $this->jsonError('ID de ticket inválido.', 422);

        $model  = new TicketModel();
        $ticket = $model->findById($ticketId);
        if (!$ticket) $this->jsonError('Ticket no encontrado.', 404);

        $slot = $model->getNextAvailableSlot(
            (int) $ticket['tecnico_id'],
            (int) $ticket['horario_id'],
            $ticket['fecha']
        );

        if (!$slot) {
            $this->jsonError('No hay horario disponible en los próximos 30 días.', 409);
        }

        $model->reschedule($ticketId, $slot['fecha'], $slot['horario_id']);

        $this->jsonSuccess([
            'ticket_id'        => $ticketId,
            'nueva_fecha'      => $slot['fecha'],
            'nueva_fecha_fmt'  => date('d/m/Y', strtotime($slot['fecha'])),
            'nueva_hora'       => $slot['hora'],
            'nuevo_horario_id' => $slot['horario_id'],
        ]);
    }

    /** POST ?action=llamada.upsert — Guardar/actualizar una llamada */
    public function upsertLlamada(): void {
        $this->requireJson();
        $usuario = $_SESSION['usuario'];

        if (!in_array($usuario['rol_id'], [1, 2, 3, 4])) {
            $this->jsonError('Sin permisos.', 403);
        }

        $body      = $this->jsonBody();
        $ticketId  = (int) ($body['ticket_id']  ?? 0);
        $noLlamada = (int) ($body['no_llamada']  ?? 0);

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

    /* ── Helpers ──────────────────────────────────────────────────── */

    private function requireJson(): void {
        header('Content-Type: application/json; charset=utf-8');
    }

    private function jsonBody(): array {
        $raw  = file_get_contents('php://input');
        $data = json_decode($raw, true);
        return is_array($data) ? $data : [];
    }

    private function jsonSuccess(array $data): void {
        echo json_encode(['success' => true, 'data' => $data]);
        exit;
    }

    private function jsonError(string $msg, int $code = 400): void {
        http_response_code($code);
        echo json_encode(['success' => false, 'message' => $msg]);
        exit;
    }
}
