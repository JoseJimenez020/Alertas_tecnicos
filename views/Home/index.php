<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tablero de Incidentes</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>public/main.css">
    <style>
        /* ── Barra superior ───────────────────────────────────────── */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            flex-wrap: wrap;
            gap: 8px;
        }
        .topbar h1 { margin: 0; font-size: 18px; }
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 12px;
        }
        .topbar-right form { margin: 0; }
        .btn-logout {
            padding: 5px 12px;
            background: #1a4d6d;
            color: #fff;
            border: none;
            cursor: pointer;
            font-size: 12px;
        }
        .btn-logout:hover { background: #245f85; }

        /* ── Celda del tablero ────────────────────────────────────── */
        td.cell-ticket {
            cursor: pointer;
            position: relative;
        }
        td.cell-ticket:hover { background: #f0f7ff; }
        td.cell-ticket.occupied { cursor: pointer; }
        td.cell-ticket.locked { cursor: default; }

        .icon-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
        }

        /* ── Modal ────────────────────────────────────────────────── */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.45);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.open { display: flex; }
        .modal-box {
            background: #fff;
            width: 460px;
            max-width: 96vw;
            box-shadow: 0 8px 32px rgba(0,0,0,.22);
        }
        .modal-header {
            background: #1a4d6d;
            color: #fff;
            padding: 12px 18px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 { margin: 0; font-size: 14px; }
        .modal-close {
            background: none;
            border: none;
            color: #fff;
            font-size: 20px;
            cursor: pointer;
            line-height: 1;
        }
        .modal-body {
            padding: 18px;
        }
        .modal-body label {
            display: block;
            font-size: 11px;
            color: #555;
            margin-bottom: 3px;
            margin-top: 10px;
        }
        .modal-body label:first-child { margin-top: 0; }
        .modal-body input,
        .modal-body select,
        .modal-body textarea {
            width: 100%;
            box-sizing: border-box;
            padding: 7px 9px;
            border: 1px solid #ccc;
            font-size: 12px;
            font-family: Arial, sans-serif;
        }
        .modal-body textarea { resize: vertical; min-height: 60px; }
        .modal-body input[readonly],
        .modal-body textarea[readonly],
        .modal-body select[disabled] {
            background: #f5f5f5;
            color: #444;
        }
        .modal-footer {
            padding: 12px 18px;
            background: #f5f5f5;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }
        .btn { padding: 7px 18px; border: none; cursor: pointer; font-size: 12px; }
        .btn-primary { background: #1a4d6d; color: #fff; }
        .btn-primary:hover { background: #245f85; }
        .btn-secondary { background: #ccc; color: #333; }
        .btn-secondary:hover { background: #bbb; }
        .btn-warning { background: #e67e00; color: #fff; }
        .btn-warning:hover { background: #cc7000; }

        /* info de técnico/horario en el modal */
        .modal-meta {
            font-size: 11px;
            color: #1a4d6d;
            background: #e8f1f8;
            padding: 6px 10px;
            margin-bottom: 10px;
        }

        /* mensajes de feedback */
        .feedback {
            font-size: 11px;
            padding: 6px 10px;
            margin-bottom: 8px;
            display: none;
        }
        .feedback.success { background: #d4edda; color: #155724; display: block; }
        .feedback.error   { background: #f8d7da; color: #721c24; display: block; }
    </style>
</head>
<body>
<?php
/**
 * Mapa de colores de usuario a clase CSS del ícono.
 * Clave: rol_id del agente que creó el ticket
 * Valor: [forma, clase-color]
 * rol 1 (CC)       → círculo, color según usuario
 * rol 2 (Mesa)     → cuadrado, color según usuario
 * rol 3 (Superv)   → círculo, color según usuario
 */

    // Mapa usuario_id → color CSS (basado en los colores del callcenter/mesa ya definidos)
    $userColorMap = [
        1  => 'bg-green',       // ELOISA
        2  => 'bg-blue',        // HENRY
        3  => 'bg-peach',       // LIZBETH 
        4  => 'bg-yellow',      // AURORA
        5  => 'bg-orange',      // SHEILA
        6  => 'bg-bluemarco',   // MARCOTULIO
        7  => 'bg-purple',      // MELQUISEDET
        8  => 'bg-gray',        // MELISSA
        9  => 'bg-lightblue',   // ABEL
        10 => 'bg-orange',      // MARIA JOSE (Mesa de control)
        11 => 'bg-m-blue',      // MAYRA (Mesa de control)
        12 => 'bg-green',      // MARIA GUADALUPE (Mesa de control)
        13 => 'bg-yellow',      // OSCAR (Mesa de control)
    ];

function getIconHtml(array $ticket): string {
    global $userColorMap;
    $rolId   = (int) $ticket['agente_rol'];
    $userId  = (int) $ticket['usuario_id'];
    $color   = $userColorMap[$userId] ?? 'bg-gray';
    // rol 2 = cuadrado, otros = círculo
    $shape   = ($rolId === 2) ? 'square' : 'circle';
    return '<span class="' . $shape . ' ' . $color . '"></span>';
}

// Ordenar técnicos para encabezado de columnas
$zonaOrder = ['Centro','Chontalpa','Sierra','Carmen','Delicias','Allende','Merida'];
$tecnicosSorted = [];
foreach ($zonaOrder as $z) {
    if (isset($tecnicosGroup[$z])) {
        foreach ($tecnicosGroup[$z] as $t) {
            $tecnicosSorted[] = $t;
        }
    }
}
// Cualquier zona no listada al final
foreach ($tecnicosGroup as $zona => $lista) {
    if (!in_array($zona, $zonaOrder)) {
        foreach ($lista as $t) $tecnicosSorted[] = $t;
    }
}

// Encabezado de zonas con colspan dinámico
$zonaSpans = [];
foreach ($tecnicosSorted as $t) {
    $z = $t['zona_nombre'];
    $zonaSpans[$z] = ($zonaSpans[$z] ?? 0) + 1;
}

$rolId  = (int) $usuario['rol_id'];
$canCreate = in_array($rolId, [1, 2, 3]);
?>
<div class="container">

    <!-- Barra superior -->
    <div class="topbar">
        <h1>Incidentes de Clientes Residenciales</h1>
        <div class="topbar-right">
            <span>👤 <?= htmlspecialchars($usuario['nombre']) ?>
                (<?= ['','Call Center','Mesa de Control','Supervisor CC','Administrador'][$rolId] ?? 'Rol '.$rolId ?>)
            </span>
            <form method="GET" action="">
                <input type="hidden" name="action" value="tablero">
                <input type="date" name="fecha" value="<?= htmlspecialchars($fecha) ?>"
                       style="padding:4px 6px;font-size:12px;border:1px solid #ccc;">
                <button type="submit" class="btn btn-primary" style="padding:5px 10px;">Ver</button>
            </form>
            <form method="GET" action="">
                <input type="hidden" name="action" value="logout">
                <button type="submit" class="btn-logout">Salir</button>
            </form>
        </div>
    </div>

    <div style="text-align:left;margin-bottom:5px;font-weight:bold;">
        <?= date('d/m/Y', strtotime($fecha)) ?>
    </div>

    <!-- TABLA DE DOBLE ENTRADA -->
    <table>
        <thead>
            <tr>
                <th rowspan="2">Hora</th>
                <?php foreach ($zonaSpans as $zonaNombre => $span): ?>
                <th colspan="<?= $span ?>" class="h-<?= strtolower(str_replace(' ','_',$zonaNombre)) ?>">
                    <?= htmlspecialchars($zonaNombre) ?>
                </th>
                <?php endforeach; ?>
            </tr>
            <tr>
                <?php foreach ($tecnicosSorted as $t): ?>
                <th title="<?= htmlspecialchars($t['TecnicoNombre']) ?>">
                    <?= $t['TecnicoId'] ?>
                </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($horarios as $h): ?>
            <tr>
                <td><?= htmlspecialchars($h['hora']) ?></td>
                <?php foreach ($tecnicosSorted as $t):
                    $ticket = $tickets[$t['TecnicoId']][$h['horario_id']] ?? null;
                    $hasTicket = $ticket !== null;
                ?>
                <td class="cell-ticket <?= $hasTicket ? 'occupied' : '' ?>"
                    data-tecnico-id="<?= $t['TecnicoId'] ?>"
                    data-tecnico-nombre="<?= htmlspecialchars($t['TecnicoNombre']) ?>"
                    data-horario-id="<?= $h['horario_id'] ?>"
                    data-horario="<?= htmlspecialchars($h['hora']) ?>"
                    data-fecha="<?= htmlspecialchars($fecha) ?>"
                    <?= $hasTicket ? 'data-ticket-id="'.$ticket['ticket_id'].'"' : '' ?>
                    data-can-create="<?= (!$hasTicket && $canCreate) ? '1' : '0' ?>"
                    onclick="handleCellClick(this)">
                    <?php if ($hasTicket): ?>
                    <div class="icon-wrap"><?= getIconHtml($ticket) ?></div>
                    <?php endif; ?>
                </td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <!-- LISTA DE TÉCNICOS -->
    <div class="staff-lists">
        <?php
        $groups = [
            'Tabasco'                   => ['Centro','Chontalpa'],
            'Sierra'                    => ['Sierra'],
            'Interior de la República'  => ['Carmen','Delicias','Allende','Merida'],
        ];
        foreach ($groups as $groupTitle => $zonas): ?>
        <div class="list-group">
            <h3><?= $groupTitle ?></h3>
            <?php foreach ($zonas as $z):
                if (!isset($tecnicosGroup[$z])) continue; ?>
            <strong><?= $z ?></strong>
            <?php foreach ($tecnicosGroup[$z] as $t): ?>
            <div class="list-item">
                <span class="id-num"><?= $t['TecnicoId'] ?></span>
                <?= htmlspecialchars(strtoupper($t['TecnicoNombre'])) ?>
            </div>
            <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>

        <div class="list-group">
            <h3>Call center</h3>
            <div class="list-item cc-green">ELOISA DEL CARMEN LARIOS CRUZ</div>
            <div class="list-item cc-blue">HENRY ROBERT GERONIMO PEREZ</div>
            <div class="list-item cc-peach">LIZBETH CRUZ CORNELIO</div>
            <div class="list-item cc-yellow">AURORA DOMINGUEZ ESCAMILLA</div>
            <div class="list-item cc-orange">SHEILA ANAHI ASTUDILLO GUTIERREZ</div>
            <div class="list-item cc-bluemarco">MARCOTULIO GARCIA HERNANDEZ</div>
            <div class="list-item cc-purple">MELQUISEDET MENDEZ GONZALEZ</div>
            <div class="list-item cc-pink">MELISSA OLSIN RUIZ</div>
            <div class="list-item cc-lightblue">ABEL HERNANDEZ HERNANDEZ</div>

            <h3 style="margin-top: 15px;">Mesa de control</h3>
            <div class="list-item cc-orange">MARIA JOSE CASTELLANOS TALANGO</div>
            <div class="list-item cc-m-blue">MAYRA GUADALUPE TRIANO JIMENEZ</div>
            <div class="list-item cc-green">MARIA GUADALUPE PINTOR PEREZ</div>
            <div class="list-item cc-yellow">OSCAR MARIO RICARDEZ RAMON</div>
        </div>

    </div>
        
</div>

<!-- ═══════════════════════════════════════════════════════════════════════
     MODAL ÚNICO — cambia de modo según acción
═══════════════════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="modalOverlay">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="modalTitle">Registrar Ticket</h3>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <div class="modal-body">
            <div class="modal-meta" id="modalMeta"></div>
            <div class="feedback" id="modalFeedback"></div>

            <!-- Campos ocultos -->
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
        </div>
        <div class="modal-footer" id="modalFooter">
            <!-- botones se insertan dinámicamente -->
        </div>
    </div>
</div>

<script>
const ROL_ID  = <?= (int) $rolId ?>;
const BASE_URL = '<?= BASE_URL ?>';
const TODAY_FECHA = '<?= htmlspecialchars($fecha) ?>';

/* ── Apertura de celda ─────────────────────────────────────────────── */
function handleCellClick(cell) {
    const hasTicket   = cell.classList.contains('occupied');
    const canCreate   = cell.dataset.canCreate === '1';
    const ticketId    = cell.dataset.ticketId  || null;

    if (hasTicket) {
        openViewMode(parseInt(ticketId));
    } else if (canCreate) {
        openCreateMode(cell);
    }
}

/* ── Modo: CREAR ───────────────────────────────────────────────────── */
function openCreateMode(cell) {
    resetModal();
    document.getElementById('modalTitle').textContent = 'Registrar Ticket';
    document.getElementById('modalMeta').textContent  =
        `Técnico: ${cell.dataset.tecnicoNombre} | Horario: ${cell.dataset.horario} | Fecha: ${formatDate(cell.dataset.fecha)}`;

    document.getElementById('fFecha').value      = cell.dataset.fecha;
    document.getElementById('fHorarioId').value  = cell.dataset.horarioId;
    document.getElementById('fTecnicoId').value  = cell.dataset.tecnicoId;

    setFieldsReadonly(false);

    document.getElementById('modalFooter').innerHTML = `
        <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
        <button class="btn btn-primary"   onclick="saveTicket()">Guardar</button>
    `;
    openModal();
}

/* ── Modo: VER / EDITAR ────────────────────────────────────────────── */
async function openViewMode(ticketId) {
    resetModal();
    document.getElementById('modalTitle').textContent = 'Detalle del Ticket';
    setFieldsReadonly(true);

    const res  = await fetch(`${BASE_URL}?action=ticket.show&id=${ticketId}`);
    const json = await res.json();

    if (!json.success) {
        showFeedback('No se pudo cargar el ticket.', 'error');
        return;
    }

    const t = json.data;
    document.getElementById('modalMeta').textContent =
        `Ticket #${t.ticket_id} | Registrado por: ${t.agente_nombre}`;

    document.getElementById('fTicketId').value   = t.ticket_id;
    document.getElementById('fFecha').value      = t.fecha;
    document.getElementById('fHorarioId').value  = t.horario_id;
    document.getElementById('fTecnicoId').value  = t.tecnico_id;
    document.getElementById('fCliente').value    = t.Cliente;
    document.getElementById('fColonia').value    = t.colonia;
    document.getElementById('fTicketNum').value  = t.Ticket;
    document.getElementById('fDescripcion').value= t.Descripcion;
    document.getElementById('fTelefono').value   = t.Telefono;

    // Botones según rol
    let footerHtml = `<button class="btn btn-secondary" onclick="closeModal()">Cerrar</button>`;
    if (t.can_edit) {
        footerHtml += `<button class="btn btn-warning" id="btnEdit" onclick="enableEdit()">Editar</button>`;
    }
    document.getElementById('modalFooter').innerHTML = footerHtml;
    openModal();
}

/* ── Habilitar edición (Supervisor CC) ─────────────────────────────── */
function enableEdit() {
    setFieldsReadonly(false);
    document.getElementById('modalTitle').textContent = 'Editar Ticket';
    document.getElementById('modalFooter').innerHTML = `
        <button class="btn btn-secondary" onclick="closeModal()">Cancelar</button>
        <button class="btn btn-primary"   onclick="updateTicket()">Guardar Cambios</button>
    `;
}

/* ── Guardar nuevo ticket ──────────────────────────────────────────── */
async function saveTicket() {
    const payload = buildPayload();
    if (!validatePayload(payload)) return;

    const res  = await fetch(`${BASE_URL}?action=ticket.store`, {
        method : 'POST',
        headers: { 'Content-Type': 'application/json' },
        body   : JSON.stringify(payload),
    });
    const json = await res.json();

    if (json.success) {
        showFeedback('Ticket registrado correctamente.', 'success');
        setTimeout(() => location.reload(), 900);
    } else {
        showFeedback(json.message || 'Error al guardar.', 'error');
    }
}

/* ── Actualizar ticket existente ───────────────────────────────────── */
async function updateTicket() {
    const payload = buildPayload();
    payload.ticket_id = parseInt(document.getElementById('fTicketId').value);
    if (!validatePayload(payload)) return;

    const res  = await fetch(`${BASE_URL}?action=ticket.update`, {
        method : 'POST',
        headers: { 'Content-Type': 'application/json' },
        body   : JSON.stringify(payload),
    });
    const json = await res.json();

    if (json.success) {
        showFeedback('Ticket actualizado.', 'success');
        setTimeout(() => location.reload(), 900);
    } else {
        showFeedback(json.message || 'Error al actualizar.', 'error');
    }
}

/* ── Helpers ────────────────────────────────────────────────────────── */
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
        showFeedback('Todos los campos son obligatorios.', 'error');
        return false;
    }
    if (!/^\d{10}$/.test(p.telefono)) {
        showFeedback('El teléfono debe tener exactamente 10 dígitos numéricos.', 'error');
        return false;
    }
    return true;
}

function setFieldsReadonly(readonly) {
    ['fCliente', 'fColonia', 'fTicketNum','fDescripcion','fTelefono'].forEach(id => {
        const el = document.getElementById(id);
        if (readonly) el.setAttribute('readonly', true);
        else          el.removeAttribute('readonly');
    });
}

function openModal()  { document.getElementById('modalOverlay').classList.add('open'); }
function closeModal() { document.getElementById('modalOverlay').classList.remove('open'); }

function resetModal() {
    ['fTicketId','fFecha','fHorarioId','fTecnicoId',
     'fCliente','fColonia','fTicketNum','fDescripcion','fTelefono'].forEach(id => {
        document.getElementById(id).value = '';
    });
    document.getElementById('modalFeedback').className = 'feedback';
    document.getElementById('modalFeedback').textContent = '';
}

function showFeedback(msg, type) {
    const el = document.getElementById('modalFeedback');
    el.textContent = msg;
    el.className   = 'feedback ' + type;
}

function formatDate(str) {
    if (!str) return '';
    const [y,m,d] = str.split('-');
    return `${d}/${m}/${y}`;
}

// Cerrar modal al hacer clic fuera
document.getElementById('modalOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
</body>
</html>
