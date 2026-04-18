<?php
class TecnicoModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAllActive(): array
    {
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

    public function getAll(): array
    {
        $stmt = $this->db->query("
            SELECT t.TecnicoId, t.TecnicoNombre, t.num_telefono, t.zona, t.status, t.status_motivo,
                   z.zona_nombre
            FROM tecnicos t
            LEFT JOIN zonas z ON z.zona_id = t.zona
            ORDER BY t.zona, t.TecnicoId
        ");
        return $stmt->fetchAll();
    }

    public function getGroupedByZona(): array
    {
        $tecnicos = $this->getAllActive();
        $grouped = [];
        foreach ($tecnicos as $t) {
            $grouped[$t['zona_nombre']][] = $t;
        }
        return $grouped;
    }

    public function findById(int $id): ?array
    {
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

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO tecnicos (TecnicoNombre, num_telefono, zona, status, status_motivo)
            VALUES (:nombre, :telefono, :zona, 1, NULL)
        ");
        $stmt->execute([
            ':nombre' => $data['nombre'],
            ':telefono' => $data['telefono'] ?? null,
            ':zona' => $data['zona_id'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $stmt = $this->db->prepare("
            UPDATE tecnicos
            SET TecnicoNombre = :nombre,
                num_telefono      = :telefono,
                zona          = :zona
            WHERE TecnicoId = :id
        ");
        $stmt->execute([
            ':nombre' => $data['nombre'],
            ':telefono' => $data['telefono'] ?? null,
            ':zona' => $data['zona_id'],
            ':id' => $id,
        ]);
    }

    public function setStatus(int $id, ?string $motivo): void
    {
        $activo = ($motivo === null) ? 1 : 0;
        $stmt = $this->db->prepare("
            UPDATE tecnicos SET status = :s, status_motivo = :m WHERE TecnicoId = :id
        ");
        $stmt->execute([':s' => $activo, ':m' => $motivo, ':id' => $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM tm_ticket WHERE tecnico_id = :id");
        $stmt->execute([':id' => $id]);
        if ((int) $stmt->fetchColumn() > 0)
            return false;

        $this->db->prepare("DELETE FROM tecnicos WHERE TecnicoId = :id")->execute([':id' => $id]);
        return true;
    }

    public function getZonas(): array
    {
        return $this->db->query("SELECT zona_id, zona_nombre FROM zonas ORDER BY zona_id")->fetchAll();
    }
}
