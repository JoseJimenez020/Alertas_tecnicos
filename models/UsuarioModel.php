<?php
class UsuarioModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function findByUsuario(string $usuario): ?array {
        $stmt = $this->db->prepare("
            SELECT usu_id, nombre, rol_id, usuario, password, color
            FROM tm_usuarios
            WHERE usuario = :u
            LIMIT 1
        ");
        $stmt->execute([':u' => $usuario]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Todos los usuarios ordenados por rol y nombre */
    public function getAll(): array {
        $stmt = $this->db->query("
            SELECT usu_id, nombre, rol_id, usuario, color
            FROM tm_usuarios
            ORDER BY rol_id, nombre
        ");
        return $stmt->fetchAll();
    }

    /** Buscar por ID */
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT usu_id, nombre, rol_id, usuario, color
            FROM tm_usuarios WHERE usu_id = :id LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Crear usuario nuevo */
    public function create(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO tm_usuarios (nombre, rol_id, usuario, password, color)
            VALUES (:nombre, :rol_id, :usuario, :password, :color)
        ");
        $stmt->execute([
            ':nombre'   => $data['nombre'],
            ':rol_id'   => $data['rol_id'],
            ':usuario'  => $data['usuario'],
            ':password' => $data['password'],
            ':color'    => $data['color'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    /** Actualizar datos del usuario (sin contraseña) */
    public function update(int $id, array $data): void {
        $stmt = $this->db->prepare("
            UPDATE tm_usuarios
            SET nombre  = :nombre,
                rol_id  = :rol_id,
                usuario = :usuario,
                color   = :color
            WHERE usu_id = :id
        ");
        $stmt->execute([
            ':nombre'  => $data['nombre'],
            ':rol_id'  => $data['rol_id'],
            ':usuario' => $data['usuario'],
            ':color'   => $data['color'],
            ':id'      => $id,
        ]);
    }

    /** Actualiza el hash de contraseña */
    public function updatePassword(int $id, string $newHash): void {
        $stmt = $this->db->prepare("
            UPDATE tm_usuarios SET password = :h WHERE usu_id = :id
        ");
        $stmt->execute([':h' => $newHash, ':id' => $id]);
    }

    /** Eliminar usuario */
    public function delete(int $id): void {
        $stmt = $this->db->prepare("DELETE FROM tm_usuarios WHERE usu_id = :id");
        $stmt->execute([':id' => $id]);
    }

    /** Verificar si el correo/usuario ya existe (excluyendo un ID) */
    public function usuarioExists(string $usuario, int $excludeId = 0): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM tm_usuarios
            WHERE usuario = :u AND usu_id != :id
        ");
        $stmt->execute([':u' => $usuario, ':id' => $excludeId]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
