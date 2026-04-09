<?php
class TecnicoModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /** Todos los técnicos activos con su zona */
    public function getAllActive(): array {
        $stmt = $this->db->query("
            SELECT t.TecnicoId, t.TecnicoNombre, t.zona,
                   z.zona_nombre
            FROM tecnicos t
            LEFT JOIN zonas z ON z.zona_id = t.zona
            WHERE t.status = 1
            ORDER BY t.zona, t.TecnicoId
        ");
        return $stmt->fetchAll();
    }

    /** Técnicos agrupados por zona */
    public function getGroupedByZona(): array {
        $tecnicos = $this->getAllActive();
        $grouped  = [];
        foreach ($tecnicos as $t) {
            $grouped[$t['zona_nombre']][] = $t;
        }
        return $grouped;
    }
}
