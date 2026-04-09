<?php
class UsuarioModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findByUsuario(string $usuario): ?array {
        $stmt = $this->db->prepare("
            SELECT usu_id, nombre, rol_id, usuario, password
            FROM tm_usuarios
            WHERE usuario = :u
            LIMIT 1
        ");
        $stmt->execute([':u' => $usuario]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Actualiza el hash de contraseña (re-hasheo automático). */
    public function updatePassword(int $id, string $newHash): void {
        $stmt = $this->db->prepare("
            UPDATE tm_usuarios SET password = :h WHERE usu_id = :id
        ");
        $stmt->execute([':h' => $newHash, ':id' => $id]);
    }
}
