<?php
class HorarioController {

    /** GET ?action=horarios.panel — Vista de gestión */
    public function panel(): void {
        $this->requireAcceso();
        $model    = new HorarioModel();
        $horarios = $model->getAll();
        $usuario  = $_SESSION['usuario'];
        require BASE_PATH . '/views/Horarios/index.php';
    }

    /** POST ?action=horario.store — Crear horario */
    public function store(): void {
        $this->requireAcceso();
        header('Content-Type: application/json; charset=utf-8');

        $body = $this->jsonBody();
        $hora = trim($body['hora'] ?? '');

        if (!preg_match('/^\d{2}:\d{2}$/', $hora)) {
            $this->jsonError('Formato de hora inválido (HH:MM).', 422);
        }

        // Validar rango
        [$hh, $mm] = explode(':', $hora);
        if ((int)$hh > 23 || (int)$mm > 59) {
            $this->jsonError('Hora fuera de rango.', 422);
        }

        $model = new HorarioModel();

        if ($model->exists($hora)) {
            $this->jsonError('Ya existe un horario con esa hora.', 409);
        }

        $id = $model->create($hora);
        $this->jsonSuccess(['horario_id' => $id]);
    }

    /** POST ?action=horario.delete — Eliminar horario */
    public function delete(): void {
        $this->requireAcceso();
        header('Content-Type: application/json; charset=utf-8');

        $body = $this->jsonBody();
        $id   = (int) ($body['horario_id'] ?? 0);

        if (!$id) $this->jsonError('ID inválido.', 422);

        $model = new HorarioModel();
        if (!$model->findById($id)) $this->jsonError('Horario no encontrado.', 404);

        if (!$model->delete($id)) {
            $this->jsonError('No se puede eliminar: el horario tiene tickets asociados.', 409);
        }

        $this->jsonSuccess(['deleted' => true]);
    }

    /* ── Helpers ──────────────────────────────────────────────────── */

    private function requireAcceso(): void {
        $rol = (int) $_SESSION['usuario']['rol_id'];
        if (!in_array($rol, [2, 4])) {
            http_response_code(403);
            die('Acceso denegado.');
        }
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
