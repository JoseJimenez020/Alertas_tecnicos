<?php
class HorarioModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function getAll(): array {
        $stmt = $this->db->query("SELECT horario_id, TIME_FORMAT(hora, '%H:%i') AS hora FROM horarios ORDER BY hora");
        return $stmt->fetchAll();
    }
}
