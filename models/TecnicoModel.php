<?php
class TecnicoModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /** Todos los técnicos activos con su zona */
    public function getAllActive(): array {
        $stmt = $this->db->query("
            SELECT t.TecnicoId, t.TecnicoNombre, t.num_telefono, t.zona, t.status, t.status_motivo,
                   z.zona_nombre
            FROM tecnicos t
            LEFT JOIN zonas z ON z.zona_id = t.zona
            WHERE t.status = 1
            ORDER BY t.zona, t.TecnicoId
        ");
        return $stmt->fetchAll();
    }

    /** TODOS los técnicos (incluye inactivos) para panel de gestión */
    public function getAll(): array {
        $stmt = $this->db->query("
            SELECT t.TecnicoId, t.TecnicoNombre, t.num_telefono, t.zona, t.status, t.status_motivo,
                   z.zona_nombre
            FROM tecnicos t
            LEFT JOIN zonas z ON z.zona_id = t.zona
            ORDER BY t.zona, t.TecnicoId
        ");
        return $stmt->fetchAll();
    }

    /** Técnicos agrupados por zona (solo activos) */
    public function getGroupedByZona(): array {
        $tecnicos = $this->getAllActive();
        $grouped  = [];
        foreach ($tecnicos as $t) {
            $grouped[$t['zona_nombre']][] = $t;
        }
        return $grouped;
    }

    /** Buscar técnico por ID */
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT t.*, z.zona_nombre
            FROM tecnicos t
            LEFT JOIN zonas z ON z.zona_id = t.zona
            WHERE t.TecnicoId = :id LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Crear técnico */
    public function create(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO tecnicos (TecnicoNombre, num_telefono, zona, status, status_motivo)
            VALUES (:nombre, :telefono, :zona, 1, NULL)
        ");
        $stmt->execute([
            ':nombre' => $data['nombre'],
            ':telefono' => $data['telefono'],
            ':zona'   => $data['zona_id'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    /** Actualizar técnico */
    public function update(int $id, array $data): void {
        $stmt = $this->db->prepare("
            UPDATE tecnicos
            SET TecnicoNombre = :nombre, num_telefono = :telefono, zona = :zona
            WHERE TecnicoId = :id
        ");
        $stmt->execute([
            ':nombre' => $data['nombre'],
            ':telefono' => $data['telefono'],
            ':zona'   => $data['zona_id'],
            ':id'     => $id,
        ]);
    }

    /**
     * Cambiar disponibilidad del técnico.
     * $motivo: null = disponible, 'apoyo' | 'vacaciones' = no disponible
     */
    public function setStatus(int $id, ?string $motivo): void {
        $activo = ($motivo === null) ? 1 : 0;
        $stmt = $this->db->prepare("
            UPDATE tecnicos
            SET status = :s, status_motivo = :m
            WHERE TecnicoId = :id
        ");
        $stmt->execute([':s' => $activo, ':m' => $motivo, ':id' => $id]);
    }

    /** Eliminar técnico (solo si no tiene tickets asociados) */
    public function delete(int $id): bool {
        // Verificar tickets
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM tm_ticket WHERE tecnico_id = :id");
        $stmt->execute([':id' => $id]);
        if ((int) $stmt->fetchColumn() > 0) return false;

        $stmt = $this->db->prepare("DELETE FROM tecnicos WHERE TecnicoId = :id");
        $stmt->execute([':id' => $id]);
        return true;
    }

    /** Todas las zonas para selectores */
    public function getZonas(): array {
        return $this->db->query("SELECT zona_id, zona_nombre FROM zonas ORDER BY zona_id")->fetchAll();
    }
}
