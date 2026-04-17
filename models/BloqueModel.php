<?php
/**
 * BloqueModel.php
 * CRUD para la tabla tm_bloqueo_tecnico.
 */
class BloqueModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    // ── Lectura ───────────────────────────────────────────────────

    /** Todos los bloqueos de un técnico, más recientes primero */
    public function getByTecnico(int $tecnicoId): array {
        $stmt = $this->db->prepare("
            SELECT b.*, h.hora AS hora_display
            FROM tm_bloqueo_tecnico b
            LEFT JOIN horarios h ON h.horario_id = 0   -- placeholder; horas_json es un array
            WHERE b.tecnico_id = :tid
            ORDER BY b.fecha_inicio DESC
        ");
        // Simplificado: traemos el registro completo; horas_json se decodifica en PHP
        $stmt = $this->db->prepare("
            SELECT *
            FROM tm_bloqueo_tecnico
            WHERE tecnico_id = :tid
            ORDER BY fecha_inicio DESC
        ");
        $stmt->execute([':tid' => $tecnicoId]);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) {
            $r['horas_ids'] = $r['horas_json'] ? json_decode($r['horas_json'], true) : null;
        }
        return $rows;
    }

    /** Bloqueo activo de un técnico en una fecha y horario_id dados */
    public function isBloqueado(int $tecnicoId, string $fecha, int $horarioId): bool {
        $stmt = $this->db->prepare("
            SELECT bloqueo_id, horas_json
            FROM tm_bloqueo_tecnico
            WHERE tecnico_id   = :tid
              AND fecha_inicio <= :fecha
              AND fecha_fin    >= :fecha
            LIMIT 10
        ");
        $stmt->execute([':tid' => $tecnicoId, ':fecha' => $fecha]);
        $rows = $stmt->fetchAll();
        foreach ($rows as $r) {
            $horas = $r['horas_json'] ? json_decode($r['horas_json'], true) : null;
            if ($horas === null) return true;          // bloqueo de día completo
            if (in_array($horarioId, (array) $horas)) return true;
        }
        return false;
    }

    /**
     * Devuelve todos los bloqueos activos en un rango de fechas para una lista de técnicos.
     * Resultado indexado: [tecnico_id][] = bloqueo
     */
    public function getActivosEnRango(array $tecnicoIds, string $desde, string $hasta): array {
        if (empty($tecnicoIds)) return [];
        $placeholders = implode(',', array_fill(0, count($tecnicoIds), '?'));
        $stmt = $this->db->prepare("
            SELECT *
            FROM tm_bloqueo_tecnico
            WHERE tecnico_id IN ({$placeholders})
              AND fecha_inicio <= ?
              AND fecha_fin    >= ?
        ");
        $params = array_merge($tecnicoIds, [$hasta, $desde]);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        $indexed = [];
        foreach ($rows as $r) {
            $r['horas_ids'] = $r['horas_json'] ? json_decode($r['horas_json'], true) : null;
            $indexed[(int)$r['tecnico_id']][] = $r;
        }
        return $indexed;
    }

    /** Un bloqueo por ID */
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT * FROM tm_bloqueo_tecnico WHERE bloqueo_id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        if (!$row) return null;
        $row['horas_ids'] = $row['horas_json'] ? json_decode($row['horas_json'], true) : null;
        return $row;
    }

    // ── Escritura ─────────────────────────────────────────────────

    public function create(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO tm_bloqueo_tecnico
                (tecnico_id, motivo, fecha_inicio, fecha_fin, horas_json, descripcion)
            VALUES
                (:tid, :motivo, :fi, :ff, :hj, :desc)
        ");
        $stmt->execute([
            ':tid'   => $data['tecnico_id'],
            ':motivo'=> $data['motivo'],
            ':fi'    => $data['fecha_inicio'],
            ':ff'    => $data['fecha_fin'],
            ':hj'    => isset($data['horas_ids']) ? json_encode($data['horas_ids']) : null,
            ':desc'  => $data['descripcion'] ?? null,
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): void {
        $stmt = $this->db->prepare("
            UPDATE tm_bloqueo_tecnico
            SET motivo      = :motivo,
                fecha_inicio= :fi,
                fecha_fin   = :ff,
                horas_json  = :hj,
                descripcion = :desc
            WHERE bloqueo_id = :id
        ");
        $stmt->execute([
            ':motivo'=> $data['motivo'],
            ':fi'    => $data['fecha_inicio'],
            ':ff'    => $data['fecha_fin'],
            ':hj'    => isset($data['horas_ids']) ? json_encode($data['horas_ids']) : null,
            ':desc'  => $data['descripcion'] ?? null,
            ':id'    => $id,
        ]);
    }

    public function delete(int $id): void {
        $stmt = $this->db->prepare("DELETE FROM tm_bloqueo_tecnico WHERE bloqueo_id = :id");
        $stmt->execute([':id' => $id]);
    }

    /** Eliminar todos los bloqueos vigentes de un técnico con cierto motivo */
    public function deleteVigentesByTecnicoMotivo(int $tecnicoId, string $motivo): void {
        $stmt = $this->db->prepare("
            DELETE FROM tm_bloqueo_tecnico
            WHERE tecnico_id = :tid AND motivo = :m AND fecha_fin >= CURDATE()
        ");
        $stmt->execute([':tid' => $tecnicoId, ':m' => $motivo]);
    }
}
