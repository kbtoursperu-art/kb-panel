<?php include '../../conexion.php'; ?>
<?php
session_start();

if(!isset($_SESSION['usuario'])){
    header("Location: login.php");
}
?>

<?php
// ── KPIs ──
$total_stock = mysqli_fetch_row(mysqli_query($conexion,"
    SELECT COALESCE(SUM(cantidad_total),0) FROM almacen_stock
"))[0] ?? 0;

$disponible = mysqli_fetch_row(mysqli_query($conexion,"
    SELECT COALESCE(SUM(cantidad_disponible),0) FROM almacen_stock
"))[0] ?? 0;

$en_uso = mysqli_fetch_row(mysqli_query($conexion,"
    SELECT COALESCE(SUM(cantidad),0) FROM almacen_salidas WHERE estado IN ('Pendiente','Parcial')
"))[0] ?? 0;

$pendientes = mysqli_fetch_row(mysqli_query($conexion,"
    SELECT COUNT(*) FROM almacen_salidas WHERE estado!='Devuelto'
"))[0] ?? 0;

$garantias = mysqli_fetch_row(mysqli_query($conexion,"
    SELECT COALESCE(SUM(garantia_original),0) FROM almacen_salidas WHERE estado!='Devuelto'
"))[0] ?? 0;

$devueltos_hoy = mysqli_fetch_row(mysqli_query($conexion,"
    SELECT COUNT(*) FROM almacen_devoluciones WHERE fecha_devolucion = CURDATE()
"))[0] ?? 0;

// ── STOCK GENERAL ──
$stock_res = mysqli_query($conexion,"
    SELECT i.nombre AS producto, i.tipo, st.talla,
           st.cantidad_total, st.cantidad_disponible,
           (st.cantidad_total - st.cantidad_disponible) AS en_uso
    FROM almacen_stock st
    JOIN almacen_items i ON st.id_item = i.id_item
    ORDER BY i.nombre, st.talla
");

// ── SALIDAS PENDIENTES RECIENTES ──
$salidas_res = mysqli_query($conexion,"
    SELECT s.id_salida, s.nombre_guia, s.cantidad, s.fecha_salida,
           s.garantia_original, s.estado,
           i.nombre AS producto, st.talla
    FROM almacen_salidas s
    JOIN almacen_stock st ON st.id_stock = s.id_stock
    JOIN almacen_items i ON i.id_item = st.id_item
    WHERE s.estado != 'Devuelto'
    ORDER BY s.fecha_salida DESC
    LIMIT 10
");

// ── ALERTAS: stock bajo (disponible < 20% del total) ──
$alertas_res = mysqli_query($conexion,"
    SELECT i.nombre, st.talla, st.cantidad_disponible, st.cantidad_total
    FROM almacen_stock st
    JOIN almacen_items i ON i.id_item = st.id_item
    WHERE st.cantidad_total > 0
      AND (st.cantidad_disponible / st.cantidad_total) < 0.20
    ORDER BY (st.cantidad_disponible / st.cantidad_total) ASC
");
$alertas = mysqli_fetch_all($alertas_res, MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Almacén — KB Tours</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">

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
}
.page-header .subtitle { color: var(--text-muted); font-size: 13px; margin: 0; }

.main-content { max-width: 1440px; margin: 0 auto; padding: 28px 24px 80px; }

/* ─── KPI GRID ─── */
.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 14px;
    margin-bottom: 28px;
}
.kpi-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 18px 18px 14px;
    box-shadow: var(--shadow);
    position: relative;
    overflow: hidden;
    transition: transform .15s, box-shadow .15s;
    cursor: default;
}
.kpi-card:hover { transform: translateY(-2px); box-shadow: var(--shadow-md); }
.kpi-icon {
    width: 38px; height: 38px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 17px; margin-bottom: 12px;
}
.kpi-label {
    font-size: 10px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .7px; color: var(--text-muted); margin-bottom: 4px;
}
.kpi-value {
    font-family: 'Outfit', sans-serif;
    font-size: 26px; font-weight: 700; line-height: 1;
}
.kpi-sub { font-size: 11px; color: var(--text-muted); margin-top: 4px; }
.kpi-accent { position: absolute; bottom: 0; left: 0; right: 0; height: 3px; }

/* ─── ACCESOS RÁPIDOS ─── */
.quick-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 12px;
    margin-bottom: 28px;
}
.quick-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 20px 16px;
    text-align: center;
    text-decoration: none;
    color: var(--text);
    box-shadow: var(--shadow);
    transition: all .15s;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
}
.quick-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-md);
    border-color: var(--brand);
    color: var(--brand);
}
.quick-icon {
    width: 48px; height: 48px; border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px; color: #fff;
    transition: transform .15s;
}
.quick-card:hover .quick-icon { transform: scale(1.08); }
.quick-label { font-size: 13px; font-weight: 600; line-height: 1.3; }
.quick-desc  { font-size: 11px; color: var(--text-muted); }

/* ─── CARDS ─── */
.kb-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    margin-bottom: 24px;
    overflow: hidden;
}
.kb-card-header {
    padding: 14px 20px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--surface-2);
}
.section-title {
    display: flex; align-items: center; gap: 8px;
    font-family: 'Outfit', sans-serif;
    font-size: 13px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .7px;
}
.section-icon {
    width: 28px; height: 28px; border-radius: 7px;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; color: #fff;
}

/* ─── TABLA ─── */
table.dataTable thead th,
.kb-table thead th {
    background: var(--surface-2) !important;
    font-size: 10px !important; font-weight: 700 !important;
    text-transform: uppercase; letter-spacing: .7px;
    color: var(--text-muted) !important;
    border-bottom: 2px solid var(--border) !important;
    padding: 10px 14px !important; white-space: nowrap;
}
table.dataTable tbody td,
.kb-table tbody td {
    padding: 11px 14px !important;
    vertical-align: middle !important;
    border-bottom: 1px solid var(--border) !important;
    font-size: 13px !important;
}
table.dataTable tbody tr:hover,
.kb-table tbody tr:hover { background: var(--surface-2) !important; }
table.dataTable { border-collapse: separate !important; border-spacing: 0 !important; }

.dataTables_wrapper .dataTables_filter input {
    border: 1px solid var(--border); border-radius: 8px;
    padding: 6px 12px; font-family: 'DM Sans',sans-serif;
    font-size: 13px; background: var(--surface-2);
    outline: none; color: var(--text);
}
.dataTables_wrapper .dataTables_filter input:focus { border-color: var(--brand); }
.dataTables_wrapper .dataTables_length select {
    border: 1px solid var(--border); border-radius: 8px;
    padding: 5px 10px; font-size: 13px; background: var(--surface-2);
}
.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background: var(--brand) !important; color: #fff !important;
    border-radius: 6px !important; border: none !important;
}
.dataTables_wrapper .dataTables_info { font-size: 12px; color: var(--text-muted); }

/* ─── BADGES ─── */
.badge-tipo {
    display: inline-block;
    padding: 2px 10px; border-radius: 20px;
    font-size: 10px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .5px;
}
.badge-consumible { background:#dbeafe; color:#1e40af; }
.badge-retornable { background:#dcfce7; color:#166534; }
.badge-garantia   { background:#fef3c7; color:#92400e; }

.estado-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 10px; border-radius: 20px;
    font-size: 11px; font-weight: 600;
    text-transform: uppercase; letter-spacing: .5px;
}
.est-pendiente { background:#fef9c3; color:#a16207; }
.est-parcial   { background:#e0f2fe; color:#0369a1; }
.est-devuelto  { background:#dcfce7; color:#15803d; }

/* ─── PROGRESS BAR STOCK ─── */
.stock-bar-wrap { display: flex; align-items: center; gap: 8px; }
.stock-bar {
    flex: 1; height: 6px; background: var(--surface-3);
    border-radius: 99px; overflow: hidden;
}
.stock-bar-fill { height: 100%; border-radius: 99px; transition: width .3s; }
.stock-pct { font-family:'DM Mono',monospace; font-size:11px; color:var(--text-muted); min-width:32px; }

/* ─── ALERTA STOCK BAJO ─── */
.alert-stock {
    background: linear-gradient(135deg,#fef3c7,#fef9c3);
    border: 1px solid #fde68a;
    border-radius: var(--radius);
    padding: 14px 18px;
    margin-bottom: 24px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}
.alert-stock-icon { font-size: 20px; color: #d97706; flex-shrink: 0; margin-top: 2px; }
.alert-stock-title { font-weight: 700; font-size: 13px; color: #92400e; margin-bottom: 4px; }
.alert-stock-items { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 6px; }
.alert-chip {
    background: #fff; border: 1px solid #fde68a;
    border-radius: 8px; padding: 3px 10px;
    font-size: 11px; font-weight: 600; color: #92400e;
}

/* ─── MONTO ─── */
.monto-garantia { font-family:'DM Mono',monospace; font-size:12px; font-weight:600; color:#dc2626; }

/* ─── BTN ─── */
.btn-kb {
    padding: 7px 16px; border-radius: 8px;
    font-size: 12px; font-weight: 600;
    font-family: 'DM Sans',sans-serif;
    cursor: pointer; border: none;
    transition: all .15s;
    display: inline-flex; align-items: center; gap: 6px;
    text-decoration: none;
}
.btn-primary-kb { background: var(--brand); color: #fff; }
.btn-primary-kb:hover { background: var(--brand-dark); color:#fff; }
.btn-outline-kb { background: transparent; color: var(--text-muted); border: 1px solid var(--border); }
.btn-outline-kb:hover { background: var(--surface-2); color: var(--text); }
.btn-danger-kb  { background: var(--danger); color: #fff; }
.btn-danger-kb:hover { background: #b91c1c; color:#fff; }
.btn-success-kb { background: var(--success); color: #fff; }
.btn-success-kb:hover { background: #15803d; color:#fff; }

/* ─── DOS COLUMNAS ─── */
.two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
@media(max-width:900px) { .two-col { grid-template-columns: 1fr; } }

@media(max-width:768px) {
    .page-header { padding: 14px 16px; }
    .main-content { padding: 14px 12px 60px; }
    .kpi-grid { grid-template-columns: repeat(2,1fr); }
    .quick-grid { grid-template-columns: repeat(2,1fr); }
}
</style>
</head>
<body>
<?php include '../sidebar.php'; ?>
<div class="kb-content">

<!-- ═══ PAGE HEADER ═══ -->
<div class="page-header">
    <div>
        <h1><i class="bi bi-boxes text-primary me-2"></i>Almacén</h1>
        <p class="subtitle">Control de stock, salidas y garantías · KB Tours</p>
    </div>
    <div class="d-flex gap-2">
        <a href="ingreso.php" class="btn-kb btn-success-kb">
            <i class="bi bi-plus-circle"></i> Ingreso Stock
        </a>
        <a href="salida.php" class="btn-kb btn-primary-kb">
            <i class="bi bi-box-arrow-right"></i> Nueva Salida
        </a>
    </div>
</div>

<div class="main-content">

<!-- ═══ ALERTAS STOCK BAJO ═══ -->
<?php if (!empty($alertas)): ?>
<div class="alert-stock">
    <i class="bi bi-exclamation-triangle-fill alert-stock-icon"></i>
    <div>
        <div class="alert-stock-title">
            ⚠️ <?= count($alertas) ?> producto(s) con stock crítico (menos del 20% disponible)
        </div>
        <div style="font-size:12px;color:#92400e">Revisa el inventario y realiza reposición pronto.</div>
        <div class="alert-stock-items">
            <?php foreach ($alertas as $a): ?>
            <span class="alert-chip">
                <?= htmlspecialchars($a['nombre']) ?>
                <?= $a['talla'] ? '· '.$a['talla'] : '' ?>
                — <?= $a['cantidad_disponible'] ?>/<?= $a['cantidad_total'] ?>
            </span>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($pendientes > 0): ?>
<div style="background:linear-gradient(135deg,#fee2e2,#fef2f2);border:1px solid #fca5a5;
            border-radius:var(--radius);padding:12px 18px;margin-bottom:24px;
            display:flex;align-items:center;gap:10px;font-size:13px;color:#991b1b">
    <i class="bi bi-clock-history" style="font-size:18px;flex-shrink:0"></i>
    <div>
        <strong><?= $pendientes ?> salida(s)</strong> con devolución pendiente ·
        Garantía retenida: <strong>S/ <?= number_format($garantias, 2) ?></strong>
        <a href="pendientes.php" class="ms-2" style="color:#dc2626;font-weight:600;font-size:12px">
            Ver detalle →
        </a>
    </div>
</div>
<?php endif; ?>

<!-- ═══ KPIs ═══ -->
<div class="kpi-grid">

    <div class="kpi-card">
        <div class="kpi-icon" style="background:#dbeafe;color:#1a56db">
            <i class="bi bi-box-seam-fill"></i>
        </div>
        <div class="kpi-label">Stock Total</div>
        <div class="kpi-value" style="color:#1a56db"><?= number_format($total_stock) ?></div>
        <div class="kpi-sub">unidades totales</div>
        <div class="kpi-accent" style="background:#1a56db"></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon" style="background:#dcfce7;color:#16a34a">
            <i class="bi bi-check-circle-fill"></i>
        </div>
        <div class="kpi-label">Disponible</div>
        <div class="kpi-value" style="color:#16a34a"><?= number_format($disponible) ?></div>
        <div class="kpi-sub">listo para salida</div>
        <div class="kpi-accent" style="background:#16a34a"></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon" style="background:#fef3c7;color:#d97706">
            <i class="bi bi-arrow-repeat"></i>
        </div>
        <div class="kpi-label">En Uso</div>
        <div class="kpi-value" style="color:#d97706"><?= number_format($en_uso) ?></div>
        <div class="kpi-sub">con guías activos</div>
        <div class="kpi-accent" style="background:#d97706"></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon" style="background:#fee2e2;color:#dc2626">
            <i class="bi bi-clock-history"></i>
        </div>
        <div class="kpi-label">Pendientes</div>
        <div class="kpi-value" style="color:#dc2626"><?= number_format($pendientes) ?></div>
        <div class="kpi-sub">sin devolver</div>
        <div class="kpi-accent" style="background:#dc2626"></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon" style="background:#fee2e2;color:#b91c1c">
            <i class="bi bi-cash-stack"></i>
        </div>
        <div class="kpi-label">Garantía</div>
        <div class="kpi-value" style="font-size:17px;color:#b91c1c">
            S/ <?= number_format($garantias, 0) ?>
        </div>
        <div class="kpi-sub">retenida activa</div>
        <div class="kpi-accent" style="background:#b91c1c"></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon" style="background:#f3e8ff;color:#7c3aed">
            <i class="bi bi-arrow-down-circle-fill"></i>
        </div>
        <div class="kpi-label">Devueltos Hoy</div>
        <div class="kpi-value" style="color:#7c3aed"><?= number_format($devueltos_hoy) ?></div>
        <div class="kpi-sub">devoluciones hoy</div>
        <div class="kpi-accent" style="background:#7c3aed"></div>
    </div>

</div>

<!-- ═══ ACCESOS RÁPIDOS ═══ -->
<div class="quick-grid">

    <a href="ingreso.php" class="quick-card">
        <div class="quick-icon" style="background:#16a34a">
            <i class="bi bi-plus-circle-fill"></i>
        </div>
        <div>
            <div class="quick-label">Ingreso Stock</div>
            <div class="quick-desc">Registrar entrada de productos</div>
        </div>
    </a>

    <a href="salida.php" class="quick-card">
        <div class="quick-icon" style="background:#1a56db">
            <i class="bi bi-box-arrow-right"></i>
        </div>
        <div>
            <div class="quick-label">Salida a Guías</div>
            <div class="quick-desc">Entregar equipo a guías</div>
        </div>
    </a>

    <a href="pendientes.php" class="quick-card">
        <div class="quick-icon" style="background:#d97706">
            <i class="bi bi-clock-history"></i>
        </div>
        <div>
            <div class="quick-label">Devoluciones</div>
            <div class="quick-desc">Gestionar retornos pendientes</div>
        </div>
    </a>

    <a href="reporte_garantias_guias.php" class="quick-card">
        <div class="quick-icon" style="background:#dc2626">
            <i class="bi bi-shield-lock-fill"></i>
        </div>
        <div>
            <div class="quick-label">Garantías por Guía</div>
            <div class="quick-desc">Ver saldos de garantía activos</div>
        </div>
    </a>

    <a href="../historial_garantias.php" class="quick-card">
        <div class="quick-icon" style="background:#7c3aed">
            <i class="bi bi-journal-text"></i>
        </div>
        <div>
            <div class="quick-label">Historial</div>
            <div class="quick-desc">Devoluciones registradas</div>
        </div>
    </a>

    <a href="../reporte_garantias_mensual.php" class="quick-card">
        <div class="quick-icon" style="background:#0891b2">
            <i class="bi bi-bar-chart-fill"></i>
        </div>
        <div>
            <div class="quick-label">Reporte Mensual</div>
            <div class="quick-desc">Garantías por período</div>
        </div>
    </a>

</div>

<!-- ═══ DOS COLUMNAS: STOCK + SALIDAS PENDIENTES ═══ -->
<div class="two-col">

    <!-- STOCK GENERAL -->
    <div class="kb-card">
        <div class="kb-card-header">
            <div class="section-title">
                <span class="section-icon" style="background:#1a56db">
                    <i class="bi bi-boxes"></i>
                </span>
                Stock General
            </div>
            <a href="ingreso.php" class="btn-kb btn-outline-kb" style="font-size:11px;padding:5px 12px">
                <i class="bi bi-plus"></i> Agregar
            </a>
        </div>
        <div style="overflow-x:auto">
            <table id="tablaStock" class="table align-middle" style="width:100%;margin:0">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Tipo</th>
                        <th>Talla</th>
                        <th style="text-align:center">Total</th>
                        <th>Disponibilidad</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($s = mysqli_fetch_assoc($stock_res)): ?>
                <?php
                    $pct = $s['cantidad_total'] > 0
                        ? round(($s['cantidad_disponible'] / $s['cantidad_total']) * 100)
                        : 0;
                    $bar_color = $pct >= 60 ? '#16a34a' : ($pct >= 30 ? '#d97706' : '#dc2626');
                    $tipo_class = match(strtolower($s['tipo'] ?? '')) {
                        'consumible' => 'badge-consumible',
                        'retornable' => 'badge-retornable',
                        'garantia'   => 'badge-garantia',
                        default      => 'badge-consumible',
                    };
                ?>
                <tr>
                    <td>
                        <div style="font-weight:600;font-size:13px"><?= htmlspecialchars($s['producto']) ?></div>
                    </td>
                    <td>
                        <span class="badge-tipo <?= $tipo_class ?>">
                            <?= htmlspecialchars($s['tipo'] ?? '—') ?>
                        </span>
                    </td>
                    <td style="font-family:'DM Mono',monospace;font-size:12px">
                        <?= $s['talla'] ? htmlspecialchars($s['talla']) : '—' ?>
                    </td>
                    <td style="text-align:center;font-family:'DM Mono',monospace;font-size:12px;font-weight:600">
                        <?= $s['cantidad_disponible'] ?><span style="color:var(--text-muted)"> / <?= $s['cantidad_total'] ?></span>
                    </td>
                    <td style="min-width:120px">
                        <div class="stock-bar-wrap">
                            <div class="stock-bar">
                                <div class="stock-bar-fill"
                                     style="width:<?= $pct ?>%;background:<?= $bar_color ?>"></div>
                            </div>
                            <span class="stock-pct"><?= $pct ?>%</span>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- SALIDAS PENDIENTES -->
    <div class="kb-card">
        <div class="kb-card-header">
            <div class="section-title">
                <span class="section-icon" style="background:#d97706">
                    <i class="bi bi-clock-history"></i>
                </span>
                Salidas Pendientes
            </div>
            <a href="pendientes.php" class="btn-kb btn-outline-kb" style="font-size:11px;padding:5px 12px">
                Ver todas
            </a>
        </div>
        <div style="overflow-x:auto">
            <table class="table align-middle kb-table" style="width:100%;margin:0">
                <thead>
                    <tr>
                        <th>Guía</th>
                        <th>Producto</th>
                        <th style="text-align:center">Cant.</th>
                        <th>Fecha</th>
                        <th>Garantía</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $tiene_salidas = false;
                while ($sal = mysqli_fetch_assoc($salidas_res)):
                    $tiene_salidas = true;
                    $est_class = match($sal['estado']) {
                        'Parcial'   => 'est-parcial',
                        'Devuelto'  => 'est-devuelto',
                        default     => 'est-pendiente',
                    };
                ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:7px">
                            <div style="width:28px;height:28px;border-radius:50%;
                                        background:var(--brand-light);color:var(--brand-dark);
                                        display:flex;align-items:center;justify-content:center;
                                        font-size:10px;font-weight:700;flex-shrink:0">
                                <?= strtoupper(substr($sal['nombre_guia'], 0, 2)) ?>
                            </div>
                            <span style="font-weight:600;font-size:12px">
                                <?= htmlspecialchars($sal['nombre_guia']) ?>
                            </span>
                        </div>
                    </td>
                    <td style="font-size:12px">
                        <?= htmlspecialchars($sal['producto']) ?>
                        <?= $sal['talla'] ? '<span style="color:var(--text-muted)"> · '.$sal['talla'].'</span>' : '' ?>
                    </td>
                    <td style="text-align:center;font-family:'DM Mono',monospace;font-weight:600">
                        <?= $sal['cantidad'] ?>
                    </td>
                    <td style="font-family:'DM Mono',monospace;font-size:11px;white-space:nowrap">
                        <?= date('d/m/Y', strtotime($sal['fecha_salida'])) ?>
                    </td>
                    <td>
                        <?php if ($sal['garantia_original'] > 0): ?>
                        <span class="monto-garantia">
                            S/ <?= number_format($sal['garantia_original'], 2) ?>
                        </span>
                        <?php else: ?>
                        <span style="color:var(--text-muted)">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="estado-badge <?= $est_class ?>">
                            <?= $sal['estado'] ?>
                        </span>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if (!$tiene_salidas): ?>
                <tr>
                    <td colspan="6" style="text-align:center;padding:30px;color:var(--text-muted)">
                        <i class="bi bi-check-circle" style="font-size:24px;display:block;margin-bottom:6px;color:#16a34a"></i>
                        Sin salidas pendientes
                    </td>
                </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- /.two-col -->

</div><!-- /.main-content -->

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function () {
    $('#tablaStock').DataTable({
        responsive: false,
        pageLength: 10,
        order: [[0,'asc']],
        language: { url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/Spanish.json' },
        dom: '<"d-flex align-items-center justify-content-between mb-3 px-3 pt-3"f>rtip',
        columnDefs: [{ orderable: false, targets: [4] }]
    });
});
</script>
</body>
</html>