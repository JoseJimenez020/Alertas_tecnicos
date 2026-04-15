<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../assets/favicon.ico">
    <title>Tablero de Incidentes</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>public/main.css">
    <style>
        /* ── Barra superior ─────────────────────────────────────── */
        .topbar {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 12px; flex-wrap: wrap; gap: 8px;
        }
        .topbar h1 { margin: 0; font-size: 18px; }
        .topbar-right { display: flex; align-items: center; gap: 10px; font-size: 12px; }
        .topbar-right form { margin: 0; }
        .btn-logout {
            padding: 5px 12px; background: #c0392b; color: #fff;
            border: none; cursor: pointer; font-size: 12px;
        }
        .btn-logout:hover { background: #a93226; }

        /* ── Menú desplegable ───────────────────────────────────── */
        .dropdown { position: relative; display: inline-block; }
        .dropdown-toggle {
            padding: 5px 14px; background: #1a4d6d; color: #fff;
            border: none; cursor: pointer; font-size: 12px;
            display: flex; align-items: center; gap: 4px;
        }
        .dropdown-toggle:hover { background: #245f85; }
        .dropdown-toggle::after { content: ' ▾'; font-size: 10px; }
        .dropdown-menu {
            display: none; position: absolute; right: 0; top: 100%;
            background: #fff; border: 1px solid #ccc;
            box-shadow: 0 4px 12px rgba(0,0,0,.18);
            min-width: 200px; z-index: 500;
        }
        .dropdown-menu.open { display: block; }
        .dropdown-menu a,
        .dropdown-menu button.menu-btn {
            display: block; width: 100%; box-sizing: border-box;
            padding: 8px 14px; text-align: left; font-size: 12px;
            text-decoration: none; color: #222;
            background: none; border: none; cursor: pointer;
            border-bottom: 1px solid #eee;
        }
        .dropdown-menu a:hover,
        .dropdown-menu button.menu-btn:hover { background: #f0f5ff; }
        .dropdown-menu .menu-section {
            padding: 5px 14px 3px; font-size: 10px; color: #888;
            font-weight: bold; text-transform: uppercase;
            background: #f8f8f8; border-bottom: 1px solid #eee;
        }

        /* ── Banner notificaciones ──────────────────────────────── */
        #bannerNotif {
            background: #fff3cd; border: 1px solid #ffc107; color: #664d03;
            padding: 8px 14px; margin-bottom: 10px; font-size: 12px;
            display: none; align-items: center; gap: 10px; flex-wrap: wrap;
        }
        #bannerNotif .banner-text { flex: 1; }
        #bannerNotif .btn-activar {
            padding: 5px 14px; background: #1a4d6d; color: #fff;
            border: none; cursor: pointer; font-size: 12px; font-weight: bold;
        }
        #bannerNotif .btn-activar:hover { background: #245f85; }
        #bannerNotif .btn-dismiss {
            background: none; color: #888; font-size: 18px;
            padding: 0; cursor: pointer; border: none;
        }

        /* ── Tabla ──────────────────────────────────────────────── */
        td.cell-ticket { cursor: pointer; position: relative; }
        td.cell-ticket:hover { background: #f0f7ff; }
        td.cell-ticket.occupied { cursor: pointer; }
        /* Estado terminado: fondo verde en la celda */
        td.cell-ticket.estado-terminado { background-color: #d4edda !important; }
        td.cell-ticket.estado-terminado:hover { background-color: #c3e6cb !important; }
        /* 1 llamada sin terminar: fondo verde en la celda */
        td.cell-ticket.estado-primera-llamada { background-color: #637052 !important; }
        /* 2 llamadas sin terminar: fondo naranja en la celda */
        td.cell-ticket.estado-segunda-llamada { background-color: #E48312 !important; }
        /* 3 llamadas sin terminar: fondo rojo en la celda */
        td.cell-ticket.estado-rojo { background-color: #fa2e2e !important; }
        td.cell-ticket.estado-rojo:hover { background-color: #f5c6cb !important; }
        /* El ícono (círculo/cuadrado) lleva su propio color inline — no hereda el fondo */
        .icon-wrap { display: flex; align-items: center; justify-content: center; height: 100%; }
        th.col-nodisponible { background-color: #156082 !important; color: #fff !important; }
        .badge-nodisponible {
            display: block; font-size: 8px; background: rgba(255,255,255,.22);
            padding: 1px 3px; border-radius: 6px; margin-top: 2px; line-height: 1.3;
        }
        td.cell-nodisponible { background-color: #156082 !important; cursor: default !important; }
        td.cell-nodisponible:hover { background-color: #156082 !important; }

        /* ── Modales base ───────────────────────────────────────── */
        .modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,.45); z-index: 1000;
            align-items: center; justify-content: center;
        }
        .modal-overlay.open { display: flex; }
        .modal-box {
            background: #fff; width: 500px; max-width: 96vw;
            box-shadow: 0 8px 32px rgba(0,0,0,.22);
            max-height: 90vh; overflow-y: auto;
        }
        .modal-header {
            background: #1a4d6d; color: #fff; padding: 12px 18px;
            display: flex; justify-content: space-between; align-items: center;
            position: sticky; top: 0; z-index: 1;
        }
        .modal-header h3 { margin: 0; font-size: 14px; }
        .modal-close { background: none; border: none; color: #fff; font-size: 20px; cursor: pointer; }
        .modal-body { padding: 18px; }
        .modal-body label {
            display: block; font-size: 11px; color: #555;
            margin-bottom: 3px; margin-top: 10px;
        }
        .modal-body label:first-child { margin-top: 0; }
        .modal-body input, .modal-body select, .modal-body textarea {
            width: 100%; box-sizing: border-box; padding: 7px 9px;
            border: 1px solid #ccc; font-size: 12px; font-family: Arial, sans-serif;
        }
        .modal-body textarea { resize: vertical; min-height: 60px; }
        .modal-body input[readonly], .modal-body textarea[readonly],
        .modal-body select[disabled] { background: #f5f5f5; color: #444; }
        .modal-footer {
            padding: 12px 18px; background: #f5f5f5;
            display: flex; justify-content: flex-end; gap: 8px; flex-wrap: wrap;
            position: sticky; bottom: 0;
        }
        .btn { padding: 7px 18px; border: none; cursor: pointer; font-size: 12px; }
        .btn-primary   { background: #1a4d6d; color: #fff; }
        .btn-primary:hover { background: #245f85; }
        .btn-secondary { background: #ccc; color: #333; }
        .btn-secondary:hover { background: #bbb; }
        .btn-warning   { background: #e67e00; color: #fff; }
        .btn-warning:hover { background: #cc7000; }
        .btn-danger    { background: #c0392b; color: #fff; }
        .btn-danger:hover { background: #a93226; }
        .btn-reschedule { background: #6f42c1; color: #fff; }
        .btn-reschedule:hover { background: #5a32a3; }
        .btn-reschedule:disabled { background: #b39ddb; cursor: not-allowed; }
        /* Botón Completado */
        .btn-completado { background: #28a745; color: #fff; }
        .btn-completado:hover { background: #218838; }
        .btn-completado.activo { background: #1e7145; box-shadow: inset 0 0 0 2px rgba(255,255,255,.4); }

        .modal-meta {
            font-size: 11px; color: #1a4d6d; background: #e8f1f8;
            padding: 6px 10px; margin-bottom: 10px;
        }
        .feedback { font-size: 11px; padding: 6px 10px; margin-bottom: 8px; display: none; }
        .feedback.success { background: #d4edda; color: #155724; display: block; }
        .feedback.error   { background: #f8d7da; color: #721c24; display: block; }

        #rescheduleResult {
            display: none;
            background: #f0f7ff; border: 1px solid #1a4d6d; border-radius: 3px;
            padding: 10px 12px; margin-top: 10px; font-size: 12px;
        }
        #rescheduleResult strong { color: #1a4d6d; }
        #rescheduleResult .new-slot {
            font-size: 14px; font-weight: bold; color: #1a4d6d; margin-top: 4px;
        }

        /* ── Llamadas ───────────────────────────────────────────── */
        .llamadas-section { margin-top: 16px; border-top: 2px solid #1a4d6d; padding-top: 12px; }
        .llamadas-section h4 { margin: 0 0 10px; font-size: 13px; color: #1a4d6d; }
        .llamada-bloque { border: 1px solid #dde; background: #f8f9ff; padding: 10px; margin-bottom: 8px; }
        .llamada-bloque legend { font-size: 11px; font-weight: bold; color: #1a4d6d; padding: 0 4px; }
        .llamada-bloque .llamada-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .llamada-bloque label { margin-top: 4px; }
        .llamada-guardada { background: #eafaf1; }
        .btn-save-llamada {
            margin-top: 8px; padding: 5px 14px; font-size: 11px;
            background: #1a4d6d; color: #fff; border: none; cursor: pointer;
        }
        .btn-save-llamada:hover { background: #245f85; }
        .llamada-status { font-size: 10px; color: #555; margin-top: 4px; display: block; }

        /* ── Lista inferior ─────────────────────────────────────── */
        .list-item { display: flex; align-items: center; margin-bottom: 2px; }
        .id-num { width: 25px; font-weight: bold; flex-shrink: 0; }
        .list-item-nombre { flex: 1; }
        .list-item.nodisponible .list-item-nombre {
            text-decoration: line-through; text-decoration-color: #c0392b; opacity: .7;
        }
        .motivo-tag { font-size: 9px; color: #c0392b; margin-left: 4px; white-space: nowrap; }
        .btn-tecnico-status {
            display: none; margin-left: 6px; background: none; border: none;
            cursor: pointer; font-size: 11px; padding: 1px 3px; line-height: 1; color: #1a4d6d;
        }
        .btn-tecnico-status:hover { color: #e67e00; }

        /* ── Modal de reagendado ─────────────────────────────────── */
        #modalReagendar .modal-box { width: 460px; }

        .reagendar-step { display: none; }
        .reagendar-step.active { display: block; }

        .slot-loading {
            text-align: center; padding: 20px; color: #888; font-size: 12px;
        }
        .slot-empty {
            text-align: center; padding: 16px; color: #c0392b; font-size: 12px;
            background: #fdf0f0; border: 1px solid #f5c6cb;
        }

        /* Select con grupo de zonas */
        #rSelectTecnico, #rSelectSlot { font-size: 12px; }
        #rSelectTecnico optgroup, #rSelectSlot optgroup {
            font-weight: bold; color: #1a4d6d;
        }

        .reagendar-info {
            background: #e8f1f8; border-left: 3px solid #1a4d6d;
            padding: 8px 12px; font-size: 11px; margin-bottom: 12px;
        }
        .reagendar-info strong { color: #1a4d6d; }

        .dias-selector { display: flex; align-items: center; gap: 8px; margin-bottom: 14px; }
        .dias-selector label { font-weight: bold; font-size: 11px; margin: 0; }
        .dias-selector select { width: auto; }

        .btn-auto-reschedule {
            width: 100%; padding: 8px; background: #f8f9fa; border: 1px dashed #1a4d6d;
            color: #1a4d6d; cursor: pointer; font-size: 11px; text-align: center;
            margin-top: 8px;
        }
        .btn-auto-reschedule:hover { background: #e8f1f8; }

        /* ── Buscador de tickets ─────────────────────────────────── */
        .buscador-wrap {
            display: none; /* se muestra solo a rol 4 y 5 via JS/PHP */
            align-items: center; gap: 6px;
        }
        .buscador-wrap input[type="text"] {
            padding: 4px 8px; font-size: 12px; border: 1px solid #ccc;
            width: 170px; box-sizing: border-box;
        }
        .buscador-wrap button {
            padding: 5px 10px; background: #1a4d6d; color: #fff;
            border: none; cursor: pointer; font-size: 12px;
        }
        .buscador-wrap button:hover { background: #245f85; }

        /* Dropdown de resultados de búsqueda */
        .search-dropdown {
            position: absolute; top: 100%; right: 0;
            background: #fff; border: 1px solid #ccc;
            box-shadow: 0 4px 14px rgba(0,0,0,.18);
            min-width: 340px; z-index: 600; max-height: 260px;
            overflow-y: auto; display: none;
        }
        .search-dropdown.open { display: block; }
        .search-result-item {
            padding: 8px 12px; cursor: pointer; font-size: 11px;
            border-bottom: 1px solid #eee;
        }
        .search-result-item:hover { background: #f0f5ff; }
        .search-result-item .sri-ticket { font-weight: bold; color: #1a4d6d; }
        .search-result-item .sri-meta { color: #666; margin-top: 2px; }
        .search-no-results {
            padding: 10px 12px; font-size: 11px; color: #888; text-align: center;
        }

        /* Efecto de pulso que domina sobre cualquier estado (terminado o rojo) */
        td.cell-highlight-strong,
        td.cell-ticket.estado-primera-llamada.cell-highlight-strong,
        td.cell-ticket.estado-segunda-llamada.cell-highlight-strong,
        td.cell-ticket.estado-terminado.cell-highlight-strong,
        td.cell-ticket.estado-rojo.cell-highlight-strong {
            background-color: #FF4500 !important;
            box-shadow: inset 0 0 15px rgba(255, 255, 255, 0.6), 0 0 12px #FF4500 !important;
            border: 1px solid #FFF !important;
        }
    </style>
</head>
<body>
<?php
function getIconHtml(array $ticket, array $colorMap): string {
    $rolId  = (int) $ticket['agente_rol'];
    $userId = (int) $ticket['usuario_id'];
    $color  = $colorMap[$userId] ?? 'bg-gray';
    $shape  = ($rolId === 2) ? 'square' : 'circle';
    return '<span class="' . $shape . ' ' . $color . '"></span>';
}

$zonaOrder = ['Centro','Chontalpa','Sierra','Carmen','Delicias','Allende','Merida'];

$allTecs = $tecnicosAll ?? [];
if (empty($allTecs)) {
    foreach ($tecnicosGroup as $lista) foreach ($lista as $t) $allTecs[] = $t;
}

$tecnicosSorted = [];
foreach ($zonaOrder as $z) {
    foreach ($allTecs as $t) {
        if (($t['zona_nombre'] ?? '') === $z) $tecnicosSorted[] = $t;
    }
}
foreach ($allTecs as $t) {
    if (!in_array($t['zona_nombre'] ?? '', $zonaOrder)) $tecnicosSorted[] = $t;
}
$seen = []; $tmp = [];
foreach ($tecnicosSorted as $t) {
    if (!isset($seen[$t['TecnicoId']])) { $seen[$t['TecnicoId']] = true; $tmp[] = $t; }
}
$tecnicosSorted = $tmp;

$zonaSpans = [];
foreach ($tecnicosSorted as $t) {
    $z = $t['zona_nombre'] ?? 'Sin zona';
    $zonaSpans[$z] = ($zonaSpans[$z] ?? 0) + 1;
}

$allTecsByZona = [];
foreach ($allTecs as $t) {
    $allTecsByZona[$t['zona_nombre'] ?? 'Sin zona'][] = $t;
}

$rolId        = (int) $usuario['rol_id'];
$canCreate    = in_array($rolId, [1, 2, 3, 4]);   // rol 5 (Encargado de Zona) solo lectura
$rolesNombres = ['','Call Center','Mesa de Control','Supervisor CC','Administrador','Encargado de Zona'];
$fechaHoy     = date('Y-m-d');
?>
<div class="container">

    <!-- ── Barra superior ───────────────────────────────────────── -->
    <div class="topbar">
        <h1>Incidentes de Clientes Residenciales</h1>
        <div class="topbar-right">
            <span>👤 <?= htmlspecialchars($usuario['nombre']) ?>
                (<?= $rolesNombres[$rolId] ?? 'Rol '.$rolId ?>)
            </span>

            <form method="GET" action="">
                <input type="hidden" name="action" value="tablero">
                <input type="date" name="fecha" value="<?= htmlspecialchars($fecha) ?>"
                       style="padding:4px 6px;font-size:12px;border:1px solid #ccc;">
                <button type="submit" class="btn btn-primary" style="padding:5px 10px;">Ver</button>
            </form>
            <?php if (in_array($rolId, [1, 2, 3, 4, 5])): ?>
            <div class="dropdown" id="searchDropdown" style="position:relative;">
                <div class="buscador-wrap" style="display:flex;">
                    <input type="text" id="searchTicketInput" placeholder="Buscar num. ticket…"
                        onkeydown="if(event.key==='Enter') buscarTicket()">
                    <button onclick="buscarTicket()">🔍</button>
                </div>
                <div class="search-dropdown" id="searchDropdownMenu"></div>
            </div>
            <?php endif; ?>
            <div class="dropdown" id="menuDropdown">
                <button class="dropdown-toggle" onclick="toggleMenu(event)">☰ Menú</button>
                <div class="dropdown-menu" id="dropdownMenu">
                    <div class="menu-section">Tablero</div>
                    <a href="?action=tablero">📋 Tablero de hoy</a>
                    <?php if (in_array($rolId, [2, 4])): ?>
                    <div class="menu-section">Gestión</div>
                    <a href="?action=horarios.panel">🕐 Gestión de Horarios</a>
                    <a href="?action=tecnicos.panel">⚙ Gestión de Técnicos</a>
                    <?php endif; ?>
                    <?php if ($rolId === 4): ?>
                    <a href="?action=admin.usuarios">👥 Gestión de Usuarios</a>
                    <a href="?action=admin.reporte">📊 Reporte de Tickets</a>
                    <?php endif; ?>
                    <div class="menu-section">Sesión</div>
                    <button class="menu-btn" onclick="document.getElementById('frmLogout').submit()">
                        🚪 Cerrar sesión
                    </button>
                </div>
            </div>
            <form id="frmLogout" method="GET" action="" style="display:none;">
                <input type="hidden" name="action" value="logout">
            </form>
            <form method="GET" action="">
                <input type="hidden" name="action" value="logout">
                <button type="submit" class="btn-logout">Salir</button>
            </form>
        </div>
    </div>

    <div id="bannerNotif">
        <span class="banner-text">🔔 Activa las notificaciones para recibir avisos.</span>
        <button class="btn-activar" onclick="pedirPermisoNotificaciones()">🔔 Activar notificaciones</button>
        <button class="btn-dismiss" onclick="document.getElementById('bannerNotif').style.display='none'">×</button>
    </div>

    <div style="text-align:left;margin-bottom:5px;font-weight:bold;">
        <?= date('d/m/Y', strtotime($fecha)) ?>
    </div>

    <!-- ── TABLA ─────────────────────────────────────────────────── -->
    <table>
        <thead>
            <tr>
                <th rowspan="2">Hora</th>
                <?php foreach ($zonaSpans as $zonaNombre => $span): ?>
                <th colspan="<?= $span ?>"
                    class="h-<?= strtolower(str_replace(' ','_',$zonaNombre)) ?>">
                    <?= htmlspecialchars($zonaNombre) ?>
                </th>
                <?php endforeach; ?>
            </tr>
            <tr>
                <?php foreach ($tecnicosSorted as $t):
                    $disponible = (int)$t['status'] === 1;
                    $motivo     = $t['status_motivo'] ?? '';
                    $thClass    = $disponible ? '' : 'col-nodisponible';
                ?>
                <th class="<?= $thClass ?>" title="<?= htmlspecialchars($t['TecnicoNombre']) ?>">
                    <?= $t['TecnicoId'] ?>
                    <?php if (!$disponible): ?>
                    <span class="badge-nodisponible">
                        <?= $motivo ? htmlspecialchars(ucfirst($motivo)) : 'No disp.' ?>
                    </span>
                    <?php endif; ?>
                </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($horarios as $h): ?>
            <tr>
                <td><?= htmlspecialchars(substr($h['hora'],0,5)) ?></td>
                <?php foreach ($tecnicosSorted as $t):
                    $disponible = (int)$t['status'] === 1;
                    $ticket     = $tickets[$t['TecnicoId']][$h['horario_id']] ?? null;
                    $hasTicket  = $ticket !== null;
                    if (!$disponible): echo '<td class="cell-nodisponible"></td>'; continue; endif;
                    $cellClass = 'cell-ticket' . ($hasTicket ? ' occupied' : '');
                    // Lógica para los estados del ticket
                    if ($hasTicket) {
                        $numLlamadas = (int)($ticket['total_llamadas'] ?? 0);
                        if (($ticket['estado'] ?? '') === 'terminado') {
                            // El estado terminado tiene prioridad máxima de color
                            $cellClass .= ' estado-terminado';
                        } elseif ($numLlamadas >= 3) {
                            $cellClass .= ' estado-rojo';
                        } elseif ($numLlamadas === 2) {
                            $cellClass .= ' estado-segunda-llamada';
                        } elseif ($numLlamadas === 1) {
                            $cellClass .= ' estado-primera-llamada';
                        }
                    }
                ?>
                <td class="<?= $cellClass ?>"
                    data-tecnico-id="<?= $t['TecnicoId'] ?>"
                    data-tecnico-nombre="<?= htmlspecialchars($t['TecnicoNombre']) ?>"
                    data-horario-id="<?= $h['horario_id'] ?>"
                    data-horario="<?= htmlspecialchars(substr($h['hora'],0,5)) ?>"
                    data-fecha="<?= htmlspecialchars($fecha) ?>"
                    <?= $hasTicket ? 'data-ticket-id="'.$ticket['ticket_id'].'"' : '' ?>
                    data-can-create="<?= (!$hasTicket && $canCreate) ? '1' : '0' ?>"
                    onclick="handleCellClick(this)">
                    <?php if ($hasTicket): ?>
                    <div class="icon-wrap"><?= getIconHtml($ticket, $userColorMap) ?></div>
                    <?php endif; ?>
                </td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <!-- ── LISTA INFERIOR ────────────────────────────────────────── -->
    <div class="staff-lists">
        <?php
        $groups = [
            'Tabasco'                  => ['Centro','Chontalpa'],
            'Sierra'                   => ['Sierra'],
            'Interior de la República' => ['Carmen','Delicias','Allende','Merida'],
        ];
        foreach ($groups as $groupTitle => $zonas): ?>
        <div class="list-group">
            <h3><?= $groupTitle ?></h3>
            <?php foreach ($zonas as $z):
                if (!isset($allTecsByZona[$z])) continue; ?>
            <strong><?= $z ?></strong>
            <?php foreach ($allTecsByZona[$z] as $t):
                $disp    = (int)$t['status'] === 1;
                $mot     = $t['status_motivo'] ?? '';
                $liClass = $disp ? 'list-item' : 'list-item nodisponible';
            ?>
            <div class="<?= $liClass ?>">
                <span class="id-num"><?= $t['TecnicoId'] ?></span>
                <span class="list-item-nombre"><?= htmlspecialchars(strtoupper($t['TecnicoNombre'])) ?> <b><?= htmlspecialchars(strtoupper($t['num_telefono'])) ?></b> </span>
                <?php if (!$disp && $mot): ?>
                <span class="motivo-tag">(<?= htmlspecialchars($mot) ?>)</span>
                <?php endif; ?>
                <button class="btn-tecnico-status"
                        data-tecnico-id="<?= $t['TecnicoId'] ?>"
                        data-tecnico-nombre="<?= htmlspecialchars($t['TecnicoNombre']) ?>"
                        data-tecnico-motivo="<?= htmlspecialchars($mot) ?>"
                        onclick="openEditTecnicoModal(this)"
                        title="Cambiar disponibilidad">✏️</button>
            </div>
            <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>

        <div class="list-group">
            <h3>Call center</h3>
            <?php
            $usuarioModel2 = new UsuarioModel();
            $todosUs   = $usuarioModel2->getAll();
            $ccUsers   = array_filter($todosUs, fn($u) => in_array($u['rol_id'], [1, 3]));
            $mesaUsers = array_filter($todosUs, fn($u) => $u['rol_id'] == 2);
            foreach ($ccUsers as $u): ?>
            <div class="list-item <?= htmlspecialchars($u['color']) ?>">
                <span class="list-item-nombre"><?= htmlspecialchars(strtoupper($u['nombre'])) ?></span>
            </div>
            <?php endforeach; ?>
            <h3 style="margin-top:15px;">Mesa de control</h3>
            <?php foreach ($mesaUsers as $u): ?>
            <div class="list-item <?= htmlspecialchars($u['color']) ?>">
                <span class="list-item-nombre"><?= htmlspecialchars(strtoupper($u['nombre'])) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ══════════════════════════ MODAL TICKET ══════════════════════════ -->
<div class="modal-overlay" id="modalOverlay">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="modalTitle">Registrar Ticket</h3>
            <button class="modal-close" onclick="closeModal('modalOverlay')">×</button>
        </div>
        <div class="modal-body">
            <div class="modal-meta" id="modalMeta"></div>
            <div class="feedback" id="modalFeedback"></div>
            <input type="hidden" id="fTicketId">
            <input type="hidden" id="fFecha">
            <input type="hidden" id="fHorarioId">
            <input type="hidden" id="fTecnicoId">
            <label>Nombre del Cliente</label>
            <input type="text" id="fCliente" maxlength="255" placeholder="Nombre completo">
            <label>Colonia</label>
            <input type="text" id="fColonia" maxlength="255" placeholder="Nombre de la colonia">
            <label>Número de Ticket</label>
            <input type="text" id="fTicketNum" maxlength="255" placeholder="Ej. TKT-00123">
            <label>Descripción del Incidente</label>
            <textarea id="fDescripcion" maxlength="255" placeholder="Describe el incidente..."></textarea>
            <label>Teléfono de Contacto (10 dígitos)</label>
            <input type="tel" id="fTelefono" maxlength="10" placeholder="9931234567">

            <div id="rescheduleResult">
                <strong>✅ Ticket reagendado para:</strong>
                <div class="new-slot" id="rescheduleSlot"></div>
            </div>

            <div class="llamadas-section" id="llamadasSection" style="display:none;">
                <h4>📞 Registro de Llamadas</h4>
                <?php for ($n = 1; $n <= 3; $n++): ?>
                <fieldset class="llamada-bloque" id="llamadaBloque<?= $n ?>">
                    <legend>Llamada <?= $n ?></legend>
                    <div class="llamada-fields">
                        <div>
                            <label>Respuesta del Técnico</label>
                            <textarea id="lTecnico<?= $n ?>" maxlength="255" placeholder="Respuesta del técnico..."></textarea>
                        </div>
                        <div>
                            <label>Respuesta del Cliente</label>
                            <textarea id="lCliente<?= $n ?>" maxlength="255" placeholder="Respuesta del cliente..."></textarea>
                        </div>
                    </div>
                    <button class="btn-save-llamada" onclick="saveLlamada(<?= $n ?>)">💾 Guardar Llamada <?= $n ?></button>
                    <span class="llamada-status" id="lStatus<?= $n ?>"></span>
                </fieldset>
                <?php endfor; ?>
            </div>
        </div>
        <div class="modal-footer" id="modalFooter"></div>
    </div>
</div>

<!-- ══════════════════════ MODAL REAGENDAR ══════════════════════ -->
<div class="modal-overlay" id="modalReagendar">
    <div class="modal-box" style="width:480px;">
        <div class="modal-header">
            <h3>🔄 Reagendar Ticket</h3>
            <button class="modal-close" onclick="closeModal('modalReagendar')">×</button>
        </div>
        <div class="modal-body">
            <div class="feedback" id="reagendarFeedback"></div>
            <input type="hidden" id="rTicketId">

            <div class="reagendar-info" id="reagendarInfo"></div>

            <!-- Paso 1: configurar búsqueda -->
            <div class="reagendar-step active" id="rStep1">
                <div class="dias-selector">
                    <label>Buscar en los próximos:</label>
                    <select id="rDias">
                        <option value="3">3 días laborables</option>
                        <option value="5" selected>5 días laborables</option>
                        <option value="7">7 días laborables</option>
                        <option value="10">10 días laborables</option>
                        <option value="14">14 días laborables</option>
                    </select>
                    <button class="btn btn-primary" style="padding:5px 12px;"
                            onclick="cargarSlots()">Buscar slots</button>
                </div>
                <div id="rSlotsContainer">
                    <p style="font-size:11px;color:#888;">Selecciona el rango y haz clic en "Buscar slots".</p>
                </div>

                <!-- Opción automática -->
                <button class="btn-auto-reschedule" onclick="reagendarAutomatico()">
                    ⚡ Reagendar automáticamente al siguiente slot del mismo técnico
                </button>
            </div>

            <!-- Paso 2: confirmación -->
            <div class="reagendar-step" id="rStep2">
                <p style="font-size:12px;">¿Confirmar el reagendado?</p>
                <div id="rConfirmInfo" style="background:#f0f7ff;border:1px solid #1a4d6d;padding:10px;font-size:12px;border-radius:3px;">
                </div>
            </div>
        </div>
        <div class="modal-footer" id="reagendarFooter">
            <button class="btn btn-secondary" onclick="closeModal('modalReagendar')">Cancelar</button>
        </div>
    </div>
</div>

<!-- ══════════════════ MODAL DISPONIBILIDAD TÉCNICO ══════════════════ -->
<div class="modal-overlay" id="modalTecnico">
    <div class="modal-box" style="width:360px;">
        <div class="modal-header">
            <h3>Disponibilidad del Técnico</h3>
            <button class="modal-close" onclick="closeModal('modalTecnico')">×</button>
        </div>
        <div class="modal-body">
            <div class="feedback" id="tecnicoFeedback"></div>
            <input type="hidden" id="tTecnicoId">
            <p id="tTecnicoNombre" style="font-weight:bold;margin:0 0 12px;font-size:13px;"></p>
            <label>Estado de disponibilidad</label>
            <select id="tMotivo">
                <option value="">✅ Disponible</option>
                <option value="apoyo">🔧 No disponible — Apoyo</option>
                <option value="vacaciones">🏖 No disponible — Vacaciones</option>
                <option value="mecanico">No disponible - Mecánico</option>
            </select>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('modalTecnico')">Cancelar</button>
            <button class="btn btn-primary" onclick="saveTecnicoStatus()">Guardar</button>
        </div>
    </div>
</div>

<script>
const ROL_ID             = <?= (int) $rolId ?>;
const BASE_URL           = '<?= BASE_URL ?>';
const FECHA_HOY_SERVIDOR = '<?= $fechaHoy ?>';
const FECHA_TABLERO      = '<?= htmlspecialchars($fecha) ?>';

/* ── Menú ─────────────────────────────────────────────────── */
function toggleMenu(e) {
    e.stopPropagation();
    document.getElementById('dropdownMenu').classList.toggle('open');
}
document.addEventListener('click', () => document.getElementById('dropdownMenu').classList.remove('open'));

/* ── Lápiz técnico (rol 2) ──────────────────────────────── */
if (ROL_ID === 2) {
    document.querySelectorAll('.btn-tecnico-status').forEach(b => b.style.display = 'inline-block');
}

/* ══════════════════════════════════════════════════════════
   MODAL TICKET — abrir/crear/ver
══════════════════════════════════════════════════════════ */
function handleCellClick(cell) {
    const hasTicket = cell.classList.contains('occupied');
    const canCreate = cell.dataset.canCreate === '1';
    if (hasTicket)      openViewMode(parseInt(cell.dataset.ticketId));
    else if (canCreate) openCreateMode(cell);
}

function openCreateMode(cell) {
    resetModal();
    document.getElementById('modalTitle').textContent = 'Registrar Ticket';
    document.getElementById('modalMeta').textContent =
        `Técnico: ${cell.dataset.tecnicoNombre} | Horario: ${cell.dataset.horario} | Fecha: ${formatDate(cell.dataset.fecha)}`;
    document.getElementById('fFecha').value     = cell.dataset.fecha;
    document.getElementById('fHorarioId').value = cell.dataset.horarioId;
    document.getElementById('fTecnicoId').value = cell.dataset.tecnicoId;
    document.getElementById('llamadasSection').style.display = 'none';
    document.getElementById('rescheduleResult').style.display = 'none';
    setFieldsReadonly(false);
    document.getElementById('modalFooter').innerHTML = `
        <button class="btn btn-secondary" onclick="closeModal('modalOverlay')">Cancelar</button>
        <button class="btn btn-primary" onclick="saveTicket()">Guardar</button>
    `;
    openModal('modalOverlay');
}

async function openViewMode(ticketId) {
    resetModal();
    document.getElementById('modalTitle').textContent = 'Detalle del Ticket';
    setFieldsReadonly(true);

    const res  = await fetch(`${BASE_URL}?action=ticket.show&id=${ticketId}`);
    const json = await res.json();
    if (!json.success) { showFeedback('No se pudo cargar el ticket.', 'error'); openModal('modalOverlay'); return; }

    const t = json.data;
    document.getElementById('modalMeta').textContent =
        `Ticket #${t.ticket_id} | Registrado por: ${t.agente_nombre}`;
    document.getElementById('fTicketId').value    = t.ticket_id;
    document.getElementById('fFecha').value       = t.fecha;
    document.getElementById('fHorarioId').value   = t.horario_id;
    document.getElementById('fTecnicoId').value   = t.tecnico_id;
    document.getElementById('fCliente').value     = t.Cliente;
    document.getElementById('fColonia').value     = t.colonia;
    document.getElementById('fTicketNum').value   = t.Ticket;
    document.getElementById('fDescripcion').value = t.Descripcion;
    document.getElementById('fTelefono').value    = t.Telefono;
    document.getElementById('rescheduleResult').style.display = 'none';

    // Rol 5 (Encargado de Zona): solo lectura, sin llamadas ni reagendado
    const soloLectura = (ROL_ID === 5);

    if (soloLectura) {
        // Ocultar la sección de llamadas completamente
        document.getElementById('llamadasSection').style.display = 'none';
    } else {
        document.getElementById('llamadasSection').style.display = 'block';
        for (let n = 1; n <= 3; n++) {
            const ll = (t.llamadas && t.llamadas[n]) || {};
            document.getElementById(`lTecnico${n}`).value = ll.respuesta_tecnico || '';
            document.getElementById(`lCliente${n}`).value = ll.respuesta_cliente || '';
            const bloque = document.getElementById(`llamadaBloque${n}`);
            const status = document.getElementById(`lStatus${n}`);
            if (ll.llamada_id) {
                bloque.classList.add('llamada-guardada');
                status.textContent = '✓ Guardada'; status.style.color = '#155724';
            } else {
                bloque.classList.remove('llamada-guardada');
                status.textContent = '';
            }
        }
    }

    let footer = `<button class="btn btn-secondary" onclick="closeModal('modalOverlay')">Cerrar</button>`;    
        footer += `<button class="btn btn-danger" onclick="deleteTicket(${t.ticket_id})">Eliminar</button>`;
    // Reagendar y Editar solo para roles que pueden operar
    if (!soloLectura) {
        footer += `<button class="btn btn-reschedule" onclick="abrirModalReagendar(${t.ticket_id}, '${t.agente_nombre}', ${t.tecnico_id})">🔄 Reagendar</button>`;
        if (t.can_edit) footer += `<button class="btn btn-warning" onclick="enableEdit()">Editar</button>`;
        // Botón Completado: verde si está terminado (permite desmarcar), gris si no
        const esTerminado = t.estado === 'terminado';
        if (esTerminado) {
            footer += `<button class="btn btn-completado activo" onclick="toggleEstado(${t.ticket_id}, null)">Reabrir</button>`;
        } else {
            footer += `<button class="btn btn-completado" onclick="toggleEstado(${t.ticket_id}, 'terminado')">Terminar</button>`;
        }
    }
    document.getElementById('modalFooter').innerHTML = footer;
    openModal('modalOverlay');
}

function enableEdit() {
    setFieldsReadonly(false);
    document.getElementById('modalTitle').textContent = 'Editar Ticket';
    document.getElementById('modalFooter').innerHTML = `
        <button class="btn btn-secondary" onclick="closeModal('modalOverlay')">Cancelar</button>
        <button class="btn btn-primary" onclick="updateTicket()">Guardar Cambios</button>
    `;
}

/* ══════════════════════════════════════════════════════════
   MODAL REAGENDAR — lógica principal
══════════════════════════════════════════════════════════ */

// Datos del slot seleccionado para confirmar
let _slotPendiente = null;

function abrirModalReagendar(ticketId, agenteName, tecnicoIdActual) {
    // Resetear estado
    _slotPendiente = null;
    window._tecnicoIdActual = tecnicoIdActual; // guardar para preseleccionar en renderSlots
    document.getElementById('rTicketId').value = ticketId;
    document.getElementById('reagendarFeedback').className = 'feedback';
    document.getElementById('reagendarFeedback').textContent = '';
    document.getElementById('reagendarInfo').textContent =
        `Ticket #${ticketId} — Elige un nuevo técnico y horario disponible.`;
    document.getElementById('rSlotsContainer').innerHTML =
        '<p style="font-size:11px;color:#888;">Selecciona el rango y haz clic en "Buscar slots".</p>';
    document.getElementById('rDias').value = '5';

    // Mostrar paso 1
    mostrarPasoReagendar(1);
    document.getElementById('reagendarFooter').innerHTML =
        `<button class="btn btn-secondary" onclick="closeModal('modalReagendar')">Cancelar</button>`;

    openModal('modalReagendar');
}

function mostrarPasoReagendar(n) {
    document.querySelectorAll('.reagendar-step').forEach(el => el.classList.remove('active'));
    document.getElementById(`rStep${n}`).classList.add('active');
}

async function cargarSlots() {
    const ticketId = parseInt(document.getElementById('rTicketId').value);
    const dias     = parseInt(document.getElementById('rDias').value);
    const container = document.getElementById('rSlotsContainer');

    container.innerHTML = '<div class="slot-loading">⏳ Buscando horarios disponibles...</div>';

    try {
        const res  = await fetch(`${BASE_URL}?action=ticket.getSlots&id=${ticketId}&dias=${dias}`, { cache: 'no-store' });
        const json = await res.json();

        if (!json.success || !json.data.tecnicos.length) {
            container.innerHTML = '<div class="slot-empty">⚠ No se encontraron horarios disponibles en el período seleccionado.</div>';
            return;
        }

        renderSlots(json.data.tecnicos, ticketId);
    } catch {
        container.innerHTML = '<div class="slot-empty">✗ Error al consultar los horarios. Intenta de nuevo.</div>';
    }
}

function renderSlots(tecnicos, ticketId) {
    const container = document.getElementById('rSlotsContainer');

    // Selector de técnico
    let tecHtml = '<label style="font-size:11px;font-weight:bold;margin-top:0;">Técnico:</label>';
    tecHtml += '<select id="rSelectTecnico" onchange="actualizarSlots()" style="margin-bottom:10px;">';
    tecnicos.forEach(tec => {
        const seleccionado = (tec.tecnico_id === window._tecnicoIdActual) ? 'selected' : '';
        tecHtml += `<option value="${tec.tecnico_id}" ${seleccionado}
                        data-zona="${tec.zona_nombre}"
                        data-slots='${JSON.stringify(tec.slots)}'>
                        ${tec.nombre} (${tec.zona_nombre})
                    </option>`;
    });
    tecHtml += '</select>';

    // Selector de horario/fecha
    tecHtml += '<label style="font-size:11px;font-weight:bold;">Fecha y horario disponible:</label>';
    tecHtml += '<select id="rSelectSlot"></select>';

    // Botón confirmar
    tecHtml += `<div style="margin-top:12px;">
        <button class="btn btn-primary" style="width:100%;" onclick="prepararConfirmacion()">
            Confirmar selección →
        </button>
    </div>`;

    container.innerHTML = tecHtml;

    // Guardar referencia a los técnicos para actualizarSlots
    window._slotsData = tecnicos;
    actualizarSlots();
}

function actualizarSlots() {
    const tecSelect = document.getElementById('rSelectTecnico');
    if (!tecSelect) return;
    const tecId  = parseInt(tecSelect.value);
    const tec    = window._slotsData?.find(t => t.tecnico_id === tecId);
    const slotSel = document.getElementById('rSelectSlot');
    if (!slotSel) return;

    slotSel.innerHTML = '';
    (tec?.slots || []).forEach(s => {
        const opt = document.createElement('option');
        opt.value = JSON.stringify({ horario_id: s.horario_id, fecha: s.fecha });
        opt.textContent = `${s.fecha_fmt} — ${s.hora}`;
        slotSel.appendChild(opt);
    });
}

function prepararConfirmacion() {
    const tecSelect  = document.getElementById('rSelectTecnico');
    const slotSelect = document.getElementById('rSelectSlot');

    if (!tecSelect || !slotSelect || !slotSelect.value) {
        mostrarFeedbackReagendar('Selecciona un técnico y un horario.', 'error');
        return;
    }

    const tecId   = parseInt(tecSelect.value);
    const tecNombre = tecSelect.options[tecSelect.selectedIndex].textContent;
    const slotData  = JSON.parse(slotSelect.value);
    const slotLabel = slotSelect.options[slotSelect.selectedIndex].textContent;

    _slotPendiente = {
        tecnico_id : tecId,
        horario_id : slotData.horario_id,
        fecha      : slotData.fecha,
        tecNombre  : tecNombre,
        slotLabel  : slotLabel,
    };

    document.getElementById('rConfirmInfo').innerHTML = `
        <strong>Técnico:</strong> ${tecNombre}<br>
        <strong>Nuevo horario:</strong> ${slotLabel}
    `;

    mostrarPasoReagendar(2);
    document.getElementById('reagendarFooter').innerHTML = `
        <button class="btn btn-secondary" onclick="mostrarPasoReagendar(1)">← Volver</button>
        <button class="btn btn-primary" onclick="confirmarReagendado()">✅ Confirmar</button>
    `;
}

async function confirmarReagendado() {
    if (!_slotPendiente) return;
    const ticketId = parseInt(document.getElementById('rTicketId').value);

    const payload = {
        ticket_id  : ticketId,
        tecnico_id : _slotPendiente.tecnico_id,
        horario_id : _slotPendiente.horario_id,
        fecha      : _slotPendiente.fecha,
    };

    const res  = await fetch(`${BASE_URL}?action=ticket.reschedule`, {
        method : 'POST',
        headers: { 'Content-Type': 'application/json' },
        body   : JSON.stringify(payload),
    });
    const json = await res.json();

    if (json.success) {
        const d = json.data;

        // Actualizar el modal del ticket si está abierto
        document.getElementById('fFecha').value     = d.nueva_fecha;
        document.getElementById('fHorarioId').value = d.nuevo_horario_id;
        document.getElementById('rescheduleSlot').textContent =
            `${d.nueva_fecha_fmt} a las ${d.nueva_hora} hrs`;
        document.getElementById('rescheduleResult').style.display = 'block';

        closeModal('modalReagendar');

        // Ocultar el botón de reagendar en el footer del ticket
        const btnR = document.querySelector('#modalFooter .btn-reschedule');
        if (btnR) btnR.style.display = 'none';

        // Recargar al cerrar el modal de ticket
        document.getElementById('modalOverlay')
            .addEventListener('click', () => location.reload(), { once: true });

        mostrarFeedbackReagendar('✓ Reagendado correctamente.', 'success');
    } else {
        mostrarPasoReagendar(1);
        mostrarFeedbackReagendar(json.message || 'Error al reagendar.', 'error');
        document.getElementById('reagendarFooter').innerHTML =
            `<button class="btn btn-secondary" onclick="closeModal('modalReagendar')">Cancelar</button>`;
    }
}

async function reagendarAutomatico() {
    const ticketId = parseInt(document.getElementById('rTicketId').value);
    mostrarFeedbackReagendar('⏳ Buscando siguiente slot disponible...', 'info');

    const res  = await fetch(`${BASE_URL}?action=ticket.reschedule`, {
        method : 'POST',
        headers: { 'Content-Type': 'application/json' },
        body   : JSON.stringify({ ticket_id: ticketId }),
    });
    const json = await res.json();

    if (json.success) {
        const d = json.data;
        document.getElementById('fFecha').value     = d.nueva_fecha;
        document.getElementById('fHorarioId').value = d.nuevo_horario_id;
        document.getElementById('rescheduleSlot').textContent =
            `${d.nueva_fecha_fmt} a las ${d.nueva_hora} hrs`;
        document.getElementById('rescheduleResult').style.display = 'block';

        closeModal('modalReagendar');
        const btnR = document.querySelector('#modalFooter .btn-reschedule');
        if (btnR) btnR.style.display = 'none';

        document.getElementById('modalOverlay')
            .addEventListener('click', () => location.reload(), { once: true });
    } else {
        mostrarFeedbackReagendar(json.message || 'No se encontró slot disponible.', 'error');
    }
}

function mostrarFeedbackReagendar(msg, tipo) {
    const el = document.getElementById('reagendarFeedback');
    el.textContent = msg; el.className = 'feedback ' + tipo;
}

/* ── Guardar ticket ─────────────────────────────────────── */
async function saveTicket() {
    const payload = buildPayload();
    if (!validatePayload(payload)) return;
    const res  = await fetch(`${BASE_URL}?action=ticket.store`, {
        method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload),
    });
    const json = await res.json();
    if (json.success) { showFeedback('Ticket registrado.', 'success'); setTimeout(()=>location.reload(),900); }
    else showFeedback(json.message || 'Error al guardar.', 'error');
}

async function updateTicket() {
    const payload = buildPayload();
    payload.ticket_id = parseInt(document.getElementById('fTicketId').value);
    if (!validatePayload(payload)) return;
    const res  = await fetch(`${BASE_URL}?action=ticket.update`, {
        method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload),
    });
    const json = await res.json();
    if (json.success) { showFeedback('Ticket actualizado.', 'success'); setTimeout(()=>location.reload(),900); }
    else showFeedback(json.message || 'Error al actualizar.', 'error');
}

async function saveLlamada(n) {
    const ticketId = parseInt(document.getElementById('fTicketId').value);
    if (!ticketId) return;
    const payload = {
        ticket_id: ticketId, no_llamada: n,
        respuesta_tecnico: document.getElementById(`lTecnico${n}`).value.trim(),
        respuesta_cliente: document.getElementById(`lCliente${n}`).value.trim(),
    };
    const res  = await fetch(`${BASE_URL}?action=llamada.upsert`, {
        method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload),
    });
    const json = await res.json();
    const st = document.getElementById(`lStatus${n}`);
    if (json.success) {
        document.getElementById(`llamadaBloque${n}`).classList.add('llamada-guardada');
        st.textContent = '✓ Guardada'; st.style.color = '#155724';
    } else { st.textContent = '✗ Error'; st.style.color = '#721c24'; }
}

function buildPayload() {
    return {
        fecha      : document.getElementById('fFecha').value,
        horario_id : parseInt(document.getElementById('fHorarioId').value),
        tecnico_id : parseInt(document.getElementById('fTecnicoId').value),
        cliente    : document.getElementById('fCliente').value.trim(),
        colonia    : document.getElementById('fColonia').value.trim(),
        ticket_num : document.getElementById('fTicketNum').value.trim(),
        descripcion: document.getElementById('fDescripcion').value.trim(),
        telefono   : document.getElementById('fTelefono').value.trim(),
    };
}
function validatePayload(p) {
    if (!p.cliente || !p.ticket_num || !p.descripcion || !p.telefono) {
        showFeedback('Todos los campos son obligatorios.', 'error'); return false;
    }
    if (!/^\d{10}$/.test(p.telefono)) {
        showFeedback('El teléfono debe tener exactamente 10 dígitos.', 'error'); return false;
    }
    return true;
}
function setFieldsReadonly(ro) {
    ['fCliente','fColonia','fTicketNum','fDescripcion','fTelefono'].forEach(id => {
        const el = document.getElementById(id);
        ro ? el.setAttribute('readonly', true) : el.removeAttribute('readonly');
    });
}
function resetModal() {
    ['fTicketId','fFecha','fHorarioId','fTecnicoId',
     'fCliente','fColonia','fTicketNum','fDescripcion','fTelefono'].forEach(id =>
        document.getElementById(id).value = ''
    );
    for (let n = 1; n <= 3; n++) {
        document.getElementById(`lTecnico${n}`).value = '';
        document.getElementById(`lCliente${n}`).value = '';
        document.getElementById(`lStatus${n}`).textContent = '';
        document.getElementById(`llamadaBloque${n}`).classList.remove('llamada-guardada');
    }
    document.getElementById('rescheduleResult').style.display = 'none';
    const fb = document.getElementById('modalFeedback');
    fb.className = 'feedback'; fb.textContent = '';
}
function showFeedback(msg, type) {
    const el = document.getElementById('modalFeedback');
    el.textContent = msg; el.className = 'feedback ' + type;
}

/* ── Modal técnico ──────────────────────────────────────── */
function openEditTecnicoModal(btn) {
    document.getElementById('tTecnicoId').value           = btn.dataset.tecnicoId;
    document.getElementById('tTecnicoNombre').textContent = btn.dataset.tecnicoNombre;
    document.getElementById('tMotivo').value              = btn.dataset.tecnicoMotivo || '';
    const fb = document.getElementById('tecnicoFeedback');
    fb.className = 'feedback'; fb.textContent = '';
    openModal('modalTecnico');
}
async function saveTecnicoStatus() {
    const id     = parseInt(document.getElementById('tTecnicoId').value);
    const motivo = document.getElementById('tMotivo').value || null;
    const res  = await fetch(`${BASE_URL}?action=tecnico.status`, {
        method:'POST', headers:{'Content-Type':'application/json'},
        body:JSON.stringify({ tecnico_id: id, motivo }),
    });
    const json = await res.json();
    const fb = document.getElementById('tecnicoFeedback');
    if (json.success) {
        fb.textContent = '✓ Actualizado. Recargando...'; fb.className = 'feedback success';
        setTimeout(() => location.reload(), 800);
    } else { fb.textContent = json.message || 'Error'; fb.className = 'feedback error'; }
}

/* ── Helpers de modales ─────────────────────────────────── */
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
function formatDate(str) {
    if (!str) return '';
    const [y,m,d] = str.split('-');
    return `${d}/${m}/${y}`;
}
['modalOverlay','modalTecnico','modalReagendar'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) closeModal(id);
    });
});

/* ══════════════════════════════════════════════════════════
   ALERTAS DE ESCRITORIO (polling)
══════════════════════════════════════════════════════════ */
function alertaYaFired(key) { return sessionStorage.getItem('alerta_' + key) === '1'; }
function marcarAlertaFired(key) { sessionStorage.setItem('alerta_' + key, '1'); }
function horaLocalActual() {
    const a = new Date();
    return String(a.getHours()).padStart(2,'0') + ':' + String(a.getMinutes()).padStart(2,'0');
}
function fechaLocalActual() {
    const h = new Date();
    return h.getFullYear() + '-' + String(h.getMonth()+1).padStart(2,'0') + '-' + String(h.getDate()).padStart(2,'0');
}

async function verificarAlertas() {
    if (Notification.permission !== 'granted') return;
    if (FECHA_TABLERO !== FECHA_HOY_SERVIDOR) return;
    if (FECHA_HOY_SERVIDOR !== fechaLocalActual()) return;

    let tickets;
    try {
        const res = await fetch(`${BASE_URL}?action=notif.tickets`, { cache: 'no-store' });
        if (!res.ok) return;
        const json = await res.json();
        if (!json.success) return;
        tickets = json.tickets;
    } catch { return; }

    const horaActual = horaLocalActual();
    const fechaHoy   = fechaLocalActual();

    tickets.forEach(t => {
        if (t.hora_alerta !== horaActual) return;
        const key = `ticket-${t.ticket_id}-${fechaHoy}`;
        if (alertaYaFired(key)) return;
        marcarAlertaFired(key);
        const notif = new Notification('⚠ Precaución!', {
            body : `El ticket asignado a ${t.tecnico_nombre} está a punto de expirar.`,
            icon : `${BASE_URL}public/icon.png`,
            tag  : key,
            requireInteraction: true,
        });
        notif.onclick = () => { window.focus(); notif.close(); };
    });
}

function inicializarNotificaciones() {
    if (!('Notification' in window)) return;
    if (Notification.permission === 'granted') {
        verificarAlertas();
        setInterval(verificarAlertas, 30000);
    } else if (Notification.permission === 'default') {
        document.getElementById('bannerNotif').style.display = 'flex';
    }
}
async function pedirPermisoNotificaciones() {
    if (!('Notification' in window)) return;
    const result = await Notification.requestPermission();
    document.getElementById('bannerNotif').style.display = 'none';
    if (result === 'granted') { verificarAlertas(); setInterval(verificarAlertas, 30000); }
}

/* ══════════════════════════════════════════════════════════
   ESTADO DEL TICKET (Completado / Revertir)
══════════════════════════════════════════════════════════ */
async function toggleEstado(ticketId, nuevoEstado) {
    const res  = await fetch(`${BASE_URL}?action=ticket.setEstado`, {
        method : 'POST',
        headers: { 'Content-Type': 'application/json' },
        body   : JSON.stringify({ ticket_id: ticketId, estado: nuevoEstado }),
    });
    const json = await res.json();
    if (json.success) {
        // Recargar para actualizar el color de la celda en el tablero
        location.reload();
    } else {
        showFeedback(json.message || 'Error al cambiar el estado.', 'error');
    }
}
/* ══════════════════════════════════════════════════════════
   BUSCADOR DE TICKETS (rol 4 y 5)
══════════════════════════════════════════════════════════ */
async function buscarTicket() {
    const q = document.getElementById('searchTicketInput')?.value.trim();
    if (!q) return;

    const menu = document.getElementById('searchDropdownMenu');
    menu.innerHTML = '<div class="search-no-results">⏳ Buscando...</div>';
    menu.classList.add('open');

    try {
        const res  = await fetch(`${BASE_URL}?action=ticket.search&q=${encodeURIComponent(q)}`, { cache: 'no-store' });
        const json = await res.json();

        if (!json.success || !json.data.results.length) {
            menu.innerHTML = '<div class="search-no-results">No se encontraron tickets.</div>';
            return;
        }

        menu.innerHTML = json.data.results.map(r => `
            <div class="search-result-item"
                 onclick="irATicket('${r.fecha}', ${r.tecnico_id}, ${r.horario_id})">
                <div class="sri-ticket">🎫 ${r.Ticket}</div>
                <div class="sri-meta">
                    ${r.tecnico_nombre} &nbsp;|&nbsp; ${formatDate(r.fecha)} ${r.hora} hrs
                    &nbsp;|&nbsp; ${r.Cliente}
                </div>
            </div>
        `).join('');
    } catch {
        menu.innerHTML = '<div class="search-no-results">Error al buscar.</div>';
    }
}

function irATicket(fecha, tecnicoId, horarioId) {
    document.getElementById('searchDropdownMenu').classList.remove('open');
    // Navegar al tablero en la fecha del ticket con parámetros de highlight
    window.location.href = `${BASE_URL}?action=tablero&fecha=${fecha}&hl_tec=${tecnicoId}&hl_hor=${horarioId}`;
}

// Cerrar dropdown de búsqueda al hacer clic fuera
document.addEventListener('click', e => {
    const wrap = document.getElementById('searchDropdown');
    if (wrap && !wrap.contains(e.target)) {
        document.getElementById('searchDropdownMenu')?.classList.remove('open');
    }
});

// Al cargar, si hay parámetros hl_tec y hl_hor, parpadear la celda
(function highlightOnLoad() {
    const params   = new URLSearchParams(window.location.search);
    const hlTec    = params.get('hl_tec');
    const hlHor    = params.get('hl_hor');
    if (!hlTec || !hlHor) return;

    // Buscar la celda por data attributes
    const celda = document.querySelector(
        `td[data-tecnico-id="${hlTec}"][data-horario-id="${hlHor}"]`
    );
    if (!celda) return;

    // Scroll a la celda centrada en la pantalla
    celda.scrollIntoView({ behavior: 'smooth', block: 'center' });

    // Parpadeo controlado por JavaScript (evita conflictos de CSS !important)
    let parpadeos = 0;
    const maxParpadeos = 21; // 21 ciclos de 500ms = 10.5 segundos exactos
    
    // Iniciar con la clase activada
    celda.classList.add('cell-highlight-strong');
    
    const intervalo = setInterval(() => {
        celda.classList.toggle('cell-highlight-strong');
        parpadeos++;
        
        if (parpadeos >= maxParpadeos) {
            clearInterval(intervalo);
            celda.classList.remove('cell-highlight-strong');
        }
    }, 500);
})();
inicializarNotificaciones();
</script>
</body>
</html>
