<?php
class TicketModel
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // ══════════════════════════════════════════════════════════════════
    // CONSULTAS PRINCIPALES
    // ══════════════════════════════════════════════════════════════════

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

    // ══════════════════════════════════════════════════════════════════
    // ESCRITURA
    // ══════════════════════════════════════════════════════════════════

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

    /** Actualizar un ticket (datos del cliente, horario, técnico) */
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

    /**
     * Reagendar un ticket: actualiza fecha, horario_id y opcionalmente tecnico_id.
     */
    public function reschedule(int $ticketId, string $newFecha, int $newHorarioId, ?int $newTecnicoId = null): void
    {
        if ($newTecnicoId !== null) {
            $stmt = $this->db->prepare("
                UPDATE tm_ticket
                SET fecha      = :fecha,
                    horario_id = :horario_id,
                    tecnico_id = :tecnico_id
                WHERE ticket_id = :id
            ");
            $stmt->execute([
                ':fecha'      => $newFecha,
                ':horario_id' => $newHorarioId,
                ':tecnico_id' => $newTecnicoId,
                ':id'         => $ticketId,
            ]);
        } else {
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

    // ══════════════════════════════════════════════════════════════════
    // VALIDACIONES
    // ══════════════════════════════════════════════════════════════════

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

    // ══════════════════════════════════════════════════════════════════
    // LÓGICA DE REAGENDADO
    // ══════════════════════════════════════════════════════════════════

    /**
     * Avanza una fecha al siguiente día LABORABLE.
     * Reglas:
     *  - Domingo (0) → lunes (+1)
     *  - Sábado (6)  → lunes (+2)
     *  - Lunes–Viernes → mismo día (sin cambio)
     */
    private function nextWorkday(string $fecha): string
    {
        $dow = (int) date('w', strtotime($fecha)); // 0=Dom, 6=Sáb
        if ($dow === 0) return date('Y-m-d', strtotime($fecha . ' +1 day'));
        if ($dow === 6) return date('Y-m-d', strtotime($fecha . ' +2 days'));
        return $fecha;
    }

    /**
     * Avanza una fecha al siguiente día LABORABLE posterior al día actual.
     * (Sábado → lunes, Domingo → lunes, Viernes → lunes siguiente)
     * Se usa para "el día SIGUIENTE laborable".
     */
    private function nextWorkdayAfter(string $fecha): string
    {
        $next = date('Y-m-d', strtotime($fecha . ' +1 day'));
        return $this->nextWorkday($next);
    }

    /**
     * Verifica si una fecha es día laborable (lunes–viernes).
     */
    private function isWorkday(string $fecha): bool
    {
        $dow = (int) date('w', strtotime($fecha));
        return $dow >= 1 && $dow <= 5;
    }

    /**
     * Obtiene todos los horarios de la BD ordenados por hora ASC.
     * Resultado: [['horario_id' => N, 'hora' => 'HH:MM'], ...]
     */
    private function getAllHorarios(): array
    {
        $stmt = $this->db->query("
            SELECT horario_id, TIME_FORMAT(hora, '%H:%i') AS hora
            FROM horarios
            ORDER BY hora ASC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Busca automáticamente el siguiente slot libre para un técnico.
     *
     * Reglas:
     *  1. No se asignan tickets en domingo ni sábado.
     *     - Si la fecha de búsqueda cae en sábado → saltar al lunes.
     *     - Si cae en domingo → saltar al lunes.
     *  2. Buscar desde el horario POSTERIOR al actual (mismo día).
     *  3. Si no hay hueco ese día, avanzar al siguiente día LABORABLE
     *     y buscar desde el PRIMER horario.
     *  4. Límite: 30 días laborables hacia adelante.
     *
     * @return array|null ['fecha', 'horario_id', 'hora'] o null si no hay hueco.
     */
    public function getNextAvailableSlot(int $tecnicoId, int $currentHorarioId, string $currentFecha): ?array
    {
        $horarios = $this->getAllHorarios();
        if (empty($horarios)) return null;

        // Índice: horario_id → posición
        $posMap = [];
        foreach ($horarios as $i => $h) {
            $posMap[(int)$h['horario_id']] = $i;
        }
        $total       = count($horarios);
        $currentPos  = $posMap[$currentHorarioId] ?? -1;
        $startPos    = $currentPos + 1; // empezar desde el horario SIGUIENTE
        $fecha       = $currentFecha;

        for ($day = 0; $day < 30; $day++) {
            // Asegurarse de que la fecha sea laborable
            $fecha = $this->nextWorkday($fecha);

            $desde = ($day === 0) ? $startPos : 0;

            for ($i = $desde; $i < $total; $i++) {
                $h = $horarios[$i];
                if (!$this->exists($tecnicoId, (int)$h['horario_id'], $fecha)) {
                    return [
                        'fecha'      => $fecha,
                        'horario_id' => (int) $h['horario_id'],
                        'hora'       => $h['hora'],
                    ];
                }
            }

            // Sin hueco este día → avanzar al siguiente laborable
            $fecha    = $this->nextWorkdayAfter($fecha);
            $startPos = 0;
        }

        return null;
    }

    /**
     * Devuelve los slots disponibles de TODOS los técnicos activos
     * para los próximos N días laborables (sin domingos ni sábados),
     * excluyendo el ticket actual.
     *
     * Estructura del resultado:
     * [
     *   'tecnico_id'   => int,
     *   'nombre'       => string,
     *   'zona_nombre'  => string,
     *   'slots'        => [
     *       ['horario_id' => N, 'hora' => 'HH:MM', 'fecha' => 'YYYY-MM-DD', 'fecha_fmt' => 'DD/MM/YYYY'],
     *       ...
     *   ]
     * ]
     *
     * Solo devuelve técnicos que tienen al menos 1 slot libre.
     * Los slots se limitan a los próximos $diasBusqueda días laborables.
     */
    public function getAvailableSlotsForReschedule(int $excludeTicketId, string $fromFecha, int $diasBusqueda = 5): array
    {
        $horarios = $this->getAllHorarios();
        if (empty($horarios)) return [];

        // Técnicos activos
        $stmt = $this->db->query("
            SELECT t.TecnicoId, t.TecnicoNombre, z.zona_nombre
            FROM tecnicos t
            LEFT JOIN zonas z ON z.zona_id = t.zona
            WHERE t.status = 1
            ORDER BY t.zona, t.TecnicoId
        ");
        $tecnicos = $stmt->fetchAll();

        // Ticket actual para excluirlo de la lógica de "ocupa ese slot"
        $ticketActual = $this->findById($excludeTicketId);

        // Construir lista de días laborables a examinar
        $diasLaborables = [];
        $cursor = $fromFecha;
        while (count($diasLaborables) < $diasBusqueda) {
            if ($this->isWorkday($cursor)) {
                $diasLaborables[] = $cursor;
            }
            $cursor = date('Y-m-d', strtotime($cursor . ' +1 day'));
        }

        $resultado = [];

        foreach ($tecnicos as $tec) {
            $tecId = (int) $tec['TecnicoId'];
            $slots = [];

            foreach ($diasLaborables as $fecha) {
                foreach ($horarios as $h) {
                    $hId = (int) $h['horario_id'];

                    // ¿Está ocupado? Ignorar el propio ticket al verificar
                    $stmt2 = $this->db->prepare("
                        SELECT COUNT(*) FROM tm_ticket
                        WHERE tecnico_id = :t
                          AND horario_id = :h
                          AND fecha      = :f
                          AND ticket_id != :excl
                    ");
                    $stmt2->execute([
                        ':t'    => $tecId,
                        ':h'    => $hId,
                        ':f'    => $fecha,
                        ':excl' => $excludeTicketId,
                    ]);
                    $ocupado = (int) $stmt2->fetchColumn() > 0;

                    if (!$ocupado) {
                        $slots[] = [
                            'horario_id' => $hId,
                            'hora'       => $h['hora'],
                            'fecha'      => $fecha,
                            'fecha_fmt'  => date('d/m/Y', strtotime($fecha)),
                            'label'      => date('d/m/Y', strtotime($fecha)) . ' — ' . $h['hora'],
                        ];
                    }
                }
            }

            if (!empty($slots)) {
                $resultado[] = [
                    'tecnico_id'  => $tecId,
                    'nombre'      => $tec['TecnicoNombre'],
                    'zona_nombre' => $tec['zona_nombre'] ?? '',
                    'slots'       => $slots,
                ];
            }
        }

        return $resultado;
    }
}
