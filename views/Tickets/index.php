<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../../assets/favicon.ico">
    <title>Reporte de Tickets</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>public/main.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
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

        .tabla-wrap { overflow-x:auto; }
        table { border-collapse:collapse; width:100%; font-size:11px; }
        th { background:#1a4d6d; color:#fff; padding:6px 8px; text-align:left; white-space:nowrap; }
        td { border:1px solid #ddd; padding:5px 7px; vertical-align:top; }
        tr:nth-child(even) { background:#f9f9f9; }

        tr.estado-terminado td { background:#d4edda !important; }
        tr.estado-rojo td      { background:#fde8e8 !important; }

        .badge-terminado {
            display:inline-block; background:#1e7145; color:#fff;
            padding:2px 8px; border-radius:10px; font-size:10px; white-space:nowrap;
        }
        .badge-proceso {
            display:inline-block; background:#888; color:#fff;
            padding:2px 8px; border-radius:10px; font-size:10px; white-space:nowrap;
        }

        /* Llamadas consolidadas */
        .llamada-cell { font-size:10px; line-height:1.5; min-width:140px; }
        .llamada-row { display:flex; gap:4px; }
        .llamada-label {
            font-weight:bold; color:#1a4d6d; white-space:nowrap;
            min-width:52px; flex-shrink:0;
        }
        .llamada-val { color:#333; }
        .llamada-sep { border-top:1px dashed #ccc; margin:3px 0; }

        .resumen { font-size:12px; color:#555; margin-bottom:10px; }
        .resumen strong { color:#1a4d6d; }

        /* Paginación */
        .paginacion {
            display:flex; align-items:center; gap:6px;
            margin-top:14px; flex-wrap:wrap; font-size:12px;
        }
        .paginacion button {
            padding:4px 10px; border:1px solid #ccc; background:#fff;
            cursor:pointer; font-size:12px; border-radius:2px;
        }
        .paginacion button:hover:not(:disabled) { background:#e8f1f8; border-color:#1a4d6d; }
        .paginacion button:disabled { opacity:.4; cursor:not-allowed; }
        .paginacion button.activa { background:#1a4d6d; color:#fff; border-color:#1a4d6d; }
        .paginacion .info-pag { color:#555; margin:0 6px; }
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
        Mostrando <strong id="resumenMostrando">0</strong> de <strong><?= count($tickets) ?></strong> ticket(s).
        <?php if (count($tickets)): ?>
        Verde = terminado &nbsp;|&nbsp; Rojo = 3 llamadas registradas (sin terminar).
        <?php endif; ?>
    </p>

    <?php if (empty($tickets)): ?>
    <p style="color:#888;font-size:12px;padding:20px 0;">No hay tickets con los filtros seleccionados.</p>
    <?php else: ?>

    <!-- Tabla visible (paginada) -->
    <div class="tabla-wrap">
        <table id="tablaVisible">
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
                    <th>Llamada 1</th>
                    <th>Llamada 2</th>
                    <th>Llamada 3</th>
                </tr>
            </thead>
            <tbody id="tbodyVisible"></tbody>
        </table>
    </div>

    <div class="paginacion" id="paginacion"></div>

    <!-- Tabla oculta con TODOS los datos para exportar -->
    <table id="tablaCompleta" style="display:none;">
        <thead>
            <tr>
                <th>#</th><th>Fecha</th><th>Hora</th><th>Estado</th>
                <th>Num. Ticket</th><th>Cliente</th><th>Colonia</th>
                <th>Tel. Cliente</th><th>Descripción</th><th>Técnico</th>
                <th>Tel. Técnico</th><th>Agente</th>
                <th>Llamada 1 — Técnico</th><th>Llamada 1 — Cliente</th>
                <th>Llamada 2 — Técnico</th><th>Llamada 2 — Cliente</th>
                <th>Llamada 3 — Técnico</th><th>Llamada 3 — Cliente</th>
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
            <td><?= $esTerminado ? 'Terminado' : 'En proceso' ?></td>
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

    <?php endif; ?>
</div>

<script>
/* ══════════════════════════════════════════════════
   DATOS — construidos desde la tabla oculta completa
══════════════════════════════════════════════════ */
const POR_PAGINA = 15;
let paginaActual = 1;

// Leer todas las filas de la tabla completa y guardarlas como objetos
const FILAS_DATOS = (() => {
    const rows = document.querySelectorAll('#tablaCompleta tbody tr');
    return Array.from(rows).map(tr => {
        const celdas = tr.querySelectorAll('td');
        return {
            num         : celdas[0]?.textContent.trim()  ?? '',
            fecha       : celdas[1]?.textContent.trim()  ?? '',
            hora        : celdas[2]?.textContent.trim()  ?? '',
            estado      : celdas[3]?.textContent.trim()  ?? '',
            num_ticket  : celdas[4]?.textContent.trim()  ?? '',
            cliente     : celdas[5]?.textContent.trim()  ?? '',
            colonia     : celdas[6]?.textContent.trim()  ?? '',
            tel_cliente : celdas[7]?.textContent.trim()  ?? '',
            descripcion : celdas[8]?.textContent.trim()  ?? '',
            tecnico     : celdas[9]?.textContent.trim()  ?? '',
            tel_tecnico : celdas[10]?.textContent.trim() ?? '',
            agente      : celdas[11]?.textContent.trim() ?? '',
            ll1_tec     : celdas[12]?.textContent.trim() ?? '',
            ll1_cli     : celdas[13]?.textContent.trim() ?? '',
            ll2_tec     : celdas[14]?.textContent.trim() ?? '',
            ll2_cli     : celdas[15]?.textContent.trim() ?? '',
            ll3_tec     : celdas[16]?.textContent.trim() ?? '',
            ll3_cli     : celdas[17]?.textContent.trim() ?? '',
            esTerminado : tr.classList.contains('estado-terminado'),
            esRojo      : tr.classList.contains('estado-rojo'),
        };
    });
})();

/* ── Construir una celda de llamada consolidada ── */
function celdaLlamada(tecVal, cliVal) {
    if (!tecVal && !cliVal) return '<td class="llamada-cell">—</td>';
    let html = '<td class="llamada-cell">';
    if (tecVal) html += `<div class="llamada-row"><span class="llamada-label">Técnico:</span><span class="llamada-val">${escHtml(tecVal)}</span></div>`;
    if (cliVal) {
        if (tecVal) html += '<div class="llamada-sep"></div>';
        html += `<div class="llamada-row"><span class="llamada-label">Cliente:</span><span class="llamada-val">${escHtml(cliVal)}</span></div>`;
    }
    html += '</td>';
    return html;
}

function escHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ── Renderizar página ── */
function renderPagina(pagina) {
    paginaActual = pagina;
    const inicio = (pagina - 1) * POR_PAGINA;
    const fin    = Math.min(inicio + POR_PAGINA, FILAS_DATOS.length);
    const slice  = FILAS_DATOS.slice(inicio, fin);

    const tbody = document.getElementById('tbodyVisible');
    tbody.innerHTML = slice.map(f => {
        const trClass = f.esTerminado ? 'estado-terminado' : (f.esRojo ? 'estado-rojo' : '');
        const badge   = f.esTerminado
            ? '<span class="badge-terminado">Terminado</span>'
            : '<span class="badge-proceso">En proceso</span>';
        return `<tr class="${trClass}">
            <td>${f.num}</td>
            <td>${f.fecha}</td>
            <td>${f.hora}</td>
            <td>${badge}</td>
            <td>${escHtml(f.num_ticket)}</td>
            <td>${escHtml(f.cliente)}</td>
            <td>${escHtml(f.colonia)}</td>
            <td>${escHtml(f.tel_cliente)}</td>
            <td>${escHtml(f.descripcion)}</td>
            <td>${escHtml(f.tecnico)}</td>
            <td>${escHtml(f.tel_tecnico)}</td>
            <td>${escHtml(f.agente)}</td>
            ${celdaLlamada(f.ll1_tec, f.ll1_cli)}
            ${celdaLlamada(f.ll2_tec, f.ll2_cli)}
            ${celdaLlamada(f.ll3_tec, f.ll3_cli)}
        </tr>`;
    }).join('');

    // Resumen
    document.getElementById('resumenMostrando').textContent =
        FILAS_DATOS.length ? `${inicio + 1}–${fin}` : '0';

    renderPaginacion();
}

/* ── Renderizar controles de paginación ── */
function renderPaginacion() {
    const totalPags = Math.ceil(FILAS_DATOS.length / POR_PAGINA);
    const el = document.getElementById('paginacion');
    if (totalPags <= 1) { el.innerHTML = ''; return; }

    let html = '';
    html += `<button onclick="renderPagina(1)" ${paginaActual===1?'disabled':''}>«</button>`;
    html += `<button onclick="renderPagina(${paginaActual-1})" ${paginaActual===1?'disabled':''}>‹ Anterior</button>`;

    // Páginas cercanas
    const rango = 2;
    for (let p = 1; p <= totalPags; p++) {
        if (p === 1 || p === totalPags || Math.abs(p - paginaActual) <= rango) {
            html += `<button onclick="renderPagina(${p})" class="${p===paginaActual?'activa':''}">${p}</button>`;
        } else if (Math.abs(p - paginaActual) === rango + 1) {
            html += `<span class="info-pag">…</span>`;
        }
    }

    html += `<button onclick="renderPagina(${paginaActual+1})" ${paginaActual===totalPags?'disabled':''}>Siguiente ›</button>`;
    html += `<button onclick="renderPagina(${totalPags})" ${paginaActual===totalPags?'disabled':''}>»</button>`;
    html += `<span class="info-pag">Página ${paginaActual} de ${totalPags} &nbsp;|&nbsp; ${FILAS_DATOS.length} registros</span>`;
    el.innerHTML = html;
}

// Inicializar
if (FILAS_DATOS.length) renderPagina(1);

/* ══════════════════════════════════════════════════
   EXPORTAR EXCEL — usa tabla completa (oculta)
══════════════════════════════════════════════════ */
function exportarExcel() {
    if (!FILAS_DATOS.length) { alert('No hay datos para exportar.'); return; }

    const cabeceras = [
        '#','Fecha','Hora','Estado','Num. Ticket','Cliente','Colonia',
        'Tel. Cliente','Descripción','Técnico','Tel. Técnico','Agente',
        'Llamada 1 — Técnico','Llamada 1 — Cliente',
        'Llamada 2 — Técnico','Llamada 2 — Cliente',
        'Llamada 3 — Técnico','Llamada 3 — Cliente',
    ];

    const filas = FILAS_DATOS.map(f => [
        f.num, f.fecha, f.hora, f.estado, f.num_ticket, f.cliente,
        f.colonia, f.tel_cliente, f.descripcion, f.tecnico, f.tel_tecnico,
        f.agente, f.ll1_tec, f.ll1_cli, f.ll2_tec, f.ll2_cli, f.ll3_tec, f.ll3_cli,
    ]);

    const ws = XLSX.utils.aoa_to_sheet([cabeceras, ...filas]);
    ws['!cols'] = cabeceras.map((_, i) => ({
        wch: Math.min(40, Math.max(10, ...[cabeceras, ...filas].map(r => String(r[i] ?? '').length)))
    }));

    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Tickets');
    XLSX.writeFile(wb, `reporte_tickets_${fechaHoy()}.xlsx`);
}

/* ══════════════════════════════════════════════════
   EXPORTAR PDF — usa todos los datos
══════════════════════════════════════════════════ */
function exportarPDF() {
    if (!FILAS_DATOS.length) { alert('No hay datos para exportar.'); return; }

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ orientation: 'landscape', unit: 'mm', format: 'a3' });

    doc.setFontSize(13);
    doc.text('Reporte de Tickets — Sistema de Incidentes', 14, 14);
    doc.setFontSize(9);
    doc.text(`Generado: ${new Date().toLocaleString('es-MX')}`, 14, 20);

    const cabeceras = [
        '#','Fecha','Hora','Estado','Núm. Ticket','Cliente','Colonia',
        'Tel. Cliente','Descripción','Técnico','Tel. Técnico','Agente',
        'Llamada 1\nTécnico','Llamada 1\nCliente',
        'Llamada 2\nTécnico','Llamada 2\nCliente',
        'Llamada 3\nTécnico','Llamada 3\nCliente',
    ];

    const rows = FILAS_DATOS.map(f => [
        f.num, f.fecha, f.hora, f.estado, f.num_ticket, f.cliente,
        f.colonia, f.tel_cliente, f.descripcion, f.tecnico, f.tel_tecnico,
        f.agente, f.ll1_tec, f.ll1_cli, f.ll2_tec, f.ll2_cli, f.ll3_tec, f.ll3_cli,
    ]);

    doc.autoTable({
        head      : [cabeceras],
        body      : rows,
        startY    : 24,
        styles    : { fontSize: 7, cellPadding: 2, overflow: 'linebreak' },
        headStyles: { fillColor: [26, 77, 109], textColor: 255, fontStyle: 'bold' },
        alternateRowStyles: { fillColor: [249, 249, 249] },
        didParseCell(data) {
            if (data.section !== 'body') return;
            const f = FILAS_DATOS[data.row.index];
            if (!f) return;
            if (f.esTerminado) data.cell.styles.fillColor = [212, 237, 218];
            else if (f.esRojo) data.cell.styles.fillColor = [253, 232, 232];
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