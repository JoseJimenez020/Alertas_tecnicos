<?php
class TicketModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Tickets del día indexados por [tecnico_id][horario_id]
     */
    public function getByDate(string $fecha): array
    {
        $stmt = $this->db->prepare("
            SELECT tt.ticket_id, tt.tecnico_id, tt.horario_id,
                   tt.Cliente, tt.colonia, tt.Ticket, tt.Descripcion, tt.Telefono,
                   tt.usuario_id,
                   u.nombre  AS agente_nombre,
                   u.rol_id  AS agente_rol
            FROM tm_ticket tt
            JOIN tm_usuarios u ON u.usu_id = tt.usuario_id
            WHERE tt.fecha = :fecha
        ");
        $stmt->execute([':fecha' => $fecha]);
        $rows = $stmt->fetchAll();
        $indexed = [];
        foreach ($rows as $row) {
            $indexed[$row['tecnico_id']][$row['horario_id']] = $row;
        }
        return $indexed;
    }

    /** Crear un ticket nuevo */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO tm_ticket
                (usuario_id, fecha, horario_id, tecnico_id, Cliente, colonia, Ticket, Descripcion, Telefono)
            VALUES
                (:usuario_id, :fecha, :horario_id, :tecnico_id, :cliente, :colonia, :ticket, :descripcion, :telefono)
        ");
        $stmt->execute([
            ':usuario_id'  => $data['usuario_id'],
            ':fecha'       => $data['fecha'],
            ':horario_id'  => $data['horario_id'],
            ':tecnico_id'  => $data['tecnico_id'],
            ':cliente'     => $data['cliente'],
            ':colonia'     => $data['colonia'],
            ':ticket'      => $data['ticket_num'],
            ':descripcion' => $data['descripcion'],
            ':telefono'    => $data['telefono'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    /** Obtener un ticket por ID */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT tt.*, u.nombre AS agente_nombre, u.rol_id AS agente_rol
            FROM tm_ticket tt
            JOIN tm_usuarios u ON u.usu_id = tt.usuario_id
            WHERE tt.ticket_id = :id
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** Actualizar un ticket */
    public function update(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE tm_ticket
            SET Cliente      = :cliente,
                colonia      = :colonia,
                Ticket       = :ticket_num,
                Descripcion  = :descripcion,
                Telefono     = :telefono,
                horario_id   = :horario_id,
                tecnico_id   = :tecnico_id
            WHERE ticket_id  = :id
        ");
        return $stmt->execute([
            ':cliente'     => $data['cliente'],
            ':colonia'     => $data['colonia'],
            ':ticket_num'  => $data['ticket_num'],
            ':descripcion' => $data['descripcion'],
            ':telefono'    => $data['telefono'],
            ':horario_id'  => $data['horario_id'],
            ':tecnico_id'  => $data['tecnico_id'],
            ':id'          => $id,
        ]);
    }

    /** Verificar si ya existe un ticket para ese técnico+horario+fecha */
    public function exists(int $tecnicoId, int $horarioId, string $fecha): bool
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM tm_ticket
            WHERE tecnico_id = :t AND horario_id = :h AND fecha = :f
        ");
        $stmt->execute([':t' => $tecnicoId, ':h' => $horarioId, ':f' => $fecha]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Buscar el siguiente slot libre para un técnico dado.
     *
     * Algoritmo:
     * 1. Obtener todos los horarios ordenados por hora ASC.
     * 2. Partir del horario_id actual del ticket para buscar el SIGUIENTE.
     * 3. Para cada horario posterior (mismo día), verificar que el técnico
     *    no tenga ya un ticket → primer hueco = slot elegido.
     * 4. Si no hay hueco en el mismo día, avanzar al día siguiente y buscar
     *    desde el PRIMER horario del listado.
     * 5. Se examina hasta 30 días hacia adelante como límite de seguridad.
     *
     * Devuelve ['fecha' => 'YYYY-MM-DD', 'horario_id' => N, 'hora' => 'HH:MM']
     * o null si no se encontró ningún slot en el período de búsqueda.
     */
    public function getNextAvailableSlot(int $tecnicoId, int $currentHorarioId, string $currentFecha): ?array
    {
        // Obtener todos los horarios ordenados
        $stmt     = $this->db->query("
            SELECT horario_id, TIME_FORMAT(hora, '%H:%i') AS hora
            FROM horarios
            ORDER BY hora ASC
        ");
        $horarios = $stmt->fetchAll();

        if (empty($horarios)) return null;

        // Construir índice: horario_id → posición en el array
        $posMap = [];
        foreach ($horarios as $i => $h) {
            $posMap[$h['horario_id']] = $i;
        }

        $currentPos  = $posMap[$currentHorarioId] ?? -1;
        $totalHorarios = count($horarios);

        // Día actual → buscar desde el horario siguiente al actual
        $startPos = $currentPos + 1;
        $fecha    = $currentFecha;

        for ($day = 0; $day < 30; $day++) {
            // Primer día: empezar desde $startPos; días siguientes: desde 0
            $desde = ($day === 0) ? $startPos : 0;

            for ($i = $desde; $i < $totalHorarios; $i++) {
                $h = $horarios[$i];
                if (!$this->exists($tecnicoId, (int)$h['horario_id'], $fecha)) {
                    return [
                        'fecha'      => $fecha,
                        'horario_id' => (int) $h['horario_id'],
                        'hora'       => $h['hora'],
                    ];
                }
            }

            // Avanzar al día siguiente
            $fecha    = date('Y-m-d', strtotime($fecha . ' +1 day'));
            $startPos = 0; // ya no necesitamos saltar nada
        }

        return null; // sin hueco en 30 días
    }

    /**
     * Reagendar un ticket: actualiza solo fecha y horario_id.
     */
    public function reschedule(int $ticketId, string $newFecha, int $newHorarioId): void
    {
        $stmt = $this->db->prepare("
            UPDATE tm_ticket
            SET fecha      = :fecha,
                horario_id = :horario_id
            WHERE ticket_id = :id
        ");
        $stmt->execute([
            ':fecha'      => $newFecha,
            ':horario_id' => $newHorarioId,
            ':id'         => $ticketId,
        ]);
    }
}
