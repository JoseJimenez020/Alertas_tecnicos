<?php
class TicketController {

    /** POST /index.php?action=ticket.store  — Crear ticket (rol 1, 2, 3) */
    public function store(): void {
        $this->requireJson();
        $usuario = $_SESSION['usuario'];

        // Solo roles 1, 2 y 3 pueden crear tickets
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
            'colonia'     => $body['colonia'],
            'ticket_num'  => $body['ticket_num'],
            'descripcion' => $body['descripcion'],
            'telefono'    => $body['telefono'],
        ]);

        $this->jsonSuccess(['ticket_id' => $id, 'rol_id' => $usuario['rol_id']]);
    }

    /** GET /index.php?action=ticket.show&id=X  — Ver ticket */
    public function show(): void {
        $id    = (int) ($_GET['id'] ?? 0);
        $model = new TicketModel();
        $ticket = $model->findById($id);

        if (!$ticket) {
            $this->jsonError('Ticket no encontrado.', 404);
        }

        $usuario = $_SESSION['usuario'];
        $ticket['can_edit'] = ($usuario['rol_id'] === 3);

        $this->jsonSuccess($ticket);
    }

    /** PUT /index.php?action=ticket.update  — Editar ticket (solo rol 3) */
    public function update(): void {
        $this->requireJson();
        $usuario = $_SESSION['usuario'];

        if ($usuario['rol_id'] !== 3) {
            $this->jsonError('Solo el Supervisor CC puede editar tickets.', 403);
        }

        $body = $this->jsonBody();
        $id   = (int) ($body['ticket_id'] ?? 0);

        if (!$id) {
            $this->jsonError('ID de ticket inválido.', 422);
        }

        $model  = new TicketModel();
        $ticket = $model->findById($id);

        if (!$ticket) {
            $this->jsonError('Ticket no encontrado.', 404);
        }

        $required = ['cliente', 'ticket_num', 'descripcion', 'telefono', 'horario_id', 'tecnico_id'];
        foreach ($required as $field) {
            if (empty($body[$field])) {
                $this->jsonError("El campo {$field} es requerido.", 422);
            }
        }

        $model->update($id, [
            'cliente'     => $body['cliente'],
            'colonia'     => $body['colonia'],
            'ticket_num'  => $body['ticket_num'],
            'descripcion' => $body['descripcion'],
            'telefono'    => $body['telefono'],
            'horario_id'  => (int) $body['horario_id'],
            'tecnico_id'  => (int) $body['tecnico_id'],
        ]);

        $this->jsonSuccess(['updated' => true]);
    }

    /* ── Helpers ─────────────────────────────────────────────────────────── */

    private function requireJson(): void {
        header('Content-Type: application/json; charset=utf-8');
    }

    private function jsonBody(): array {
        $raw = file_get_contents('php://input');
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
