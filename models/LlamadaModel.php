<?php
class LlamadaModel {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /** Obtener las 3 llamadas de un ticket */
    public function getByTicket(int $ticketId): array {
        $stmt = $this->db->prepare("
            SELECT llamada_id, no_llamada, respuesta_tecnico, respuesta_cliente
            FROM tm_llamadas
            WHERE ticket_id = :tid
            ORDER BY no_llamada
        ");
        $stmt->execute([':tid' => $ticketId]);
        $rows = $stmt->fetchAll();
        // Indexar por no_llamada para fácil acceso en la vista
        $indexed = [];
        foreach ($rows as $r) {
            $indexed[$r['no_llamada']] = $r;
        }
        return $indexed;
    }

    /** Guardar o actualizar una llamada (upsert por ticket_id + no_llamada) */
    public function upsert(int $ticketId, int $noLlamada, string $respTecnico, string $respCliente): void {
        $stmt = $this->db->prepare("
            INSERT INTO tm_llamadas (ticket_id, no_llamada, respuesta_tecnico, respuesta_cliente)
            VALUES (:tid, :no, :rt, :rc)
            ON DUPLICATE KEY UPDATE
                respuesta_tecnico = VALUES(respuesta_tecnico),
                respuesta_cliente = VALUES(respuesta_cliente)
        ");
        $stmt->execute([
            ':tid' => $ticketId,
            ':no'  => $noLlamada,
            ':rt'  => $respTecnico,
            ':rc'  => $respCliente,
        ]);
    }
}
