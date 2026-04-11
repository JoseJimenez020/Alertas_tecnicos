<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Horarios</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>public/main.css">
    <style>
        .topbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; }
        .topbar h1 { margin:0; font-size:18px; }
        .topbar-right { display:flex; align-items:center; gap:10px; }
        .btn { padding:7px 16px; border:none; cursor:pointer; font-size:12px; }
        .btn-primary { background:#1a4d6d; color:#fff; }
        .btn-primary:hover { background:#245f85; }
        .btn-danger  { background:#c0392b; color:#fff; }
        .btn-danger:hover { background:#a93226; }
        .btn-back {
            padding:5px 12px; background:#1a4d6d; color:#fff; border:none;
            cursor:pointer; font-size:12px; text-decoration:none; display:inline-block;
        }
        .btn-back:hover { background:#245f85; }

        table { border-collapse:collapse; width:100%; max-width:400px; }
        th,td { border:1px solid #ccc; padding:8px 12px; text-align:left; font-size:12px; }
        th { background:#1a4d6d; color:#fff; }
        tr:nth-child(even) { background:#f5f5f5; }

        .add-form {
            display:flex; align-items:center; gap:10px;
            margin-bottom:16px;
        }
        .add-form input[type="time"] {
            padding:6px 10px; border:1px solid #ccc;
            font-size:12px; font-family:Arial,sans-serif;
        }

        .feedback { font-size:11px; padding:6px 10px; margin-bottom:10px; display:none; }
        .feedback.success { background:#d4edda; color:#155724; display:block; }
        .feedback.error   { background:#f8d7da; color:#721c24; display:block; }

        .info-box {
            background:#e8f1f8; border-left:3px solid #1a4d6d;
            padding:8px 12px; font-size:11px; color:#1a4d6d;
            margin-bottom:14px; max-width:400px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="topbar">
        <h1>🕐 Gestión de Horarios</h1>
        <div class="topbar-right">
            <a href="?action=tablero" class="btn-back">← Tablero</a>
        </div>
    </div>

    <div class="info-box">
        Los horarios aquí definidos determinan las columnas de hora en el tablero.
        Solo se pueden eliminar horarios que <strong>no tengan tickets registrados</strong>.
    </div>

    <div class="feedback" id="globalFeedback"></div>

    <!-- Formulario de agregar -->
    <div class="add-form">
        <label style="font-size:12px;font-weight:bold;">Nueva hora:</label>
        <input type="time" id="nuevaHora" step="60">
        <button class="btn btn-primary" onclick="agregarHorario()">+ Agregar</button>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Hora</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody id="tablaHorarios">
        <?php foreach ($horarios as $h): ?>
        <tr id="fila-<?= $h['horario_id'] ?>">
            <td><?= $h['horario_id'] ?></td>
            <td><?= htmlspecialchars($h['hora']) ?></td>
            <td>
                <button class="btn btn-danger" style="padding:3px 10px;"
                    onclick="eliminarHorario(<?= $h['horario_id'] ?>, '<?= htmlspecialchars($h['hora']) ?>')">
                    Eliminar
                </button>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
const BASE_URL = '<?= BASE_URL ?>';

async function agregarHorario() {
    const input = document.getElementById('nuevaHora');
    const hora  = input.value; // "HH:MM"

    if (!hora) {
        mostrarFeedback('Selecciona una hora.', 'error'); return;
    }

    const res  = await fetch(`${BASE_URL}?action=horario.store`, {
        method : 'POST',
        headers: { 'Content-Type': 'application/json' },
        body   : JSON.stringify({ hora }),
    });
    const json = await res.json();

    if (json.success) {
        mostrarFeedback('✓ Horario agregado. Recargando...', 'success');
        setTimeout(() => location.reload(), 700);
    } else {
        mostrarFeedback(json.message || 'Error al agregar.', 'error');
    }
}

async function eliminarHorario(id, hora) {
    if (!confirm(`¿Eliminar el horario ${hora}?\nSolo es posible si no tiene tickets asociados.`)) return;

    const res  = await fetch(`${BASE_URL}?action=horario.delete`, {
        method : 'POST',
        headers: { 'Content-Type': 'application/json' },
        body   : JSON.stringify({ horario_id: id }),
    });
    const json = await res.json();

    if (json.success) {
        document.getElementById(`fila-${id}`)?.remove();
        mostrarFeedback('✓ Horario eliminado.', 'success');
    } else {
        mostrarFeedback(json.message || 'Error al eliminar.', 'error');
    }
}

function mostrarFeedback(msg, tipo) {
    const el = document.getElementById('globalFeedback');
    el.textContent = msg;
    el.className   = 'feedback ' + tipo;
    // Auto-ocultar mensajes de éxito después de 3s
    if (tipo === 'success') setTimeout(() => { el.className = 'feedback'; }, 3000);
}
</script>
</body>
</html>
