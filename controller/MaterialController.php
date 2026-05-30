<?php
class MaterialController
{
    // ══════════════════════════════════════════════════════════════════
    // GET ?action=almacen — Vista de almacén
    // ══════════════════════════════════════════════════════════════════

    public function lista(): void
    {
        $this->requireAcceso();

        $filtros = [
            'fecha_desde' => $_GET['fecha_desde'] ?? '',
            'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
            'tecnico_id'  => $_GET['tecnico_id']  ?? '',
        ];

        $materialModel = new MaterialModel();
        $tecnicoModel  = new TecnicoModel();

        $registros = $materialModel->getResumen(array_filter($filtros));
        $tecnicos  = $tecnicoModel->getAll();
        $usuario   = $_SESSION['usuario'];

        require BASE_PATH . '/views/Almacen/index.php';
    }

    // ══════════════════════════════════════════════════════════════════
    // GET ?action=materiales.get&ticket_id=X — Carga registros del ticket
    // ══════════════════════════════════════════════════════════════════

    public function get(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $this->requireAccesoJson();

        $ticketId = (int) ($_GET['ticket_id'] ?? 0);
        if (!$ticketId) {
            $this->jsonError('ticket_id requerido.', 422);
        }

        $model    = new MaterialModel();
        $catalogo = $model->getAll();
        $guardados = $model->getByTicket($ticketId);

        // Combinar catálogo con cantidades ya guardadas
        $items = array_map(function ($m) use ($guardados) {
            $mid = (int) $m['material_id'];
            return [
                'material_id'     => $mid,
                'material_nombre' => $m['material_nombre'],
                'cantidad'        => $guardados[$mid]['cantidad'] ?? '',
            ];
        }, $catalogo);

        $this->jsonSuccess(['items' => $items]);
    }

    // ══════════════════════════════════════════════════════════════════
    // POST ?action=materiales.store — Guarda los registros del modal
    // ══════════════════════════════════════════════════════════════════

    public function store(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $this->requireAccesoJson();

        $body     = $this->jsonBody();
        $ticketId = (int) ($body['ticket_id'] ?? 0);
        $items    = $body['materiales'] ?? [];

        if (!$ticketId) {
            $this->jsonError('ticket_id requerido.', 422);
        }

        // Verificar que el ticket exista
        $db   = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT ticket_id FROM tm_ticket WHERE ticket_id = :id LIMIT 1");
        $stmt->execute([':id' => $ticketId]);
        if (!$stmt->fetch()) {
            $this->jsonError('Ticket no encontrado.', 404);
        }

        if (!is_array($items)) {
            $this->jsonError('Formato de materiales inválido.', 422);
        }

        $model = new MaterialModel();
        $model->saveRegistros($ticketId, $items);

        $this->jsonSuccess(['saved' => true]);
    }

    // ── Helpers ───────────────────────────────────────────────────────

    /** Roles con acceso: 4 (Administrador), 5 (Encargado de zona), 6 (Cajera) */
    private const ROLES_PERMITIDOS = [4, 5, 6];

    private function requireAcceso(): void
    {
        if (!in_array((int) $_SESSION['usuario']['rol_id'], self::ROLES_PERMITIDOS)) {
            http_response_code(403);
            die('Acceso denegado.');
        }
    }

    private function requireAccesoJson(): void
    {
        if (!in_array((int) $_SESSION['usuario']['rol_id'], self::ROLES_PERMITIDOS)) {
            $this->jsonError('Acceso denegado.', 403);
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
