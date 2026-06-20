<?php
session_start();
include('../../conexion.php');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set("America/Lima");
$hoy = date("Y-m-d");

/* ── MÉTRICAS KPI ── */

// Reservas activas (operaciones_detalle con fecha futura)
$r = mysqli_query($conexion,"SELECT COUNT(DISTINCT od.id_operaciones) total FROM operaciones_detalle od JOIN operaciones o ON o.id_operaciones=od.id_operaciones WHERE od.fecha_salida >= CURDATE() AND o.estado != 'cancelado'");
$reservasActivas = mysqli_fetch_assoc($r)['total'] ?? 0;

// Tours total programados
$r = mysqli_query($conexion,"SELECT COUNT(*) total FROM operaciones_detalle WHERE fecha_salida >= CURDATE()");
$toursProgramados = mysqli_fetch_assoc($r)['total'] ?? 0;

// Nuevos clientes (30 días)
$r = mysqli_query($conexion,"SELECT COUNT(*) total FROM datos_clientes WHERE fecha_registro >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$nuevosClientes = mysqli_fetch_assoc($r)['total'] ?? 0;

// Total clientes
$r = mysqli_query($conexion,"SELECT COUNT(*) total FROM datos_clientes");
$totalClientes = mysqli_fetch_assoc($r)['total'] ?? 0;

// Grupos activos
$r = mysqli_query($conexion,"SELECT COUNT(*) total FROM grupos WHERE estado='abierto'");
$gruposActivos = mysqli_fetch_assoc($r)['total'] ?? 0;

// Operaciones confirmadas vs pendientes vs canceladas
$r = mysqli_query($conexion,"SELECT estado, COUNT(*) total FROM operaciones GROUP BY estado");
$opEstados = ['confirmado'=>0,'pendiente'=>0,'cancelado'=>0];
while($row = mysqli_fetch_assoc($r)) $opEstados[$row['estado']] = $row['total'];
$totalOp = array_sum($opEstados);

// Ingresos del día (tabla pagos real)
$r = mysqli_query($conexion,"SELECT moneda, SUM(monto) total FROM pagos WHERE fecha = CURDATE() AND tipo != 'reembolso' GROUP BY moneda");
$ingresosHoy = ['Soles'=>0,'Dólares'=>0];
while($row = mysqli_fetch_assoc($r)) $ingresosHoy[$row['moneda']] = $row['total'];

// Ingresos totales
$r = mysqli_query($conexion,"SELECT moneda, SUM(monto) total FROM pagos WHERE tipo != 'reembolso' GROUP BY moneda");
$ingresosTotal = ['Soles'=>0,'Dólares'=>0];
while($row = mysqli_fetch_assoc($r)) $ingresosTotal[$row['moneda']] = $row['total'];

// Reembolsos totales
$r = mysqli_query($conexion,"SELECT IFNULL(SUM(monto),0) total FROM pagos WHERE tipo='reembolso'");
$reembolsos = mysqli_fetch_assoc($r)['total'] ?? 0;

// Contabilidad pendiente
$r = mysqli_query($conexion,"SELECT COUNT(*) total, IFNULL(SUM(comision),0) comisiones FROM contabilidad WHERE estado='pendiente'");
$contRow = mysqli_fetch_assoc($r);
$contPend = $contRow['total'] ?? 0;
$comisionPend = $contRow['comisiones'] ?? 0;

// Artículos almacén en uso
$r = mysqli_query($conexion,"SELECT IFNULL(SUM(cantidad),0) t FROM almacen_salidas WHERE estado != 'Devuelto'");
$almacenUso = mysqli_fetch_assoc($r)['t'] ?? 0;

// Tours hoy
$r = mysqli_query($conexion,"SELECT COUNT(DISTINCT od.id_detalle) t FROM operaciones_detalle od WHERE od.fecha_salida = CURDATE()");
$toursHoy = mysqli_fetch_assoc($r)['t'] ?? 0;

/* ── GRÁFICO: ingresos últimos 30 días (por día, soles) ── */
$q = mysqli_query($conexion,"
    SELECT DATE(fecha) as dia, SUM(monto) total
    FROM pagos
    WHERE moneda='Soles' AND tipo != 'reembolso'
      AND fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY dia ORDER BY dia ASC
");
$chartLabels=[]; $chartData=[];
while($row = mysqli_fetch_assoc($q)){
    $chartLabels[] = date('d/m', strtotime($row['dia']));
    $chartData[]   = (float)$row['total'];
}

/* ── GRÁFICO: operaciones por mes (últimos 6) ── */
$q = mysqli_query($conexion,"
    SELECT DATE_FORMAT(fecha_reserva,'%b %Y') mes, DATE_FORMAT(fecha_reserva,'%Y-%m') ord, COUNT(*) total
    FROM operaciones
    WHERE fecha_reserva >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY ord ORDER BY ord ASC
");
$barLabels=[]; $barData=[];
while($row = mysqli_fetch_assoc($q)){
    $barLabels[] = $row['mes'];
    $barData[]   = (int)$row['total'];
}

/* ── GRÁFICO: distribución por servicio (donut) ── */
$q = mysqli_query($conexion,"
    SELECT IFNULL(s.nombre,'Sin asignar') nombre, COUNT(*) total
    FROM operaciones_detalle od
    LEFT JOIN servicios s ON s.id_servicio = od.id_servicio
    GROUP BY s.nombre ORDER BY total DESC LIMIT 6
");
$donutLabels=[]; $donutData=[];
while($row = mysqli_fetch_assoc($q)){
    $donutLabels[] = $row['nombre'];
    $donutData[]   = (int)$row['total'];
}

/* ── GRÁFICO: métodos de pago ── */
$q = mysqli_query($conexion,"
    SELECT metodo_pago, SUM(monto) total
    FROM pagos WHERE tipo != 'reembolso'
    GROUP BY metodo_pago ORDER BY total DESC
");
$metLabels=[]; $metData=[];
while($row = mysqli_fetch_assoc($q)){
    $metLabels[] = $row['metodo_pago'] ?? 'N/D';
    $metData[]   = (float)$row['total'];
}

/* ── TOP SERVICIOS ── */
$qTop = mysqli_query($conexion,"
    SELECT IFNULL(s.nombre,'Sin asignar') nombre_servicio, COUNT(*) total,
           SUM(od.precio) ingresos, od.tipo_moneda
    FROM operaciones_detalle od
    LEFT JOIN servicios s ON s.id_servicio = od.id_servicio
    GROUP BY s.nombre ORDER BY total DESC LIMIT 6
");
$topRows = [];
while($row = mysqli_fetch_assoc($qTop)) $topRows[] = $row;
$maxTop = $topRows ? max(array_column($topRows,'total')) : 1;

/* ── TOP ENCARGADOS ── */
$qEnc = mysqli_query($conexion,"
    SELECT IFNULL(NULLIF(encargado,''),'Sin encargado') encargado,
           COUNT(*) total,
           SUM(CASE WHEN estado='confirmado' THEN 1 ELSE 0 END) conf,
           SUM(CASE WHEN estado='pendiente'  THEN 1 ELSE 0 END) pend,
           SUM(CASE WHEN estado='cancelado'  THEN 1 ELSE 0 END) canc
    FROM operaciones
    GROUP BY encargado ORDER BY total DESC LIMIT 6
");

/* ── PRÓXIMOS TOURS (7 días) ── */
$qProx = mysqli_query($conexion,"
    SELECT od.fecha_salida, od.fecha_retorno, od.modalidad_retorno,
           od.incluye_ingreso, od.precio, od.tipo_moneda,
           IFNULL(s.nombre,'Sin asignar') nombre_servicio,
           o.encargado, o.estado,
           g.nombre_grupo,
           COUNT(DISTINCT cg.id_cliente) pax
    FROM operaciones_detalle od
    JOIN operaciones o ON o.id_operaciones = od.id_operaciones
    LEFT JOIN servicios s ON s.id_servicio = od.id_servicio
    LEFT JOIN grupos g ON g.id_grupo = o.id_grupo
    LEFT JOIN clientes_grupo cg ON cg.id_grupo = o.id_grupo
    WHERE od.fecha_salida BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    GROUP BY od.id_detalle
    ORDER BY od.fecha_salida ASC
    LIMIT 10
");

/* ── CONTABILIDAD RECIENTE ── */
$qConta = mysqli_query($conexion,"
    SELECT c.*, o.encargado, o.estado as op_estado, o.total_operacion,
           dc.nombre, dc.apellido
    FROM contabilidad c
    JOIN operaciones o ON o.id_operaciones = c.id_operaciones
    JOIN datos_clientes dc ON dc.id_cliente = o.id_cliente
    ORDER BY c.id_contabilidad DESC LIMIT 6
");

/* ── ÚLTIMAS OPERACIONES ── */
$qUlt = mysqli_query($conexion,"
    SELECT o.id_operaciones, o.encargado, o.fecha_reserva, o.estado,
           o.total_operacion, o.tipo_precio,
           dc.nombre, dc.apellido,
           g.nombre_grupo,
           COUNT(DISTINCT od.id_detalle) n_tours
    FROM operaciones o
    JOIN datos_clientes dc ON dc.id_cliente = o.id_cliente
    LEFT JOIN grupos g ON g.id_grupo = o.id_grupo
    LEFT JOIN operaciones_detalle od ON od.id_operaciones = o.id_operaciones
    GROUP BY o.id_operaciones
    ORDER BY o.id_operaciones DESC LIMIT 8
");

/* ── ALMACÉN: salidas activas ── */
$qAlm = mysqli_query($conexion,"
    SELECT als.*, ast.talla, ai.nombre as nombre_item, ai.tipo,
           als.estado, als.cantidad, als.fecha_salida
    FROM almacen_salidas als
    JOIN almacen_stock ast ON ast.id_stock = als.id_stock
    JOIN almacen_items ai ON ai.id_item = ast.id_item
    WHERE als.estado != 'Devuelto'
    ORDER BY als.fecha_salida DESC LIMIT 6
");

/* ── PAGOS RECIENTES ── */
$qPagos = mysqli_query($conexion,"
    SELECT p.*, dc.nombre, dc.apellido, o.id_operaciones, o.encargado
    FROM pagos p
    JOIN operaciones o ON o.id_operaciones = p.id_operaciones
    JOIN datos_clientes dc ON dc.id_cliente = o.id_cliente
    ORDER BY p.id_pago DESC LIMIT 8
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panel Gerencial — KB Tours</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
:root{
    --brand:#1a56db; --brand-light:#dbeafe; --brand-dark:#1e40af;
    --surface:#fff; --surface-2:#f8fafc; --border:#e2e8f0;
    --text:#0f172a; --text-muted:#64748b;
    --success:#16a34a; --warning:#d97706; --danger:#dc2626; --info:#0891b2;
    --radius:12px;
    --shadow:0 1px 3px rgba(0,0,0,.06),0 4px 16px rgba(0,0,0,.04);
}
*,*::before,*::after{box-sizing:border-box}
body{font-family:'DM Sans',sans-serif;background:var(--surface-2);color:var(--text);font-size:14px;line-height:1.6}

/* HEADER */
.page-header{background:var(--surface);border-bottom:1px solid var(--border);padding:18px 32px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100}
.page-header h1{font-size:18px;font-weight:700;margin:0}
.page-header .subtitle{color:var(--text-muted);font-size:13px;margin:0}
.date-pill{display:flex;align-items:center;gap:6px;background:var(--surface-2);border:1px solid var(--border);padding:6px 14px;border-radius:20px;font-size:13px;font-weight:500;color:var(--text-muted)}

/* LAYOUT */
.main-content{max-width:1400px;margin:0 auto;padding:28px 24px 60px}

/* CARDS */
.kb-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow);margin-bottom:24px;overflow:hidden}
.kb-card-header{display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-bottom:1px solid var(--border);background:var(--surface-2)}
.section-title{display:flex;align-items:center;gap:10px;font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--text)}
.section-icon{width:28px;height:28px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:14px;color:#fff}
.kb-card-body{padding:20px}

/* KPI */
.kpi-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(165px,1fr));gap:16px;margin-bottom:28px}
.kpi-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:18px 20px;box-shadow:var(--shadow);position:relative;overflow:hidden;transition:transform .15s,box-shadow .15s}
.kpi-card:hover{transform:translateY(-2px);box-shadow:0 4px 20px rgba(0,0,0,.08)}
.kpi-icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:18px;margin-bottom:12px}
.kpi-label{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.6px;color:var(--text-muted);margin-bottom:4px}
.kpi-value{font-size:24px;font-weight:700;line-height:1}
.kpi-sub{font-size:11px;color:var(--text-muted);margin-top:4px}
.kpi-accent{position:absolute;bottom:0;left:0;right:0;height:3px}

/* GRIDS */
.two-col{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px}
.three-col{display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px;margin-bottom:24px}
.four-col{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:24px}
.chart-main{display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-bottom:24px}
.chart-secondary{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px}

/* TABLES */
.kb-table{width:100%;border-collapse:separate;border-spacing:0}
.kb-table thead th{background:var(--surface-2);padding:10px 14px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--text-muted);border-bottom:1px solid var(--border);white-space:nowrap}
.kb-table tbody td{padding:11px 14px;border-bottom:1px solid var(--border);vertical-align:middle}
.kb-table tbody tr:last-child td{border-bottom:none}
.kb-table tbody tr:hover{background:var(--surface-2)}

/* BADGES */
.estado-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.estado-confirmado{background:#dcfce7;color:#15803d}
.estado-pendiente{background:#fef9c3;color:#a16207}
.estado-cancelado{background:#fee2e2;color:#b91c1c}
.estado-pagado{background:#dcfce7;color:#15803d}
.tipo-badge{display:inline-block;padding:2px 8px;border-radius:5px;font-size:11px;font-weight:600;text-transform:uppercase}
.tipo-tour{background:#dbeafe;color:#1e40af}
.tipo-adicional{background:#fef3c7;color:#92400e}
.tipo-cuenta{background:#dcfce7;color:#15803d}
.tipo-saldo{background:#f3e8ff;color:#6b21a8}
.tipo-reembolso{background:#fee2e2;color:#b91c1c}

/* MONEY */
.monto-val{font-family:'DM Mono',monospace;font-weight:500;font-size:13px}
.monto-soles{color:#1e40af}
.monto-dolares{color:#166534}
.monto-neg{color:#b91c1c}

/* MISC */
.mini-bar{height:6px;border-radius:3px;background:var(--border);overflow:hidden;margin-top:4px}
.mini-bar-fill{height:100%;border-radius:3px;background:var(--brand);transition:width .4s}
.client-avatar{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0}
.chart-wrap{position:relative;height:240px}
.chart-wrap-sm{position:relative;height:200px}
.alert-strip{display:flex;align-items:center;gap:10px;padding:10px 16px;border-radius:9px;margin-bottom:14px;font-size:13px;font-weight:500}
.alert-strip.info{background:#dbeafe;color:#1e40af;border:1px solid #bfdbfe}
.alert-strip.warning{background:#fef9c3;color:#a16207;border:1px solid #fde68a}
.alert-strip.danger{background:#fee2e2;color:#b91c1c;border:1px solid #fecaca}
.empty-state{text-align:center;padding:28px;color:var(--text-muted)}
.empty-state i{font-size:28px;opacity:.3;display:block;margin-bottom:6px}

/* FINANCE SUMMARY STRIP */
.fin-strip{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:0;border-top:1px solid var(--border)}
.fin-item{padding:14px 20px;border-right:1px solid var(--border);display:flex;flex-direction:column;gap:3px}
.fin-item:last-child{border-right:none}
.fin-label{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted)}
.fin-value{font-size:18px;font-weight:700;font-family:'DM Mono',monospace}

/* SECTION DIVIDER */
.section-divider{display:flex;align-items:center;gap:12px;margin:28px 0 18px;color:var(--text-muted);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.8px}
.section-divider::after{content:'';flex:1;height:1px;background:var(--border)}

@media(max-width:1100px){.four-col{grid-template-columns:repeat(2,1fr)}.chart-main{grid-template-columns:1fr}.chart-secondary{grid-template-columns:1fr}}
@media(max-width:768px){.two-col,.three-col{grid-template-columns:1fr}.kpi-grid{grid-template-columns:repeat(2,1fr)}.page-header{padding:14px 16px}}
</style>
</head>
<body>
<?php include '../sidebar.php'; ?>

<!-- ════ HEADER ════ -->
 <div class="kb-content">
<div class="page-header">
    <div>
        <h1><i class="bi bi-shield-check text-primary me-2"></i>Panel Gerencial</h1>
        <p class="subtitle">Vista completa de operaciones, finanzas y logística</p>
    </div>
    <div class="d-flex align-items-center gap-3">
        <div class="date-pill"><i class="bi bi-calendar3"></i><?= date('l, d \d\e F Y') ?></div>
        <div class="date-pill" style="background:#dcfce7;border-color:#bbf7d0;color:#15803d">
            <i class="bi bi-circle-fill" style="font-size:7px"></i> En vivo
        </div>
    </div>
</div>

<div class="main-content">

<!-- ════ ALERTAS ════ -->
<?php if($opEstados['pendiente'] > 0): ?>
<div class="alert-strip warning">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <strong><?= $opEstados['pendiente'] ?></strong> operación(es) pendientes de confirmar.
</div>
<?php endif; ?>
<?php if($contPend > 0): ?>
<div class="alert-strip info">
    <i class="bi bi-file-earmark-text"></i>
    <strong><?= $contPend ?></strong> registro(s) contables sin cerrar &nbsp;·&nbsp;
    Comisiones por cobrar: <strong>S/ <?= number_format($comisionPend,2) ?></strong>
</div>
<?php endif; ?>
<?php if($almacenUso > 0): ?>
<div class="alert-strip warning">
    <i class="bi bi-box-seam-fill"></i>
    <strong><?= $almacenUso ?></strong> artículo(s) de almacén fuera sin devolución.
</div>
<?php endif; ?>


<!-- ════ KPI ════ -->
<div class="kpi-grid">

    <div class="kpi-card">
        <div class="kpi-icon" style="background:#dbeafe;color:#1a56db"><i class="bi bi-clipboard2-data-fill"></i></div>
        <div class="kpi-label">Total Operaciones</div>
        <div class="kpi-value text-primary"><?= number_format($totalOp) ?></div>
        <div class="kpi-sub"><?= $opEstados['confirmado'] ?> confirmadas</div>
        <div class="kpi-accent" style="background:#1a56db"></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon" style="background:#dcfce7;color:#16a34a"><i class="bi bi-people-fill"></i></div>
        <div class="kpi-label">Total Clientes</div>
        <div class="kpi-value" style="color:#16a34a"><?= number_format($totalClientes) ?></div>
        <div class="kpi-sub">+<?= $nuevosClientes ?> últimos 30 días</div>
        <div class="kpi-accent" style="background:#16a34a"></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon" style="background:#f3e8ff;color:#7c3aed"><i class="bi bi-collection-fill"></i></div>
        <div class="kpi-label">Grupos Activos</div>
        <div class="kpi-value" style="color:#7c3aed"><?= $gruposActivos ?></div>
        <div class="kpi-sub">estado abierto</div>
        <div class="kpi-accent" style="background:#7c3aed"></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon" style="background:#fef3c7;color:#d97706"><i class="bi bi-compass-fill"></i></div>
        <div class="kpi-label">Reservas Activas</div>
        <div class="kpi-value" style="color:#d97706"><?= $reservasActivas ?></div>
        <div class="kpi-sub"><?= $toursHoy ?> salen hoy</div>
        <div class="kpi-accent" style="background:#d97706"></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon" style="background:#dcfce7;color:#16a34a"><i class="bi bi-cash-stack"></i></div>
        <div class="kpi-label">Ingresos Hoy S/</div>
        <div class="kpi-value monto-soles" style="font-size:19px">S/ <?= number_format($ingresosHoy['Soles'],0) ?></div>
        <div class="kpi-sub">$ <?= number_format($ingresosHoy['Dólares'],0) ?> dólares</div>
        <div class="kpi-accent" style="background:#16a34a"></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon" style="background:#dbeafe;color:#1e40af"><i class="bi bi-graph-up-arrow"></i></div>
        <div class="kpi-label">Total Cobrado S/</div>
        <div class="kpi-value monto-soles" style="font-size:19px">S/ <?= number_format($ingresosTotal['Soles'],0) ?></div>
        <div class="kpi-sub">$ <?= number_format($ingresosTotal['Dólares'],0) ?> dólares</div>
        <div class="kpi-accent" style="background:#1e40af"></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon" style="background:#fee2e2;color:#dc2626"><i class="bi bi-arrow-counterclockwise"></i></div>
        <div class="kpi-label">Reembolsos</div>
        <div class="kpi-value monto-neg" style="font-size:19px">S/ <?= number_format($reembolsos,0) ?></div>
        <div class="kpi-sub">total acumulado</div>
        <div class="kpi-accent" style="background:#dc2626"></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon" style="background:#fee2e2;color:#dc2626"><i class="bi bi-box-seam-fill"></i></div>
        <div class="kpi-label">Almacén en Uso</div>
        <div class="kpi-value" style="color:#dc2626"><?= $almacenUso ?></div>
        <div class="kpi-sub">artículos sin devolver</div>
        <div class="kpi-accent" style="background:#dc2626"></div>
    </div>

</div>

<!-- ════ ESTADO OPERACIONES STRIP ════ -->
<div class="kb-card" style="margin-bottom:24px">
    <div class="fin-strip">
        <?php
        $total_op_nz = $totalOp ?: 1;
        $items = [
            ['Confirmadas', $opEstados['confirmado'], '#15803d', 'estado-confirmado'],
            ['Pendientes',  $opEstados['pendiente'],  '#a16207', 'estado-pendiente'],
            ['Canceladas',  $opEstados['cancelado'],  '#b91c1c', 'estado-cancelado'],
            ['Tours programados', $toursProgramados, '#1a56db', 'tipo-tour'],
            ['Clientes únicos', $totalClientes, '#7c3aed', ''],
            ['Cont. pendiente', $contPend, '#a16207', 'estado-pendiente'],
        ];
        foreach($items as [$label, $val, $color, $badge]): ?>
        <div class="fin-item">
            <span class="fin-label"><?= $label ?></span>
            <span class="fin-value" style="color:<?= $color ?>"><?= number_format($val) ?></span>
            <div class="mini-bar"><div class="mini-bar-fill" style="background:<?= $color ?>;width:<?= min(100,round($val/$total_op_nz*100)) ?>%"></div></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ════ GRÁFICOS PRINCIPALES ════ -->
<div class="chart-main">

    <!-- Ingresos 30 días -->
    <div class="kb-card" style="margin:0">
        <div class="kb-card-header">
            <div class="section-title">
                <span class="section-icon" style="background:#1a56db"><i class="bi bi-graph-up"></i></span>
                Ingresos en Soles — Últimos 30 Días
            </div>
        </div>
        <div class="kb-card-body">
            <div class="chart-wrap"><canvas id="chartIngresos"></canvas></div>
        </div>
    </div>

    <!-- Donut servicios -->
    <div style="display:flex;flex-direction:column;gap:20px">
        <div class="kb-card" style="margin:0">
            <div class="kb-card-header">
                <div class="section-title">
                    <span class="section-icon" style="background:#0891b2"><i class="bi bi-pie-chart-fill"></i></span>
                    Distribución Servicios
                </div>
            </div>
            <div class="kb-card-body">
                <div class="chart-wrap-sm"><canvas id="chartDonut"></canvas></div>
            </div>
        </div>
    </div>

</div>

<!-- ════ GRÁFICOS SECUNDARIOS ════ -->
<div class="chart-secondary">

    <!-- Operaciones por mes -->
    <div class="kb-card" style="margin:0">
        <div class="kb-card-header">
            <div class="section-title">
                <span class="section-icon" style="background:#7c3aed"><i class="bi bi-bar-chart-fill"></i></span>
                Operaciones por Mes
            </div>
        </div>
        <div class="kb-card-body">
            <div class="chart-wrap-sm"><canvas id="chartMeses"></canvas></div>
        </div>
    </div>

    <!-- Métodos de pago -->
    <div class="kb-card" style="margin:0">
        <div class="kb-card-header">
            <div class="section-title">
                <span class="section-icon" style="background:#d97706"><i class="bi bi-wallet2"></i></span>
                Métodos de Pago
            </div>
        </div>
        <div class="kb-card-body">
            <div class="chart-wrap-sm"><canvas id="chartMetodos"></canvas></div>
        </div>
    </div>

</div>

<!-- ════ PRÓXIMOS TOURS ════ -->
<div class="section-divider"><i class="bi bi-compass-fill me-1"></i>Operaciones & Logística</div>

<div class="kb-card">
    <div class="kb-card-header">
        <div class="section-title">
            <span class="section-icon" style="background:#d97706"><i class="bi bi-calendar-week-fill"></i></span>
            Próximos Tours — 7 Días
        </div>
    </div>
    <?php
    $prox = [];
    while($t = mysqli_fetch_assoc($qProx)) $prox[] = $t;
    ?>
    <?php if(empty($prox)): ?>
        <div class="empty-state"><i class="bi bi-calendar-x"></i>Sin tours próximos</div>
    <?php else: ?>
    <div style="overflow-x:auto">
    <table class="kb-table">
        <thead><tr>
            <th>Servicio</th><th>Grupo</th><th>Salida</th><th>Retorno</th>
            <th>Modalidad</th><th>Ingreso</th><th>PAX</th><th>Encargado</th><th>Estado</th>
        </tr></thead>
        <tbody>
        <?php foreach($prox as $t): $diasFaltan = (strtotime($t['fecha_salida'])-strtotime($hoy))/86400; ?>
        <tr>
            <td><span style="font-weight:600"><?= htmlspecialchars($t['nombre_servicio']) ?></span></td>
            <td style="font-size:12px;color:var(--text-muted)"><?= htmlspecialchars($t['nombre_grupo'] ?? '—') ?></td>
            <td>
                <span style="font-family:'DM Mono',monospace;font-size:12px"><?= date('d/m/Y',strtotime($t['fecha_salida'])) ?></span>
                <?php if($diasFaltan == 0): ?>
                    <span class="estado-badge" style="background:#fef3c7;color:#92400e;margin-left:4px">Hoy</span>
                <?php elseif($diasFaltan == 1): ?>
                    <span class="estado-badge" style="background:#dbeafe;color:#1e40af;margin-left:4px">Mañana</span>
                <?php endif; ?>
            </td>
            <td style="font-family:'DM Mono',monospace;font-size:12px"><?= $t['fecha_retorno'] ? date('d/m/Y',strtotime($t['fecha_retorno'])) : '—' ?></td>
            <td><?php $im=['Carro'=>'bi-car-front','Tren'=>'bi-train-front','Caminata'=>'bi-person-walking'][$t['modalidad_retorno']] ?? 'bi-arrow-right'; ?><i class="bi <?= $im ?? 'bi-arrow-right' ?> me-1"></i><?= htmlspecialchars($t['modalidad_retorno'] ?? '—') ?></td>
            <td><?= $t['incluye_ingreso']==='SI' ? '<span class="estado-badge estado-confirmado">Sí</span>' : '<span class="estado-badge estado-cancelado">No</span>' ?></td>
            <td><strong><?= $t['pax'] ?></strong></td>
            <td><?= htmlspecialchars($t['encargado'] ?? '—') ?></td>
            <td><span class="estado-badge estado-<?= $t['estado'] ?>"><?= ucfirst($t['estado']) ?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>


<!-- ════ DOS COL: TOP SERVICIOS + ENCARGADOS ════ -->
<div class="two-col">

    <div class="kb-card" style="margin:0">
        <div class="kb-card-header">
            <div class="section-title">
                <span class="section-icon" style="background:#0891b2"><i class="bi bi-trophy-fill"></i></span>
                Top Servicios
            </div>
        </div>
        <div class="kb-card-body p-0">
        <table class="kb-table">
            <thead><tr><th>#</th><th>Servicio</th><th>Ventas</th><th>Ingresos</th><th></th></tr></thead>
            <tbody>
            <?php foreach($topRows as $idx => $t): ?>
            <tr>
                <td style="color:var(--text-muted);font-size:12px"><?= $idx+1 ?></td>
                <td style="font-weight:600"><?= htmlspecialchars($t['nombre_servicio']) ?></td>
                <td><strong><?= $t['total'] ?></strong></td>
                <td><span class="monto-val monto-soles">S/ <?= number_format($t['ingresos'] ?? 0,0) ?></span></td>
                <td style="width:80px">
                    <div class="mini-bar"><div class="mini-bar-fill" style="width:<?= round($t['total']/$maxTop*100) ?>%"></div></div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>

    <div class="kb-card" style="margin:0">
        <div class="kb-card-header">
            <div class="section-title">
                <span class="section-icon" style="background:#374151"><i class="bi bi-person-badge-fill"></i></span>
                Rendimiento por Encargado
            </div>
        </div>
        <div class="kb-card-body p-0">
        <table class="kb-table">
            <thead><tr><th>Encargado</th><th>Total</th><th>Conf.</th><th>Pend.</th><th>Canc.</th></tr></thead>
            <tbody>
            <?php
            $encAll=[];
            while($e=mysqli_fetch_assoc($qEnc)) $encAll[]=$e;
            $maxE = $encAll ? max(array_column($encAll,'total')) : 1;
            $colors=['#1a56db','#16a34a','#d97706','#dc2626','#7c3aed','#0891b2'];
            foreach($encAll as $i => $e):
                $col = $colors[$i % count($colors)];
            ?>
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="client-avatar" style="background:<?= $col ?>"><?= strtoupper(substr($e['encargado'],0,2)) ?></div>
                        <span style="font-weight:500"><?= htmlspecialchars($e['encargado']) ?></span>
                    </div>
                </td>
                <td>
                    <strong><?= $e['total'] ?></strong>
                    <div class="mini-bar" style="width:60px"><div class="mini-bar-fill" style="background:<?= $col ?>;width:<?= round($e['total']/$maxE*100) ?>%"></div></div>
                </td>
                <td><span class="estado-badge estado-confirmado"><?= $e['conf'] ?></span></td>
                <td><span class="estado-badge estado-pendiente"><?= $e['pend'] ?></span></td>
                <td><span class="estado-badge estado-cancelado"><?= $e['canc'] ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>

</div>


<!-- ════ FINANZAS ════ -->
<div class="section-divider"><i class="bi bi-cash-stack me-1"></i>Finanzas & Contabilidad</div>

<!-- Pagos recientes + Contabilidad -->
<div class="two-col">

    <div class="kb-card" style="margin:0">
        <div class="kb-card-header">
            <div class="section-title">
                <span class="section-icon" style="background:#16a34a"><i class="bi bi-cash-coin"></i></span>
                Pagos Recientes
            </div>
        </div>
        <div style="overflow-x:auto">
        <table class="kb-table">
            <thead><tr><th>Cliente</th><th>Op.</th><th>Tipo</th><th>Método</th><th>Monto</th><th>T/C</th><th>Fecha</th></tr></thead>
            <tbody>
            <?php while($p=mysqli_fetch_assoc($qPagos)): ?>
            <tr>
                <td>
                    <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($p['nombre'].' '.$p['apellido']) ?></div>
                    <div style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($p['encargado'] ?? '—') ?></div>
                </td>
                <td style="font-size:12px;color:var(--text-muted);font-family:'DM Mono',monospace">#<?= $p['id_operaciones'] ?></td>
                <td><span class="tipo-badge tipo-<?= $p['tipo'] ?>"><?= ucfirst($p['tipo']) ?></span></td>
                <td style="font-size:12px"><?= htmlspecialchars($p['metodo_pago'] ?? '—') ?></td>
                <td>
                    <span class="monto-val <?= $p['moneda']==='Dólares'?'monto-dolares':'monto-soles' ?><?= $p['tipo']==='reembolso'?' monto-neg':'' ?>">
                        <?= $p['moneda']==='Dólares'?'$':'S/' ?> <?= number_format($p['monto'],2) ?>
                    </span>
                </td>
                <td style="font-size:11px;color:var(--text-muted);font-family:'DM Mono',monospace">
                    <?= ($p['tipo_cambio'] && $p['tipo_cambio'] != 1) ? number_format($p['tipo_cambio'],3) : '—' ?>
                </td>
                <td style="font-size:12px;color:var(--text-muted)"><?= $p['fecha'] ? date('d/m/Y',strtotime($p['fecha'])) : '—' ?></td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        </div>
    </div>

    <div class="kb-card" style="margin:0">
        <div class="kb-card-header">
            <div class="section-title">
                <span class="section-icon" style="background:#374151"><i class="bi bi-file-earmark-text-fill"></i></span>
                Contabilidad Reciente
            </div>
            <span class="estado-badge estado-pendiente"><?= $contPend ?> pendientes</span>
        </div>
        <div style="overflow-x:auto">
        <table class="kb-table">
            <thead><tr><th>Cliente</th><th>Estado</th><th>Comisión</th><th>IGV</th><th>Detracción</th><th>Boleta</th></tr></thead>
            <tbody>
            <?php while($c=mysqli_fetch_assoc($qConta)): ?>
            <tr>
                <td>
                    <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($c['nombre'].' '.$c['apellido']) ?></div>
                    <div style="font-size:11px;color:var(--text-muted)">Op. #<?= $c['id_operaciones'] ?></div>
                </td>
                <td><span class="estado-badge estado-<?= $c['estado'] ?>"><?= ucfirst($c['estado']) ?></span></td>
                <td><span class="monto-val monto-soles"><?= $c['comision'] ? 'S/ '.number_format($c['comision'],2) : '—' ?></span></td>
                <td><span class="monto-val" style="color:var(--text-muted)"><?= $c['igv'] ? 'S/ '.number_format($c['igv'],2) : '—' ?></span></td>
                <td><?= $c['detraccion'] ? 'S/ '.number_format($c['detraccion'],2) : '—' ?></td>
                <td style="font-family:'DM Mono',monospace;font-size:12px"><?= htmlspecialchars($c['nro_boleta_total'] ?? $c['nro_boleta_cuenta'] ?? '—') ?></td>
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
            Últimas Operaciones Registradas
        </div>
        <a href="operaciones/lista.php" style="font-size:12px;color:var(--brand);text-decoration:none;font-weight:600">Ver todas <i class="bi bi-arrow-right"></i></a>
    </div>
    <div style="overflow-x:auto">
    <table class="kb-table">
        <thead><tr><th>#</th><th>Cliente</th><th>Grupo</th><th>Tours</th><th>Encargado</th><th>Fecha reserva</th><th>Total</th><th>Tipo precio</th><th>Estado</th></tr></thead>
        <tbody>
        <?php
        $accentColors=['#1a56db','#16a34a','#d97706','#dc2626','#7c3aed','#0891b2'];
        $idx=0;
        while($u=mysqli_fetch_assoc($qUlt)):
            $ac=$accentColors[$idx++ % count($accentColors)];
        ?>
        <tr>
            <td style="font-family:'DM Mono',monospace;font-size:12px;color:var(--text-muted)">#<?= $u['id_operaciones'] ?></td>
            <td>
                <div class="d-flex align-items-center gap-2">
                    <div class="client-avatar" style="background:<?= $ac ?>"><?= strtoupper(substr($u['nombre'],0,1).substr($u['apellido'],0,1)) ?></div>
                    <span style="font-weight:600"><?= htmlspecialchars($u['nombre'].' '.$u['apellido']) ?></span>
                </div>
            </td>
            <td style="font-size:12px;color:var(--text-muted)"><?= htmlspecialchars($u['nombre_grupo'] ?? '—') ?></td>
            <td><span class="estado-badge" style="background:#dbeafe;color:#1e40af"><?= $u['n_tours'] ?> tour(s)</span></td>
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


<!-- ════ ALMACÉN ════ -->
<div class="section-divider"><i class="bi bi-box-seam me-1"></i>Almacén</div>

<div class="kb-card">
    <div class="kb-card-header">
        <div class="section-title">
            <span class="section-icon" style="background:#dc2626"><i class="bi bi-box-seam-fill"></i></span>
            Artículos en Campo (sin devolver)
        </div>
        <span class="estado-badge estado-pendiente"><?= $almacenUso ?> unidades</span>
    </div>
    <?php
    $almRows=[];
    while($a=mysqli_fetch_assoc($qAlm)) $almRows[]=$a;
    ?>
    <?php if(empty($almRows)): ?>
        <div class="empty-state"><i class="bi bi-box2"></i>Todo devuelto</div>
    <?php else: ?>
    <div style="overflow-x:auto">
    <table class="kb-table">
        <thead><tr><th>Artículo</th><th>Tipo</th><th>Talla</th><th>Cantidad</th><th>Guía</th><th>Fecha salida</th><th>Estado</th><th>Garantía</th></tr></thead>
        <tbody>
        <?php foreach($almRows as $a): ?>
        <tr>
            <td style="font-weight:600"><?= htmlspecialchars($a['nombre_item']) ?></td>
            <td><span class="tipo-badge tipo-tour"><?= htmlspecialchars($a['tipo']) ?></span></td>
            <td style="font-family:'DM Mono',monospace;font-size:12px"><?= htmlspecialchars($a['talla'] ?? '—') ?></td>
            <td><strong><?= $a['cantidad'] ?></strong></td>
            <td><?= htmlspecialchars($a['nombre_guia'] ?? '—') ?></td>
            <td style="font-family:'DM Mono',monospace;font-size:12px"><?= $a['fecha_salida'] ? date('d/m/Y',strtotime($a['fecha_salida'])) : '—' ?></td>
            <td>
                <?php
                $ec = ['Pendiente'=>'estado-pendiente','Parcial'=>'estado-pendiente','Devuelto'=>'estado-confirmado'];
                echo '<span class="estado-badge '.($ec[$a['estado']] ?? 'estado-pendiente').'">'.htmlspecialchars($a['estado']).'</span>';
                ?>
            </td>
            <td><span class="monto-val monto-soles">S/ <?= number_format($a['garantia_original'],2) ?></span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

</div><!-- /.main-content -->
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const COLORS = ['#1a56db','#16a34a','#d97706','#dc2626','#7c3aed','#0891b2','#db2777','#374151'];

/* ── INGRESOS 30 DÍAS ── */
new Chart(document.getElementById('chartIngresos'), {
    type: 'line',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [{
            label: 'Ingresos (S/)',
            data: <?= json_encode($chartData) ?>,
            tension: 0.4,
            fill: true,
            backgroundColor: 'rgba(26,86,219,.08)',
            borderColor: '#1a56db',
            borderWidth: 2,
            pointRadius: 3,
            pointBackgroundColor: '#1a56db',
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: '#e2e8f0' }, ticks: { color: '#64748b', font: { family: 'DM Sans', size: 11 }, callback: v => 'S/ '+v.toLocaleString() } },
            x: { grid: { display: false }, ticks: { color: '#64748b', font: { family: 'DM Sans', size: 10 } } }
        }
    }
});

/* ── DONUT SERVICIOS ── */
new Chart(document.getElementById('chartDonut'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($donutLabels) ?>,
        datasets: [{ data: <?= json_encode($donutData) ?>, backgroundColor: COLORS, borderWidth: 2, borderColor: '#fff' }]
    },
    options: {
        responsive: true, maintainAspectRatio: false, cutout: '65%',
        plugins: { legend: { position: 'bottom', labels: { font: { family: 'DM Sans', size: 10 }, padding: 8, boxWidth: 10 } } }
    }
});

/* ── OPERACIONES POR MES ── */
new Chart(document.getElementById('chartMeses'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($barLabels) ?>,
        datasets: [{ label: 'Operaciones', data: <?= json_encode($barData) ?>, backgroundColor: 'rgba(124,58,237,.15)', borderColor: '#7c3aed', borderWidth: 2, borderRadius: 6, borderSkipped: false }]
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

/* ── MÉTODOS DE PAGO ── */
new Chart(document.getElementById('chartMetodos'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($metLabels) ?>,
        datasets: [{ label: 'Total (S/)', data: <?= json_encode($metData) ?>, backgroundColor: COLORS.map(c => c+'33'), borderColor: COLORS, borderWidth: 2, borderRadius: 6, borderSkipped: false }]
    },
    options: {
        indexAxis: 'y',
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { beginAtZero: true, grid: { color: '#e2e8f0' }, ticks: { color: '#64748b', font: { family: 'DM Sans', size: 11 }, callback: v => 'S/ '+v.toLocaleString() } },
            y: { grid: { display: false }, ticks: { color: '#64748b', font: { family: 'DM Sans', size: 11 } } }
        }
    }
});
</script>
</body>
</html>
