<?php
class TecnicoController
{

    public function panel(): void
    {
        $this->requireMesa();
        $model = new TecnicoModel();
        $tecnicos = $model->getAll();
        $zonas = $model->getZonas();
        $usuario = $_SESSION['usuario'];

        // Cargar todos los horarios para el selector de horas del modal
        $horarioModel = new HorarioModel();
        $horarios = $horarioModel->getAll();

        // Cargar bloqueos vigentes por técnico para mostrar en tabla
        $bloqueModel = new BloqueModel();
        $bloqueosMap = []; // tecnico_id → bloqueos
        foreach ($tecnicos as $t) {
            $bloqueosMap[(int) $t['TecnicoId']] = $bloqueModel->getByTecnico((int) $t['TecnicoId']);
        }

        require BASE_PATH . '/views/Tecnicos/index.php';
    }

    public function store(): void
    {
        $this->requireMesa();
        header('Content-Type: application/json; charset=utf-8');
        $body = $this->jsonBody();
        if (empty($body['nombre']) || empty($body['zona_id']))
            $this->jsonError('Nombre y zona son obligatorios.', 422);

        $model = new TecnicoModel();
        $id = $model->create([
            'nombre' => trim($body['nombre']),
            'telefono' => trim($body['telefono'] ?? ''),
            'zona_id' => (int) $body['zona_id'],
        ]);
        $this->jsonSuccess(['TecnicoId' => $id]);
    }

    public function update(): void
    {
        $this->requireMesa();
        header('Content-Type: application/json; charset=utf-8');
        $body = $this->jsonBody();
        $id = (int) ($body['tecnico_id'] ?? 0);
        if (!$id || empty($body['nombre']) || empty($body['zona_id']))
            $this->jsonError('Datos incompletos.', 422);

        $model = new TecnicoModel();
        if (!$model->findById($id))
            $this->jsonError('Técnico no encontrado.', 404);
        $model->update($id, [
            'nombre' => trim($body['nombre']),
            'telefono' => trim($body['telefono'] ?? ''),
            'zona_id' => (int) $body['zona_id'],
        ]);
        $this->jsonSuccess(['updated' => true]);
    }

    /**
     * POST ?action=tecnico.status
     * Actualiza el motivo de no-disponibilidad Y crea/actualiza el bloqueo correspondiente.
     *
     * Body esperado:
     *   { tecnico_id, motivo,                    ← siempre
     *     fecha_inicio, fecha_fin,                ← todos los motivos
     *     horas_ids,                              ← solo 'mecanico'; null = todas las horas
     *     descripcion                             ← mecanico y apoyo
     *   }
     *
     * Si motivo === null|'' → reactivar técnico (no crear bloqueo).
     */
    public function setStatus(): void
    {
        $this->requireMesa();
        header('Content-Type: application/json; charset=utf-8');
        $body = $this->jsonBody();
        $id = (int) ($body['tecnico_id'] ?? 0);
        $motivo = $body['motivo'] ?? null;

        if (!$id)
            $this->jsonError('ID inválido.', 422);

        $motivosValidos = [null, '', 'apoyo', 'vacaciones', 'mecanico', 'no_se_presento'];
        if (!in_array($motivo, $motivosValidos, true))
            $this->jsonError('Motivo inválido.', 422);

        $model = new TecnicoModel();
        if (!$model->findById($id))
            $this->jsonError('Técnico no encontrado.', 404);

        // Normalizar motivo vacío a null
        $motivoNorm = ($motivo === '' ? null : $motivo);

        // ── Guardar el status en la tabla de técnicos ──────────────
        $model->setStatus($id, $motivoNorm);

        // ── Gestionar bloqueo si se activa no-disponibilidad ───────
        if ($motivoNorm !== null) {
            $fechaInicio = $body['fecha_inicio'] ?? null;
            $fechaFin = $body['fecha_fin'] ?? null;

            if (!$fechaInicio || !$fechaFin)
                $this->jsonError('Las fechas de inicio y fin son obligatorias.', 422);
            if ($fechaInicio > $fechaFin)
                $this->jsonError('La fecha de inicio no puede ser posterior a la fecha final.', 422);

            $horasIds = null; // null = bloqueo total del día (todas las horas)
            $descripcion = trim($body['descripcion'] ?? '');

            if ($motivoNorm === 'mecanico') {
                // El frontend envía null cuando seleccionó "Todas las horas",
                // o un array de IDs cuando seleccionó horas específicas.
                // array_key_exists distingue entre clave ausente y valor null.
                if (array_key_exists('horas_ids', $body) && $body['horas_ids'] !== null) {
                    // Horas específicas seleccionadas
                    $horasRaw = (array) $body['horas_ids'];
                    if (empty($horasRaw))
                        $this->jsonError('Selecciona al menos una hora para bloquear.', 422);
                    $horasIds = array_map('intval', $horasRaw);
                }
                // Si horas_ids es null o la clave no existe → $horasIds queda null (todas)
                if (empty($descripcion))
                    $this->jsonError('El motivo es obligatorio para mecánico.', 422);
            } elseif ($motivoNorm === 'apoyo') {
                if (empty($descripcion))
                    $this->jsonError('El motivo es obligatorio para apoyo.', 422);
            } elseif ($motivoNorm === 'no_se_presento') {
                // No requiere descripción obligatoria, pero se permite
            }

            // Guardar bloqueo
            $bloqueoModel = new BloqueModel();
            $bloqueoModel->create([
                'tecnico_id' => $id,
                'motivo' => $motivoNorm,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'horas_ids' => $horasIds,
                'descripcion' => $descripcion ?: null,
            ]);
        }

        $this->jsonSuccess(['updated' => true]);
    }

    public function delete(): void
    {
        $this->requireMesa();
        header('Content-Type: application/json; charset=utf-8');
        $body = $this->jsonBody();
        $id = (int) ($body['tecnico_id'] ?? 0);
        if (!$id)
            $this->jsonError('ID inválido.', 422);

        $model = new TecnicoModel();
        if (!$model->findById($id))
            $this->jsonError('Técnico no encontrado.', 404);

        // Soft delete: marcar como inactivo en lugar de eliminar
        $model->setStatus($id, 'Eliminado');
        $this->jsonSuccess(['deleted' => true]);
    }

    private function requireMesa(): void
    {
        $rol = (int) $_SESSION['usuario']['rol_id'];
        if (!in_array($rol, [2, 4, 6])) {
            http_response_code(403);
            die('Acceso denegado.');
        }
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
