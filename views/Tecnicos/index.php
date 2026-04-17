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
        .btn-sm        { padding:3px 10px; font-size:11px; }
        .btn-back { padding:5px 12px; background:#1a4d6d; color:#fff; border:none; cursor:pointer;
                    font-size:12px; text-decoration:none; display:inline-block; }
        .btn-back:hover { background:#245f85; }

        table { border-collapse:collapse; width:100%; }
        th,td { border:1px solid #ccc; padding:6px 8px; text-align:left; font-size:12px; vertical-align:top; }
        th { background:#1a4d6d; color:#fff; }
        tr:nth-child(even) { background:#f5f5f5; }
        tr.inactivo > td:not(.bloqueos-col) { color:#888; }

        .badge { display:inline-block; padding:2px 8px; border-radius:10px; font-size:10px; font-weight:bold; }
        .badge-activo     { background:#d4edda; color:#155724; }
        .badge-apoyo      { background:#fff3cd; color:#856404; }
        .badge-vacaciones { background:#cce5ff; color:#004085; }
        .badge-mecanico   { background:#f8d7da; color:#721c24; }

        /* Tarjetas de bloqueo en la tabla */
        .bloqueo-card {
            background:#f8f9fa; border:1px solid #dee2e6; border-radius:4px;
            padding:5px 8px; margin-bottom:4px; font-size:11px; position:relative;
        }
        .bloqueo-card .bc-motivo {
            font-weight:bold; font-size:10px; text-transform:uppercase; margin-bottom:2px;
        }
        .bloqueo-card .bc-fechas { color:#555; }
        .bloqueo-card .bc-horas  { color:#1a4d6d; font-size:10px; margin-top:2px; }
        .bloqueo-card .bc-desc   { color:#666; font-style:italic; margin-top:2px; }
        .bloqueo-card .bc-actions { display:flex; gap:4px; margin-top:4px; }
        .bloqueo-card.motivo-mecanico   { border-left:3px solid #c0392b; }
        .bloqueo-card.motivo-vacaciones { border-left:3px solid #2e75b6; }
        .bloqueo-card.motivo-apoyo      { border-left:3px solid #e67e00; }
        .btn-add-bloqueo {
            font-size:11px; padding:3px 8px; background:#1a4d6d; color:#fff;
            border:none; cursor:pointer; border-radius:2px; margin-top:2px;
        }
        .btn-add-bloqueo:hover { background:#245f85; }

        /* ── Modales ── */
        .modal-overlay {
            display:none; position:fixed; inset:0;
            background:rgba(0,0,0,.45); z-index:1000;
            align-items:center; justify-content:center;
        }
        .modal-overlay.open { display:flex; }
        .modal-box { background:#fff; width:480px; max-width:96vw; box-shadow:0 8px 32px rgba(0,0,0,.22); max-height:90vh; overflow-y:auto; }
        .modal-header {
            background:#1a4d6d; color:#fff; padding:12px 18px;
            display:flex; justify-content:space-between; align-items:center;
            position:sticky; top:0; z-index:1;
        }
        .modal-header h3 { margin:0; font-size:14px; }
        .modal-close { background:none; border:none; color:#fff; font-size:20px; cursor:pointer; }
        .modal-body { padding:18px; }
        .modal-body label { display:block; font-size:11px; color:#555; margin-bottom:3px; margin-top:10px; }
        .modal-body label:first-child { margin-top:0; }
        .modal-body input, .modal-body select, .modal-body textarea {
            width:100%; box-sizing:border-box; padding:7px 9px;
            border:1px solid #ccc; font-size:12px; font-family:Arial,sans-serif;
        }
        .modal-body textarea { resize:vertical; min-height:60px; }
        .modal-footer {
            padding:12px 18px; background:#f5f5f5;
            display:flex; justify-content:flex-end; gap:8px;
            position:sticky; bottom:0;
        }
        .feedback { font-size:11px; padding:6px 10px; margin-bottom:8px; display:none; }
        .feedback.success { background:#d4edda; color:#155724; display:block; }
        .feedback.error   { background:#f8d7da; color:#721c24; display:block; }

        /* ── Selector de horas (mecánico) ── */
        .horas-grid {
            display:grid; grid-template-columns:repeat(4,1fr); gap:6px;
            margin-top:6px;
        }
        .hora-check {
            display:flex; align-items:center; gap:5px;
            font-size:12px; cursor:pointer; padding:5px 7px;
            border:1px solid #ccc; border-radius:3px;
            user-select:none; transition:background .1s;
        }
        .hora-check input[type="checkbox"] { display:none; }
        .hora-check.selected { background:#1a4d6d; color:#fff; border-color:#1a4d6d; }
        .hora-check-todas {
            grid-column:1/-1; background:#f0f5ff; border-color:#1a4d6d;
            color:#1a4d6d; font-weight:bold;
        }
        .hora-check-todas.selected { background:#1a4d6d; color:#fff; }

        /* ── Sección de bloqueos en el modal de disponibilidad ── */
        .bloqueo-section { border-top:2px solid #1a4d6d; margin-top:16px; padding-top:14px; }
        .bloqueo-section h4 { margin:0 0 10px; font-size:13px; color:#1a4d6d; }

        .fechas-row { display:grid; grid-template-columns:1fr 1fr; gap:10px; }

        /* Campos condicionales por motivo */
        .campo-mecanico, .campo-apoyo, .campo-vacaciones { display:none; }
        .campo-mecanico.visible, .campo-apoyo.visible, .campo-vacaciones.visible { display:block; }

        .info-box {
            background:#e8f1f8; border-left:3px solid #1a4d6d;
            padding:8px 12px; font-size:11px; color:#1a4d6d; margin-bottom:10px;
        }
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
                <th>Bloqueos registrados</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($tecnicos as $t):
            $activo = (int)$t['status'] === 1;
            $motivo = $t['status_motivo'] ?? null;
            $badgeClass = $activo ? 'badge-activo' :
                ($motivo === 'vacaciones' ? 'badge-vacaciones' :
                ($motivo === 'mecanico'   ? 'badge-mecanico'   : 'badge-apoyo'));
            $badgeLabel = $activo ? 'Activo' : ucfirst($motivo ?? 'Inactivo');
            $bloqueos   = $bloqueosMap[(int)$t['TecnicoId']] ?? [];
        ?>
        <tr class="<?= !$activo ? 'inactivo' : '' ?>">
            <td><?= $t['TecnicoId'] ?></td>
            <td><?= htmlspecialchars($t['TecnicoNombre']) ?></td>
            <td><?= htmlspecialchars($t['num_telefono'] ?? '—') ?></td>
            <td><?= htmlspecialchars($t['zona_nombre'] ?? '—') ?></td>
            <td><span class="badge <?= $badgeClass ?>"><?= $badgeLabel ?></span></td>
            <td class="bloqueos-col" style="min-width:220px;">
                <?php foreach ($bloqueos as $b):
                    $motivoB = $b['motivo'];
                    $horasStr = '';
                    if ($b['horas_ids']) {
                        $nombres = [];
                        foreach ((array)$b['horas_ids'] as $hid) {
                            foreach ($horarios as $hrow) {
                                if ((int)$hrow['horario_id'] === (int)$hid) {
                                    $nombres[] = substr($hrow['hora'],0,5);
                                    break;
                                }
                            }
                        }
                        $horasStr = implode(', ', $nombres);
                    } else {
                        $horasStr = 'Todo el día';
                    }
                ?>
                <div class="bloqueo-card motivo-<?= $motivoB ?>">
                    <div class="bc-motivo"><?= ucfirst($motivoB) ?></div>
                    <div class="bc-fechas">
                        <?= date('d/m/Y', strtotime($b['fecha_inicio'])) ?>
                        <?= $b['fecha_inicio'] !== $b['fecha_fin'] ? ' — ' . date('d/m/Y', strtotime($b['fecha_fin'])) : '' ?>
                    </div>
                    <?php if ($horasStr): ?><div class="bc-horas">🕐 <?= htmlspecialchars($horasStr) ?></div><?php endif; ?>
                    <?php if ($b['descripcion']): ?><div class="bc-desc"><?= htmlspecialchars($b['descripcion']) ?></div><?php endif; ?>
                    <div class="bc-actions">
                        <button class="btn btn-warning btn-sm"
                            onclick='openEditBloqueo(<?= json_encode($b) ?>, <?= $t['TecnicoId'] ?>, "<?= htmlspecialchars(addslashes($t['TecnicoNombre'])) ?>")'>
                            Editar
                        </button>
                        <button class="btn btn-danger btn-sm"
                            onclick="deleteBloqueo(<?= $b['bloqueo_id'] ?>)">
                            Eliminar
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
                <button class="btn-add-bloqueo"
                    onclick='openAddBloqueo(<?= $t['TecnicoId'] ?>, "<?= htmlspecialchars(addslashes($t['TecnicoNombre'])) ?>", "<?= $motivo ?>")'>
                    + Agregar bloqueo
                </button>
            </td>
            <td>
                <button class="btn btn-warning btn-sm"
                    onclick='openEdit(<?= json_encode($t) ?>)'>Editar</button>
                <button class="btn btn-secondary btn-sm"
                    onclick='openStatus(<?= json_encode($t) ?>)'>Disponibilidad</button>
                <button class="btn btn-danger btn-sm"
                    onclick="confirmDelete(<?= $t['TecnicoId'] ?>, '<?= htmlspecialchars(addslashes($t['TecnicoNombre'])) ?>')">
                    Eliminar</button>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- ════════════════ MODAL CREAR/EDITAR TÉCNICO ════════════════ -->
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

<!-- ════════════════ MODAL DISPONIBILIDAD (status) ════════════════ -->
<div class="modal-overlay" id="modalStatus">
    <div class="modal-box" style="width:380px;">
        <div class="modal-header">
            <h3>Cambiar Disponibilidad</h3>
            <button class="modal-close" onclick="cerrarModal('modalStatus')">×</button>
        </div>
        <div class="modal-body">
            <div class="feedback" id="statusFeedback"></div>
            <input type="hidden" id="sId">
            <p id="sNombre" style="font-weight:bold;margin:0 0 12px;font-size:13px;"></p>
            <label>Estado</label>
            <select id="sMotivo" onchange="cerrarModal('modalStatus')">
                <option value="">✅ Disponible</option>
                <option value="apoyo">🔧 No disponible — Apoyo</option>
                <option value="vacaciones">🏖 No disponible — Vacaciones</option>
                <option value="mecanico">🔴 No disponible — Mecánico</option>
            </select>
            <p style="font-size:11px;color:#888;margin-top:10px;">
                Al seleccionar un estado, se abrirá el formulario de bloqueo correspondiente.
            </p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="cerrarModal('modalStatus')">Cancelar</button>
            <button class="btn btn-primary" onclick="iniciarCambioStatus()">Continuar →</button>
        </div>
    </div>
</div>

<!-- ════════════════ MODAL BLOQUEO (crear / editar) ════════════════ -->
<div class="modal-overlay" id="modalBloqueo">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="modalBloqueoTitle">Registrar Bloqueo</h3>
            <button class="modal-close" onclick="cerrarModal('modalBloqueo')">×</button>
        </div>
        <div class="modal-body">
            <div class="feedback" id="bloqueoFeedback"></div>
            <input type="hidden" id="bTecnicoId">
            <input type="hidden" id="bBloqueoId">
            <input type="hidden" id="bModoEdicion" value="0">

            <p id="bTecnicoNombre" style="font-weight:bold;margin:0 0 10px;font-size:13px;color:#1a4d6d;"></p>

            <label>Tipo de no-disponibilidad</label>
            <select id="bMotivo" onchange="actualizarCamposBloqueo()">
                <option value="apoyo">🔧 Apoyo</option>
                <option value="vacaciones">🏖 Vacaciones</option>
                <option value="mecanico">🔴 Mecánico</option>
            </select>

            <!-- Fechas — todos los motivos -->
            <div class="fechas-row" style="margin-top:10px;">
                <div>
                    <label>Fecha de inicio</label>
                    <input type="date" id="bFechaInicio">
                </div>
                <div>
                    <label>Fecha final</label>
                    <input type="date" id="bFechaFin">
                </div>
            </div>

            <!-- ── MECÁNICO: selector de horas ── -->
            <div class="campo-mecanico" id="seccionHoras">
                <label style="margin-top:12px;">Horas a bloquear</label>
                <div class="info-box" style="margin-top:6px;">
                    Selecciona las horas específicas que estarán bloqueadas. Usa "Todas" para bloquear el día completo.
                </div>
                <div class="horas-grid" id="horasGrid">
                    <label class="hora-check hora-check-todas" data-value="todas" onclick="toggleHora(this)">
                        <input type="checkbox"> Todas las horas
                    </label>
                    <?php foreach ($horarios as $h): ?>
                    <label class="hora-check" data-value="<?= $h['horario_id'] ?>" onclick="toggleHora(this)">
                        <input type="checkbox"> <?= htmlspecialchars(substr($h['hora'],0,5)) ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- ── MECÁNICO + APOYO: descripción del motivo ── -->
            <div class="campo-mecanico campo-apoyo" id="seccionDescripcion" style="display:none;">
                <label style="margin-top:12px;">Motivo / Descripción</label>
                <textarea id="bDescripcion" maxlength="500" placeholder="Escribe el motivo detallado..."></textarea>
            </div>

        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="cerrarModal('modalBloqueo')">Cancelar</button>
            <button class="btn btn-primary" id="btnGuardarBloqueo" onclick="guardarBloqueo()">Guardar</button>
        </div>
    </div>
</div>

<script>
const BASE_URL    = '<?= BASE_URL ?>';
let modoEdicion   = false;
// Estado guardado al cambiar disponibilidad desde el modal de status
let _statusPendiente = null;

/* ══════════════════════════════════════════════════
   TÉCNICO — crear / editar
══════════════════════════════════════════════════ */
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
    document.getElementById('tTelefono').value = t.num_telefono || '';
    document.getElementById('tZona').value     = t.zona;
    document.getElementById('modalTecnico').classList.add('open');
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

/* ══════════════════════════════════════════════════
   STATUS — modal simple (solo elige el estado)
══════════════════════════════════════════════════ */
function openStatus(t) {
    limpiar('statusFeedback');
    document.getElementById('sId').value              = t.TecnicoId;
    document.getElementById('sNombre').textContent    = t.TecnicoNombre;
    document.getElementById('sMotivo').value          = t.status_motivo || '';
    _statusPendiente = { tecnicoId: t.TecnicoId, nombre: t.TecnicoNombre };
    document.getElementById('modalStatus').classList.add('open');
}

function iniciarCambioStatus() {
    const motivo = document.getElementById('sMotivo').value;
    const id     = document.getElementById('sId').value;
    const nombre = document.getElementById('sNombre').textContent;
    cerrarModal('modalStatus');

    if (motivo === '') {
        // Reactivar directamente sin bloqueo
        reactivarTecnico(parseInt(id), nombre);
    } else {
        // Abrir formulario de bloqueo con el motivo preseleccionado
        openAddBloqueo(parseInt(id), nombre, motivo, true /* esStatusChange */);
    }
}

async function reactivarTecnico(id, nombre) {
    if (!confirm(`¿Marcar a "${nombre}" como disponible?`)) return;
    const res  = await fetch(`${BASE_URL}?action=tecnico.status`, {
        method:'POST', headers:{'Content-Type':'application/json'},
        body:JSON.stringify({ tecnico_id: id, motivo: null }),
    });
    const json = await res.json();
    if (json.success) location.reload();
    else alert(json.message || 'Error al actualizar.');
}

/* ══════════════════════════════════════════════════
   MODAL BLOQUEO — abrir para agregar
══════════════════════════════════════════════════ */
/**
 * @param {number}  tecnicoId
 * @param {string}  nombre
 * @param {string}  motivoPreset  — preselecciona el motivo
 * @param {boolean} esStatusChange — si true, también actualiza tm_tecnicos.status al guardar
 */
function openAddBloqueo(tecnicoId, nombre, motivoPreset, esStatusChange) {
    limpiar('bloqueoFeedback');
    document.getElementById('bBloqueoId').value     = '';
    document.getElementById('bModoEdicion').value   = '0';
    document.getElementById('bTecnicoId').value     = tecnicoId;
    document.getElementById('bTecnicoNombre').textContent = nombre;
    document.getElementById('modalBloqueoTitle').textContent = 'Registrar Bloqueo — ' + nombre;
    document.getElementById('bMotivo').value        = motivoPreset || 'apoyo';
    document.getElementById('bMotivo').disabled     = !!motivoPreset; // bloquear selector si viene de status
    // Guardar si hay que actualizar status también
    document.getElementById('bMotivo').dataset.esStatusChange = esStatusChange ? '1' : '0';

    // Fechas: hoy por defecto
    const hoy = new Date().toISOString().split('T')[0];
    document.getElementById('bFechaInicio').value = hoy;
    document.getElementById('bFechaFin').value    = hoy;
    document.getElementById('bDescripcion').value = '';
    limpiarHoras();
    actualizarCamposBloqueo();
    document.getElementById('modalBloqueo').classList.add('open');
}

/* ══════════════════════════════════════════════════
   MODAL BLOQUEO — abrir para editar
══════════════════════════════════════════════════ */
function openEditBloqueo(b, tecnicoId, nombre) {
    limpiar('bloqueoFeedback');
    document.getElementById('bBloqueoId').value   = b.bloqueo_id;
    document.getElementById('bModoEdicion').value = '1';
    document.getElementById('bTecnicoId').value   = tecnicoId;
    document.getElementById('bTecnicoNombre').textContent = nombre;
    document.getElementById('modalBloqueoTitle').textContent = 'Editar Bloqueo — ' + nombre;
    document.getElementById('bMotivo').value      = b.motivo;
    document.getElementById('bMotivo').disabled   = false;
    document.getElementById('bMotivo').dataset.esStatusChange = '0';
    document.getElementById('bFechaInicio').value = b.fecha_inicio;
    document.getElementById('bFechaFin').value    = b.fecha_fin;
    document.getElementById('bDescripcion').value = b.descripcion || '';
    limpiarHoras();
    if (b.horas_ids) {
        b.horas_ids.forEach(hid => {
            const el = document.querySelector(`.hora-check[data-value="${hid}"]`);
            if (el) el.classList.add('selected');
        });
    } else if (b.motivo === 'mecanico') {
        // Si no hay horas_ids pero es mecánico, marcar "todas"
        const todas = document.querySelector('.hora-check-todas');
        if (todas) todas.classList.add('selected');
    }
    actualizarCamposBloqueo();
    document.getElementById('modalBloqueo').classList.add('open');
}

/* ══════════════════════════════════════════════════
   CAMPOS CONDICIONALES POR MOTIVO
══════════════════════════════════════════════════ */
function actualizarCamposBloqueo() {
    const motivo = document.getElementById('bMotivo').value;
    const secHoras = document.getElementById('seccionHoras');
    const secDesc  = document.getElementById('seccionDescripcion');

    // Ocultar todo primero
    secHoras.style.display = 'none';
    secDesc.style.display  = 'none';

    if (motivo === 'mecanico') {
        secHoras.style.display = 'block';
        secDesc.style.display  = 'block';
    } else if (motivo === 'apoyo') {
        secDesc.style.display  = 'block';
    }
    // vacaciones: solo fechas (ya visibles siempre)
}

/* ── Selector de horas ── */
function toggleHora(el) {
    const esTodas = el.classList.contains('hora-check-todas');

    if (esTodas) {
        const seleccionado = el.classList.contains('selected');
        // Limpiar todas
        document.querySelectorAll('.hora-check').forEach(h => h.classList.remove('selected'));
        if (!seleccionado) el.classList.add('selected');
    } else {
        // Desmarcar "todas" si se elige individual
        document.querySelector('.hora-check-todas')?.classList.remove('selected');
        el.classList.toggle('selected');
    }
}

function limpiarHoras() {
    document.querySelectorAll('.hora-check').forEach(h => h.classList.remove('selected'));
}

function getHorasSeleccionadas() {
    const todas = document.querySelector('.hora-check-todas.selected');
    if (todas) return null; // null = bloqueo total
    const ids = [];
    document.querySelectorAll('.hora-check:not(.hora-check-todas).selected').forEach(el => {
        ids.push(parseInt(el.dataset.value));
    });
    return ids.length ? ids : null;
}

/* ══════════════════════════════════════════════════
   GUARDAR BLOQUEO
══════════════════════════════════════════════════ */
async function guardarBloqueo() {
    const modoEdicion_     = document.getElementById('bModoEdicion').value === '1';
    const bloqueoId        = parseInt(document.getElementById('bBloqueoId').value) || null;
    const tecnicoId        = parseInt(document.getElementById('bTecnicoId').value);
    const motivo           = document.getElementById('bMotivo').value;
    const fechaInicio      = document.getElementById('bFechaInicio').value;
    const fechaFin         = document.getElementById('bFechaFin').value;
    const descripcion      = document.getElementById('bDescripcion').value.trim();
    const esStatusChange   = document.getElementById('bMotivo').dataset.esStatusChange === '1';

    let horasIds = null;
    if (motivo === 'mecanico') {
        horasIds = getHorasSeleccionadas();
        if (horasIds !== null && horasIds.length === 0) {
            mostrarFeedback('bloqueoFeedback', 'Selecciona al menos una hora o "Todas".', 'error');
            return;
        }
    }

    if (!fechaInicio || !fechaFin) {
        mostrarFeedback('bloqueoFeedback', 'Las fechas son obligatorias.', 'error'); return;
    }
    if (fechaInicio > fechaFin) {
        mostrarFeedback('bloqueoFeedback', 'La fecha de inicio no puede ser posterior a la fecha final.', 'error'); return;
    }
    if ((motivo === 'mecanico' || motivo === 'apoyo') && !descripcion) {
        mostrarFeedback('bloqueoFeedback', 'El motivo/descripción es obligatorio.', 'error'); return;
    }

    const payload = {
        tecnico_id  : tecnicoId,
        motivo,
        fecha_inicio: fechaInicio,
        fecha_fin   : fechaFin,
        descripcion : descripcion || null,
        // Para mecánico: null=todas, [ids]=específicas
        horas_ids   : motivo === 'mecanico' ? (horasIds === null ? ['todas'] : horasIds) : null,
    };

    if (modoEdicion_) payload.bloqueo_id = bloqueoId;

    const action = modoEdicion_ ? 'bloqueo.update' : 'bloqueo.store';
    const res    = await fetch(`${BASE_URL}?action=${action}`, {
        method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(payload),
    });
    const json = await res.json();

    if (!json.success) {
        mostrarFeedback('bloqueoFeedback', json.message || 'Error al guardar.', 'error');
        return;
    }

    // Si viene de un cambio de status, también actualizar tm_tecnicos.status
    if (esStatusChange && !modoEdicion_) {
        const resStatus = await fetch(`${BASE_URL}?action=tecnico.status`, {
            method:'POST', headers:{'Content-Type':'application/json'},
            body:JSON.stringify({
                tecnico_id  : tecnicoId,
                motivo,
                fecha_inicio: fechaInicio,
                fecha_fin   : fechaFin,
                descripcion : descripcion || null,
                horas_ids   : motivo === 'mecanico' ? (horasIds === null ? ['todas'] : horasIds) : null,
            }),
        });
        const jsonStatus = await resStatus.json();
        if (!jsonStatus.success) {
            // El bloqueo ya se creó; solo mostrar advertencia
            mostrarFeedback('bloqueoFeedback', '⚠ Bloqueo guardado pero no se pudo actualizar el status del técnico.', 'error');
            return;
        }
    }

    mostrarFeedback('bloqueoFeedback', '✓ Guardado correctamente. Recargando...', 'success');
    setTimeout(() => location.reload(), 800);
}

/* ══════════════════════════════════════════════════
   ELIMINAR BLOQUEO
══════════════════════════════════════════════════ */
async function deleteBloqueo(id) {
    if (!confirm('¿Eliminar este bloqueo?')) return;
    const res  = await fetch(`${BASE_URL}?action=bloqueo.delete`, {
        method:'POST', headers:{'Content-Type':'application/json'},
        body:JSON.stringify({ bloqueo_id: id }),
    });
    const json = await res.json();
    if (json.success) location.reload();
    else alert(json.message || 'Error al eliminar.');
}

/* ── Helpers ── */
function cerrarModal(id) { document.getElementById(id).classList.remove('open'); }
function limpiar(fbId) {
    const el = document.getElementById(fbId);
    if (el) { el.className = 'feedback'; el.textContent = ''; }
}
function mostrarFeedback(fbId, msg, tipo) {
    const el = document.getElementById(fbId);
    if (!el) return;
    el.textContent = msg; el.className = 'feedback ' + tipo;
}

['modalTecnico','modalStatus','modalBloqueo'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) cerrarModal(id);
    });
});

// Inicializar campos condicionales
actualizarCamposBloqueo();
</script>
</body>
</html>
