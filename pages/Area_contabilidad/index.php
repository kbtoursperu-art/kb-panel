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
$search_enc  = $_GET['encargado']        ?? '';

// ── CONSULTA PRINCIPAL ──
$where = "WHERE o.id_operaciones IS NOT NULL";

if (!empty($search_from) && !empty($search_to)) {
    $sf = mysqli_real_escape_string($conexion, $search_from);
    $st = mysqli_real_escape_string($conexion, $search_to);
    $where .= " AND od.fecha_salida BETWEEN '$sf' AND '$st'";
}

if (!empty($search_est)) {
    $se = mysqli_real_escape_string($conexion, $search_est);
    $where .= " AND c.estado='$se'";
}

if (!empty($search_enc)) {
    $se2 = mysqli_real_escape_string($conexion, $search_enc);
    $where .= " AND o.encargado LIKE '%$se2%'";
}

$query = "
SELECT
    g.id_grupo,
    g.nombre_grupo,
    g.estado AS estado_grupo,

    (SELECT CONCAT(dc2.nombre,' ',dc2.apellido)
     FROM clientes_grupo cg2
     JOIN datos_clientes dc2
        ON dc2.id_cliente=cg2.id_cliente
     WHERE cg2.id_grupo=g.id_grupo
     ORDER BY cg2.es_pagador DESC,cg2.id ASC
     LIMIT 1
    ) AS primer_cliente,

    (SELECT cg2.id_cliente
     FROM clientes_grupo cg2
     WHERE cg2.id_grupo=g.id_grupo
     ORDER BY cg2.es_pagador DESC,cg2.id ASC
     LIMIT 1
    ) AS primer_cliente_id,

    (SELECT COUNT(*)
     FROM clientes_grupo cgx
     WHERE cgx.id_grupo=g.id_grupo
    ) AS pasajeros,

    o.id_operaciones,
    o.encargado,
    o.fecha_reserva,
    o.op_estado,
    o.total_operacion,
    o.tipo_precio,
    o.observaciones,

    GROUP_CONCAT(
        DISTINCT CONCAT(
            IFNULL(s.nombre,'Sin servicio'),
            '||',
            IFNULL(od.fecha_salida,'')
        )
        ORDER BY od.fecha_salida
        SEPARATOR ';;'
    ) AS servicios_raw,

    MIN(od.fecha_salida)  AS primera_salida,
    MAX(od.fecha_retorno) AS ultimo_retorno,

   -- POR ESTO:
(SELECT IFNULL(SUM(CASE WHEN od2.tipo_moneda='Soles'   THEN od2.precio ELSE 0 END),0)
 FROM operaciones_detalle od2 WHERE od2.id_operaciones = o.id_operaciones) AS total_soles,

(SELECT IFNULL(SUM(CASE WHEN od2.tipo_moneda='Dólares' THEN od2.precio ELSE 0 END),0)
 FROM operaciones_detalle od2 WHERE od2.id_operaciones = o.id_operaciones) AS total_dolares,

    c.id_contabilidad,
    c.estado           AS estado_contabilidad,
    c.comision,
    c.igv,
    c.detraccion,
    c.nro_boleta_cuenta,
    c.nro_boleta_total,
    c.modalidad_recibo,
    c.fecha_registro,

   -- POR ESTO:
(SELECT IFNULL(SUM(p2.monto),0) FROM pagos p2 WHERE p2.id_operaciones=o.id_operaciones AND p2.tipo='cuenta'      AND p2.moneda='Soles')   AS cuenta_soles,
(SELECT IFNULL(SUM(p2.monto),0) FROM pagos p2 WHERE p2.id_operaciones=o.id_operaciones AND p2.tipo='cuenta'      AND p2.moneda='Dólares') AS cuenta_dolares,
(SELECT IFNULL(SUM(p2.monto),0) FROM pagos p2 WHERE p2.id_operaciones=o.id_operaciones AND p2.tipo!='reembolso'  AND p2.moneda='Soles')   AS pagado_soles,
(SELECT IFNULL(SUM(p2.monto),0) FROM pagos p2 WHERE p2.id_operaciones=o.id_operaciones AND p2.tipo!='reembolso'  AND p2.moneda='Dólares') AS pagado_dolares,
(SELECT IFNULL(SUM(p2.monto),0) FROM pagos p2 WHERE p2.id_operaciones=o.id_operaciones AND p2.tipo='reembolso'   AND p2.moneda='Soles')   AS reembolso_soles,
(SELECT IFNULL(SUM(p2.monto),0) FROM pagos p2 WHERE p2.id_operaciones=o.id_operaciones AND p2.tipo='reembolso'   AND p2.moneda='Dólares') AS reembolso_dolares

FROM grupos g

LEFT JOIN (
    SELECT
        o1.id_operaciones,
        o1.id_grupo,
        o1.encargado,
        o1.fecha_reserva,
        o1.estado AS op_estado,
        o1.total_operacion,
        o1.tipo_precio,
        o1.observaciones
    FROM operaciones o1
    INNER JOIN (
        SELECT id_grupo, MAX(id_operaciones) AS max_id
        FROM operaciones
        GROUP BY id_grupo
    ) o2 ON o1.id_operaciones = o2.max_id
) o ON o.id_grupo = g.id_grupo

LEFT JOIN operaciones_detalle od ON od.id_operaciones = o.id_operaciones
LEFT JOIN servicios s             ON s.id_servicio    = od.id_servicio
LEFT JOIN pagos p                 ON p.id_operaciones = o.id_operaciones
LEFT JOIN contabilidad c          ON c.id_operaciones = o.id_operaciones

$where

GROUP BY
    g.id_grupo,
    g.nombre_grupo,
    g.estado,
    o.id_operaciones,
    o.encargado,
    o.fecha_reserva,
    o.op_estado,
    o.total_operacion,
    o.tipo_precio,
    o.observaciones,
    c.id_contabilidad,
    c.estado,
    c.comision,
    c.igv,
    c.detraccion,
    c.nro_boleta_cuenta,
    c.nro_boleta_total,
    c.modalidad_recibo,
    c.fecha_registro

ORDER BY g.id_grupo ASC
";
$resultado = mysqli_query($conexion, $query);
if (!$resultado) die("Error SQL: " . mysqli_error($conexion));
$datos = mysqli_fetch_all($resultado, MYSQLI_ASSOC);

// ── TOTALES ──
$tot_soles      = 0;
$tot_dolares    = 0;
$tot_cuenta     = 0;
$tot_saldo      = 0;
$tot_reembolso  = 0;
$tot_comision   = 0;
$tot_igv        = 0;
$tot_detraccion = 0;

$cnt_pagado    = 0;
$cnt_pendiente = 0;
$cnt_cancelado = 0;


foreach ($datos as $row) {

    $pagado_soles_r    = (float)($row['pagado_soles']    ?? 0);
    $pagado_dolares_r  = (float)($row['pagado_dolares']  ?? 0);
    $total_soles_r     = (float)($row['total_soles']     ?? 0);
    $total_dolares_r   = (float)($row['total_dolares']   ?? 0);
    $reembolso_soles_r = (float)($row['reembolso_soles'] ?? 0);
    $cuenta_soles_r    = (float)($row['cuenta_soles']    ?? 0);

    $tot_soles      += $pagado_soles_r;
    $tot_dolares    += $pagado_dolares_r;
    $tot_cuenta     += $cuenta_soles_r;
    $tot_reembolso  += $reembolso_soles_r;
    $tot_comision   += (float)($row['comision']   ?? 0);
    $tot_igv        += (float)($row['igv']        ?? 0);
    $tot_detraccion += (float)($row['detraccion'] ?? 0);

    // Saldo pendiente por cobrar (soles)
    $saldo_fila = $total_soles_r - $pagado_soles_r + $reembolso_soles_r;
    $tot_saldo += $saldo_fila;

    // ← CORRECCIÓN CLAVE: leer estado_contabilidad
    $est_cont = $row['estado_contabilidad'] ?? 'pendiente';

    if ($est_cont === 'pagado')        $cnt_pagado++;
    elseif ($est_cont === 'cancelado') $cnt_cancelado++;
    else                               $cnt_pendiente++;
}

$total_rows = count($datos);

// ── Encargados únicos para filtro ──
$enc_query = mysqli_query($conexion,
    "SELECT DISTINCT encargado FROM operaciones
     WHERE encargado IS NOT NULL AND encargado != ''
     ORDER BY encargado ASC");
$encargados = [];
while ($enc = mysqli_fetch_assoc($enc_query)) $encargados[] = $enc['encargado'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contabilidad — KB Tours</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=DM+Mono:wght@400;500&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">

<style>
/* ─── TOKENS ─── */
:root {
    --brand:       #1a56db;
    --brand-light: #dbeafe;
    --brand-dark:  #1e40af;
    --surface:     #fff;
    --surface-2:   #f8fafc;
    --surface-3:   #f1f5f9;
    --border:      #e2e8f0;
    --text:        #0f172a;
    --text-muted:  #64748b;
    --success:     #16a34a;
    --warning:     #d97706;
    --danger:      #dc2626;
    --info:        #0891b2;
    --purple:      #7c3aed;
    --radius:      12px;
    --shadow:      0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.04);
    --shadow-md:   0 4px 12px rgba(0,0,0,.08), 0 12px 32px rgba(0,0,0,.06);
}
*, *::before, *::after { box-sizing: border-box; }
body {
    font-family: 'DM Sans', sans-serif;
    background: var(--surface-2);
    color: var(--text);
    font-size: 14px;
    line-height: 1.6;
    margin: 0;
}

/* ─── PAGE HEADER ─── */
.page-header {
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    padding: 16px 32px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 1px 0 var(--border);
}
.page-header h1 {
    font-family: 'Outfit', sans-serif;
    font-size: 20px;
    font-weight: 700;
    margin: 0;
    color: var(--text);
}
.page-header .subtitle {
    color: var(--text-muted);
    font-size: 13px;
    margin: 0;
}

.main-content {
    max-width: 1480px;
    margin: 0 auto;
    padding: 28px 24px 80px;
}

/* ─── CARDS ─── */
.kb-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    margin-bottom: 24px;
    overflow: hidden;
}

.section-header-colored {
    padding: 12px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: none;
}
.section-header-colored .sh-title {
    font-family: 'Outfit', sans-serif;
    font-size: 13px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .8px;
}
.section-header-colored .sh-sub {
    font-size: 11px;
    opacity: .85;
}
.sh-blue  { background: linear-gradient(135deg,#1a56db,#2563eb); color:#fff; }
.sh-green { background: linear-gradient(135deg,#16a34a,#22c55e); color:#fff; }
.sh-amber { background: linear-gradient(135deg,#d97706,#f59e0b); color:#fff; }
.sh-cyan  { background: linear-gradient(135deg,#0891b2,#06b6d4); color:#fff; }

/* ─── KPI GRID ─── */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(155px, 1fr));
    gap: 14px;
    margin-bottom: 24px;
}
.kpi-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 16px 18px;
    box-shadow: var(--shadow);
    position: relative;
    overflow: hidden;
    transition: transform .15s, box-shadow .15s;
    cursor: default;
}
.kpi-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
.kpi-icon {
    width: 36px;
    height: 36px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
    margin-bottom: 10px;
}
.kpi-label {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .7px;
    color: var(--text-muted);
    margin-bottom: 4px;
}
.kpi-value {
    font-family: 'Outfit', sans-serif;
    font-size: 21px;
    font-weight: 700;
    line-height: 1;
}
.kpi-sub { font-size: 11px; color: var(--text-muted); margin-top: 3px; }
.kpi-accent {
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 3px;
    border-radius: 0 0 2px 2px;
}

/* ─── FILTROS ─── */
.filter-group { display: flex; flex-direction: column; gap: 5px; }
.filter-group label {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .6px;
    color: var(--text-muted);
}
.kb-input, .kb-select {
    background: var(--surface-2);
    border: 1px solid var(--border);
    border-radius: 8px;
    padding: 7px 12px;
    font-size: 13px;
    font-family: 'DM Sans', sans-serif;
    color: var(--text);
    outline: none;
    transition: border-color .15s, box-shadow .15s;
    min-width: 140px;
}
.kb-input:focus, .kb-select:focus {
    border-color: var(--brand);
    box-shadow: 0 0 0 3px rgba(26,86,219,.1);
}
.btn-kb {
    padding: 8px 18px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    font-family: 'DM Sans', sans-serif;
    cursor: pointer;
    border: none;
    transition: all .15s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    text-decoration: none;
    white-space: nowrap;
}
.btn-primary-kb { background: var(--brand); color: #fff; }
.btn-primary-kb:hover { background: var(--brand-dark); }
.btn-outline-kb { background: transparent; color: var(--text-muted); border: 1px solid var(--border); }
.btn-outline-kb:hover { background: var(--surface-2); color: var(--text); }
.btn-success-kb { background: var(--success); color: #fff; }
.btn-success-kb:hover { background: #15803d; }

/* ─── TABLA ─── */
table.dataTable thead th {
    background: var(--surface-2) !important;
    font-size: 10px !important;
    font-weight: 700 !important;
    text-transform: uppercase;
    letter-spacing: .7px;
    color: var(--text-muted) !important;
    border-bottom: 2px solid var(--border) !important;
    padding: 10px 14px !important;
    white-space: nowrap;
}
table.dataTable tbody td {
    padding: 11px 14px !important;
    vertical-align: middle !important;
    border-bottom: 1px solid var(--border) !important;
    font-size: 13px !important;
}
table.dataTable tbody tr:hover { background: var(--surface-2) !important; }
table.dataTable { border-collapse: separate !important; border-spacing: 0 !important; }
table.dataTable tfoot td {
    padding: 10px 14px !important;
    font-weight: 700;
    font-size: 12px;
    background: var(--surface-3);
    border-top: 2px solid var(--border) !important;
}

.dataTables_wrapper .dataTables_filter input {
    border: 1px solid var(--border); border-radius: 8px;
    padding: 6px 12px; font-family: 'DM Sans', sans-serif;
    font-size: 13px; background: var(--surface-2);
    outline: none; color: var(--text); transition: border-color .15s;
}
.dataTables_wrapper .dataTables_filter input:focus { border-color: var(--brand); }
.dataTables_wrapper .dataTables_length select {
    border: 1px solid var(--border); border-radius: 8px;
    padding: 5px 10px; font-family: 'DM Sans', sans-serif;
    font-size: 13px; background: var(--surface-2);
}
.dataTables_wrapper .dt-buttons .btn {
    font-family: 'DM Sans', sans-serif !important;
    font-size: 12px !important;
    font-weight: 600 !important;
    border-radius: 8px !important;
    padding: 7px 14px !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: var(--brand) !important; color: #fff !important;
    border-radius: 6px !important; border: none !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background: var(--surface-2) !important; color: var(--text) !important;
    border-radius: 6px !important; border: none !important;
}
.dataTables_wrapper .dataTables_info { font-size: 12px; color: var(--text-muted); }

/* ─── TOUR CHIPS ─── */
.tour-chip {
    border-radius: 8px;
    padding: 7px 11px;
    margin-bottom: 5px;
    border-left: 3px solid;
    display: block;
}
.tour-chip:last-child { margin-bottom: 0; }
.tour-chip.tc-blue   { background:#dbeafe; border-left-color:#2563eb; }
.tour-chip.tc-green  { background:#dcfce7; border-left-color:#16a34a; }
.tour-chip.tc-amber  { background:#fef3c7; border-left-color:#d97706; }
.tour-chip.tc-purple { background:#f3e8ff; border-left-color:#7c3aed; }
.tour-chip.tc-red    { background:#fee2e2; border-left-color:#dc2626; }
.tour-chip.tc-cyan   { background:#e0f2fe; border-left-color:#0891b2; }
.tour-name { font-size:12px; font-weight:700; text-transform:uppercase; margin-bottom:2px; line-height:1.3; }
.tc-blue .tour-name   { color:#1d4ed8; }
.tc-green .tour-name  { color:#166534; }
.tc-amber .tour-name  { color:#92400e; }
.tc-purple .tour-name { color:#5b21b6; }
.tc-red .tour-name    { color:#991b1b; }
.tc-cyan .tour-name   { color:#0e7490; }
.tour-date { font-size:11px; color:var(--text-muted); font-family:'DM Mono',monospace; }

/* ─── BADGES ─── */
.estado-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .5px;
    white-space: nowrap;
}
.estado-pagado    { background:#dcfce7; color:#15803d; }
.estado-pendiente { background:#fef9c3; color:#a16207; }
.estado-cancelado { background:#fee2e2; color:#b91c1c; }
.estado-confirmado{ background:#dcfce7; color:#15803d; }
.estado-parcial   { background:#e0f2fe; color:#0369a1; }
/* fallback para estado desconocido */
.estado-          { background:#f1f5f9; color:#64748b; }

/* ─── MONEY ─── */
.monto-val     { font-family:'DM Mono',monospace; font-weight:500; font-size:12px; }
.monto-soles   { color:#1e40af; }
.monto-dolares { color:#166534; }
.monto-purple  { color:var(--purple); }
.monto-muted   { color:var(--text-muted); }

/* ─── AVATAR ─── */
.avatar-circle {
    width:32px; height:32px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:10px; font-weight:700; color:#fff; flex-shrink:0;
}

/* ─── GRUPO BADGE ─── */
.grupo-badge {
    display:inline-block;
    background:var(--brand-light);
    color:var(--brand-dark);
    border-radius:20px;
    padding:2px 10px;
    font-size:11px;
    font-weight:600;
    white-space:nowrap;
}

/* ─── ACTION BTNS ─── */
.action-btn {
    display:inline-flex; align-items:center; justify-content:center;
    width:30px; height:30px; border-radius:7px;
    border:1px solid var(--border);
    background:var(--surface-2); color:var(--text);
    font-size:13px; text-decoration:none;
    transition:all .15s; cursor:pointer;
}
.action-btn:hover              { background:var(--brand);   color:#fff; border-color:var(--brand); }
.action-btn.btn-danger:hover   { background:#dc2626; color:#fff; border-color:#dc2626; }
.action-btn.btn-success:hover  { background:#16a34a; color:#fff; border-color:#16a34a; }
.action-btn.btn-warning:hover  { background:#d97706; color:#fff; border-color:#d97706; }

/* ─── MODAL ─── */
.modal-header-kb {
    background:var(--surface-2);
    border-bottom:1px solid var(--border);
    padding:16px 20px;
}
.modal-title-kb {
    font-family:'Outfit',sans-serif;
    font-size:15px; font-weight:700;
    display:flex; align-items:center; gap:8px;
}
.section-icon {
    width:28px; height:28px; border-radius:7px;
    display:flex; align-items:center; justify-content:center;
    font-size:14px; color:#fff;
}
.form-label-kb {
    font-size:10px; font-weight:700; text-transform:uppercase;
    letter-spacing:.6px; color:var(--text-muted);
    margin-bottom:5px; display:block;
}
.form-control-kb {
    background:var(--surface-2); border:1px solid var(--border);
    border-radius:8px; padding:8px 12px;
    font-size:13px; font-family:'DM Sans',sans-serif;
    width:100%; outline:none; color:var(--text);
    transition:border-color .15s;
}
.form-control-kb:focus { border-color:var(--brand); }

@media (max-width:768px) {
    .page-header { padding:14px 16px; }
    .main-content { padding:14px 12px 60px; }
    .kpi-grid { grid-template-columns:repeat(2,1fr); }
}
</style>
</head>
<body>
<?php include './../sidebar.php'; ?>
<div class="kb-content">

<!-- ═══════════════════════════════════
     PAGE HEADER
════════════════════════════════════ -->
<div class="page-header">
    <div>
        <h1><i class="bi bi-journal-text text-primary me-2"></i>Contabilidad</h1>
        <p class="subtitle">Gestión de pagos, boletas y estados por operación · KB Tours</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <button class="btn-kb btn-outline-kb" data-bs-toggle="modal" data-bs-target="#modalImportar">
            <i class="bi bi-file-earmark-arrow-down"></i> Importar
        </button>
        <button class="btn-kb btn-outline-kb" onclick="window.print()">
            <i class="bi bi-printer"></i> Imprimir
        </button>
    </div>
</div>

<div class="main-content">

<!-- ═══════════════════════════════════
     KPIs
════════════════════════════════════ -->
<div class="kpi-grid">

    <div class="kpi-card">
        <div class="kpi-icon" style="background:#dbeafe;color:#1a56db"><i class="bi bi-clipboard2-data-fill"></i></div>
        <div class="kpi-label">Total Registros</div>
        <div class="kpi-value" style="color:#1a56db"><?= number_format($total_rows) ?></div>
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
        <div class="kpi-icon" style="background:#fef9c3;color:#a16207"><i class="bi bi-hourglass-split"></i></div>
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
        <div class="kpi-value monto-soles" style="font-size:16px">S/ <?= number_format($tot_soles, 0) ?></div>
        <div class="kpi-sub">en soles</div>
        <div class="kpi-accent" style="background:#15803d"></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon" style="background:#d1fae5;color:#166534"><i class="bi bi-currency-dollar"></i></div>
        <div class="kpi-label">Cobrado $</div>
        <div class="kpi-value monto-dolares" style="font-size:16px">$ <?= number_format($tot_dolares, 0) ?></div>
        <div class="kpi-sub">en dólares</div>
        <div class="kpi-accent" style="background:#166534"></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon" style="background:#dbeafe;color:#1e40af"><i class="bi bi-wallet2"></i></div>
        <div class="kpi-label">A Cuenta</div>
        <div class="kpi-value monto-soles" style="font-size:16px">S/ <?= number_format($tot_cuenta, 0) ?></div>
        <div class="kpi-sub">anticipos S/</div>
        <div class="kpi-accent" style="background:#1e40af"></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon" style="background:#f3e8ff;color:#7c3aed"><i class="bi bi-receipt-cutoff"></i></div>
        <div class="kpi-label">Saldo Pend.</div>
        <div class="kpi-value" style="font-size:16px;color:<?= $tot_saldo > 0 ? '#dc2626' : '#16a34a' ?>">
            S/ <?= number_format($tot_saldo, 0) ?>
        </div>
        <div class="kpi-sub">por cobrar</div>
        <div class="kpi-accent" style="background:#7c3aed"></div>
    </div>

</div>

<!-- ═══════════════════════════════════
     FILTROS
════════════════════════════════════ -->
<div class="kb-card" style="margin-bottom:20px">
    <div class="section-header-colored sh-blue">
        <div>
            <div class="sh-title"><i class="bi bi-funnel-fill me-1"></i> Filtros de búsqueda</div>
            <div class="sh-sub">Filtra por fecha, estado o encargado</div>
        </div>
    </div>
    <div style="padding:16px 20px;display:flex;align-items:flex-end;gap:12px;flex-wrap:wrap">
        <div class="filter-group">
            <label><i class="bi bi-calendar me-1"></i>Desde</label>
            <input type="date" class="kb-input" id="search_date_from" value="<?= htmlspecialchars($search_from) ?>">
        </div>
        <div class="filter-group">
            <label><i class="bi bi-calendar me-1"></i>Hasta</label>
            <input type="date" class="kb-input" id="search_date_to" value="<?= htmlspecialchars($search_to) ?>">
        </div>
        <div class="filter-group">
            <label><i class="bi bi-circle-half me-1"></i>Estado Contabilidad</label>
            <select class="kb-select" id="filter_estado">
                <option value="">Todos los estados</option>
                <option value="pendiente" <?= $search_est==='pendiente'?'selected':'' ?>>⏳ Pendiente</option>
                <option value="pagado"    <?= $search_est==='pagado'?'selected':'' ?>>✅ Pagado</option>
                <option value="cancelado" <?= $search_est==='cancelado'?'selected':'' ?>>❌ Cancelado</option>
            </select>
        </div>
        <div class="filter-group">
            <label><i class="bi bi-person me-1"></i>Encargado</label>
            <select class="kb-select" id="filter_encargado">
                <option value="">Todos</option>
                <?php foreach ($encargados as $enc): ?>
                <option value="<?= htmlspecialchars($enc) ?>" <?= $search_enc===$enc?'selected':'' ?>>
                    <?= htmlspecialchars($enc) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button class="btn-kb btn-primary-kb" id="btnFiltrar">
            <i class="bi bi-search"></i> Buscar
        </button>
        <a href="?" class="btn-kb btn-outline-kb">
            <i class="bi bi-x-circle"></i> Limpiar
        </a>
    </div>
</div>

<!-- ═══════════════════════════════════
     TABLA PRINCIPAL
════════════════════════════════════ -->
<div class="kb-card">
    <div class="section-header-colored sh-green">
        <div>
            <div class="sh-title"><i class="bi bi-table me-1"></i> Registro de Contabilidad</div>
            <div class="sh-sub"><?= $total_rows ?> registro(s) encontrado(s)</div>
        </div>
        <div class="d-flex gap-2">
            <span style="background:rgba(255,255,255,.2);padding:4px 12px;border-radius:20px;font-size:11px;font-weight:600;color:#fff">
                S/ <?= number_format($tot_soles,2) ?> · $ <?= number_format($tot_dolares,2) ?>
            </span>
        </div>
    </div>

    <div style="overflow-x:auto">
        <table id="tablaContabilidad" class="table nowrap align-middle" style="width:100%">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cliente / Grupo</th>
                    <th>Tours Programados</th>
                    <th>Retorno</th>
                    <th>PAX</th>
                    <th>Total Tour</th>
                    <th>A Cuenta S/</th>
                    <th>Saldo Cobrar</th>
                    <th>Comisión</th>
                    <th>IGV</th>
                    <th>Detracción</th>
                    <th>Boleta</th>
                    <th>Modalidad</th>
                    <th>Encargado</th>
                    <th>Est. Contabilidad</th>
                    <th>Est. Operación</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $tour_colors   = ['tc-blue','tc-green','tc-amber','tc-purple','tc-red','tc-cyan'];
            $avatar_colors = ['#1a56db','#16a34a','#d97706','#dc2626','#7c3aed','#0891b2','#db6b1a','#1adbae'];
            $i = 1;

            foreach ($datos as $row):

                // ── CORRECCIÓN: leer los campos correctos ──
                $est_cont = $row['estado_contabilidad'] ?? 'pendiente';   // de tabla contabilidad
                $est_op   = $row['op_estado']           ?? 'pendiente';   // de tabla operaciones

                // Normalizar para el CSS (si viene null o vacío → pendiente)
                if (empty(trim($est_cont))) $est_cont = 'pendiente';
                if (empty(trim($est_op)))   $est_op   = 'pendiente';

                $total_soles_r     = (float)($row['total_soles']     ?? 0);
                $total_dolares_r   = (float)($row['total_dolares']   ?? 0);
                $cuenta_soles_r    = (float)($row['cuenta_soles']    ?? 0);
                $cuenta_dolares_r  = (float)($row['cuenta_dolares']  ?? 0);
                $pagado_soles_r    = (float)($row['pagado_soles']    ?? 0);
                $pagado_dolares_r  = (float)($row['pagado_dolares']  ?? 0);
                $reembolso_soles_r = (float)($row['reembolso_soles'] ?? 0);
                $reembolso_dol_r   = (float)($row['reembolso_dolares']?? 0);

                // Saldo = total - pagado + reembolso
                $saldo_soles_r   = $total_soles_r   - $pagado_soles_r   + $reembolso_soles_r;
                $saldo_dolares_r = $total_dolares_r  - $pagado_dolares_r + $reembolso_dol_r;

                // Avatar
                $initials = '';
                if (!empty($row['primer_cliente'])) {
                    $pts = explode(' ', trim($row['primer_cliente']));
                    $initials = strtoupper(substr($pts[0]??'',0,1) . substr($pts[1]??'',0,1));
                }
                $av_color = $avatar_colors[$i % count($avatar_colors)];
            ?>
            <tr>

                <!-- # -->
                <td style="color:var(--text-muted);font-size:12px;font-family:'DM Mono',monospace"><?= $i++ ?></td>

                <!-- CLIENTE / GRUPO -->
                <td style="min-width:200px">
                    <div style="display:flex;align-items:center;gap:10px">
                        <div class="avatar-circle" style="background:<?= $av_color ?>">
                            <?= $initials ?: '?' ?>
                        </div>
                        <div>
                            <div style="font-weight:600;font-size:13px;line-height:1.3">
                                <?= htmlspecialchars($row['primer_cliente'] ?? '—') ?>
                            </div>
                            <div style="font-size:11px;color:var(--text-muted);margin-top:2px">
                                <span class="grupo-badge"><?= htmlspecialchars($row['nombre_grupo'] ?? '—') ?></span>
                                <?php if (!empty($row['id_operaciones'])): ?>
                                <span style="font-family:'DM Mono',monospace;font-size:10px;color:var(--text-muted);margin-left:4px">
                                    Op.#<?= $row['id_operaciones'] ?>
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </td>

                <!-- TOURS -->
                <td style="min-width:160px">
                    <?php
                    if (!empty($row['servicios_raw'])) {
                        $parts = explode(';;', $row['servicios_raw']);
                        foreach ($parts as $idx => $part) {
                            [$nombre, $fecha] = array_pad(explode('||', $part), 2, '');
                            if (empty(trim($nombre))) continue;
                            $cc = $tour_colors[$idx % count($tour_colors)];
                            $fecha_fmt = $fecha ? date('d/m/Y', strtotime($fecha)) : '-';
                    ?>
                        <div class="tour-chip <?= $cc ?>">
                            <div class="tour-name"><?= htmlspecialchars($nombre) ?></div>
                            <div class="tour-date">📅 <?= $fecha_fmt ?></div>
                        </div>
                    <?php
                        }
                    } else {
                        echo '<span style="color:var(--text-muted);font-size:12px">—</span>';
                    }
                    ?>
                </td>

                <!-- RETORNO -->
                <td style="font-family:'DM Mono',monospace;font-size:12px;white-space:nowrap">
                    <?= $row['ultimo_retorno'] ? date('d/m/Y', strtotime($row['ultimo_retorno'])) : '—' ?>
                </td>

                <!-- PAX -->
                <td style="text-align:center">
                    <span style="background:var(--brand-light);color:var(--brand-dark);border-radius:20px;padding:2px 10px;font-size:11px;font-weight:700">
                        <?= intval($row['pasajeros']) ?>
                    </span>
                </td>

             <!-- TOTAL TOUR -->
<td class="text-end" style="min-width:110px">
    <?php if ($total_soles_r > 0): ?>
        <span class="monto-val monto-soles">S/ <?= number_format($total_soles_r, 2) ?></span><br>
    <?php endif; ?>
    <?php if ($total_dolares_r > 0): ?>
        <span class="monto-val monto-dolares">$ <?= number_format($total_dolares_r, 2) ?></span>
    <?php endif; ?>
    <?php if ($total_soles_r == 0 && $total_dolares_r == 0): ?>
        <span style="color:var(--text-muted)">—</span>
    <?php endif; ?>
</td>

<!-- A CUENTA -->
<td class="text-end" style="min-width:110px">
    <?php if ($cuenta_soles_r > 0): ?>
        <span class="monto-val monto-muted">S/ <?= number_format($cuenta_soles_r, 2) ?></span><br>
    <?php endif; ?>
    <?php if ($cuenta_dolares_r > 0): ?>
        <span class="monto-val monto-muted">$ <?= number_format($cuenta_dolares_r, 2) ?></span>
    <?php endif; ?>
    <?php if ($cuenta_soles_r == 0 && $cuenta_dolares_r == 0): ?>
        <span style="color:var(--text-muted)">—</span>
    <?php endif; ?>
</td>

<!-- SALDO POR COBRAR -->
<td class="text-end" style="min-width:110px">
    <?php if ($total_soles_r > 0 || $saldo_soles_r != 0): ?>
        <span class="monto-val" style="color:<?= $saldo_soles_r <= 0 ? '#16a34a' : '#dc2626' ?>">
            S/ <?= number_format($saldo_soles_r, 2) ?>
        </span><br>
    <?php endif; ?>
    <?php if ($total_dolares_r > 0 || $saldo_dolares_r != 0): ?>
        <span class="monto-val" style="color:<?= $saldo_dolares_r <= 0 ? '#16a34a' : '#dc2626' ?>">
            $ <?= number_format($saldo_dolares_r, 2) ?>
        </span>
    <?php endif; ?>
    <?php if ($total_soles_r == 0 && $total_dolares_r == 0): ?>
        <span style="color:var(--text-muted)">—</span>
    <?php endif; ?>
</td>

                <!-- COMISIÓN -->
                <td class="text-end">
                    <?php if (!empty($row['comision']) && $row['comision'] > 0): ?>
                        <span class="monto-val monto-soles">S/ <?= number_format($row['comision'], 2) ?></span>
                    <?php else: ?>
                        <span style="color:var(--text-muted)">—</span>
                    <?php endif; ?>
                </td>

                <!-- IGV -->
                <td class="text-end">
                    <?php if (!empty($row['igv']) && $row['igv'] > 0): ?>
                        <span class="monto-val monto-muted">S/ <?= number_format($row['igv'], 2) ?></span>
                    <?php else: ?>
                        <span style="color:var(--text-muted)">—</span>
                    <?php endif; ?>
                </td>

                <!-- DETRACCIÓN -->
                <td class="text-end">
                    <?php if (!empty($row['detraccion']) && $row['detraccion'] > 0): ?>
                        <span class="monto-val" style="color:#d97706">S/ <?= number_format($row['detraccion'], 2) ?></span>
                    <?php else: ?>
                        <span style="color:var(--text-muted)">—</span>
                    <?php endif; ?>
                </td>

                <!-- BOLETA -->
                <td style="font-family:'DM Mono',monospace;font-size:12px;white-space:nowrap">
                    <?php
                    $boleta = $row['nro_boleta_total'] ?? ($row['nro_boleta_cuenta'] ?? '');
                    echo $boleta
                        ? htmlspecialchars($boleta)
                        : '<span style="color:var(--text-muted)">Sin boleta</span>';
                    ?>
                </td>

                <!-- MODALIDAD -->
                <td style="font-size:12px">
                    <?= htmlspecialchars($row['modalidad_recibo'] ?? '—') ?>
                </td>

                <!-- ENCARGADO -->
                <td style="font-size:12px;white-space:nowrap">
                    <?php if (!empty($row['encargado'])): ?>
                    <div style="display:flex;align-items:center;gap:6px">
                        <i class="bi bi-person-circle" style="color:var(--brand)"></i>
                        <?= htmlspecialchars($row['encargado']) ?>
                    </div>
                    <?php else: echo '—'; endif; ?>
                </td>

                <!-- ESTADO CONTABILIDAD ← CORREGIDO -->
                <td>
                    <?php
                    $icons_cont = [
                        'pagado'    => 'bi-check-circle-fill',
                        'pendiente' => 'bi-hourglass-split',
                        'cancelado' => 'bi-x-circle-fill',
                    ];
                    $icon_c = $icons_cont[$est_cont] ?? 'bi-circle';
                    ?>
                    <span class="estado-badge estado-<?= $est_cont ?>">
                        <i class="bi <?= $icon_c ?>" style="font-size:8px"></i>
                        <?= ucfirst($est_cont) ?>
                    </span>
                </td>

                <!-- ESTADO OPERACIÓN ← CORREGIDO -->
                <!-- ESTADO OPERACIÓN - calculado igual que en operaciones -->
<td>
<?php
$total_gen  = $total_soles_r + $total_dolares_r;
$pagado_gen = $pagado_soles_r + $pagado_dolares_r;

if ($total_gen <= 0) {
    echo '<span class="estado-badge estado-pendiente"><i class="bi bi-hourglass-split" style="font-size:8px"></i> Pendiente</span>';
} elseif ($pagado_gen >= $total_gen) {
    echo '<span class="estado-badge estado-confirmado"><i class="bi bi-check-circle-fill" style="font-size:8px"></i> Confirmado</span>';
} elseif ($pagado_gen > 0) {
    echo '<span class="estado-badge estado-parcial"><i class="bi bi-circle-half" style="font-size:8px"></i> Parcial</span>';
} else {
    echo '<span class="estado-badge estado-pendiente"><i class="bi bi-hourglass-split" style="font-size:8px"></i> Pendiente</span>';
}
?>
</td>

                <!-- ACCIONES -->
                <td>
                    <div style="display:flex;gap:4px;justify-content:center">
                        <?php if (!empty($row['id_contabilidad'])): ?>
                            <a href="ver.php?id=<?= $row['id_contabilidad'] ?>" class="action-btn" title="Ver detalle">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="editar.php?id=<?= $row['id_contabilidad'] ?>" class="action-btn btn-warning" title="Editar">
                                <i class="bi bi-pencil"></i>
                            </a>
                        <?php elseif (!empty($row['id_operaciones'])): ?>
                            <a href="nuevo.php?id_operaciones=<?= $row['id_operaciones'] ?>" class="action-btn btn-success" title="Agregar contabilidad">
                                <i class="bi bi-plus-lg"></i>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($row['id_grupo'])): ?>
                            <a href="../grupos/ver.php?id_grupo=<?= $row['id_grupo'] ?>" class="action-btn" title="Ver grupo">
                                <i class="bi bi-people"></i>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($row['id_operaciones'])): ?>
                            <a href="../operaciones/ver/index.php?id_grupo=<?= $row['id_grupo'] ?>" class="action-btn" title="Ver operación">
                                <i class="bi bi-clipboard2-check"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </td>

            </tr>
            <?php endforeach; ?>
            </tbody>

            <!-- TFOOT: 17 columnas exactas -->
            <tfoot>
            <tr>
                <!-- 1 # + 2 Cliente + 3 Tours + 4 Retorno + 5 PAX -->
                <td colspan="5" style="color:var(--text-muted);font-size:11px;text-transform:uppercase;letter-spacing:.6px">
                    <i class="bi bi-sigma me-1"></i> Totales · <?= $total_rows ?> registros
                </td>

                <!-- 6 Total Tour (suma soles) -->
                <td class="text-end">
                    <span class="monto-val monto-soles">S/ <?= number_format(array_sum(array_column($datos,'total_soles')), 2) ?></span>
                </td>

                <!-- 7 A Cuenta -->
                <td class="text-end">
                    <span class="monto-val monto-muted">S/ <?= number_format($tot_cuenta, 2) ?></span>
                </td>

                <!-- 8 Saldo -->
                <td class="text-end">
                    <span class="monto-val" style="color:<?= $tot_saldo > 0 ? '#dc2626' : '#16a34a' ?>">
                        S/ <?= number_format($tot_saldo, 2) ?>
                    </span>
                </td>

                <!-- 9 Comisión -->
                <td class="text-end">
                    <span class="monto-val">S/ <?= number_format($tot_comision, 2) ?></span>
                </td>

                <!-- 10 IGV -->
                <td class="text-end">
                    <span class="monto-val monto-muted">S/ <?= number_format($tot_igv, 2) ?></span>
                </td>

                <!-- 11 Detracción -->
                <td class="text-end">
                    <span class="monto-val" style="color:#d97706">S/ <?= number_format($tot_detraccion, 2) ?></span>
                </td>

                <!-- 12 Boleta · 13 Modalidad · 14 Encargado · 15 Est.Cont · 16 Est.Op · 17 Acciones -->
                <td colspan="6"></td>
            </tr>
            </tfoot>
        </table>
    </div><!-- /.overflow-x -->
</div><!-- /.kb-card tabla -->

</div><!-- /.main-content -->

<!-- ═══════════════════════════════════
     MODAL IMPORTAR
════════════════════════════════════ -->
<div class="modal fade" id="modalImportar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:var(--radius);border:1px solid var(--border)">
            <form action="importar_excel.php" method="POST" enctype="multipart/form-data">
                <div class="modal-header-kb">
                    <div class="modal-title-kb">
                        <span class="section-icon" style="background:#16a34a"><i class="bi bi-file-earmark-arrow-down"></i></span>
                        Importar desde Excel
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="padding:20px">
                    <label class="form-label-kb">Seleccionar archivo</label>
                    <input type="file" name="archivo_excel" class="form-control-kb" accept=".xlsx,.xls,.csv" required
                           style="display:block;padding:10px 12px">
                    <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:8px;
                                padding:12px;margin-top:12px;font-size:12px;color:var(--text-muted)">
                        <i class="bi bi-info-circle text-primary me-1"></i>
                        Formatos aceptados: <strong>.xlsx</strong>, <strong>.xls</strong>, <strong>.csv</strong><br>
                        Asegúrese que las columnas coincidan con la plantilla KB Tours.
                    </div>
                </div>
                <div class="modal-footer" style="padding:14px 20px;border-top:1px solid var(--border);
                                                  display:flex;gap:8px;justify-content:flex-end">
                    <button type="button" class="btn-kb btn-outline-kb" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-kb btn-success-kb">
                        <i class="bi bi-upload"></i> Importar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════
     SCRIPTS
════════════════════════════════════ -->
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
$(document).ready(function () {

    $('#tablaContabilidad').DataTable({
        responsive: false,
        scrollX: true,
        dom: '<"d-flex align-items-center justify-content-between mb-3 px-3 pt-3"Bf>rtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: '<i class="bi bi-file-earmark-spreadsheet me-1"></i> Excel',
                className: 'btn btn-success btn-sm',
                exportOptions: { columns: ':not(:last-child)' },
                title: 'Contabilidad KB Tours'
            },
            {
                extend: 'pdfHtml5',
                text: '<i class="bi bi-file-earmark-pdf me-1"></i> PDF',
                className: 'btn btn-danger btn-sm',
                exportOptions: { columns: ':not(:last-child)' },
                orientation: 'landscape',
                pageSize: 'A3',
                title: 'Contabilidad KB Tours'
            }
        ],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/Spanish.json'
        },
        pageLength: 15,
        order: [[0, 'desc']],
        columnDefs: [
            { orderable: false,  targets: [2, 16] },
            { searchable: false, targets: [3, 4, 6, 7, 8, 9, 10, 11, 12, 16] }
        ]
    });

    // Filtrar por GET
    $('#btnFiltrar').on('click', function () {
        const from = $('#search_date_from').val();
        const to   = $('#search_date_to').val();
        const est  = $('#filter_estado').val();
        const enc  = $('#filter_encargado').val();
        location.href = '?search_date_from=' + encodeURIComponent(from)
                      + '&search_date_to='   + encodeURIComponent(to)
                      + '&estado='           + encodeURIComponent(est)
                      + '&encargado='        + encodeURIComponent(enc);
    });

    // Enter en filtros
    $('#search_date_from, #search_date_to, #filter_estado, #filter_encargado').on('keydown', function (e) {
        if (e.key === 'Enter') $('#btnFiltrar').click();
    });

});
</script>
</body>
</html>