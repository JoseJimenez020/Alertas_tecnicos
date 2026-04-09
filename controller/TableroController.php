<?php
class TableroController {

    public function index(): void {
        $fecha = $_GET['fecha'] ?? date('Y-m-d');

        $horarioModel = new HorarioModel();
        $tecnicoModel = new TecnicoModel();
        $ticketModel  = new TicketModel();

        $horarios      = $horarioModel->getAll();
        $tecnicos      = $tecnicoModel->getAllActive();
        $tecnicosGroup = $tecnicoModel->getGroupedByZona();
        $tickets       = $ticketModel->getByDate($fecha);
        $usuario       = $_SESSION['usuario'];

        // Mapa de colores por usuario (rol_id -> color CSS)
        $coloresRol = [
            1 => 'circle',   // Call center  → círculo
            2 => 'square',   // Mesa control → cuadrado
            3 => 'circle',   // Supervisor   → círculo
        ];

        require BASE_PATH . '/views/Home/index.php';
    }
}
