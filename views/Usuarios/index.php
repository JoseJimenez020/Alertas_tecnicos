<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" type="image/png" href="../../assets/favicon.ico">
    <title>Gestión de Usuarios</title>
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

        .color-chip {
            display:inline-block; width:16px; height:16px;
            border:1px solid #999; vertical-align:middle; border-radius:2px;
        }

        /* ── Modal ── */
        .modal-overlay {
            display:none; position:fixed; inset:0;
            background:rgba(0,0,0,.45); z-index:1000;
            align-items:center; justify-content:center;
        }
        .modal-overlay.open { display:flex; }
        .modal-box {
            background:#fff; width:460px; max-width:96vw;
            box-shadow:0 8px 32px rgba(0,0,0,.22);
        }
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
        .modal-footer {
            padding:12px 18px; background:#f5f5f5;
            display:flex; justify-content:flex-end; gap:8px;
        }
        .feedback { font-size:11px; padding:6px 10px; margin-bottom:8px; display:none; }
        .feedback.success { background:#d4edda; color:#155724; display:block; }
        .feedback.error   { background:#f8d7da; color:#721c24; display:block; }

        .color-preview-row {
            display:flex; align-items:center; gap:8px; margin-top:6px;
        }
        #colorPreviewBox {
            width:24px; height:24px; border:1px solid #999; border-radius:3px;
        }

        .roles-ref { font-size:11px; color:#555; margin-bottom:12px; }
        .roles-ref span { display:inline-block; margin-right:12px; }
    </style>
</head>
<body>
<div class="container">
    <div class="topbar">
        <h1>👥 Gestión de Usuarios</h1>
        <div class="topbar-right">
            <a href="?action=tablero" class="btn-back">← Tablero</a>
        </div>
    </div>

    <div class="roles-ref">
        <strong>Roles:</strong>
        <span>1 = Call Center</span>
        <span>2 = Mesa de Control</span>
        <span>3 = Supervisor CC</span>
        <span>4 = Administrador</span>
        <span>5 = Encargado de zona</span>
    </div>

    <button class="btn btn-primary" onclick="openCreate()" style="margin-bottom:12px;">
        + Nuevo Usuario
    </button>

    <table>
        <thead>
            <tr>
                <th>ID</th><th>Nombre</th><th>Rol</th><th>Usuario / Correo</th><th>Color</th><th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $rolesNombres = ['','Call Center','Mesa de Control','Supervisor CC','Administrador'];
        foreach ($usuarios as $u):
        ?>
        <tr>
            <td><?= $u['usu_id'] ?></td>
            <td><?= htmlspecialchars($u['nombre']) ?></td>
            <td><?= $rolesNombres[$u['rol_id']] ?? 'Rol '.$u['rol_id'] ?></td>
            <td><?= htmlspecialchars($u['usuario']) ?></td>
            <td>
                <span class="color-chip <?= htmlspecialchars($u['color']) ?>"></span>
                <?= htmlspecialchars($u['color']) ?>
            </td>
            <td>
                <button class="btn btn-warning" style="padding:4px 10px;"
                    onclick='openEdit(<?= json_encode($u) ?>)'>Editar</button>
                <?php if ((int)$u['usu_id'] !== (int)$usuario['id']): ?>
                <button class="btn btn-danger" style="padding:4px 10px;"
                    onclick="confirmDelete(<?= $u['usu_id'] ?>, '<?= htmlspecialchars(addslashes($u['nombre'])) ?>')">
                    Eliminar</button>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Modal crear / editar -->
<div class="modal-overlay" id="modalUsuario">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="modalUsuarioTitle">Nuevo Usuario</h3>
            <button class="modal-close" onclick="cerrarModal()">×</button>
        </div>
        <div class="modal-body">
            <div class="feedback" id="usuFeedback"></div>
            <input type="hidden" id="uId">

            <label>Nombre completo</label>
            <input type="text" id="uNombre" maxlength="255">

            <label>Correo / Usuario (único)</label>
            <input type="text" id="uUsuario" maxlength="255">

            <label>Contraseña <span id="passHint" style="color:#999;">(dejar vacío para no cambiar)</span></label>
            <input type="password" id="uPassword" maxlength="72" autocomplete="new-password">

            <label>Rol</label>
            <select id="uRol">
                <option value="1">1 — Call Center</option>
                <option value="2">2 — Mesa de Control</option>
                <option value="3">3 — Supervisor CC</option>
                <option value="4">4 — Administrador</option>
                <option value="5">5 — Encargado de zona</option>
            </select>

            <label>Color en el tablero</label>
            <select id="uColor" onchange="actualizarColorPreview()">
                <?php
                $colores = [
                    'bg-green'     => 'Verde (#92D050)',
                    'bg-yellow'    => 'Amarillo (#FFFF00)',
                    'bg-pink'      => 'Rosa (#ff69b4)',
                    'bg-peach'     => 'Durazno (#F1A983)',
                    'bg-blue'      => 'Azul (#00B0F0)',
                    'bg-orange'    => 'Naranja (#FFC000)',
                    'bg-gray'      => 'Gris (#F2CEEF)',
                    'bg-violet'    => 'Violeta (alias gray)',
                    'bg-lightblue' => 'Azul claro (#DAE9F8)',
                    'bg-m-blue'    => 'Azul medio (#5bc0de)',
                    'bg-bluemarco' => 'Azul marco (#94DCF8)',
                    'bg-purple'    => 'Morado (#D86DCD)',
                ];
                foreach ($colores as $val => $lbl): ?>
                <option value="<?= $val ?>"><?= $lbl ?></option>
                <?php endforeach; ?>
            </select>
            <div class="color-preview-row">
                <div id="colorPreviewBox"></div>
                <span id="colorPreviewLabel" style="font-size:11px;color:#555;"></span>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="cerrarModal()">Cancelar</button>
            <button class="btn btn-primary" id="btnGuardarUsuario" onclick="guardarUsuario()">Guardar</button>
        </div>
    </div>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';

// Mapa de color CSS → hex para el preview
const COLOR_HEX = {
    'bg-green'    :'#92D050','bg-yellow'   :'#FFFF00','bg-pink'     :'#ff69b4',
    'bg-peach'    :'#F1A983','bg-blue'     :'#00B0F0','bg-orange'   :'#FFC000',
    'bg-gray'     :'#F2CEEF','bg-violet'   :'#D86DCD','bg-lightblue':'#DAE9F8',
    'bg-m-blue'   :'#5bc0de','bg-bluemarco':'#94DCF8','bg-purple'   :'#D86DCD',
};

let modoEdicion = false;

function openCreate() {
    modoEdicion = false;
    limpiarModal();
    document.getElementById('modalUsuarioTitle').textContent = 'Nuevo Usuario';
    document.getElementById('passHint').style.display = 'none';
    document.getElementById('uId').value = '';
    document.getElementById('modalUsuario').classList.add('open');
    actualizarColorPreview();
}

function openEdit(u) {
    modoEdicion = true;
    limpiarModal();
    document.getElementById('modalUsuarioTitle').textContent = 'Editar Usuario';
    document.getElementById('passHint').style.display = 'inline';
    document.getElementById('uId').value      = u.usu_id;
    document.getElementById('uNombre').value  = u.nombre;
    document.getElementById('uUsuario').value = u.usuario;
    document.getElementById('uRol').value     = u.rol_id;
    document.getElementById('uColor').value   = u.color || 'bg-gray';
    document.getElementById('modalUsuario').classList.add('open');
    actualizarColorPreview();
}

function cerrarModal() {
    document.getElementById('modalUsuario').classList.remove('open');
}

function limpiarModal() {
    ['uNombre','uUsuario','uPassword'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('uRol').value   = '1';
    document.getElementById('uColor').value = 'bg-gray';
    const fb = document.getElementById('usuFeedback');
    fb.className = 'feedback'; fb.textContent = '';
}

function actualizarColorPreview() {
    const val = document.getElementById('uColor').value;
    const box = document.getElementById('colorPreviewBox');
    box.style.backgroundColor = COLOR_HEX[val] || '#ccc';
    document.getElementById('colorPreviewLabel').textContent = val;
}

async function guardarUsuario() {
    const id      = document.getElementById('uId').value;
    const payload = {
        usu_id  : id ? parseInt(id) : null,
        nombre  : document.getElementById('uNombre').value.trim(),
        usuario : document.getElementById('uUsuario').value.trim(),
        password: document.getElementById('uPassword').value,
        rol_id  : parseInt(document.getElementById('uRol').value),
        color   : document.getElementById('uColor').value,
    };

    if (!payload.nombre || !payload.usuario) {
        mostrarFeedback('Nombre y usuario son obligatorios.', 'error'); return;
    }
    if (!modoEdicion && !payload.password) {
        mostrarFeedback('La contraseña es obligatoria para nuevos usuarios.', 'error'); return;
    }

    const action = modoEdicion ? 'admin.usuario.update' : 'admin.usuario.store';
    const res    = await fetch(`${BASE_URL}?action=${action}`, {
        method: 'POST', headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(payload),
    });
    const json = await res.json();

    if (json.success) {
        mostrarFeedback('✓ Guardado correctamente. Recargando...', 'success');
        setTimeout(() => location.reload(), 800);
    } else {
        mostrarFeedback(json.message || 'Error al guardar.', 'error');
    }
}

async function confirmDelete(id, nombre) {
    if (!confirm(`¿Eliminar al usuario "${nombre}"?\nEsta acción no se puede deshacer.`)) return;
    const res  = await fetch(`${BASE_URL}?action=admin.usuario.delete`, {
        method: 'POST', headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ usu_id: id }),
    });
    const json = await res.json();
    if (json.success) location.reload();
    else alert(json.message || 'Error al eliminar.');
}

function mostrarFeedback(msg, tipo) {
    const el = document.getElementById('usuFeedback');
    el.textContent = msg; el.className = 'feedback ' + tipo;
}

// Inicializar preview al cargar
actualizarColorPreview();
</script>
</body>
</html>
