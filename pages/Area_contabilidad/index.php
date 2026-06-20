<?php
session_start(); 

if (!isset($_SESSION["usuario"])) {
    header("Location: ../../index.php");
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../../conexion.php';
mysqli_set_charset($conexion, 'utf8mb4');
date_default_timezone_set("America/Lima");

// ── FILTROS ──
$search_from = $_GET['search_date_from'] ?? '';
$search_to   = $_GET['search_date_to']   ?? '';
$search_est  = $_GET['estado']           ?? '';

// ── CONSULTA PRINCIPAL (adaptada al BD real) ──
$where = "WHERE 1=1";
if (!empty($search_from) && !empty($search_to)) {
    $sf = mysqli_real_escape_string($conexion, $search_from);
    $st = mysqli_real_escape_string($conexion, $search_to);
    $where .= " AND od.fecha_salida BETWEEN '$sf' AND '$st'";
}
if (!empty($search_est)) {
    $se = mysqli_real_escape_string($conexion, $search_est);
    $where .= " AND c.estado = '$se'";
}

$query = "
SELECT
    g.id_grupo,
    g.nombre_grupo,
    g.estado AS estado_grupo,

    -- Cliente principal (pagador o primero)
    (SELECT CONCAT(dc2.nombre,' ',dc2.apellido)
     FROM clientes_grupo cg2
     JOIN datos_clientes dc2 ON dc2.id_cliente = cg2.id_cliente
     WHERE cg2.id_grupo = g.id_grupo
     ORDER BY cg2.es_pagador DESC, cg2.id ASC
     LIMIT 1) AS primer_cliente,

    -- Total pasajeros
    (SELECT COUNT(*) FROM clientes_grupo cgx WHERE cgx.id_grupo = g.id_grupo) AS pasajeros,

    o.id_operaciones,
    o.encargado,
    o.fecha_reserva,
    o.estado        AS op_estado,
    o.total_operacion,
    o.tipo_precio,
    o.observaciones,

    -- Servicios agrupados
    GROUP_CONCAT(
        DISTINCT CONCAT(IFNULL(s.nombre,'Sin servicio'), '||', IFNULL(od.fecha_salida,''))
        ORDER BY od.fecha_salida ASC
        SEPARATOR ';;'
    ) AS servicios_raw,

    -- Primer tour (para columna salida)
    MIN(od.fecha_salida) AS primera_salida,
    MAX(od.fecha_retorno) AS ultimo_retorno,

    -- Suma precios detalle
    SUM(DISTINCT od.precio) AS suma_precio_tours,

    -- Contabilidad
    c.id_contabilidad,
    c.estado,
    c.comision,
    c.igv,
    c.detraccion,
    c.nro_boleta_cuenta,
    c.nro_boleta_total,
    c.modalidad_recibo,
    c.fecha_registro AS fecha_conta,

    -- Pagos reales
    IFNULL((SELECT SUM(p.monto) FROM pagos p WHERE p.id_operaciones = o.id_operaciones AND p.tipo != 'reembolso' AND p.moneda='Soles'),0) AS pagado_soles,
    IFNULL((SELECT SUM(p.monto) FROM pagos p WHERE p.id_operaciones = o.id_operaciones AND p.tipo != 'reembolso' AND p.moneda='Dólares'),0) AS pagado_dolares,
    IFNULL((SELECT SUM(p.monto) FROM pagos p WHERE p.id_operaciones = o.id_operaciones AND p.tipo = 'reembolso'),0) AS reembolsos,
    IFNULL((SELECT SUM(p.monto) FROM pagos p WHERE p.id_operaciones = o.id_operaciones AND p.tipo = 'cuenta'),0) AS pago_cuenta,
    IFNULL((SELECT SUM(p.monto) FROM pagos p WHERE p.id_operaciones = o.id_operaciones AND p.tipo = 'saldo'),0) AS pago_saldo

FROM grupos g
LEFT JOIN operaciones o ON o.id_grupo = g.id_grupo
LEFT JOIN operaciones_detalle od ON od.id_operaciones = o.id_operaciones
LEFT JOIN servicios s ON s.id_servicio = od.id_servicio
LEFT JOIN contabilidad c ON c.id_operaciones = o.id_operaciones
$where
GROUP BY g.id_grupo, o.id_operaciones, c.id_contabilidad
ORDER BY g.id_grupo ASC
";

$resultado = mysqli_query($conexion, $query);
if (!$resultado) die("Error: " . mysqli_error($conexion));
$datos = mysqli_fetch_all($resultado, MYSQLI_ASSOC);

// ── TOTALES RESUMEN ──
$tot_soles = 0; $tot_dolares = 0; $tot_saldo = 0; $tot_cuenta = 0;
$cnt_pagado = 0; $cnt_pendiente = 0; $cnt_cancelado = 0;
foreach ($datos as $row) {
    $tot_soles   += $row['pagado_soles'];
    $tot_dolares += $row['pagado_dolares'];
    $tot_cuenta  += $row['pago_cuenta'];
    $tot_saldo   += $row['pago_saldo'];
    $est = $row['estado'] ?? 'pendiente';
    if ($est === 'pagado')    $cnt_pagado++;
    elseif ($est === 'cancelado') $cnt_cancelado++;
    else $cnt_pendiente++;
}
$total_rows = count($datos);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contabilidad — KB Tours</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
<style>
:root{
    --brand:#1a56db;--brand-light:#dbeafe;--brand-dark:#1e40af;
    --surface:#fff;--surface-2:#f8fafc;--border:#e2e8f0;
    --text:#0f172a;--text-muted:#64748b;
    --success:#16a34a;--warning:#d97706;--danger:#dc2626;--info:#0891b2;
    --radius:12px;--shadow:0 1px 3px rgba(0,0,0,.06),0 4px 16px rgba(0,0,0,.04);
}
*,*::before,*::after{box-sizing:border-box}
body{font-family:'DM Sans',sans-serif;background:var(--surface-2);color:var(--text);font-size:14px;line-height:1.6}

.page-header{background:var(--surface);border-bottom:1px solid var(--border);padding:18px 32px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100}
.page-header h1{font-size:18px;font-weight:700;margin:0}
.page-header .subtitle{color:var(--text-muted);font-size:13px;margin:0}

.main-content{max-width:1400px;margin:0 auto;padding:28px 24px 60px}

.kb-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow);margin-bottom:24px;overflow:hidden}
.kb-card-header{display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--border);background:var(--surface-2)}
.section-title{display:flex;align-items:center;gap:10px;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--text)}
.section-icon{width:28px;height:28px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:14px;color:#fff}

.kpi-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:16px;margin-bottom:28px}
.kpi-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:16px 18px;box-shadow:var(--shadow);position:relative;overflow:hidden;transition:transform .15s}
.kpi-card:hover{transform:translateY(-2px)}
.kpi-icon{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:17px;margin-bottom:10px}
.kpi-label{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.6px;color:var(--text-muted);margin-bottom:4px}
.kpi-value{font-size:20px;font-weight:700;line-height:1}
.kpi-sub{font-size:11px;color:var(--text-muted);margin-top:3px}
.kpi-accent{position:absolute;bottom:0;left:0;right:0;height:3px}

/* FILTROS */
.filter-bar{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:16px 20px;margin-bottom:20px;display:flex;align-items:flex-end;gap:14px;flex-wrap:wrap;box-shadow:var(--shadow)}
.filter-group{display:flex;flex-direction:column;gap:4px}
.filter-group label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted)}
.filter-group input,.filter-group select{background:var(--surface-2);border:1px solid var(--border);border-radius:8px;padding:7px 12px;font-size:13px;font-family:'DM Sans',sans-serif;color:var(--text);outline:none;transition:border-color .15s}
.filter-group input:focus,.filter-group select:focus{border-color:var(--brand)}
.btn-filter{padding:8px 18px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;border:none;transition:all .15s;display:flex;align-items:center;gap:6px}
.btn-primary-kb{background:var(--brand);color:#fff}
.btn-primary-kb:hover{background:var(--brand-dark)}
.btn-outline-kb{background:transparent;color:var(--text-muted);border:1px solid var(--border)}
.btn-outline-kb:hover{background:var(--surface-2);color:var(--text)}

/* ESTADOS */
.estado-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;white-space:nowrap}
.estado-pagado{background:#dcfce7;color:#15803d}
.estado-pendiente{background:#fef9c3;color:#a16207}
.estado-cancelado{background:#fee2e2;color:#b91c1c}
.estado-confirmado{background:#dcfce7;color:#15803d}
.tipo-badge{display:inline-block;padding:2px 8px;border-radius:5px;font-size:11px;font-weight:600;text-transform:uppercase}
.tipo-tour{background:#dbeafe;color:#1e40af}

/* MONEY */
.monto-val{font-family:'DM Mono',monospace;font-weight:500;font-size:12px}
.monto-soles{color:#1e40af}
.monto-dolares{color:#166534}
.monto-neg{color:#b91c1c}

/* SERVICIO CHIPS */
.servicio-chip{display:inline-flex;align-items:center;gap:4px;padding:2px 8px;border-radius:5px;font-size:11px;font-weight:500;margin:1px;white-space:nowrap}

/* DATATABLE OVERRIDES */
.dataTables_wrapper .dataTables_filter input{
    border:1px solid var(--border);border-radius:8px;padding:6px 12px;
    font-family:'DM Sans',sans-serif;font-size:13px;background:var(--surface-2);
    outline:none;color:var(--text);
}
.dataTables_wrapper .dataTables_filter input:focus{border-color:var(--brand)}
.dataTables_wrapper .dataTables_length select{
    border:1px solid var(--border);border-radius:8px;padding:5px 10px;
    font-family:'DM Sans',sans-serif;font-size:13px;background:var(--surface-2);
}
.dataTables_wrapper .dt-buttons .btn{
    font-family:'DM Sans',sans-serif !important;font-size:12px !important;
    font-weight:600 !important;border-radius:8px !important;
    padding:7px 14px !important;
}
table.dataTable thead th{
    background:var(--surface-2) !important;
    font-size:11px !important;font-weight:700 !important;
    text-transform:uppercase;letter-spacing:.6px;color:var(--text-muted) !important;
    border-bottom:2px solid var(--border) !important;
    padding:10px 12px !important;white-space:nowrap;
}
table.dataTable tbody td{padding:11px 12px !important;vertical-align:middle !important;border-bottom:1px solid var(--border) !important;}
table.dataTable tbody tr:hover{background:var(--surface-2) !important}
table.dataTable{border-collapse:separate !important;border-spacing:0 !important}
.dataTables_wrapper .dataTables_paginate .paginate_button.current{
    background:var(--brand) !important;color:#fff !important;border-radius:6px !important;border:none !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button:hover{
    background:var(--surface-2) !important;color:var(--text) !important;border-radius:6px !important;border:none !important;
}
.dataTables_wrapper .dataTables_info{font-size:12px;color:var(--text-muted)}

/* ACCIONES */
.action-btn{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:7px;border:1px solid var(--border);background:var(--surface-2);color:var(--text);font-size:14px;text-decoration:none;transition:all .15s;cursor:pointer}
.action-btn:hover{background:var(--brand);color:#fff;border-color:var(--brand)}
.action-btn.danger:hover{background:#dc2626;border-color:#dc2626;color:#fff}
.action-btn.success:hover{background:#16a34a;border-color:#16a34a;color:#fff}
.action-btn.warning:hover{background:#d97706;border-color:#d97706;color:#fff}

/* MODAL */
.modal-header-kb{background:var(--surface-2);border-bottom:1px solid var(--border);padding:16px 20px}
.modal-title-kb{font-size:15px;font-weight:700;display:flex;align-items:center;gap:8px}
.form-label-kb{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);margin-bottom:5px}
.form-control-kb{background:var(--surface-2);border:1px solid var(--border);border-radius:8px;padding:8px 12px;font-size:13px;font-family:'DM Sans',sans-serif;width:100%;outline:none;color:var(--text);transition:border-color .15s}
.form-control-kb:focus{border-color:var(--brand)}

@media(max-width:768px){.page-header{padding:14px 16px}.main-content{padding:14px 12px 40px}.filter-bar{flex-direction:column;align-items:stretch}}

.tour-chip{
    border-radius:12px;
    padding:12px 14px;
    margin-bottom:8px;
    border-left:4px solid;
}

.tour-blue{
    background:#dbeafe;
    border-left-color:#2563eb;
}

.tour-green{
    background:#dcfce7;
    border-left-color:#16a34a;
}

.tour-name{
    font-size:13px;
    font-weight:700;
    text-transform:uppercase;
    margin-bottom:6px;
}

.tour-blue .tour-name{
    color:#1d4ed8;
}

.tour-green .tour-name{
    color:#166534;
}

.tour-date{
    font-size:12px;
    color:#64748b;
}
</style>
</head>
<body>
<?php include './../sidebar.php'; ?>
<div class="kb-content">
<!-- ════ HEADER ════ -->
<div class="page-header">
    <div>
        <h1><i class="bi bi-file-earmark-text-fill text-primary me-2"></i>Contabilidad</h1>
        <p class="subtitle">Gestión de pagos, boletas y estados por operación</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn-filter btn-outline-kb" data-bs-toggle="modal" data-bs-target="#modalImportar">
            <i class="bi bi-file-earmark-arrow-down"></i> Importar Excel
        </button>
        <button class="btn-filter btn-primary-kb" onclick="window.print()">
            <i class="bi bi-printer"></i> Imprimir
        </button>
    </div>
</div>

<div class="main-content">

<!-- ════ KPI ════ -->
<div class="kpi-grid">

    <div class="kpi-card">
        <div class="kpi-icon" style="background:#dbeafe;color:#1a56db"><i class="bi bi-clipboard2-data-fill"></i></div>
        <div class="kpi-label">Total registros</div>
        <div class="kpi-value text-primary"><?= number_format($total_rows) ?></div>
        <div class="kpi-sub">operaciones</div>
        <div class="kpi-accent" style="background:#1a56db"></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon" style="background:#dcfce7;color:#16a34a"><i class="bi bi-check-circle-fill"></i></div>
        <div class="kpi-label">Pagadas</div>
        <div class="kpi-value" style="color:#16a34a"><?= $cnt_pagado ?></div>
        <div class="kpi-sub">completadas</div>
        <div class="kpi-accent" style="background:#16a34a"></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon" style="background:#fef9c3;color:#a16207"><i class="bi bi-clock-fill"></i></div>
        <div class="kpi-label">Pendientes</div>
        <div class="kpi-value" style="color:#a16207"><?= $cnt_pendiente ?></div>
        <div class="kpi-sub">por cobrar</div>
        <div class="kpi-accent" style="background:#d97706"></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon" style="background:#fee2e2;color:#dc2626"><i class="bi bi-x-circle-fill"></i></div>
        <div class="kpi-label">Canceladas</div>
        <div class="kpi-value" style="color:#dc2626"><?= $cnt_cancelado ?></div>
        <div class="kpi-sub">anuladas</div>
        <div class="kpi-accent" style="background:#dc2626"></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon" style="background:#dcfce7;color:#15803d"><i class="bi bi-cash-stack"></i></div>
        <div class="kpi-label">Cobrado S/</div>
        <div class="kpi-value monto-soles" style="font-size:17px">S/ <?= number_format($tot_soles,0) ?></div>
        <div class="kpi-sub">en soles</div>
        <div class="kpi-accent" style="background:#15803d"></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon" style="background:#dcfce7;color:#166534"><i class="bi bi-currency-dollar"></i></div>
        <div class="kpi-label">Cobrado $</div>
        <div class="kpi-value monto-dolares" style="font-size:17px">$ <?= number_format($tot_dolares,0) ?></div>
        <div class="kpi-sub">en dólares</div>
        <div class="kpi-accent" style="background:#166534"></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon" style="background:#dbeafe;color:#1e40af"><i class="bi bi-wallet2"></i></div>
        <div class="kpi-label">Pago a cuenta</div>
        <div class="kpi-value monto-soles" style="font-size:17px">S/ <?= number_format($tot_cuenta,0) ?></div>
        <div class="kpi-sub">anticipos</div>
        <div class="kpi-accent" style="background:#1e40af"></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon" style="background:#f3e8ff;color:#7c3aed"><i class="bi bi-receipt-cutoff"></i></div>
        <div class="kpi-label">Pago saldo</div>
        <div class="kpi-value" style="color:#7c3aed;font-size:17px">S/ <?= number_format($tot_saldo,0) ?></div>
        <div class="kpi-sub">saldos cobrados</div>
        <div class="kpi-accent" style="background:#7c3aed"></div>
    </div>

</div>

<!-- ════ FILTROS ════ -->
<div class="filter-bar">
    <div class="filter-group">
        <label><i class="bi bi-calendar me-1"></i>Desde</label>
        <input type="date" id="search_date_from" value="<?= htmlspecialchars($search_from) ?>">
    </div>
    <div class="filter-group">
        <label><i class="bi bi-calendar me-1"></i>Hasta</label>
        <input type="date" id="search_date_to" value="<?= htmlspecialchars($search_to) ?>">
    </div>
    <div class="filter-group">
        <label><i class="bi bi-funnel me-1"></i>Estado</label>
        <select id="filter_estado">
            <option value="">Todos</option>
            <option value="pendiente" <?= $search_est==='pendiente'?'selected':'' ?>>Pendiente</option>
            <option value="pagado"    <?= $search_est==='pagado'?'selected':'' ?>>Pagado</option>
            <option value="cancelado" <?= $search_est==='cancelado'?'selected':'' ?>>Cancelado</option>
        </select>
    </div>
    <button class="btn-filter btn-primary-kb" id="btnFiltrar">
        <i class="bi bi-search"></i> Filtrar
    </button>
    <a href="?" class="btn-filter btn-outline-kb">
        <i class="bi bi-x-circle"></i> Limpiar
    </a>
</div>

<!-- ════ TABLA ════ -->
<div class="kb-card">
    <div class="kb-card-header">
        <div class="section-title">
            <span class="section-icon" style="background:#374151"><i class="bi bi-table"></i></span>
            Registro de Contabilidad
        </div>
        <span style="font-size:12px;color:var(--text-muted)"><?= $total_rows ?> registro(s)</span>
    </div>
    <div class="kb-card-body" style="padding:0 0 4px">
    <div style="overflow-x:auto">
    <table id="tablaContabilidad" class="table nowrap align-middle" style="width:100%">
        <thead>
            <tr>
                <th>#</th>
                <th>Cliente / Grupo</th>
                <th>Servicios</th>
                <th>Salida</th>
                <th>Retorno</th>
                <th>PAX</th>
                <th>Pagado S/</th>
                <th>Pagado $</th>
                <th>A cuenta</th>
                <th>Saldo</th>
                <th>Comisión</th>
                <th>IGV</th>
                <th>Detracción</th>
                <th>Boleta</th>
                <th>Encargado</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $chip_colors = [
            ['#dbeafe','#1e40af'],['#dcfce7','#15803d'],['#fef3c7','#92400e'],
            ['#f3e8ff','#6b21a8'],['#fee2e2','#b91c1c'],['#e0f2fe','#0369a1'],
        ];
        $i = 1;
        foreach ($datos as $row):
            $est     = $row['estado'] ?? 'pendiente';
            $saldo   = ($row['suma_precio_tours'] ?? 0) - $row['pagado_soles'] - ($row['pagado_dolares'] * 3.7); // estimado
            $saldo   = max(0, $saldo);

            // Parsear servicios
            $serv_chips = '';
            if (!empty($row['servicios_raw'])) {
                $parts = explode(';;', $row['servicios_raw']);
                foreach ($parts as $idx => $part) {
                    [$nombre, $fecha] = array_pad(explode('||', $part), 2, '');
                    [$bg, $col] = $chip_colors[$idx % count($chip_colors)];
                    $fecha_fmt = $fecha ? date('d/m/Y', strtotime($fecha)) : '';
                    $serv_chips .= "<span class='servicio-chip' style='background:{$bg};color:{$col}'>"
                        . "<i class='bi bi-compass' style='font-size:10px'></i> "
                        . htmlspecialchars($nombre)
                        . ($fecha_fmt ? " <span style='opacity:.7'>· {$fecha_fmt}</span>" : '')
                        . "</span>";
                }
            }
        ?>
        <tr>
            <td style="color:var(--text-muted);font-size:12px"><?= $i++ ?></td>

            <td>
                <?php
                $initials = '';
                if (!empty($row['primer_cliente'])) {
                    $parts = explode(' ', trim($row['primer_cliente']));
                    $initials = strtoupper(substr($parts[0]??'',0,1).(substr($parts[1]??'',0,1)));
                }
                $ac_colors = ['#1a56db','#16a34a','#d97706','#dc2626','#7c3aed','#0891b2'];
                $ac = $ac_colors[$i % count($ac_colors)];
                ?>
                <div style="display:flex;align-items:center;gap:8px">
                    <div style="width:30px;height:30px;border-radius:50%;background:<?= $ac ?>;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#fff;flex-shrink:0">
                        <?= $initials ?>
                    </div>
                    <div>
                        <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($row['primer_cliente'] ?? '—') ?></div>
                        <div style="font-size:11px;color:var(--text-muted)">
                            <i class="bi bi-collection"></i> <?= htmlspecialchars($row['nombre_grupo'] ?? '—') ?>
                            <?php if ($row['id_operaciones']): ?>
                            &nbsp;· <span style="font-family:'DM Mono',monospace">Op.#<?= $row['id_operaciones'] ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </td>

            <td style="min-width:250px">

<?php
if (!empty($row['servicios_raw'])) {

    $parts = explode(';;', $row['servicios_raw']);

    foreach ($parts as $idx => $part) {

        [$nombre, $fecha] = array_pad(explode('||', $part), 2, '');

        $colorClass = ($idx % 2 == 0)
            ? 'tour-blue'
            : 'tour-green';
?>

        <div class="tour-chip <?= $colorClass ?>">

            <div class="tour-name">
                <?= htmlspecialchars($nombre) ?>
            </div>

            <div class="tour-date">
                📅
                <?= $fecha ? date('d/m/Y', strtotime($fecha)) : '-' ?>
            </div>

        </div>

<?php
    }
} else {
    echo '<span style="color:var(--text-muted)">—</span>';
}
?>

</td>

            <td style="font-family:'DM Mono',monospace;font-size:12px;white-space:nowrap">
                <?= $row['primera_salida'] ? date('d/m/Y',strtotime($row['primera_salida'])) : '—' ?>
            </td>

            <td style="font-family:'DM Mono',monospace;font-size:12px;white-space:nowrap">
                <?= $row['ultimo_retorno'] ? date('d/m/Y',strtotime($row['ultimo_retorno'])) : '—' ?>
            </td>

            <td style="text-align:center;font-weight:600"><?= $row['pasajeros'] ?? 0 ?></td>

            <td class="text-end">
                <span class="monto-val monto-soles">S/ <?= number_format($row['pagado_soles'],2) ?></span>
            </td>

            <td class="text-end">
                <span class="monto-val monto-dolares">$ <?= number_format($row['pagado_dolares'],2) ?></span>
            </td>

            <td class="text-end">
                <span class="monto-val" style="color:var(--text-muted)">S/ <?= number_format($row['pago_cuenta'],2) ?></span>
            </td>

            <td class="text-end">
                <span class="monto-val" style="color:<?= $row['pago_saldo']>0?'#7c3aed':'var(--text-muted)' ?>">
                    S/ <?= number_format($row['pago_saldo'],2) ?>
                </span>
            </td>

            <td class="text-end">
                <?php echo $row['comision'] ? '<span class="monto-val monto-soles">S/ '.number_format($row['comision'],2).'</span>' : '<span style="color:var(--text-muted)">—</span>'; ?>
            </td>

            <td class="text-end">
                <?php echo $row['igv'] ? '<span class="monto-val" style="color:var(--text-muted)">S/ '.number_format($row['igv'],2).'</span>' : '<span style="color:var(--text-muted)">—</span>'; ?>
            </td>

            <td class="text-end">
                <?php echo $row['detraccion'] ? '<span class="monto-val" style="color:#d97706">S/ '.number_format($row['detraccion'],2).'</span>' : '<span style="color:var(--text-muted)">—</span>'; ?>
            </td>

            <td style="font-family:'DM Mono',monospace;font-size:12px;white-space:nowrap">
                <?php
                $boleta = $row['nro_boleta_total'] ?? ($row['nro_boleta_cuenta'] ?? '');
                echo $boleta ? htmlspecialchars($boleta) : '<span style="color:var(--text-muted)">Sin boleta</span>';
                ?>
            </td>

            <td style="font-size:12px"><?= htmlspecialchars($row['encargado'] ?? '—') ?></td>

            <td>
                <span class="estado-badge estado-<?= $est ?>">
                    <i class="bi bi-circle-fill" style="font-size:6px"></i>
                    <?= ucfirst($est) ?>
                </span>
                <?php if ($row['op_estado'] && $row['op_estado'] !== $est): ?>
                <div style="margin-top:3px">
                    <span class="estado-badge estado-<?= $row['op_estado'] ?>" style="font-size:10px;padding:2px 7px">Op: <?= ucfirst($row['op_estado']) ?></span>
                </div>
                <?php endif; ?>
            </td>

            <td>
                <div style="display:flex;gap:4px;justify-content:center">
                    <?php if (!empty($row['id_contabilidad'])): ?>
                        <a href="ver.php?id=<?= $row['id_contabilidad'] ?>" class="action-btn" title="Ver detalle"><i class="bi bi-eye"></i></a>
                        <a href="editar.php?id=<?= $row['id_contabilidad'] ?>" class="action-btn warning" title="Editar"><i class="bi bi-pencil"></i></a>
                    <?php elseif (!empty($row['id_operaciones'])): ?>
                        <a href="nuevo.php?id_operaciones=<?= $row['id_operaciones'] ?>" class="action-btn success" title="Agregar contabilidad"><i class="bi bi-plus-lg"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($row['id_grupo'])): ?>
                        <a href="../grupos/ver.php?id_grupo=<?= $row['id_grupo'] ?>" class="action-btn" title="Ver grupo"><i class="bi bi-people"></i></a>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>

        <!-- TOTALES EN TFOOT -->
        <tfoot>
            <tr style="background:var(--surface-2);font-weight:700;font-size:12px">
                <td colspan="6" style="padding:10px 14px;color:var(--text-muted)">TOTALES (<?= $total_rows ?> registros)</td>
                <td class="text-end" style="padding:10px 14px">
                    <span class="monto-val monto-soles">S/ <?= number_format($tot_soles,2) ?></span>
                </td>
                <td class="text-end" style="padding:10px 14px">
                    <span class="monto-val monto-dolares">$ <?= number_format($tot_dolares,2) ?></span>
                </td>
                <td class="text-end" style="padding:10px 14px">
                    <span class="monto-val">S/ <?= number_format($tot_cuenta,2) ?></span>
                </td>
                <td class="text-end" style="padding:10px 14px">
                    <span class="monto-val" style="color:#7c3aed">S/ <?= number_format($tot_saldo,2) ?></span>
                </td>
                <td colspan="7"></td>
            </tr>
        </tfoot>
    </table>
    </div>
    </div>
</div>
</div>
</div><!-- /.main-content -->

<!-- ════ MODAL IMPORTAR ════ -->
<div class="modal fade" id="modalImportar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:var(--radius);border:1px solid var(--border)">
            <form action="importar_excel.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header-kb">
                    <div class="modal-title-kb">
                        <span class="section-icon" style="background:#16a34a"><i class="bi bi-file-earmark-arrow-down"></i></span>
                        Importar Operaciones desde Excel
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding:20px">
                    <label class="form-label-kb">Seleccionar archivo</label>
                    <input type="file" name="archivo_excel" class="form-control-kb" accept=".xlsx,.xls,.csv" required
                        style="display:block;padding:10px 12px">
                    <p style="font-size:12px;color:var(--text-muted);margin-top:10px">
                        <i class="bi bi-info-circle me-1"></i>
                        Formatos aceptados: .xlsx, .xls, .csv
                    </p>
                </div>
                <div class="modal-footer" style="padding:14px 20px;border-top:1px solid var(--border);display:flex;gap:8px;justify-content:flex-end">
                    <button type="button" class="btn-filter btn-outline-kb" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-filter btn-primary-kb">
                        <i class="bi bi-upload"></i> Importar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script>
$(document).ready(function() {

    $('#tablaContabilidad').DataTable({
        responsive: false,
        scrollX: true,
        dom: '<"d-flex align-items-center justify-content-between mb-3 px-3 pt-3"Bf>rtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: '<i class="bi bi-file-earmark-spreadsheet me-1"></i> Excel',
                className: 'btn btn-success btn-sm',
                exportOptions: { columns: ':not(:last-child)' }
            },
            {
                extend: 'pdfHtml5',
                text: '<i class="bi bi-file-earmark-pdf me-1"></i> PDF',
                className: 'btn btn-danger btn-sm',
                exportOptions: { columns: ':not(:last-child)' },
                orientation: 'landscape',
                pageSize: 'A3'
            }
        ],
        language: { url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/Spanish.json' },
        pageLength: 15,
        order: [[0, 'desc']],
        columnDefs: [
            { orderable: false, targets: [2, 16] },  // servicios y acciones
            { searchable: false, targets: [3,4,6,7,8,9,10,11,12] }
        ]
    });

    // Filtros
    $('#btnFiltrar').on('click', function() {
        var from  = $('#search_date_from').val();
        var to    = $('#search_date_to').val();
        var est   = $('#filter_estado').val();
        location.href = '?search_date_from='+from+'&search_date_to='+to+'&estado='+est;
    });

    // Enter en filtros
    $('#search_date_from, #search_date_to, #filter_estado').on('keydown', function(e) {
        if (e.key === 'Enter') $('#btnFiltrar').click();
    });

});
</script>
</body>
</html>