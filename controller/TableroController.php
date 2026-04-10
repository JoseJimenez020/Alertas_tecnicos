<?php
class TableroController {

    public function index(): void {
        // ── Fecha: siempre desde el servidor en la zona correcta ──────
        // Si el servidor tiene date.timezone = America/Mexico_City en php.ini
        // esta fecha siempre será la correcta independientemente del cliente.
        $fecha = $_GET['fecha'] ?? date('Y-m-d');

        $horarioModel = new HorarioModel();
        $tecnicoModel = new TecnicoModel();
        $ticketModel  = new TicketModel();
        $usuarioModel = new UsuarioModel();

        $horarios = $horarioModel->getAll();

        // getAll() devuelve TODOS los técnicos (activos e inactivos)
        // Se necesitan ambos: la tabla siempre muestra todas las columnas;
        // las inactivas se colorean pero no permiten asignar tickets.
        $tecnicosAll   = $tecnicoModel->getAll();

        // getGroupedByZona() devuelve solo los ACTIVOS, para la lista inferior
        // agrupada y para la lógica de la lista de call-center.
        // Sin embargo, la vista ahora construye su propio agrupado desde $tecnicosAll.
        $tecnicosGroup = $tecnicoModel->getGroupedByZona();

        $tickets  = $ticketModel->getByDate($fecha);
        $usuario  = $_SESSION['usuario'];

        // Mapa dinámico de colores: usuario_id → clase CSS
        $todosUsuarios = $usuarioModel->getAll();
        $userColorMap  = [];
        foreach ($todosUsuarios as $u) {
            $userColorMap[(int) $u['usu_id']] = $u['color'];
        }

        require BASE_PATH . '/views/Home/index.php';
    }
}
