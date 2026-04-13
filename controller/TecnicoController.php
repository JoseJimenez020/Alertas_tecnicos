<?php
class TecnicoController {

    /** GET ?action=tecnicos.panel — Panel de gestión de técnicos */
    public function panel(): void {
        $this->requireMesa();
        $model    = new TecnicoModel();
        $tecnicos = $model->getAll();
        $zonas    = $model->getZonas();
        $usuario  = $_SESSION['usuario'];
        require BASE_PATH . '/views/Tecnicos/index.php';
    }

    /** POST ?action=tecnico.store — Crear técnico */
    public function store(): void {
        $this->requireMesa();
        header('Content-Type: application/json; charset=utf-8');

        $body = $this->jsonBody();

        if (empty($body['nombre']) || empty($body['zona_id'])) {
            $this->jsonError('Nombre y zona son obligatorios.', 422);
        }

        $model = new TecnicoModel();
        $id    = $model->create([
            'nombre'  => trim($body['nombre']),
            'zona_id' => (int) $body['zona_id'],
        ]);

        $this->jsonSuccess(['TecnicoId' => $id]);
    }

    /** POST ?action=tecnico.update — Actualizar técnico */
    public function update(): void {
        $this->requireMesa();
        header('Content-Type: application/json; charset=utf-8');

        $body = $this->jsonBody();
        $id   = (int) ($body['tecnico_id'] ?? 0);

        if (!$id || empty($body['nombre']) || empty($body['zona_id'])) {
            $this->jsonError('Datos incompletos.', 422);
        }

        $model = new TecnicoModel();
        if (!$model->findById($id)) $this->jsonError('Técnico no encontrado.', 404);

        $model->update($id, [
            'nombre'  => trim($body['nombre']),
            'zona_id' => (int) $body['zona_id'],
        ]);

        $this->jsonSuccess(['updated' => true]);
    }

    /** POST ?action=tecnico.status — Cambiar disponibilidad */
    public function setStatus(): void {
        $this->requireMesa();
        header('Content-Type: application/json; charset=utf-8');

        $body   = $this->jsonBody();
        $id     = (int) ($body['tecnico_id'] ?? 0);
        $motivo = $body['motivo'] ?? null; // null | 'apoyo' | 'vacaciones'

        if (!$id) $this->jsonError('ID inválido.', 422);

        // Validar motivo
        $valid = [null, 'apoyo', 'vacaciones', 'mecanico'];
        if (!in_array($motivo, $valid, true)) {
            $this->jsonError('Motivo inválido.', 422);
        }

        $model = new TecnicoModel();
        if (!$model->findById($id)) $this->jsonError('Técnico no encontrado.', 404);

        $model->setStatus($id, $motivo ?: null);
        $this->jsonSuccess(['updated' => true]);
    }

    /** POST ?action=tecnico.delete — Eliminar técnico */
    public function delete(): void {
        $this->requireMesa();
        header('Content-Type: application/json; charset=utf-8');

        $body = $this->jsonBody();
        $id   = (int) ($body['tecnico_id'] ?? 0);

        if (!$id) $this->jsonError('ID inválido.', 422);

        $model = new TecnicoModel();
        if (!$model->findById($id)) $this->jsonError('Técnico no encontrado.', 404);

        if (!$model->delete($id)) {
            $this->jsonError('No se puede eliminar: el técnico tiene tickets asociados.', 409);
        }

        $this->jsonSuccess(['deleted' => true]);
    }

    /* ── Helpers ──────────────────────────────────────────────────── */

    private function requireMesa(): void {
        if ((int) $_SESSION['usuario']['rol_id'] !== 2) {
            http_response_code(403);
            die('Acceso denegado. Solo Mesa de Control puede gestionar técnicos.');
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
