<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../assets/favicon.ico">
    <title>Almacén — Materiales Utilizados</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>public/main.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <style>
        .topbar {
            display: flex; justify-content: space-between;
            align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 8px;
        }
        .topbar h1 { margin: 0; font-size: 18px; }
        .topbar-right { display: flex; align-items: center; gap: 8px; }

        .btn { padding: 7px 14px; border: none; cursor: pointer; font-size: 12px; }
        .btn-back {
            padding: 5px 12px; background: #1a4d6d; color: #fff;
            border: none; cursor: pointer; font-size: 12px;
            text-decoration: none; display: inline-block;
        }
        .btn-back:hover { background: #245f85; }
        .btn-excel { background: #1e7145; color: #fff; }
        .btn-excel:hover { background: #155a36; }

        /* ── Filtros ── */
        .filtros {
            background: #f8f9fa; border: 1px solid #ddd; padding: 14px 16px;
            margin-bottom: 16px; display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end;
        }
        .filtros label { display: block; font-size: 11px; color: #555; margin-bottom: 3px; font-weight: bold; }
        .filtros select, .filtros input[type="date"] {
            padding: 6px 8px; border: 1px solid #ccc; font-size: 12px; min-width: 160px;
        }
        .f-group { display: flex; flex-direction: column; }
        .btn-filtrar {
            background: #1a4d6d; color: #fff; padding: 7px 16px;
            border: none; cursor: pointer; font-size: 12px; align-self: flex-end;
        }
        .btn-filtrar:hover { background: #245f85; }
        .btn-limpiar {
            background: #888; color: #fff; padding: 7px 12px;
            border: none; cursor: pointer; font-size: 12px;
            align-self: flex-end; text-decoration: none; display: inline-block;
        }
        .btn-limpiar:hover { background: #666; }

        /* ── Tabla ── */
        .tabla-wrap { overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; }
        th {
            background: #1a4d6d; color: #fff; padding: 7px 10px;
            text-align: left; font-size: 12px; white-space: nowrap;
        }
        td { border: 1px solid #ddd; padding: 6px 9px; font-size: 12px; vertical-align: top; }
        tr:nth-child(even) { background: #f9f9f9; }
        tr:hover td { background: #f0f5ff; }

        /* ── Chips de materiales ── */
        .materiales-lista { display: flex; flex-wrap: wrap; gap: 5px; }
        .mat-chip {
            display: inline-flex; align-items: center; gap: 4px;
            background: #e8f1f8; border: 1px solid #b8d0e8;
            color: #1a4d6d; padding: 2px 8px;
            border-radius: 12px; font-size: 11px; white-space: nowrap;
        }
        .mat-chip .mat-nombre { font-weight: bold; }
        .mat-chip .mat-sep { color: #8aaccc; }
        .mat-chip .mat-qty { color: #333; }

        /* ── Resumen ── */
        .resumen { font-size: 12px; color: #555; margin-bottom: 10px; }
        .resumen strong { color: #1a4d6d; }

        /* ── Vacío ── */
        .empty-state {
            text-align: center; padding: 40px 20px;
            color: #888; font-size: 13px;
        }
        .empty-state .icon { font-size: 36px; margin-bottom: 10px; }
    </style>
</head>
<body>
<div class="container">

    <div class="topbar">
        <h1>📦 Almacén — Materiales Utilizados</h1>
        <div class="topbar-right">
            <?php if (!empty($registros)): ?>
            <button class="btn btn-excel" onclick="exportarExcel()">⬇ Excel</button>
            <?php endif; ?>
            <a href="?action=tablero" class="btn-back">← Tablero</a>
        </div>
    </div>

    <!-- ── Filtros ── -->
    <form method="GET" action="">
        <input type="hidden" name="action" value="almacen">
        <div class="filtros">
            <div class="f-group">
                <label>Fecha desde</label>
                <input type="date" name="fecha_desde"
                       value="<?= htmlspecialchars($_GET['fecha_desde'] ?? '') ?>">
            </div>
            <div class="f-group">
                <label>Fecha hasta</label>
                <input type="date" name="fecha_hasta"
                       value="<?= htmlspecialchars($_GET['fecha_hasta'] ?? '') ?>">
            </div>
            <div class="f-group">
                <label>Técnico</label>
                <select name="tecnico_id">
                    <option value="">— Todos —</option>
                    <?php foreach ($tecnicos as $tec): ?>
                    <option value="<?= $tec['TecnicoId'] ?>"
                        <?= (($_GET['tecnico_id'] ?? '') == $tec['TecnicoId']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($tec['TecnicoNombre']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn-filtrar">🔍 Filtrar</button>
            <a href="?action=almacen" class="btn-limpiar">✕ Limpiar</a>
        </div>
    </form>

    <p class="resumen">
        <?php if (!empty($registros)): ?>
            <strong><?= count($registros) ?></strong> ticket(s) con materiales registrados.
        <?php else: ?>
            Sin registros con los filtros seleccionados.
        <?php endif; ?>
    </p>

    <?php if (!empty($registros)): ?>
    <div style="margin-bottom:10px;display:flex;align-items:center;gap:8px;">
        <input type="text" id="buscadorTicket"
               placeholder="Buscar por número de ticket…"
               oninput="filtrarTabla()"
               style="padding:6px 10px;border:1px solid #ccc;font-size:12px;width:240px;">
        <button onclick="limpiarBuscador()"
                style="padding:5px 10px;background:#888;color:#fff;border:none;cursor:pointer;font-size:12px;">
            ✕
        </button>
        <span id="contadorFiltro" style="font-size:11px;color:#888;"></span>
    </div>
    <?php endif; ?>

    <?php if (empty($registros)): ?>
        <div class="empty-state">
            <div class="icon">📭</div>
            No hay materiales registrados todavía.
        </div>
    <?php else: ?>

    <div class="tabla-wrap">
        <table id="tablaAlmacen">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Número de Ticket</th>
                    <th>Cliente</th>
                    <th>Técnico</th>
                    <th>Materiales utilizados</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($registros as $r): ?>
            <tr data-ticket="<?= htmlspecialchars(strtolower($r['num_ticket'])) ?>">
                <td><?= date('d/m/Y', strtotime($r['fecha'])) ?></td>
                <td><?= htmlspecialchars($r['hora']) ?></td>
                <td><?= htmlspecialchars($r['num_ticket']) ?></td>
                <td><?= htmlspecialchars($r['Cliente']) ?></td>
                <td><?= htmlspecialchars($r['tecnico_nombre']) ?></td>
                <td>
                    <div class="materiales-lista">
                        <?php foreach ($r['materiales'] as $mat): ?>
                        <span class="mat-chip">
                            <span class="mat-nombre"><?= htmlspecialchars($mat['material_nombre']) ?></span>
                            <span class="mat-sep">:</span>
                            <span class="mat-qty"><?= htmlspecialchars($mat['cantidad']) ?></span>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php endif; ?>
</div>

<script>
function filtrarTabla() {
    const q     = document.getElementById('buscadorTicket').value.trim().toLowerCase();
    const filas = document.querySelectorAll('#tablaAlmacen tbody tr');
    let visibles = 0;

    filas.forEach(tr => {
        const coincide = !q || tr.dataset.ticket.includes(q);
        tr.style.display = coincide ? '' : 'none';
        if (coincide) visibles++;
    });

    const contador = document.getElementById('contadorFiltro');
    contador.textContent = q
        ? `${visibles} resultado(s) de ${filas.length}`
        : '';
}

function limpiarBuscador() {
    document.getElementById('buscadorTicket').value = '';
    filtrarTabla();
}

function exportarExcel() {
    const rows      = [];
    const cabeceras = ['Fecha', 'Hora', 'Número de Ticket', 'Cliente', 'Técnico', 'Materiales utilizados'];
    rows.push(cabeceras);

    // Solo exportar filas visibles (respeta el filtro activo)
    document.querySelectorAll('#tablaAlmacen tbody tr').forEach(tr => {
        if (tr.style.display === 'none') return;
        const celdas = tr.querySelectorAll('td');
        const chips  = tr.querySelectorAll('.mat-chip');
        const mats   = Array.from(chips).map(c => {
            const nombre = c.querySelector('.mat-nombre')?.textContent.trim() ?? '';
            const qty    = c.querySelector('.mat-qty')?.textContent.trim()    ?? '';
            return `${nombre}: ${qty}`;
        }).join(' / ');

        rows.push([
            celdas[0]?.textContent.trim() ?? '',
            celdas[1]?.textContent.trim() ?? '',
            celdas[2]?.textContent.trim() ?? '',
            celdas[3]?.textContent.trim() ?? '',
            celdas[4]?.textContent.trim() ?? '',
            mats,
        ]);
    });

    const ws = XLSX.utils.aoa_to_sheet(rows);
    ws['!cols'] = [10, 6, 14, 20, 20, 50].map(wch => ({ wch }));

    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Materiales');
    const fecha  = new Date();
    const nombre = `almacen_${fecha.getFullYear()}${String(fecha.getMonth()+1).padStart(2,'0')}${String(fecha.getDate()).padStart(2,'0')}.xlsx`;
    XLSX.writeFile(wb, nombre);
}
</script>
</body>
</html>
