<?php
/**
 * BloqueController.php
 * Endpoints JSON para gestionar bloqueos de técnicos.
 */
class BloqueController {

    /** GET ?action=bloqueo.list&tecnico_id=X — bloqueos del técnico */
    public function list(): void {
        $this->requireMesa();
        header('Content-Type: application/json; charset=utf-8');
        $tid = (int) ($_GET['tecnico_id'] ?? 0);
        if (!$tid) $this->jsonError('ID inválido.', 422);

        $model = new BloqueModel();
        $bloqueos = $model->getByTecnico($tid);

        // Traer nombres de horarios para mostrar en UI
        $horariosMap = $this->getHorariosMap();
        foreach ($bloqueos as &$b) {
            $b['horas_nombres'] = [];
            if ($b['horas_ids']) {
                foreach ((array)$b['horas_ids'] as $hid) {
                    $b['horas_nombres'][] = $horariosMap[$hid] ?? $hid;
                }
            }
        }

        $this->jsonSuccess(['bloqueos' => $bloqueos]);
    }

    /** POST ?action=bloqueo.store */
    public function store(): void {
        $this->requireMesa();
        header('Content-Type: application/json; charset=utf-8');
        $body = $this->jsonBody();

        $this->validateBody($body);

        $model = new BloqueModel();
        $id = $model->create([
            'tecnico_id'  => (int) $body['tecnico_id'],
            'motivo'      => $body['motivo'],
            'fecha_inicio'=> $body['fecha_inicio'],
            'fecha_fin'   => $body['fecha_fin'],
            'horas_ids'   => $body['horas_ids'] ?? null,
            'descripcion' => $body['descripcion'] ?? null,
        ]);
        WsNotifier::send('tecnico.changed', []);

        $this->jsonSuccess(['bloqueo_id' => $id]);
    }

    /** POST ?action=bloqueo.update */
    public function update(): void {
        $this->requireMesa();
        header('Content-Type: application/json; charset=utf-8');
        $body = $this->jsonBody();
        $id   = (int) ($body['bloqueo_id'] ?? 0);
        if (!$id) $this->jsonError('ID inválido.', 422);

        $this->validateBody($body);

        $model = new BloqueModel();
        if (!$model->findById($id)) $this->jsonError('Bloqueo no encontrado.', 404);

        $model->update($id, [
            'motivo'      => $body['motivo'],
            'fecha_inicio'=> $body['fecha_inicio'],
            'fecha_fin'   => $body['fecha_fin'],
            'horas_ids'   => $body['horas_ids'] ?? null,
            'descripcion' => $body['descripcion'] ?? null,
        ]);
        WsNotifier::send('tecnico.changed', []);

        $this->jsonSuccess(['updated' => true]);
    }

    /** POST ?action=bloqueo.delete */
    public function delete(): void {
        $this->requireMesa();
        header('Content-Type: application/json; charset=utf-8');
        $body = $this->jsonBody();
        $id   = (int) ($body['bloqueo_id'] ?? 0);
        if (!$id) $this->jsonError('ID inválido.', 422);

        $model = new BloqueModel();
        if (!$model->findById($id)) $this->jsonError('Bloqueo no encontrado.', 404);
        $model->delete($id);
        WsNotifier::send('tecnico.changed', []);

        $this->jsonSuccess(['deleted' => true]);
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function validateBody(array $body): void {
        if (empty($body['tecnico_id']) || empty($body['motivo'])
            || empty($body['fecha_inicio']) || empty($body['fecha_fin'])) {
            $this->jsonError('Faltan campos obligatorios.', 422);
        }
        if (!in_array($body['motivo'], ['mecanico', 'vacaciones', 'apoyo'])) {
            $this->jsonError('Motivo inválido.', 422);
        }
        if ($body['fecha_inicio'] > $body['fecha_fin']) {
            $this->jsonError('La fecha de inicio no puede ser posterior a la fecha final.', 422);
        }
        if ($body['motivo'] === 'mecanico' && empty($body['horas_ids'])) {
            $this->jsonError('Debes seleccionar al menos una hora para bloquear.', 422);
        }
    }

    private function getHorariosMap(): array {
        $stmt = Database::getInstance()->getConnection()->query("
            SELECT horario_id, TIME_FORMAT(hora, '%H:%i') AS hora FROM horarios ORDER BY hora
        ");
        $map = [];
        foreach ($stmt->fetchAll() as $h) $map[(int)$h['horario_id']] = $h['hora'];
        return $map;
    }

    private function requireMesa(): void {
        $rol = (int) $_SESSION['usuario']['rol_id'];
        if (!in_array($rol, [2, 4])) {
            http_response_code(403);
            die(json_encode(['success' => false, 'message' => 'Acceso denegado.']));
        }
    }

    private function jsonBody(): array {
        $data = json_decode(file_get_contents('php://input'), true);
        return is_array($data) ? $data : [];
    }

    private function jsonSuccess(array $data): void {
        echo json_encode(['success' => true, 'data' => $data]); exit;
    }

    private function jsonError(string $msg, int $code = 400): void {
        http_response_code($code);
        echo json_encode(['success' => false, 'message' => $msg]); exit;
    }
}
