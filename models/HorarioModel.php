<?php
class HorarioModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /** Todos los horarios ordenados */
    public function getAll(): array {
        $stmt = $this->db->query("
            SELECT horario_id, TIME_FORMAT(hora, '%H:%i') AS hora
            FROM horarios
            ORDER BY hora
        ");
        return $stmt->fetchAll();
    }

    /** Buscar por ID */
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT horario_id, TIME_FORMAT(hora, '%H:%i') AS hora
            FROM horarios WHERE horario_id = :id LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Crear horario */
    public function create(string $hora): int {
        $stmt = $this->db->prepare("
            INSERT INTO horarios (hora) VALUES (:hora)
        ");
        $stmt->execute([':hora' => $hora . ':00']);
        return (int) $this->db->lastInsertId();
    }

    /** Eliminar horario (solo si no tiene tickets) */
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM tm_ticket WHERE horario_id = :id
        ");
        $stmt->execute([':id' => $id]);
        if ((int) $stmt->fetchColumn() > 0) return false;

        $stmt = $this->db->prepare("DELETE FROM horarios WHERE horario_id = :id");
        $stmt->execute([':id' => $id]);
        return true;
    }

    /** Verificar si ya existe ese horario */
    public function exists(string $hora, int $excludeId = 0): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM horarios
            WHERE hora = :hora AND horario_id != :id
        ");
        $stmt->execute([':hora' => $hora . ':00', ':id' => $excludeId]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
