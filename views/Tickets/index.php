<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../assets/favicon.ico">
    <title>Reporte de Tickets</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>public/main.css">
    <!-- SheetJS para Excel -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <!-- jsPDF + AutoTable para PDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
    <style>
        .topbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:8px; }
        .topbar h1 { margin:0; font-size:18px; }
        .topbar-right { display:flex; align-items:center; gap:8px; }
        .btn { padding:7px 14px; border:none; cursor:pointer; font-size:12px; }
        .btn-back { padding:5px 12px; background:#1a4d6d; color:#fff; border:none; cursor:pointer; font-size:12px; text-decoration:none; display:inline-block; }
        .btn-back:hover { background:#245f85; }
        .btn-primary { background:#1a4d6d; color:#fff; }
        .btn-primary:hover { background:#245f85; }
        .btn-excel  { background:#1e7145; color:#fff; }
        .btn-excel:hover  { background:#155a36; }
        .btn-pdf    { background:#c0392b; color:#fff; }
        .btn-pdf:hover    { background:#a93226; }

        /* ── Filtros ── */
        .filtros {
            background:#f8f9fa; border:1px solid #ddd; padding:14px 16px;
            margin-bottom:16px; display:flex; flex-wrap:wrap; gap:12px; align-items:flex-end;
        }
        .filtros label { display:block; font-size:11px; color:#555; margin-bottom:3px; font-weight:bold; }
        .filtros select, .filtros input[type="date"] {
            padding:6px 8px; border:1px solid #ccc; font-size:12px; min-width:160px;
        }
        .filtros .f-group { display:flex; flex-direction:column; }
        .btn-filtrar { background:#1a4d6d; color:#fff; padding:7px 16px; border:none; cursor:pointer; font-size:12px; align-self:flex-end; }
        .btn-filtrar:hover { background:#245f85; }
        .btn-limpiar { background:#888; color:#fff; padding:7px 12px; border:none; cursor:pointer; font-size:12px; align-self:flex-end; text-decoration:none; display:inline-block; }
        .btn-limpiar:hover { background:#666; }

        /* ── Tabla ── */
        .tabla-wrap { overflow-x:auto; }
        table { border-collapse:collapse; width:100%; font-size:11px; }
        th { background:#1a4d6d; color:#fff; padding:6px 8px; text-align:left; white-space:nowrap; }
        td { border:1px solid #ddd; padding:5px 7px; vertical-align:top; }
        tr:nth-child(even) { background:#f9f9f9; }

        /* ── Estados de fila ── */
        tr.estado-terminado td { background:#d4edda !important; }
        tr.estado-rojo td      { background:#fde8e8 !important; }
        /* El ícono de asignación NO debe heredar el fondo — se protege con inline bg */

        .badge-terminado {
            display:inline-block; background:#1e7145; color:#fff;
            padding:2px 8px; border-radius:10px; font-size:10px; white-space:nowrap;
        }
        .badge-proceso {
            display:inline-block; background:#888; color:#fff;
            padding:2px 8px; border-radius:10px; font-size:10px; white-space:nowrap;
        }

        .llamada-cell { font-size:10px; line-height:1.4; }
        .llamada-cell strong { color:#1a4d6d; }
        .llamada-sep { border-top:1px dashed #ccc; margin:3px 0; }

        .resumen {
            font-size:12px; color:#555; margin-bottom:10px;
        }
        .resumen strong { color:#1a4d6d; }
    </style>
</head>
<body>
<div class="container">
    <div class="topbar">
        <h1>📋 Reporte de Tickets</h1>
        <div class="topbar-right">
            <button class="btn btn-excel" onclick="exportarExcel()">⬇ Excel</button>
            <button class="btn btn-pdf"   onclick="exportarPDF()">⬇ PDF</button>
            <a href="?action=tablero" class="btn-back">← Tablero</a>
        </div>
    </div>

    <!-- ── Filtros ── -->
    <form method="GET" action="" id="formFiltros">
        <input type="hidden" name="action" value="admin.reporte">
        <div class="filtros">
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
            <div class="f-group">
                <label>Fecha desde</label>
                <input type="date" name="fecha_desde" value="<?= htmlspecialchars($_GET['fecha_desde'] ?? '') ?>">
            </div>
            <div class="f-group">
                <label>Fecha hasta</label>
                <input type="date" name="fecha_hasta" value="<?= htmlspecialchars($_GET['fecha_hasta'] ?? '') ?>">
            </div>
            <div class="f-group">
                <label>Agente</label>
                <select name="usuario_id">
                    <option value="">— Todos —</option>
                    <?php foreach ($usuarios as $u): ?>
                    <option value="<?= $u['usu_id'] ?>"
                        <?= (($_GET['usuario_id'] ?? '') == $u['usu_id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($u['nombre']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="f-group">
                <label>Estado</label>
                <select name="estado">
                    <option value="">— Todos —</option>
                    <option value="pendiente"  <?= (($_GET['estado'] ?? '') === 'pendiente')  ? 'selected' : '' ?>>En proceso</option>
                    <option value="terminado"  <?= (($_GET['estado'] ?? '') === 'terminado')  ? 'selected' : '' ?>>Terminado</option>
                </select>
            </div>
            <button type="submit" class="btn-filtrar">🔍 Filtrar</button>
            <a href="?action=admin.reporte" class="btn-limpiar">✕ Limpiar</a>
        </div>
    </form>

    <p class="resumen">
        Mostrando <strong><?= count($tickets) ?></strong> ticket(s).
        <?php if (count($tickets)): ?>
        Verde = terminado &nbsp;|&nbsp; Rojo = 3 llamadas registradas (sin terminar).
        <?php endif; ?>
    </p>

    <?php if (empty($tickets)): ?>
    <p style="color:#888;font-size:12px;padding:20px 0;">No hay tickets con los filtros seleccionados.</p>
    <?php else: ?>
    <div class="tabla-wrap">
        <table id="tablaReporte">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Estado</th>
                    <th>Num. Ticket</th>
                    <th>Cliente</th>
                    <th>Colonia</th>
                    <th>Tel. Cliente</th>
                    <th>Descripción</th>
                    <th>Técnico</th>
                    <th>Tel. Técnico</th>
                    <th>Agente</th>
                    <th>Llamada 1 — Técnico</th>
                    <th>Llamada 1 — Cliente</th>
                    <th>Llamada 2 — Técnico</th>
                    <th>Llamada 2 — Cliente</th>
                    <th>Llamada 3 — Técnico</th>
                    <th>Llamada 3 — Cliente</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($tickets as $i => $t):
                $esTerminado = $t['estado'] === 'terminado';
                $esRojo      = !$esTerminado && (int)$t['total_llamadas'] >= 3;
                $trClass     = $esTerminado ? 'estado-terminado' : ($esRojo ? 'estado-rojo' : '');
            ?>
            <tr class="<?= $trClass ?>">
                <td><?= $i + 1 ?></td>
                <td><?= date('d/m/Y', strtotime($t['fecha'])) ?></td>
                <td><?= htmlspecialchars(substr($t['hora'], 0, 5)) ?></td>
                <td>
                    <?php if ($esTerminado): ?>
                    <span class="badge-terminado">Terminado</span>
                    <?php else: ?>
                    <span class="badge-proceso">En proceso</span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($t['num_ticket']) ?></td>
                <td><?= htmlspecialchars($t['Cliente']) ?></td>
                <td><?= htmlspecialchars($t['colonia']) ?></td>
                <td><?= htmlspecialchars($t['telefono_cliente']) ?></td>
                <td><?= htmlspecialchars($t['Descripcion']) ?></td>
                <td><?= htmlspecialchars($t['tecnico_nombre']) ?></td>
                <td><?= htmlspecialchars($t['telefono_tecnico'] ?? '—') ?></td>
                <td><?= htmlspecialchars($t['agente_nombre']) ?></td>
                <td><?= htmlspecialchars($t['ll1_tecnico'] ?? '') ?></td>
                <td><?= htmlspecialchars($t['ll1_cliente'] ?? '') ?></td>
                <td><?= htmlspecialchars($t['ll2_tecnico'] ?? '') ?></td>
                <td><?= htmlspecialchars($t['ll2_cliente'] ?? '') ?></td>
                <td><?= htmlspecialchars($t['ll3_tecnico'] ?? '') ?></td>
                <td><?= htmlspecialchars($t['ll3_cliente'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script>
/* ── Exportar Excel con SheetJS ───────────────────────────── */
function exportarExcel() {
    const tabla = document.getElementById('tablaReporte');
    if (!tabla) { alert('No hay datos para exportar.'); return; }

    // Construir array de datos desde la tabla (sin clases de color)
    const filas = [];
    // Encabezados
    const ths = tabla.querySelectorAll('thead th');
    filas.push(Array.from(ths).map(th => th.textContent.trim()));
    // Datos
    tabla.querySelectorAll('tbody tr').forEach(tr => {
        filas.push(Array.from(tr.querySelectorAll('td')).map(td => td.textContent.trim()));
    });

    const ws = XLSX.utils.aoa_to_sheet(filas);
    // Ancho de columnas automático
    ws['!cols'] = filas[0].map((_, i) => ({
        wch: Math.min(40, Math.max(10,
            ...filas.map(f => (f[i] || '').toString().length)
        ))
    }));

    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Tickets');
    XLSX.writeFile(wb, `reporte_tickets_${fechaHoy()}.xlsx`);
}

/* ── Exportar PDF con jsPDF + AutoTable ───────────────────── */
function exportarPDF() {
    const tabla = document.getElementById('tablaReporte');
    if (!tabla) { alert('No hay datos para exportar.'); return; }

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a3' });

    doc.setFontSize(13);
    doc.text('Reporte de Tickets — Sistema de Incidentes', 14, 14);
    doc.setFontSize(9);
    doc.text(`Generado: ${new Date().toLocaleString('es-MX')}`, 14, 20);

    // Encabezados y filas
    const ths  = Array.from(tabla.querySelectorAll('thead th')).map(th => th.textContent.trim());
    const rows = Array.from(tabla.querySelectorAll('tbody tr')).map(tr =>
        Array.from(tr.querySelectorAll('td')).map(td => td.textContent.trim())
    );

    doc.autoTable({
        head      : [ths],
        body      : rows,
        startY    : 24,
        styles    : { fontSize: 7, cellPadding: 2, overflow: 'linebreak' },
        headStyles: { fillColor: [26, 77, 109], textColor: 255, fontStyle: 'bold' },
        alternateRowStyles: { fillColor: [249, 249, 249] },
        // Colorear filas según estado
        didParseCell: function(data) {
            if (data.section === 'body') {
                const tr = tabla.querySelectorAll('tbody tr')[data.row.index];
                if (tr) {
                    if (tr.classList.contains('estado-terminado')) {
                        data.cell.styles.fillColor = [212, 237, 218];
                    } else if (tr.classList.contains('estado-rojo')) {
                        data.cell.styles.fillColor = [253, 232, 232];
                    }
                }
            }
        },
    });

    doc.save(`reporte_tickets_${fechaHoy()}.pdf`);
}

function fechaHoy() {
    const d = new Date();
    return `${d.getFullYear()}${String(d.getMonth()+1).padStart(2,'0')}${String(d.getDate()).padStart(2,'0')}`;
}
</script>
</body>
</html>
