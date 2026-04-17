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
     * Tickets del día indexados por [tecnico_id][horario_id].
     * Incluye estado y conteo de llamadas para colorear celdas.
     */
    public function getByDate(string $fecha): array
    {
        $stmt = $this->db->prepare("
            SELECT tt.ticket_id, tt.tecnico_id, tt.horario_id,
                   tt.Cliente, tt.colonia, tt.Ticket, tt.Descripcion, tt.Telefono,
                   tt.usuario_id, tt.estado,
                   u.nombre  AS agente_nombre,
                   u.rol_id  AS agente_rol,
                   COUNT(tl.llamada_id) AS total_llamadas
            FROM tm_ticket tt
            JOIN tm_usuarios u ON u.usu_id = tt.usuario_id
            LEFT JOIN tm_llamadas tl ON tl.ticket_id = tt.ticket_id
            WHERE tt.fecha = :fecha
            GROUP BY tt.ticket_id
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

    /**
     * Obtener tickets para el reporte de administrador.
     */
    public function getForReport(array $filtros = []): array
    {
        $where  = [];
        $params = [];

        if (!empty($filtros['tecnico_id'])) {
            $where[]  = 'tt.tecnico_id = :tecnico_id';
            $params[':tecnico_id'] = (int) $filtros['tecnico_id'];
        }
        if (!empty($filtros['fecha_desde'])) {
            $where[]  = 'tt.fecha >= :fecha_desde';
            $params[':fecha_desde'] = $filtros['fecha_desde'];
        }
        if (!empty($filtros['fecha_hasta'])) {
            $where[]  = 'tt.fecha <= :fecha_hasta';
            $params[':fecha_hasta'] = $filtros['fecha_hasta'];
        }
        if (!empty($filtros['usuario_id'])) {
            $where[]  = 'tt.usuario_id = :usuario_id';
            $params[':usuario_id'] = (int) $filtros['usuario_id'];
        }
        if (!empty($filtros['estado'])) {
            if ($filtros['estado'] === 'terminado') {
                $where[] = "tt.estado = 'terminado'";
            } elseif ($filtros['estado'] === 'pendiente') {
                $where[] = "(tt.estado IS NULL OR tt.estado != 'terminado')";
            }
        }

        $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $stmt = $this->db->prepare("
            SELECT
                tt.ticket_id,
                tt.fecha,
                tt.Cliente,
                tt.colonia,
                tt.Telefono    AS telefono_cliente,
                tt.Ticket      AS num_ticket,
                tt.Descripcion,
                tt.estado,
                tec.TecnicoNombre  AS tecnico_nombre,
                tec.num_telefono       AS telefono_tecnico,
                u.nombre           AS agente_nombre,
                h.hora,
                MAX(CASE WHEN tl.no_llamada = 1 THEN tl.respuesta_tecnico END) AS ll1_tecnico,
                MAX(CASE WHEN tl.no_llamada = 1 THEN tl.respuesta_cliente END) AS ll1_cliente,
                MAX(CASE WHEN tl.no_llamada = 2 THEN tl.respuesta_tecnico END) AS ll2_tecnico,
                MAX(CASE WHEN tl.no_llamada = 2 THEN tl.respuesta_cliente END) AS ll2_cliente,
                MAX(CASE WHEN tl.no_llamada = 3 THEN tl.respuesta_tecnico END) AS ll3_tecnico,
                MAX(CASE WHEN tl.no_llamada = 3 THEN tl.respuesta_cliente END) AS ll3_cliente,
                COUNT(tl.llamada_id) AS total_llamadas
            FROM tm_ticket tt
            JOIN tecnicos     tec ON tec.TecnicoId  = tt.tecnico_id
            JOIN tm_usuarios  u   ON u.usu_id        = tt.usuario_id
            JOIN horarios     h   ON h.horario_id    = tt.horario_id
            LEFT JOIN tm_llamadas tl ON tl.ticket_id = tt.ticket_id
            {$whereSQL}
            GROUP BY tt.ticket_id
            ORDER BY tt.fecha DESC, h.hora ASC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // ══════════════════════════════════════════════════════════════════
    // ESCRITURA
    // ══════════════════════════════════════════════════════════════════

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

    public function setEstado(int $id, ?string $estado): void
    {
        $stmt = $this->db->prepare("
            UPDATE tm_ticket SET estado = :estado WHERE ticket_id = :id
        ");
        $stmt->execute([':estado' => $estado, ':id' => $id]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM tm_ticket WHERE ticket_id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function reschedule(int $ticketId, string $newFecha, int $newHorarioId, ?int $newTecnicoId = null): void
    {
        if ($newTecnicoId !== null) {
            $stmt = $this->db->prepare("
                UPDATE tm_ticket SET fecha = :fecha, horario_id = :horario_id, tecnico_id = :tecnico_id
                WHERE ticket_id = :id
            ");
            $stmt->execute([':fecha' => $newFecha, ':horario_id' => $newHorarioId, ':tecnico_id' => $newTecnicoId, ':id' => $ticketId]);
        } else {
            $stmt = $this->db->prepare("
                UPDATE tm_ticket SET fecha = :fecha, horario_id = :horario_id WHERE ticket_id = :id
            ");
            $stmt->execute([':fecha' => $newFecha, ':horario_id' => $newHorarioId, ':id' => $ticketId]);
        }
    }

    // ══════════════════════════════════════════════════════════════════
    // VALIDACIONES
    // ══════════════════════════════════════════════════════════════════

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
    // LÓGICA DE REAGENDADO — respeta bloqueos
    // ══════════════════════════════════════════════════════════════════

    private function nextWorkday(string $fecha): string
    {
        $dow = (int) date('w', strtotime($fecha));
        if ($dow === 0) return date('Y-m-d', strtotime($fecha . ' +1 day'));
        return $fecha;
    }

    private function nextWorkdayAfter(string $fecha): string
    {
        $next = date('Y-m-d', strtotime($fecha . ' +1 day'));
        return $this->nextWorkday($next);
    }

    private function isWorkday(string $fecha): bool
    {
        $dow = (int) date('w', strtotime($fecha));
        return $dow >= 1 && $dow <= 6;
    }

    private function getAllHorarios(): array
    {
        $stmt = $this->db->query("
            SELECT horario_id, TIME_FORMAT(hora, '%H:%i') AS hora
            FROM horarios ORDER BY hora ASC
        ");
        return $stmt->fetchAll();
    }

    /**
     * Verifica si un slot está bloqueado por tm_bloqueo_tecnico.
     * Usa instancia compartida (evita N+1 en loops — los bloqueos se pre-cargan fuera).
     */
    private function isSlotBloqueado(int $tecnicoId, string $fecha, int $horarioId, array $bloqueosCache): bool
    {
        $bloqueosTec = $bloqueosCache[$tecnicoId] ?? [];
        foreach ($bloqueosTec as $b) {
            if ($fecha < $b['fecha_inicio'] || $fecha > $b['fecha_fin']) continue;
            $horas = $b['horas_ids'] ?? null;
            if ($horas === null) return true;                        // bloqueo total del día
            if (in_array($horarioId, (array) $horas)) return true;  // hora específica bloqueada
        }
        return false;
    }

    public function getNextAvailableSlot(int $tecnicoId, int $currentHorarioId, string $currentFecha): ?array
    {
        $horarios = $this->getAllHorarios();
        if (empty($horarios)) return null;

        // Pre-cargar bloqueos del técnico para los próximos 30 días
        $hasta = date('Y-m-d', strtotime($currentFecha . ' +35 days'));
        $bloqueModel = new BloqueModel();
        $bloqueosCache = $bloqueModel->getActivosEnRango([$tecnicoId], $currentFecha, $hasta);

        $posMap = [];
        foreach ($horarios as $i => $h) $posMap[(int)$h['horario_id']] = $i;

        $total      = count($horarios);
        $currentPos = $posMap[$currentHorarioId] ?? -1;
        $startPos   = $currentPos + 1;
        $fecha      = $currentFecha;

        for ($day = 0; $day < 30; $day++) {
            $fecha = $this->nextWorkday($fecha);
            $desde = ($day === 0) ? $startPos : 0;
            for ($i = $desde; $i < $total; $i++) {
                $h   = $horarios[$i];
                $hId = (int)$h['horario_id'];
                if ($this->exists($tecnicoId, $hId, $fecha)) continue;
                if ($this->isSlotBloqueado($tecnicoId, $fecha, $hId, $bloqueosCache)) continue;
                return ['fecha' => $fecha, 'horario_id' => $hId, 'hora' => $h['hora']];
            }
            $fecha    = $this->nextWorkdayAfter($fecha);
            $startPos = 0;
        }
        return null;
    }

    public function getAvailableSlotsForReschedule(int $excludeTicketId, string $fromFecha, int $diasBusqueda = 5): array
    {
        $horarios = $this->getAllHorarios();
        if (empty($horarios)) return [];

        $stmt = $this->db->query("
            SELECT t.TecnicoId, t.TecnicoNombre, z.zona_nombre
            FROM tecnicos t LEFT JOIN zonas z ON z.zona_id = t.zona
            WHERE t.status = 1 ORDER BY t.zona, t.TecnicoId
        ");
        $tecnicos = $stmt->fetchAll();

        $diasLaborables = [];
        $cursor = $fromFecha;
        while (count($diasLaborables) < $diasBusqueda) {
            if ($this->isWorkday($cursor)) $diasLaborables[] = $cursor;
            $cursor = date('Y-m-d', strtotime($cursor . ' +1 day'));
        }

        // Pre-cargar bloqueos de todos los técnicos activos en el rango
        $hasta = end($diasLaborables);
        $tecIds = array_map(fn($t) => (int)$t['TecnicoId'], $tecnicos);
        $bloqueModel   = new BloqueModel();
        $bloqueosCache = $bloqueModel->getActivosEnRango($tecIds, $fromFecha, $hasta);

        $resultado = [];
        foreach ($tecnicos as $tec) {
            $tecId = (int) $tec['TecnicoId'];
            $slots = [];
            foreach ($diasLaborables as $fecha) {
                foreach ($horarios as $h) {
                    $hId = (int) $h['horario_id'];
                    // Verificar ticket existente (excluyendo el que se está reagendando)
                    $stmt2 = $this->db->prepare("
                        SELECT COUNT(*) FROM tm_ticket
                        WHERE tecnico_id = :t AND horario_id = :h AND fecha = :f AND ticket_id != :excl
                    ");
                    $stmt2->execute([':t' => $tecId, ':h' => $hId, ':f' => $fecha, ':excl' => $excludeTicketId]);
                    if ((int)$stmt2->fetchColumn()) continue;
                    // Verificar bloqueo
                    if ($this->isSlotBloqueado($tecId, $fecha, $hId, $bloqueosCache)) continue;

                    $slots[] = [
                        'horario_id' => $hId,
                        'hora'       => $h['hora'],
                        'fecha'      => $fecha,
                        'fecha_fmt'  => date('d/m/Y', strtotime($fecha)),
                        'label'      => date('d/m/Y', strtotime($fecha)) . ' — ' . $h['hora'],
                    ];
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
