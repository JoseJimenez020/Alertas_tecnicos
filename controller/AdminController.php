<?php
class AdminController {

    /** GET ?action=admin.usuarios — Panel de gestión de usuarios */
    public function usuarios(): void {
        $this->requireAdmin();
        $model    = new UsuarioModel();
        $usuarios = $model->getAll();
        $usuario  = $_SESSION['usuario'];
        require BASE_PATH . '/views/Usuarios/index.php';
    }

    /** GET ?action=admin.reporte — Vista de reporte de tickets */
    public function reporte(): void {
        $this->requireAdmin();
        $ticketModel  = new TicketModel();
        $tecnicoModel = new TecnicoModel();
        $usuarioModel = new UsuarioModel();

        // Filtros desde GET
        $filtros = [
            'tecnico_id'  => $_GET['tecnico_id']  ?? '',
            'fecha_desde' => $_GET['fecha_desde']  ?? '',
            'fecha_hasta' => $_GET['fecha_hasta']  ?? '',
            'usuario_id'  => $_GET['usuario_id']   ?? '',
            'estado'      => $_GET['estado']        ?? '',
            'tipo_ticket' => $_GET['tipo_ticket']   ?? '',
        ];

        $tickets  = $ticketModel->getForReport(array_filter($filtros));
        $tecnicos = $tecnicoModel->getAll();
        $usuarios = $usuarioModel->getAll();
        $usuario  = $_SESSION['usuario'];

        require BASE_PATH . '/views/Tickets/index.php';
    }

    /** POST ?action=admin.usuario.store */
    public function storeUsuario(): void {
        $this->requireAdmin();
        header('Content-Type: application/json; charset=utf-8');
        $body = $this->jsonBody();

        if (empty($body['nombre']) || empty($body['usuario']) || empty($body['password']) || empty($body['rol_id']))
            $this->jsonError('Todos los campos son obligatorios.', 422);
        if (strlen($body['password']) > 72) $this->jsonError('La contraseña no puede superar 72 caracteres.', 422);

        $model = new UsuarioModel();
        if ($model->usuarioExists($body['usuario'])) $this->jsonError('El correo/usuario ya está registrado.', 409);

        $id = $model->create([
            'nombre'   => trim($body['nombre']),
            'rol_id'   => (int) $body['rol_id'],
            'usuario'  => trim($body['usuario']),
            'password' => Security::hashPassword($body['password']),
            'color'    => $body['color'] ?? 'bg-gray',
        ]);
        $this->jsonSuccess(['usu_id' => $id]);
    }

    /** POST ?action=admin.usuario.update */
    public function updateUsuario(): void {
        $this->requireAdmin();
        header('Content-Type: application/json; charset=utf-8');
        $body = $this->jsonBody();
        $id   = (int) ($body['usu_id'] ?? 0);
        if (!$id) $this->jsonError('ID inválido.', 422);

        $model = new UsuarioModel();
        if (!$model->findById($id)) $this->jsonError('Usuario no encontrado.', 404);
        if (empty($body['nombre']) || empty($body['usuario']) || empty($body['rol_id']))
            $this->jsonError('Nombre, usuario y rol son obligatorios.', 422);
        if ($model->usuarioExists($body['usuario'], $id))
            $this->jsonError('El correo/usuario ya está en uso.', 409);

        $model->update($id, [
            'nombre'  => trim($body['nombre']),
            'rol_id'  => (int) $body['rol_id'],
            'usuario' => trim($body['usuario']),
            'color'   => $body['color'] ?? 'bg-gray',
        ]);
        if (!empty($body['password'])) {
            if (strlen($body['password']) > 72) $this->jsonError('La contraseña no puede superar 72 caracteres.', 422);
            $model->updatePassword($id, Security::hashPassword($body['password']));
        }
        $this->jsonSuccess(['updated' => true]);
    }

    /** POST ?action=admin.usuario.delete */
    public function deleteUsuario(): void {
        $this->requireAdmin();
        header('Content-Type: application/json; charset=utf-8');
        $body = $this->jsonBody();
        $id   = (int) ($body['usu_id'] ?? 0);
        if ($id === (int) $_SESSION['usuario']['id']) $this->jsonError('No puedes eliminar tu propia cuenta.', 403);

        $model = new UsuarioModel();
        if (!$model->findById($id)) $this->jsonError('Usuario no encontrado.', 404);
        $model->delete($id);
        $this->jsonSuccess(['deleted' => true]);
    }

    private function requireAdmin(): void {
        if ((int) $_SESSION['usuario']['rol_id'] !== 4) { http_response_code(403); die('Acceso denegado.'); }
    }

    private function jsonBody(): array {
        $data = json_decode(file_get_contents('php://input'), true);
        return is_array($data) ? $data : [];
    }

    private function jsonSuccess(array $data): void {
        echo json_encode(['success' => true, 'data' => $data]); exit;
    }

    private function jsonError(string $msg, int $code = 400): void {
        http_response_code($code); echo json_encode(['success' => false, 'message' => $msg]); exit;
    }
}
