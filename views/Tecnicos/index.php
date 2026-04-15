<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../assets/favicon.ico">
    <title>Gestión de Técnicos</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>public/main.css">
    <style>
        .topbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; }
        .topbar h1 { margin:0; font-size:18px; }
        .topbar-right { display:flex; align-items:center; gap:10px; font-size:12px; }
        .btn { padding:7px 16px; border:none; cursor:pointer; font-size:12px; }
        .btn-primary   { background:#1a4d6d; color:#fff; }
        .btn-primary:hover { background:#245f85; }
        .btn-secondary { background:#ccc; color:#333; }
        .btn-secondary:hover { background:#bbb; }
        .btn-danger    { background:#c0392b; color:#fff; }
        .btn-danger:hover { background:#a93226; }
        .btn-warning   { background:#e67e00; color:#fff; }
        .btn-warning:hover { background:#cc7000; }
        .btn-back { padding:5px 12px; background:#1a4d6d; color:#fff; border:none; cursor:pointer;
                    font-size:12px; text-decoration:none; display:inline-block; }
        .btn-back:hover { background:#245f85; }

        table { border-collapse:collapse; width:100%; }
        th,td { border:1px solid #ccc; padding:6px 8px; text-align:left; font-size:12px; }
        th { background:#1a4d6d; color:#fff; }
        tr:nth-child(even) { background:#f5f5f5; }
        tr.inactivo td { color:#888; }

        .badge { display:inline-block; padding:2px 8px; border-radius:10px; font-size:10px; font-weight:bold; }
        .badge-activo    { background:#d4edda; color:#155724; }
        .badge-apoyo     { background:#fff3cd; color:#856404; }
        .badge-vacaciones{ background:#cce5ff; color:#004085; }

        .modal-overlay {
            display:none; position:fixed; inset:0;
            background:rgba(0,0,0,.45); z-index:1000;
            align-items:center; justify-content:center;
        }
        .modal-overlay.open { display:flex; }
        .modal-box { background:#fff; width:420px; max-width:96vw; box-shadow:0 8px 32px rgba(0,0,0,.22); }
        .modal-header {
            background:#1a4d6d; color:#fff; padding:12px 18px;
            display:flex; justify-content:space-between; align-items:center;
        }
        .modal-header h3 { margin:0; font-size:14px; }
        .modal-close { background:none; border:none; color:#fff; font-size:20px; cursor:pointer; }
        .modal-body { padding:18px; }
        .modal-body label { display:block; font-size:11px; color:#555; margin-bottom:3px; margin-top:10px; }
        .modal-body label:first-child { margin-top:0; }
        .modal-body input, .modal-body select {
            width:100%; box-sizing:border-box; padding:7px 9px;
            border:1px solid #ccc; font-size:12px;
        }
        .modal-footer { padding:12px 18px; background:#f5f5f5; display:flex; justify-content:flex-end; gap:8px; }
        .feedback { font-size:11px; padding:6px 10px; margin-bottom:8px; display:none; }
        .feedback.success { background:#d4edda; color:#155724; display:block; }
        .feedback.error   { background:#f8d7da; color:#721c24; display:block; }
    </style>
</head>
<body>
<div class="container">
    <div class="topbar">
        <h1>⚙ Gestión de Técnicos</h1>
        <div class="topbar-right">
            <a href="?action=tablero" class="btn-back">← Tablero</a>
        </div>
    </div>

    <button class="btn btn-primary" onclick="openCreate()" style="margin-bottom:12px;">
        + Nuevo Técnico
    </button>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Teléfono</th>
                <th>Zona</th>
                <th>Status</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($tecnicos as $t):
            $activo = (int)$t['status'] === 1;
            $motivo = $t['status_motivo'] ?? null;
            $badgeClass = $activo ? 'badge-activo' : ($motivo === 'vacaciones' ? 'badge-vacaciones' : 'badge-apoyo');
            $badgeLabel = $activo ? 'Activo' : ucfirst($motivo ?? 'Inactivo');
        ?>
        <tr class="<?= !$activo ? 'inactivo' : '' ?>">
            <td><?= $t['TecnicoId'] ?></td>
            <td><?= htmlspecialchars($t['TecnicoNombre']) ?></td>
            <td><?= htmlspecialchars($t['telefono'] ?? '—') ?></td>
            <td><?= htmlspecialchars($t['zona_nombre'] ?? '—') ?></td>
            <td><span class="badge <?= $badgeClass ?>"><?= $badgeLabel ?></span></td>
            <td>
                <button class="btn btn-warning" style="padding:4px 10px;"
                    onclick='openEdit(<?= json_encode($t) ?>)'>Editar</button>
                <button class="btn btn-secondary" style="padding:4px 10px;"
                    onclick='openStatus(<?= json_encode($t) ?>)'>Disponibilidad</button>
                <button class="btn btn-danger" style="padding:4px 10px;"
                    onclick="confirmDelete(<?= $t['TecnicoId'] ?>, '<?= htmlspecialchars(addslashes($t['TecnicoNombre'])) ?>')">
                    Eliminar</button>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal Crear/Editar Técnico -->
<div class="modal-overlay" id="modalTecnico">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="modalTecnicoTitle">Nuevo Técnico</h3>
            <button class="modal-close" onclick="cerrarModal('modalTecnico')">×</button>
        </div>
        <div class="modal-body">
            <div class="feedback" id="tecnicoFeedback"></div>
            <input type="hidden" id="tId">
            <label>Nombre completo</label>
            <input type="text" id="tNombre" maxlength="255" placeholder="Nombre del técnico">
            <label>Teléfono</label>
            <input type="tel" id="tTelefono" maxlength="15" placeholder="Ej. 9931234567">
            <label>Zona</label>
            <select id="tZona">
                <?php foreach ($zonas as $z): ?>
                <option value="<?= $z['zona_id'] ?>"><?= htmlspecialchars($z['zona_nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="cerrarModal('modalTecnico')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarTecnico()">Guardar</button>
        </div>
    </div>
</div>

<!-- Modal Disponibilidad -->
<div class="modal-overlay" id="modalStatus">
    <div class="modal-box" style="width:360px;">
        <div class="modal-header">
            <h3>Cambiar Disponibilidad</h3>
            <button class="modal-close" onclick="cerrarModal('modalStatus')">×</button>
        </div>
        <div class="modal-body">
            <div class="feedback" id="statusFeedback"></div>
            <input type="hidden" id="sId">
            <p id="sNombre" style="font-weight:bold;margin:0 0 12px;font-size:13px;"></p>
            <label>Estado</label>
            <select id="sMotivo">
                <option value="">✅ Disponible</option>
                <option value="apoyo">🔧 No disponible — Apoyo</option>
                <option value="vacaciones">🏖 No disponible — Vacaciones</option>
            </select>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="cerrarModal('modalStatus')">Cancelar</button>
            <button class="btn btn-primary" onclick="guardarStatus()">Guardar</button>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';
let modoEdicion = false;

function openCreate() {
    modoEdicion = false;
    limpiar('tecnicoFeedback');
    document.getElementById('modalTecnicoTitle').textContent = 'Nuevo Técnico';
    document.getElementById('tId').value       = '';
    document.getElementById('tNombre').value   = '';
    document.getElementById('tTelefono').value = '';
    document.getElementById('tZona').value     = document.getElementById('tZona').options[0]?.value || '';
    document.getElementById('modalTecnico').classList.add('open');
}

function openEdit(t) {
    modoEdicion = true;
    limpiar('tecnicoFeedback');
    document.getElementById('modalTecnicoTitle').textContent = 'Editar Técnico';
    document.getElementById('tId').value       = t.TecnicoId;
    document.getElementById('tNombre').value   = t.TecnicoNombre;
    document.getElementById('tTelefono').value = t.telefono || '';
    document.getElementById('tZona').value     = t.zona;
    document.getElementById('modalTecnico').classList.add('open');
}

function openStatus(t) {
    limpiar('statusFeedback');
    document.getElementById('sId').value              = t.TecnicoId;
    document.getElementById('sNombre').textContent    = t.TecnicoNombre;
    document.getElementById('sMotivo').value          = t.status_motivo || '';
    document.getElementById('modalStatus').classList.add('open');
}

function cerrarModal(id) {
    document.getElementById(id).classList.remove('open');
}

async function guardarTecnico() {
    const id       = document.getElementById('tId').value;
    const nombre   = document.getElementById('tNombre').value.trim();
    const telefono = document.getElementById('tTelefono').value.trim();
    const zona     = document.getElementById('tZona').value;

    if (!nombre || !zona) { mostrarFeedback('tecnicoFeedback', 'Nombre y zona son obligatorios.', 'error'); return; }

    const action  = modoEdicion ? 'tecnico.update' : 'tecnico.store';
    const payload = modoEdicion
        ? { tecnico_id: parseInt(id), nombre, telefono, zona_id: parseInt(zona) }
        : { nombre, telefono, zona_id: parseInt(zona) };

    const res  = await fetch(`${BASE_URL}?action=${action}`, {
        method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload),
    });
    const json = await res.json();
    if (json.success) {
        mostrarFeedback('tecnicoFeedback', '✓ Guardado. Recargando...', 'success');
        setTimeout(() => location.reload(), 700);
    } else {
        mostrarFeedback('tecnicoFeedback', json.message || 'Error', 'error');
    }
}

async function guardarStatus() {
    const id     = parseInt(document.getElementById('sId').value);
    const motivo = document.getElementById('sMotivo').value || null;
    const res  = await fetch(`${BASE_URL}?action=tecnico.status`, {
        method:'POST', headers:{'Content-Type':'application/json'},
        body:JSON.stringify({ tecnico_id: id, motivo }),
    });
    const json = await res.json();
    if (json.success) {
        mostrarFeedback('statusFeedback', '✓ Actualizado. Recargando...', 'success');
        setTimeout(() => location.reload(), 700);
    } else {
        mostrarFeedback('statusFeedback', json.message || 'Error', 'error');
    }
}

async function confirmDelete(id, nombre) {
    if (!confirm(`¿Eliminar al técnico "${nombre}"?\nSolo es posible si no tiene tickets registrados.`)) return;
    const res  = await fetch(`${BASE_URL}?action=tecnico.delete`, {
        method:'POST', headers:{'Content-Type':'application/json'},
        body:JSON.stringify({ tecnico_id: id }),
    });
    const json = await res.json();
    if (json.success) location.reload();
    else alert(json.message || 'Error al eliminar.');
}

function limpiar(fbId) {
    const el = document.getElementById(fbId);
    if (el) { el.className = 'feedback'; el.textContent = ''; }
}
function mostrarFeedback(fbId, msg, tipo) {
    const el = document.getElementById(fbId);
    el.textContent = msg; el.className = 'feedback ' + tipo;
}

['modalTecnico','modalStatus'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) cerrarModal(id);
    });
});
</script>
</body>
</html>
