<?php
class MaterialModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // ══════════════════════════════════════════════════════════════════
    // CATÁLOGO
    // ══════════════════════════════════════════════════════════════════

    /** Todos los materiales del catálogo ordenados por nombre */
    public function getAll(): array
    {
        $stmt = $this->db->query("
            SELECT material_id, material_nombre
            FROM tm_material
            ORDER BY material_nombre
        ");
        return $stmt->fetchAll();
    }

    // ══════════════════════════════════════════════════════════════════
    // REGISTROS POR TICKET
    // ══════════════════════════════════════════════════════════════════

    /**
     * Registros ya guardados para un ticket, indexados por material_id.
     * Útil para pre-cargar el modal al reabrirlo.
     */
    public function getByTicket(int $ticketId): array
    {
        $stmt = $this->db->prepare("
            SELECT r.registro_id, r.material_id, r.cantidad,
                   m.material_nombre
            FROM tm_registro_materiales r
            JOIN tm_material m ON m.material_id = r.material_id
            WHERE r.ticket_id = :tid
            ORDER BY m.material_nombre
        ");
        $stmt->execute([':tid' => $ticketId]);
        $rows = $stmt->fetchAll();

        $indexed = [];
        foreach ($rows as $r) {
            $indexed[(int) $r['material_id']] = $r;
        }
        return $indexed;
    }

    /**
     * Guarda (upsert) los materiales de un ticket.
     * - Items con cantidad vacía se eliminan si existían.
     * - Items con cantidad se insertan o actualizan.
     *
     * Requiere índice UNIQUE(ticket_id, material_id) en tm_registro_materiales.
     *
     * @param int   $ticketId
     * @param array $items    [ ['material_id' => int, 'cantidad' => string], ... ]
     */
    public function saveRegistros(int $ticketId, array $items): void
    {
        // Separar los que tienen cantidad de los vacíos
        $conCantidad = array_filter($items, fn($i) => trim($i['cantidad'] ?? '') !== '');
        $sinCantidad = array_filter($items, fn($i) => trim($i['cantidad'] ?? '') === '');

        // Eliminar registros con cantidad vacía
        foreach ($sinCantidad as $item) {
            $matId = (int) ($item['material_id'] ?? 0);
            if (!$matId) continue;
            $stmt = $this->db->prepare("
                DELETE FROM tm_registro_materiales
                WHERE ticket_id = :tid AND material_id = :mid
            ");
            $stmt->execute([':tid' => $ticketId, ':mid' => $matId]);
        }

        // Upsert de los que tienen cantidad
        foreach ($conCantidad as $item) {
            $matId    = (int) ($item['material_id'] ?? 0);
            $cantidad = trim($item['cantidad']);
            if (!$matId) continue;

            $stmt = $this->db->prepare("
                INSERT INTO tm_registro_materiales (ticket_id, material_id, cantidad)
                VALUES (:tid, :mid, :qty)
                ON DUPLICATE KEY UPDATE cantidad = VALUES(cantidad)
            ");
            $stmt->execute([
                ':tid' => $ticketId,
                ':mid' => $matId,
                ':qty' => $cantidad,
            ]);
        }
    }

    // ══════════════════════════════════════════════════════════════════
    // VISTA DE ALMACÉN
    // ══════════════════════════════════════════════════════════════════

    /**
     * Resumen de materiales por ticket para la vista de almacén.
     * Devuelve un array de tickets, cada uno con una lista de materiales.
     *
     * Filtros opcionales:
     *   - fecha_desde  (Y-m-d)
     *   - fecha_hasta  (Y-m-d)
     *   - tecnico_id   (int)
     */
    public function getResumen(array $filtros = []): array
    {
        $where  = [];
        $params = [];

        if (!empty($filtros['fecha_desde'])) {
            $where[]               = 'tt.fecha >= :fecha_desde';
            $params[':fecha_desde'] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $where[]               = 'tt.fecha <= :fecha_hasta';
            $params[':fecha_hasta'] = $filtros['fecha_hasta'];
        }
        if (!empty($filtros['tecnico_id'])) {
            $where[]              = 'tt.tecnico_id = :tecnico_id';
            $params[':tecnico_id'] = (int) $filtros['tecnico_id'];
        }

        $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        // Traer todos los registros con sus datos de ticket y técnico
        $stmt = $this->db->prepare("
            SELECT
                tt.ticket_id,
                tt.Ticket       AS num_ticket,
                tt.fecha,
                tt.Cliente,
                tec.TecnicoNombre AS tecnico_nombre,
                m.material_nombre,
                r.cantidad,
                TIME_FORMAT(h.hora, '%H:%i') AS hora
            FROM tm_registro_materiales r
            JOIN tm_ticket  tt  ON tt.ticket_id   = r.ticket_id
            JOIN tm_material m  ON m.material_id   = r.material_id
            JOIN tecnicos   tec ON tec.TecnicoId   = tt.tecnico_id
            JOIN horarios   h   ON h.horario_id    = tt.horario_id
            {$whereSQL}
            ORDER BY tt.fecha DESC, tt.ticket_id, m.material_nombre
        ");
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        // Agrupar por ticket_id
        $agrupado = [];
        foreach ($rows as $r) {
            $tid = (int) $r['ticket_id'];
            if (!isset($agrupado[$tid])) {
                $agrupado[$tid] = [
                    'ticket_id'      => $tid,
                    'num_ticket'     => $r['num_ticket'],
                    'fecha'          => $r['fecha'],
                    'hora'           => $r['hora'],
                    'Cliente'        => $r['Cliente'],
                    'tecnico_nombre' => $r['tecnico_nombre'],
                    'materiales'     => [],
                ];
            }
            $agrupado[$tid]['materiales'][] = [
                'material_nombre' => $r['material_nombre'],
                'cantidad'        => $r['cantidad'],
            ];
        }

        return array_values($agrupado);
    }
}
