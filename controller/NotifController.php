<?php
/**
 * Endpoint: GET ?action=notif.tickets
 *
 * Devuelve los tickets del usuario logueado para HOY,
 * con la hora de alerta (30 min antes) y el nombre del técnico.
 *
 * Usado por el sistema de notificaciones de escritorio para
 * hacer polling periódico sin depender de datos cargados al inicio.
 */
class NotifController {

    public function tickets(): void {
        header('Content-Type: application/json; charset=utf-8');
        // Cache-Control: no-cache para que el navegador siempre consulte
        header('Cache-Control: no-store, no-cache, must-revalidate');

        $usuario  = $_SESSION['usuario'];
        $usuarioId = (int) $usuario['id'];

        // Fecha de HOY en la zona horaria del servidor
        $hoy = date('Y-m-d');

        $db = Database::getInstance()->getConnection();

        // Traer tickets de hoy del usuario, con nombre del técnico y hora del horario
        $stmt = $db->prepare("
            SELECT
                tt.ticket_id,
                tt.horario_id,
                tt.tecnico_id,
                tec.TecnicoNombre AS tecnico_nombre,
                TIME_FORMAT(h.hora, '%H:%i') AS hora
            FROM tm_ticket tt
            JOIN tecnicos  tec ON tec.TecnicoId  = tt.tecnico_id
            JOIN horarios  h   ON h.horario_id   = tt.horario_id
            WHERE tt.fecha      = :fecha
              AND tt.usuario_id = :uid
        ");
        $stmt->execute([':fecha' => $hoy, ':uid' => $usuarioId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calcular hora de alerta (30 min antes) para cada ticket
        $resultado = [];
        foreach ($rows as $r) {
            [$hh, $mm] = explode(':', $r['hora']);
            $totalMin  = (int)$hh * 60 + (int)$mm - 30;
            if ($totalMin < 0) continue; // horario antes de las 00:30, sin alerta

            $resultado[] = [
                'ticket_id'      => (int) $r['ticket_id'],
                'horario_id'     => (int) $r['horario_id'],
                'tecnico_nombre' => $r['tecnico_nombre'],
                'hora'           => $r['hora'],          // "HH:MM" — hora real del ticket
                'hora_alerta'    => sprintf(             // "HH:MM" — cuándo disparar
                    '%02d:%02d',
                    intdiv($totalMin, 60),
                    $totalMin % 60
                ),
                'fecha'          => $hoy,
            ];
        }

        echo json_encode([
            'success' => true,
            'fecha'   => $hoy,
            'tickets' => $resultado,
        ]);
        exit;
    }
}
