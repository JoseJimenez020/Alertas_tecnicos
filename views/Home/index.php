<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

        /* ── Banner permiso notificaciones ─────────────────────── */
        #bannerNotif {
            background: #fff3cd; border: 1px solid #ffc107; color: #664d03;
            padding: 8px 14px; margin-bottom: 10px; font-size: 12px;
            display: none; align-items: center; gap: 10px; flex-wrap: wrap;
        }
        #bannerNotif .banner-text { flex: 1; }
        #bannerNotif .btn-activar {
            padding: 5px 14px; background: #1a4d6d; color: #fff;
            border: none; cursor: pointer; font-size: 12px; font-weight: bold;
            white-space: nowrap;
        }
        #bannerNotif .btn-activar:hover { background: #245f85; }
        #bannerNotif .btn-dismiss {
            background: none; color: #888; font-size: 18px; line-height: 1;
            padding: 0; cursor: pointer; border: none; flex-shrink: 0;
        }

        /* ── Tabla ──────────────────────────────────────────────── */
        td.cell-ticket { cursor: pointer; position: relative; }
        td.cell-ticket:hover { background: #f0f7ff; }
        td.cell-ticket.occupied { cursor: pointer; }
        .icon-wrap { display: flex; align-items: center; justify-content: center; height: 100%; }

        th.col-nodisponible { background-color: #156082 !important; color: #fff !important; }
        .badge-nodisponible {
            display: block; font-size: 8px; background: rgba(255,255,255,.22);
            padding: 1px 3px; border-radius: 6px; margin-top: 2px; line-height: 1.3;
        }
        td.cell-nodisponible { background-color: #156082 !important; cursor: default !important; }
        td.cell-nodisponible:hover { background-color: #156082 !important; }

        /* ── Modal ──────────────────────────────────────────────── */
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
        .modal-close { background: none; border: none; color: #fff; font-size: 20px; cursor: pointer; line-height: 1; }
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

        .modal-meta {
            font-size: 11px; color: #1a4d6d; background: #e8f1f8;
            padding: 6px 10px; margin-bottom: 10px;
        }
        .feedback { font-size: 11px; padding: 6px 10px; margin-bottom: 8px; display: none; }
        .feedback.success { background: #d4edda; color: #155724; display: block; }
        .feedback.error   { background: #f8d7da; color: #721c24; display: block; }
        .feedback.info    { background: #e8f1f8; color: #1a4d6d; display: block; }

        /* Caja de reagendado confirmado */
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
        .llamada-bloque {
            border: 1px solid #dde; background: #f8f9ff;
            padding: 10px; margin-bottom: 8px;
        }
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
            cursor: pointer; font-size: 11px; padding: 1px 3px;
            line-height: 1; color: #1a4d6d; flex-shrink: 0;
        }
        .btn-tecnico-status:hover { color: #e67e00; }
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
$canCreate    = in_array($rolId, [1, 2, 3]);
$rolesNombres = ['','Call Center','Mesa de Control','Supervisor CC','Administrador'];
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
        <span class="banner-text">
            🔔 Activa las notificaciones para recibir avisos 30 minutos antes de cada ticket asignado.
        </span>
        <button class="btn-activar" onclick="pedirPermisoNotificaciones()">🔔 Activar notificaciones</button>
        <button class="btn-dismiss"
                onclick="document.getElementById('bannerNotif').style.display='none'"
                title="Cerrar">×</button>
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

                    if (!$disponible):
                        echo '<td class="cell-nodisponible"></td>';
                        continue;
                    endif;
                    $cellClass = 'cell-ticket' . ($hasTicket ? ' occupied' : '');
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
                <span class="list-item-nombre">
                    <?= htmlspecialchars(strtoupper($t['TecnicoNombre'])) ?>
                </span>
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
            $ccUsers   = array_filter($todosUs, fn($u) => $u['rol_id'] == 1 || $u['rol_id'] == 3);
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

<!-- ═══════════════════════════ MODAL TICKET ═══════════════════════════ -->
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

            <!-- Resultado de reagendado — visible solo tras usar el botón -->
            <div id="rescheduleResult">
                <strong>✅ Ticket reagendado para:</strong>
                <div class="new-slot" id="rescheduleSlot"></div>
            </div>

            <!-- Llamadas (solo en modo ver/editar) -->
            <div class="llamadas-section" id="llamadasSection" style="display:none;">
                <h4>📞 Registro de Llamadas</h4>
                <?php for ($n = 1; $n <= 3; $n++): ?>
                <fieldset class="llamada-bloque" id="llamadaBloque<?= $n ?>">
                    <legend>Llamada <?= $n ?></legend>
                    <div class="llamada-fields">
                        <div>
                            <label>Respuesta del Técnico</label>
                            <textarea id="lTecnico<?= $n ?>" maxlength="255"
                                      placeholder="Respuesta del técnico..."></textarea>
                        </div>
                        <div>
                            <label>Respuesta del Cliente</label>
                            <textarea id="lCliente<?= $n ?>" maxlength="255"
                                      placeholder="Respuesta del cliente..."></textarea>
                        </div>
                    </div>
                    <button class="btn-save-llamada" onclick="saveLlamada(<?= $n ?>)">
                        💾 Guardar Llamada <?= $n ?>
                    </button>
                    <span class="llamada-status" id="lStatus<?= $n ?>"></span>
                </fieldset>
                <?php endfor; ?>
            </div>
        </div>
        <div class="modal-footer" id="modalFooter"></div>
    </div>
</div>

<!-- ═══════════════ MODAL DISPONIBILIDAD TÉCNICO ═══════════════ -->
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

/* ── Menú ───────────────────────────────────────────────────── */
function toggleMenu(e) {
    e.stopPropagation();
    document.getElementById('dropdownMenu').classList.toggle('open');
}
document.addEventListener('click', () => {
    document.getElementById('dropdownMenu').classList.remove('open');
});

/* ── Lápiz técnico (rol 2) ──────────────────────────────────── */
if (ROL_ID === 2) {
    document.querySelectorAll('.btn-tecnico-status')
            .forEach(b => b.style.display = 'inline-block');
}

/* ══════════════════════════════════════════════════════════════
   MODAL DE TICKET
══════════════════════════════════════════════════════════════ */
function handleCellClick(cell) {
    const hasTicket = cell.classList.contains('occupied');
    const canCreate = cell.dataset.canCreate === '1';
    const ticketId  = cell.dataset.ticketId || null;
    if (hasTicket)      openViewMode(parseInt(ticketId));
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
        <button class="btn btn-primary"   onclick="saveTicket()">Guardar</button>
    `;
    openModal('modalOverlay');
}

async function openViewMode(ticketId) {
    resetModal();
    document.getElementById('modalTitle').textContent = 'Detalle del Ticket';
    setFieldsReadonly(true);

    const res  = await fetch(`${BASE_URL}?action=ticket.show&id=${ticketId}`);
    const json = await res.json();
    if (!json.success) {
        showFeedback('No se pudo cargar el ticket.', 'error');
        openModal('modalOverlay'); return;
    }
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

    // Cargar llamadas
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

    // Footer: Cerrar + Reagendar (siempre) + Editar (si tiene permiso)
    let footer = `<button class="btn btn-secondary" onclick="closeModal('modalOverlay')">Cerrar</button>`;
    footer += `<button class="btn btn-reschedule" id="btnReagendar" onclick="reagendarTicket()">
                   🔄 Reagendar
               </button>`;
    if (t.can_edit) {
        footer += `<button class="btn btn-warning" onclick="enableEdit()">Editar</button>`;
    }
    document.getElementById('modalFooter').innerHTML = footer;
    openModal('modalOverlay');
}

function enableEdit() {
    setFieldsReadonly(false);
    document.getElementById('modalTitle').textContent = 'Editar Ticket';
    document.getElementById('modalFooter').innerHTML = `
        <button class="btn btn-secondary" onclick="closeModal('modalOverlay')">Cancelar</button>
        <button class="btn btn-primary"   onclick="updateTicket()">Guardar Cambios</button>
    `;
}

/* ── Reagendar ticket ────────────────────────────────────────── */
async function reagendarTicket() {
    const ticketId = parseInt(document.getElementById('fTicketId').value);
    if (!ticketId) return;

    const btn = document.getElementById('btnReagendar');
    btn.disabled    = true;
    btn.textContent = '⏳ Buscando...';

    const res  = await fetch(`${BASE_URL}?action=ticket.reschedule`, {
        method : 'POST',
        headers: { 'Content-Type': 'application/json' },
        body   : JSON.stringify({ ticket_id: ticketId }),
    });
    const json = await res.json();

    btn.disabled    = false;
    btn.textContent = '🔄 Reagendar';

    if (json.success) {
        const d = json.data;
        // Actualizar campos ocultos con el nuevo slot
        document.getElementById('fFecha').value     = d.nueva_fecha;
        document.getElementById('fHorarioId').value = d.nuevo_horario_id;

        // Mostrar confirmación visual
        document.getElementById('rescheduleSlot').textContent =
            `${d.nueva_fecha_fmt} a las ${d.nueva_hora} hrs`;
        document.getElementById('rescheduleResult').style.display = 'block';

        // Actualizar el meta del modal
        const metaEl = document.getElementById('modalMeta');
        const metaBase = metaEl.textContent.split('|')[0].trim();
        metaEl.textContent = `${metaBase} | 📅 Reagendado: ${d.nueva_fecha_fmt} ${d.nueva_hora}`;

        // Ocultar el botón de reagendar una vez usado (ya reagendado)
        btn.style.display = 'none';

        // El tablero se recarga al cerrar para reflejar el cambio
        document.getElementById('modalOverlay')
            .addEventListener('click', () => location.reload(), { once: true });
    } else {
        showFeedback(json.message || 'No se pudo reagendar.', 'error');
    }
}

async function saveTicket() {
    const payload = buildPayload();
    if (!validatePayload(payload)) return;
    const res  = await fetch(`${BASE_URL}?action=ticket.store`, {
        method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload),
    });
    const json = await res.json();
    if (json.success) { showFeedback('Ticket registrado correctamente.', 'success'); setTimeout(()=>location.reload(),900); }
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

/* ── Modal técnico ──────────────────────────────────────────── */
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

function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
function formatDate(str) {
    if (!str) return '';
    const [y,m,d] = str.split('-');
    return `${d}/${m}/${y}`;
}
['modalOverlay','modalTecnico'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) closeModal(id);
    });
});

/* ══════════════════════════════════════════════════════════════
   ALERTAS DE ESCRITORIO (polling)
══════════════════════════════════════════════════════════════ */
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
    if (result === 'granted') {
        verificarAlertas();
        setInterval(verificarAlertas, 30000);
    }
}

inicializarNotificaciones();
</script>
</body>
</html>
