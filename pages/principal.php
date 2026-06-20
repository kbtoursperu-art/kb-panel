<?php
session_start();
include '../conexion.php';
date_default_timezone_set("America/Lima");
$hoy = date("Y-m-d");

/* ── OPERACIONES TOTAL ── */
$qTotalOp = mysqli_query($conexion,"SELECT COUNT(*) total FROM operaciones");
$totalOp = mysqli_fetch_assoc($qTotalOp)['total'] ?? 0;

/* ── PASAJEROS TOTAL ── */
$qPax = mysqli_query($conexion,"SELECT COUNT(DISTINCT id_cliente) total FROM clientes_grupo");
$totalPax = mysqli_fetch_assoc($qPax)['total'] ?? 0;

/* ── GRUPOS ACTIVOS ── */
$qGrupos = mysqli_query($conexion,"SELECT COUNT(*) total FROM grupos WHERE estado='abierto'");
$totalGrupos = mysqli_fetch_assoc($qGrupos)['total'] ?? 0;

/* ── TOURS HOY ── */
$qToursHoy = mysqli_query($conexion,"
    SELECT COUNT(DISTINCT od.id_detalle) total
    FROM operaciones_detalle od
    WHERE od.fecha_salida = CURDATE()
");
$toursHoy = mysqli_fetch_assoc($qToursHoy)['total'] ?? 0;

/* ── TOURS PROXIMOS 7 DIAS ── */
$qProx = mysqli_query($conexion,"
    SELECT COUNT(*) total FROM operaciones_detalle
    WHERE fecha_salida BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
");
$toursProx = mysqli_fetch_assoc($qProx)['total'] ?? 0;

/* ── ARTICULOS EN USO (ALMACEN) ── */
$qUso = mysqli_query($conexion,"
    SELECT IFNULL(SUM(cantidad),0) total FROM almacen_salidas
    WHERE estado NOT IN ('Devuelto')
");
$uso = mysqli_fetch_assoc($qUso)['total'] ?? 0;

/* ── PAGOS HOY (por moneda) ── */
$qPagosHoy = mysqli_query($conexion,"
    SELECT moneda, SUM(monto) total
    FROM pagos WHERE fecha = CURDATE()
    GROUP BY moneda
");
$pagosHoy = ['Soles'=>0,'Dólares'=>0];
while ($r = mysqli_fetch_assoc($qPagosHoy)) {
    $pagosHoy[$r['moneda']] = $r['total'];
}

/* ── PAGOS TOTAL HISTORICO ── */
$qCobradoS = mysqli_query($conexion,"SELECT IFNULL(SUM(monto),0) t FROM pagos WHERE moneda='Soles'   AND tipo != 'reembolso'");
$qCobradoD = mysqli_query($conexion,"SELECT IFNULL(SUM(monto),0) t FROM pagos WHERE moneda='Dólares' AND tipo != 'reembolso'");
$cobradoS = mysqli_fetch_assoc($qCobradoS)['t'] ?? 0;
$cobradoD = mysqli_fetch_assoc($qCobradoD)['t'] ?? 0;

/* ── OPERACIONES PENDIENTES ── */
$qPend = mysqli_query($conexion,"SELECT COUNT(*) total FROM operaciones WHERE estado='pendiente'");
$opPend = mysqli_fetch_assoc($qPend)['total'] ?? 0;

/* ── LISTA TOURS HOY ── */
$qListaHoy = mysqli_query($conexion,"
    SELECT
        IFNULL(s.nombre,'Sin servicio') as nombre_servicio,
        od.fecha_salida, od.fecha_retorno,
        od.modalidad_retorno, od.incluye_ingreso,
        od.tipo_moneda, od.precio,
        o.encargado, o.estado as estado_op,
        g.nombre_grupo,
        COUNT(DISTINCT cg.id_cliente) as pax
    FROM operaciones_detalle od
    JOIN operaciones o ON o.id_operaciones = od.id_operaciones
    LEFT JOIN servicios s ON s.id_servicio = od.id_servicio
    LEFT JOIN grupos g ON g.id_grupo = o.id_grupo
    LEFT JOIN clientes_grupo cg ON cg.id_grupo = o.id_grupo
    WHERE od.fecha_salida = CURDATE()
    GROUP BY od.id_detalle
    ORDER BY s.nombre
");

/* ── LISTA TOURS MANANA ── */
$qManana = mysqli_query($conexion,"
    SELECT
        IFNULL(s.nombre,'Sin servicio') as nombre_servicio,
        od.fecha_salida, od.fecha_retorno,
        od.modalidad_retorno,
        o.encargado,
        g.nombre_grupo,
        COUNT(DISTINCT cg.id_cliente) as pax
    FROM operaciones_detalle od
    JOIN operaciones o ON o.id_operaciones = od.id_operaciones
    LEFT JOIN servicios s ON s.id_servicio = od.id_servicio
    LEFT JOIN grupos g ON g.id_grupo = o.id_grupo
    LEFT JOIN clientes_grupo cg ON cg.id_grupo = o.id_grupo
    WHERE od.fecha_salida = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
    GROUP BY od.id_detalle
    ORDER BY s.nombre
");

/* ── TOURS POR MES (últimos 6) ── */
$qMes = mysqli_query($conexion,"
    SELECT DATE_FORMAT(fecha_salida,'%Y-%m') mes,
           DATE_FORMAT(fecha_salida,'%b %Y') mes_label,
           COUNT(*) total
    FROM operaciones_detalle
    WHERE fecha_salida >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY mes ORDER BY mes ASC
");
$meses=[]; $mesesTotal=[];
while ($m = mysqli_fetch_assoc($qMes)) {
    $meses[]      = $m['mes_label'];
    $mesesTotal[] = $m['total'];
}

/* ── TOURS POR SERVICIO ── */
$qTipo = mysqli_query($conexion,"
    SELECT IFNULL(s.nombre,'Sin asignar') as nombre_servicio, COUNT(*) total
    FROM operaciones_detalle od
    LEFT JOIN servicios s ON s.id_servicio = od.id_servicio
    GROUP BY od.id_servicio ORDER BY total DESC LIMIT 8
");
$tipoLabels=[]; $tipoTotal=[];
while ($t = mysqli_fetch_assoc($qTipo)) {
    $tipoLabels[] = $t['nombre_servicio'];
    $tipoTotal[]  = $t['total'];
}

/* ── OPERACIONES POR ENCARGADO ── */
$qEnc = mysqli_query($conexion,"
    SELECT IFNULL(NULLIF(encargado,''),'Sin encargado') as encargado,
           COUNT(*) total,
           SUM(CASE WHEN estado='confirmado' THEN 1 ELSE 0 END) confirmadas,
           SUM(CASE WHEN estado='pendiente'  THEN 1 ELSE 0 END) pendientes
    FROM operaciones
    GROUP BY encargado ORDER BY total DESC LIMIT 8
");

/* ── ULTIMAS 6 OPERACIONES ── */
$qUlt = mysqli_query($conexion,"
    SELECT
        o.id_operaciones, o.encargado, o.fecha_reserva, o.estado,
        o.total_operacion, o.tipo_precio,
        dc.nombre, dc.apellido,
        g.nombre_grupo
    FROM operaciones o
    JOIN datos_clientes dc ON dc.id_cliente = o.id_cliente
    LEFT JOIN grupos g ON g.id_grupo = o.id_grupo
    ORDER BY o.id_operaciones DESC
    LIMIT 6
");

/* ── CONTABILIDAD PENDIENTE ── */
$qContPend = mysqli_query($conexion,"
    SELECT COUNT(*) total FROM contabilidad WHERE estado='pendiente'
");
$contPend = mysqli_fetch_assoc($qContPend)['total'] ?? 0;

/* ── ULTIMAS OBSERVACIONES ── */
$qObs = mysqli_query($conexion,"
    SELECT o.id_operaciones, o.observaciones, o.fecha_reserva,
           dc.nombre, dc.apellido, g.nombre_grupo
    FROM operaciones o
    JOIN datos_clientes dc ON dc.id_cliente = o.id_cliente
    LEFT JOIN grupos g ON g.id_grupo = o.id_grupo
    WHERE o.observaciones IS NOT NULL AND o.observaciones <> ''
    ORDER BY o.id_operaciones DESC LIMIT 5
");

/* ── PAGOS RECIENTES ── */
$qPagosRec = mysqli_query($conexion,"
    SELECT p.*, dc.nombre, dc.apellido, o.id_operaciones
    FROM pagos p
    JOIN operaciones o ON o.id_operaciones = p.id_operaciones
    JOIN datos_clientes dc ON dc.id_cliente = o.id_cliente
    ORDER BY p.id_pago DESC LIMIT 6
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — KB Tours</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
:root {
    --brand:       #1a56db;
    --brand-light: #dbeafe;
    --brand-dark:  #1e40af;
    --surface:     #ffffff;
    --surface-2:   #f8fafc;
    --border:      #e2e8f0;
    --text:        #0f172a;
    --text-muted:  #64748b;
    --success:     #16a34a;
    --warning:     #d97706;
    --danger:      #dc2626;
    --info:        #0891b2;
    --radius:      12px;
    --shadow:      0 1px 3px rgba(0,0,0,.06), 0 4px 16px rgba(0,0,0,.04);
}
*, *::before, *::after { box-sizing: border-box; }
body {
    font-family: 'DM Sans', sans-serif;
    background: var(--surface-2);
    color: var(--text);
    font-size: 14px;
    line-height: 1.6;
}

/* ── PAGE HEADER ── */
.page-header {
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    padding: 18px 32px;
    display: flex; align-items: center; justify-content: space-between;
    position: flex; top: 0; z-index: 100;
}
.page-header h1 { font-size: 18px; font-weight: 700; margin: 0; }
.page-header .subtitle { color: var(--text-muted); font-size: 13px; margin: 0; }
.date-pill {
    display: flex; align-items: center; gap: 6px;
    background: var(--surface-2); border: 1px solid var(--border);
    padding: 6px 14px; border-radius: 20px;
    font-size: 13px; font-weight: 500; color: var(--text-muted);
}

/* ── LAYOUT ── */
.main-content { max-width: 1280px; margin: 0 auto; padding: 28px 24px 60px; }

/* ── CARDS ── */
.kb-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    margin-bottom: 24px;
    overflow: hidden;
}
.kb-card-header {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 20px;
    border-bottom: 1px solid var(--border);
    background: var(--surface-2);
}
.kb-card-header .section-title {
    display: flex; align-items: center; gap: 10px;
    font-size: 13px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .8px; color: var(--text);
}
.section-icon {
    width: 28px; height: 28px; border-radius: 7px;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; color: #fff;
}
.kb-card-body { padding: 20px; }

/* ── KPI GRID ── */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
    gap: 16px; margin-bottom: 28px;
}
.kpi-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 18px 20px;
    box-shadow: var(--shadow);
    position: relative; overflow: hidden;
    transition: transform .15s, box-shadow .15s;
}
.kpi-card:hover { transform: translateY(-2px); box-shadow: 0 4px 20px rgba(0,0,0,.08); }
.kpi-card .kpi-icon {
    width: 40px; height: 40px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; margin-bottom: 12px;
}
.kpi-card .kpi-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .6px; color: var(--text-muted); margin-bottom: 4px; }
.kpi-card .kpi-value { font-size: 26px; font-weight: 700; line-height: 1; }
.kpi-card .kpi-sub { font-size: 11px; color: var(--text-muted); margin-top: 4px; }
.kpi-accent { position: absolute; bottom: 0; left: 0; right: 0; height: 3px; }

/* ── CHARTS ROW ── */
.charts-row {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px; margin-bottom: 24px;
}
.chart-wrap { position: relative; height: 240px; }

/* ── TABLES ── */
.kb-table { width: 100%; border-collapse: separate; border-spacing: 0; }
.kb-table thead th {
    background: var(--surface-2); padding: 10px 14px;
    font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .6px; color: var(--text-muted);
    border-bottom: 1px solid var(--border); white-space: nowrap;
}
.kb-table tbody td {
    padding: 11px 14px; border-bottom: 1px solid var(--border); vertical-align: middle;
}
.kb-table tbody tr:last-child td { border-bottom: none; }
.kb-table tbody tr:hover { background: var(--surface-2); }

/* ── TWO COLUMN ── */
.two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 24px; }
.three-col { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 24px; }

/* ── BADGES ── */
.estado-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 10px; border-radius: 20px;
    font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px;
}
.estado-confirmado { background:#dcfce7; color:#15803d; }
.estado-pendiente  { background:#fef9c3; color:#a16207; }
.estado-cancelado  { background:#fee2e2; color:#b91c1c; }
.tipo-badge { display:inline-block; padding:2px 8px; border-radius:5px; font-size:11px; font-weight:600; text-transform:uppercase; }
.tipo-tour      { background:#dbeafe; color:#1e40af; }
.tipo-adicional { background:#fef3c7; color:#92400e; }
.tipo-cuenta    { background:#dcfce7; color:#15803d; }
.tipo-saldo     { background:#f3e8ff; color:#6b21a8; }
.tipo-reembolso { background:#fee2e2; color:#b91c1c; }

/* ── MONTO ── */
.monto-val   { font-family:'DM Mono',monospace; font-weight:500; font-size:13px; }
.monto-soles { color:#1e40af; }
.monto-dolares{ color:#166534; }

/* ── PROGRESS BAR ── */
.mini-bar { height:6px; border-radius:3px; background:var(--border); overflow:hidden; margin-top:4px; }
.mini-bar-fill { height:100%; border-radius:3px; background:var(--brand); transition:width .4s; }

/* ── TOUR ROW ── */
.service-dot {
    width: 8px; height: 8px; border-radius: 50%; display: inline-block; flex-shrink: 0;
}
.client-avatar {
    width: 30px; height: 30px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 700; color: #fff; flex-shrink: 0;
}

/* ── ALERT STRIP ── */
.alert-strip {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 16px; border-radius: 9px; margin-bottom: 20px;
    font-size: 13px; font-weight: 500;
}
.alert-strip.info    { background:#dbeafe; color:#1e40af; border:1px solid #bfdbfe; }
.alert-strip.warning { background:#fef9c3; color:#a16207; border:1px solid #fde68a; }

/* ── EMPTY ── */
.empty-state { text-align:center; padding:28px; color:var(--text-muted); }
.empty-state i { font-size:28px; opacity:.3; display:block; margin-bottom:6px; }

@media (max-width: 900px) {
    .charts-row { grid-template-columns: 1fr; }
    .two-col, .three-col { grid-template-columns: 1fr; }
    .kpi-grid { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 640px) {
    .page-header { padding: 14px 16px; }
    .main-content { padding: 14px 12px 40px; }
    .kpi-grid { grid-template-columns: 1fr 1fr; }
}
</style>
</head>
<body>
<?php include 'sidebar.php'; ?>

<!-- ════ HEADER ════ -->

<div class="kb-content">
<div class="page-header">
    <div>
        <h1><i class="bi bi-speedometer2 text-primary me-2"></i>Dashboard General</h1>
        <p class="subtitle">Resumen operativo en tiempo real</p>
    </div>
    <div class="date-pill">
        <i class="bi bi-calendar3"></i>
        <?= date('l, d \d\e F Y') ?>
    </div>
</div>

<div class="main-content">

<!-- ════ ALERTAS ════ -->
<?php if ($opPend > 0): ?>
<div class="alert-strip warning">
    <i class="bi bi-exclamation-triangle-fill"></i>
    Hay <strong><?= $opPend ?></strong> operación(es) en estado <strong>pendiente</strong> sin confirmar.
</div>
<?php endif; ?>
<?php if ($contPend > 0): ?>
<div class="alert-strip info">
    <i class="bi bi-file-earmark-text"></i>
    <strong><?= $contPend ?></strong> registro(s) de contabilidad pendiente(s) de cierre.
</div>
<?php endif; ?>
<?php if ($toursHoy > 0): ?>
<div class="alert-strip info">
    <i class="bi bi-compass-fill"></i>
    <strong><?= $toursHoy ?></strong> tour(s) con salida <strong>hoy</strong>.
    &nbsp;·&nbsp; <strong><?= $toursProx ?></strong> en los próximos 7 días.
</div>
<?php endif; ?>

<!-- ════ KPI CARDS ════ -->
<div class="kpi-grid">

    <div class="kpi-card">
        <div class="kpi-icon" style="background:#dbeafe;color:#1a56db"><i class="bi bi-clipboard2-data-fill"></i></div>
        <div class="kpi-label">Operaciones</div>
        <div class="kpi-value text-primary"><?= number_format($totalOp) ?></div>
        <div class="kpi-sub"><?= $opPend ?> pendientes</div>
        <div class="kpi-accent" style="background:#1a56db"></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon" style="background:#dcfce7;color:#16a34a"><i class="bi bi-people-fill"></i></div>
        <div class="kpi-label">Pasajeros</div>
        <div class="kpi-value" style="color:#16a34a"><?= number_format($totalPax) ?></div>
        <div class="kpi-sub">únicos registrados</div>
        <div class="kpi-accent" style="background:#16a34a"></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon" style="background:#f3e8ff;color:#7c3aed"><i class="bi bi-collection-fill"></i></div>
        <div class="kpi-label">Grupos activos</div>
        <div class="kpi-value" style="color:#7c3aed"><?= $totalGrupos ?></div>
        <div class="kpi-sub">estado abierto</div>
        <div class="kpi-accent" style="background:#7c3aed"></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon" style="background:#fef3c7;color:#d97706"><i class="bi bi-compass-fill"></i></div>
        <div class="kpi-label">Tours hoy</div>
        <div class="kpi-value" style="color:#d97706"><?= $toursHoy ?></div>
        <div class="kpi-sub"><?= $toursProx ?> próx. 7 días</div>
        <div class="kpi-accent" style="background:#d97706"></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon" style="background:#dcfce7;color:#16a34a"><i class="bi bi-cash-stack"></i></div>
        <div class="kpi-label">Cobrado soles</div>
        <div class="kpi-value monto-soles" style="font-size:20px">S/ <?= number_format($cobradoS,0) ?></div>
        <div class="kpi-sub">total histórico</div>
        <div class="kpi-accent" style="background:#16a34a"></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon" style="background:#dcfce7;color:#166534"><i class="bi bi-currency-dollar"></i></div>
        <div class="kpi-label">Cobrado dólares</div>
        <div class="kpi-value monto-dolares" style="font-size:20px">$ <?= number_format($cobradoD,0) ?></div>
        <div class="kpi-sub">total histórico</div>
        <div class="kpi-accent" style="background:#166534"></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon" style="background:#fee2e2;color:#dc2626"><i class="bi bi-box-seam-fill"></i></div>
        <div class="kpi-label">Almacén en uso</div>
        <div class="kpi-value" style="color:#dc2626"><?= $uso ?></div>
        <div class="kpi-sub">artículos sin devolver</div>
        <div class="kpi-accent" style="background:#dc2626"></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon" style="background:#fef9c3;color:#a16207"><i class="bi bi-file-earmark-check"></i></div>
        <div class="kpi-label">Conta. pendiente</div>
        <div class="kpi-value" style="color:#a16207"><?= $contPend ?></div>
        <div class="kpi-sub">sin cerrar</div>
        <div class="kpi-accent" style="background:#d97706"></div>
    </div>

</div>


<!-- ════ RESUMEN DEL DÍA + CHARTS ════ -->
<div class="charts-row">

    <!-- Chart tours por mes -->
    <div class="kb-card">
        <div class="kb-card-header">
            <div class="section-title">
                <span class="section-icon" style="background:#1a56db"><i class="bi bi-bar-chart-fill"></i></span>
                Tours por Mes
            </div>
            <span style="font-size:12px;color:var(--text-muted)">Últimos 6 meses</span>
        </div>
        <div class="kb-card-body">
            <div class="chart-wrap"><canvas id="chartMeses"></canvas></div>
        </div>
    </div>

    <!-- Resumen día + chart tipo -->
    <div style="display:flex;flex-direction:column;gap:20px">

        <!-- Cobros hoy -->
        <div class="kb-card" style="margin:0">
            <div class="kb-card-header">
                <div class="section-title">
                    <span class="section-icon" style="background:#16a34a"><i class="bi bi-calendar-check-fill"></i></span>
                    Cobros de Hoy
                </div>
            </div>
            <div class="kb-card-body" style="padding:14px 18px">
                <div class="d-flex flex-column gap-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span style="font-size:12px;color:var(--text-muted)"><i class="bi bi-cash me-1"></i>Soles</span>
                        <span class="monto-val monto-soles">S/ <?= number_format($pagosHoy['Soles'],2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span style="font-size:12px;color:var(--text-muted)"><i class="bi bi-currency-dollar me-1"></i>Dólares</span>
                        <span class="monto-val monto-dolares">$ <?= number_format($pagosHoy['Dólares'],2) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart tipo servicio -->
        <div class="kb-card" style="margin:0;flex:1">
            <div class="kb-card-header">
                <div class="section-title">
                    <span class="section-icon" style="background:#0891b2"><i class="bi bi-pie-chart-fill"></i></span>
                    Por Servicio
                </div>
            </div>
            <div class="kb-card-body">
                <div style="position:relative;height:160px"><canvas id="chartTipo"></canvas></div>
            </div>
        </div>

    </div>
</div>


<!-- ════ TOURS HOY ════ -->
<div class="kb-card">
    <div class="kb-card-header">
        <div class="section-title">
            <span class="section-icon" style="background:#d97706"><i class="bi bi-compass-fill"></i></span>
            Tours con Salida Hoy
        </div>
        <span class="estado-badge estado-<?= $toursHoy>0?'confirmado':'pendiente' ?>">
            <?= $toursHoy ?> tour(s)
        </span>
    </div>
    <?php
    $rowsHoy = [];
    while ($t = mysqli_fetch_assoc($qListaHoy)) $rowsHoy[] = $t;
    ?>
    <?php if (empty($rowsHoy)): ?>
        <div class="empty-state"><i class="bi bi-compass"></i>Sin tours hoy</div>
    <?php else: ?>
    <div style="overflow-x:auto">
    <table class="kb-table">
        <thead><tr>
            <th>Servicio</th><th>Grupo</th><th>Salida</th><th>Retorno</th>
            <th>Modalidad</th><th>Ingreso</th><th>PAX</th><th>Encargado</th><th>Estado</th>
        </tr></thead>
        <tbody>
        <?php foreach ($rowsHoy as $t): ?>
        <tr>
            <td><span style="font-weight:600"><?= htmlspecialchars($t['nombre_servicio']) ?></span></td>
            <td><span style="font-size:12px;color:var(--text-muted)"><?= htmlspecialchars($t['nombre_grupo'] ?? '—') ?></span></td>
            <td style="font-family:'DM Mono',monospace;font-size:12px"><?= $t['fecha_salida'] ? date('d/m/Y',strtotime($t['fecha_salida'])) : '—' ?></td>
            <td style="font-family:'DM Mono',monospace;font-size:12px"><?= $t['fecha_retorno'] ? date('d/m/Y',strtotime($t['fecha_retorno'])) : '—' ?></td>
            <td>
                <?php $im=['Carro'=>'bi-car-front','Tren'=>'bi-train-front','Caminata'=>'bi-person-walking'][$t['modalidad_retorno']] ?? 'bi-arrow-right'; ?>
                <i class="bi <?= $im ?> me-1"></i><?= htmlspecialchars($t['modalidad_retorno'] ?? '—') ?>
            </td>
            <td>
                <?php echo $t['incluye_ingreso']==='SI'
                    ? '<span class="estado-badge estado-confirmado">Sí</span>'
                    : '<span class="estado-badge estado-cancelado">No</span>'; ?>
            </td>
            <td><strong><?= $t['pax'] ?></strong></td>
            <td><?= htmlspecialchars($t['encargado'] ?? '—') ?></td>
            <td><span class="estado-badge estado-<?= $t['estado_op'] ?>"><?= ucfirst($t['estado_op']) ?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>


<!-- ════ TOURS MAÑANA ════ -->
<div class="kb-card">
    <div class="kb-card-header">
        <div class="section-title">
            <span class="section-icon" style="background:#0891b2"><i class="bi bi-calendar-event-fill"></i></span>
            Tours con Salida Mañana
        </div>
    </div>
    <?php
    $rowsManana = [];
    while ($m = mysqli_fetch_assoc($qManana)) $rowsManana[] = $m;
    ?>
    <?php if (empty($rowsManana)): ?>
        <div class="empty-state"><i class="bi bi-calendar-x"></i>Sin tours mañana</div>
    <?php else: ?>
    <div style="overflow-x:auto">
    <table class="kb-table">
        <thead><tr>
            <th>Servicio</th><th>Grupo</th><th>Salida</th><th>Retorno</th><th>Modalidad</th><th>PAX</th><th>Encargado</th>
        </tr></thead>
        <tbody>
        <?php foreach ($rowsManana as $m): ?>
        <tr>
            <td><span style="font-weight:600"><?= htmlspecialchars($m['nombre_servicio']) ?></span></td>
            <td><span style="font-size:12px;color:var(--text-muted)"><?= htmlspecialchars($m['nombre_grupo'] ?? '—') ?></span></td>
            <td style="font-family:'DM Mono',monospace;font-size:12px"><?= $m['fecha_salida'] ? date('d/m/Y',strtotime($m['fecha_salida'])) : '—' ?></td>
            <td style="font-family:'DM Mono',monospace;font-size:12px"><?= $m['fecha_retorno'] ? date('d/m/Y',strtotime($m['fecha_retorno'])) : '—' ?></td>
            <td><?= htmlspecialchars($m['modalidad_retorno'] ?? '—') ?></td>
            <td><strong><?= $m['pax'] ?></strong></td>
            <td><?= htmlspecialchars($m['encargado'] ?? '—') ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>


<!-- ════ DOS COLUMNAS: ENCARGADOS + PAGOS RECIENTES ════ -->
<div class="two-col">

    <!-- Encargados -->
    <div class="kb-card" style="margin:0">
        <div class="kb-card-header">
            <div class="section-title">
                <span class="section-icon" style="background:#374151"><i class="bi bi-person-badge-fill"></i></span>
                Operaciones por Encargado
            </div>
        </div>
        <div class="kb-card-body p-0">
        <table class="kb-table">
            <thead><tr><th>Encargado</th><th>Total</th><th>Confirmadas</th><th>Pendientes</th><th></th></tr></thead>
            <tbody>
            <?php
            $encRows = [];
            while ($e = mysqli_fetch_assoc($qEnc)) $encRows[] = $e;
            $maxEnc = $encRows ? max(array_column($encRows,'total')) : 1;
            foreach ($encRows as $e):
            ?>
            <tr>
                <td>
                    <?php $avatarColors=['#1a56db','#16a34a','#d97706','#dc2626','#7c3aed','#0891b2'];
                          $ci = crc32($e['encargado']) % count($avatarColors);
                          $col = $avatarColors[abs($ci)]; ?>
                    <div class="d-flex align-items-center gap-2">
                        <div class="client-avatar" style="background:<?= $col ?>">
                            <?= strtoupper(substr($e['encargado'],0,2)) ?>
                        </div>
                        <span style="font-weight:500;font-size:13px"><?= htmlspecialchars($e['encargado']) ?></span>
                    </div>
                </td>
                <td><strong><?= $e['total'] ?></strong></td>
                <td><span class="estado-badge estado-confirmado"><?= $e['confirmadas'] ?></span></td>
                <td><span class="estado-badge estado-pendiente"><?= $e['pendientes'] ?></span></td>
                <td style="width:80px">
                    <div class="mini-bar">
                        <div class="mini-bar-fill" style="width:<?= round($e['total']/$maxEnc*100) ?>%"></div>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- Pagos recientes -->
    <div class="kb-card" style="margin:0">
        <div class="kb-card-header">
            <div class="section-title">
                <span class="section-icon" style="background:#16a34a"><i class="bi bi-cash-stack"></i></span>
                Pagos Recientes
            </div>
        </div>
        <div class="kb-card-body p-0">
        <table class="kb-table">
            <thead><tr><th>Cliente</th><th>Tipo</th><th>Monto</th><th>Fecha</th></tr></thead>
            <tbody>
            <?php while ($p = mysqli_fetch_assoc($qPagosRec)): ?>
            <tr>
                <td>
                    <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($p['nombre'].' '.$p['apellido']) ?></div>
                    <div style="font-size:11px;color:var(--text-muted)">Op. #<?= $p['id_operaciones'] ?> · <?= htmlspecialchars($p['metodo_pago'] ?? '—') ?></div>
                </td>
                <td><span class="tipo-badge tipo-<?= $p['tipo'] ?>"><?= ucfirst($p['tipo']) ?></span></td>
                <td>
                    <span class="monto-val <?= $p['moneda']==='Dólares'?'monto-dolares':'monto-soles' ?>">
                        <?= $p['moneda']==='Dólares'?'$':'S/' ?> <?= number_format($p['monto'],2) ?>
                    </span>
                </td>
                <td style="font-size:12px;color:var(--text-muted)"><?= $p['fecha'] ? date('d/m/Y',strtotime($p['fecha'])) : '—' ?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </div>

</div>


<!-- ════ ÚLTIMAS OPERACIONES ════ -->
<div class="kb-card">
    <div class="kb-card-header">
        <div class="section-title">
            <span class="section-icon" style="background:#7c3aed"><i class="bi bi-clock-history"></i></span>
            Últimas Operaciones
        </div>
        <a href="operaciones/lista.php" style="font-size:12px;color:var(--brand);text-decoration:none;font-weight:600">
            Ver todas <i class="bi bi-arrow-right"></i>
        </a>
    </div>
    <div style="overflow-x:auto">
    <table class="kb-table">
        <thead><tr>
            <th>#</th><th>Cliente</th><th>Grupo</th><th>Encargado</th>
            <th>Fecha reserva</th><th>Total</th><th>Tipo precio</th><th>Estado</th>
        </tr></thead>
        <tbody>
        <?php while ($u = mysqli_fetch_assoc($qUlt)): ?>
        <tr>
            <td style="font-family:'DM Mono',monospace;font-size:12px;color:var(--text-muted)">#<?= $u['id_operaciones'] ?></td>
            <td>
                <?php $colors=['#1a56db','#16a34a','#d97706','#dc2626','#7c3aed'];
                      $ci2 = (intval($u['id_operaciones'])) % count($colors); ?>
                <div class="d-flex align-items-center gap-2">
                    <div class="client-avatar" style="background:<?= $colors[$ci2] ?>">
                        <?= strtoupper(substr($u['nombre'],0,1).substr($u['apellido'],0,1)) ?>
                    </div>
                    <span style="font-weight:600"><?= htmlspecialchars($u['nombre'].' '.$u['apellido']) ?></span>
                </div>
            </td>
            <td style="font-size:12px;color:var(--text-muted)"><?= htmlspecialchars($u['nombre_grupo'] ?? '—') ?></td>
            <td><?= htmlspecialchars($u['encargado'] ?? '—') ?></td>
            <td style="font-family:'DM Mono',monospace;font-size:12px"><?= $u['fecha_reserva'] ? date('d/m/Y',strtotime($u['fecha_reserva'])) : '—' ?></td>
            <td><span class="monto-val"><?= number_format($u['total_operacion'],2) ?></span></td>
            <td style="font-size:12px;color:var(--text-muted)"><?= $u['tipo_precio'] ?></td>
            <td><span class="estado-badge estado-<?= $u['estado'] ?>"><?= ucfirst($u['estado']) ?></span></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div>
</div>


<!-- ════ OBSERVACIONES ════ -->
<div class="kb-card">
    <div class="kb-card-header">
        <div class="section-title">
            <span class="section-icon" style="background:#0891b2"><i class="bi bi-chat-left-text-fill"></i></span>
            Últimas Observaciones
        </div>
    </div>
    <div class="kb-card-body" style="display:flex;flex-direction:column;gap:10px">
    <?php
    $obsRows = [];
    while ($o = mysqli_fetch_assoc($qObs)) $obsRows[] = $o;
    if (empty($obsRows)):
    ?>
        <div class="empty-state"><i class="bi bi-chat-left"></i>Sin observaciones</div>
    <?php else: foreach ($obsRows as $o): ?>
        <div style="display:flex;gap:12px;padding:10px 14px;border:1px solid var(--border);border-radius:9px;background:var(--surface-2)">
            <div style="flex-shrink:0;width:36px;height:36px;border-radius:9px;background:#dbeafe;color:#1a56db;display:flex;align-items:center;justify-content:center;font-size:16px">
                <i class="bi bi-chat-quote"></i>
            </div>
            <div style="flex:1;min-width:0">
                <div style="font-weight:600;font-size:13px">
                    <?= htmlspecialchars($o['nombre'].' '.$o['apellido']) ?>
                    <span style="font-weight:400;color:var(--text-muted);font-size:12px">
                        — <?= htmlspecialchars($o['nombre_grupo'] ?? 'Sin grupo') ?> · Op. #<?= $o['id_operaciones'] ?>
                    </span>
                </div>
                <div style="font-size:13px;color:var(--text-muted);margin-top:2px"><?= htmlspecialchars($o['observaciones']) ?></div>
            </div>
            <div style="flex-shrink:0;font-size:11px;color:var(--text-muted);white-space:nowrap">
                <?= $o['fecha_reserva'] ? date('d/m/Y',strtotime($o['fecha_reserva'])) : '—' ?>
            </div>
        </div>
    <?php endforeach; endif; ?>
    </div>
</div>

</div><!-- /.main-content -->
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* ── CHART MESES ── */
const ctxMeses = document.getElementById('chartMeses').getContext('2d');
new Chart(ctxMeses, {
    type: 'bar',
    data: {
        labels: <?= json_encode($meses) ?>,
        datasets: [{
            label: 'Tours',
            data: <?= json_encode($mesesTotal) ?>,
            backgroundColor: 'rgba(26,86,219,.15)',
            borderColor: '#1a56db',
            borderWidth: 2,
            borderRadius: 6,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: '#e2e8f0' }, ticks: { color: '#64748b', font: { family: 'DM Sans', size: 11 } } },
            x: { grid: { display: false }, ticks: { color: '#64748b', font: { family: 'DM Sans', size: 11 } } }
        }
    }
});

/* ── CHART TIPO ── */
const ctxTipo = document.getElementById('chartTipo').getContext('2d');
new Chart(ctxTipo, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($tipoLabels) ?>,
        datasets: [{
            data: <?= json_encode($tipoTotal) ?>,
            backgroundColor: ['#1a56db','#16a34a','#d97706','#dc2626','#7c3aed','#0891b2','#db2777','#374151'],
            borderWidth: 2, borderColor: '#fff',
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: { font: { family: 'DM Sans', size: 10 }, padding: 8, boxWidth: 10 }
            }
        },
        cutout: '65%',
    }
});
</script>
</body>
</html>

