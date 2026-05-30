<?php
class MaterialController
{

    public function store(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $usuario = $_SESSION['usuario'];

        if ((int) $usuario['rol_id'] !== 6) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acceso denegado.']);
            exit;
        }

        $body = $this->jsonBody();
        $ticketId = (int) ($body['ticket_id'] ?? 0);
        if (!$ticketId) {
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'ticket_id requerido.']);
            exit;
        }

        // Verificar que el ticket existe y es tipo 1 (círculo/square = rol2)
        // Aquí irá la lógica real cuando se definan los campos
        // Por ahora sólo valida y responde OK
        echo json_encode(['success' => true, 'data' => ['saved' => true]]);
        exit;
    }

    private function jsonBody(): array
    {
        $data = json_decode(file_get_contents('php://input'), true);
        return is_array($data) ? $data : [];
    }
}