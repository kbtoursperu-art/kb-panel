<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../../../conexion.php';

if (!isset($_GET['id_grupo'])) {
    die("Falta id_grupo");
}

$id_grupo = intval($_GET['id_grupo']);

/* ================= GRUPO ================= */
$qGrupo = mysqli_query($conexion,"
    SELECT g.*, 
           COUNT(DISTINCT cg.id_cliente) AS total_clientes
    FROM grupos g
    LEFT JOIN clientes_grupo cg ON cg.id_grupo = g.id_grupo
    WHERE g.id_grupo = $id_grupo
    GROUP BY g.id_grupo
");
$grupo = mysqli_fetch_assoc($qGrupo);
if (!$grupo) die("Grupo no encontrado.");

/* ================= CLIENTES ================= */
$qClientes = mysqli_query($conexion,"
    SELECT 
        d.id_cliente,
        d.nombre,
        d.apellido,
        d.dni,
        d.telefono,
        d.email,
        d.nacionalidad,
        d.hotel,
        d.comida,
        d.genero,
        d.fecha_nacimiento,
        cg.es_pagador,
        cg.tipo_cliente,
        cg.empresa_endosadora
    FROM clientes_grupo cg
    JOIN datos_clientes d ON d.id_cliente = cg.id_cliente
    WHERE cg.id_grupo = $id_grupo
    ORDER BY cg.es_pagador DESC, d.apellido ASC
");

/* ================= ULTIMA OPERACION ================= */
$qOperacion = mysqli_query($conexion,"
    SELECT o.*,
           dc.nombre AS nombre_cliente,
           dc.apellido AS apellido_cliente
    FROM operaciones o
    JOIN datos_clientes dc ON dc.id_cliente = o.id_cliente
    WHERE o.id_grupo = $id_grupo
    ORDER BY o.id_operaciones DESC
    LIMIT 1
");
$op = mysqli_fetch_assoc($qOperacion);
$id_operacion = $op ? intval($op['id_operaciones']) : 0;

/* ================= CONTABILIDAD ================= */
$conta = [];
if ($id_operacion > 0) {
    $qConta = mysqli_query($conexion,"
        SELECT * FROM contabilidad
        WHERE id_operaciones = $id_operacion
        ORDER BY id_contabilidad DESC
        LIMIT 1
    ");
    $conta = mysqli_fetch_assoc($qConta) ?: [];
    if ($conta) $op = array_merge($op, $conta);
}

/* ================= DETALLE DE TOURS ================= */
$qDetalle = mysqli_query($conexion,"
    SELECT 
        od.*,
        s.nombre AS nombre_servicio,
        s.duracion_dias,
        DATEDIFF(od.fecha_retorno, od.fecha_salida) AS dias_calculados
    FROM operaciones_detalle od
    LEFT JOIN servicios s ON s.id_servicio = od.id_servicio
    WHERE od.id_operaciones = $id_operacion
    ORDER BY od.fecha_salida ASC
");

/* ================= ADICIONALES POR DETALLE ================= */
$adicionales = [];
if ($id_operacion > 0) {
    $qAdi = mysqli_query($conexion,"
        SELECT ad.*, od.id_operaciones
        FROM adicionales_detalle ad
        JOIN operaciones_detalle od ON od.id_detalle = ad.id_detalle
        WHERE od.id_operaciones = $id_operacion
    ");
    while ($a = mysqli_fetch_assoc($qAdi)) {
        $adicionales[$a['id_detalle']][] = $a;
    }
}

/* ================= PAGOS (todos) ================= */
$qPagos = mysqli_query($conexion,"
    SELECT * FROM pagos
    WHERE id_operaciones = $id_operacion
    ORDER BY fecha ASC, id_pago ASC
");
$todos_pagos = [];
while ($p = mysqli_fetch_assoc($qPagos)) {
    $todos_pagos[] = $p;
}

/* ================= TOTALES ================= */
$total_pagado = 0;
$total_soles   = 0;
$total_dolares = 0;
foreach ($todos_pagos as $p) {
    if (!in_array($p['tipo'], ['reembolso'])) {
        if ($p['moneda'] === 'Soles')   $total_soles   += $p['monto'];
        if ($p['moneda'] === 'Dólares') $total_dolares += $p['monto'];
    }
}

/* ================= PLANIFICACION ================= */
$qPlan = mysqli_query($conexion,"
    SELECT * FROM planificacion
    WHERE id_grupo = $id_grupo
    ORDER BY id_planificacion DESC
    LIMIT 1
");
$plan = mysqli_fetch_assoc($qPlan) ?: [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Grupo <?= htmlspecialchars($grupo['nombre_grupo']) ?> — Detalle Completo</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
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

/* ── TOP HEADER ── */
.page-header {
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    padding: 18px 32px;
    display: flex;
    align-items: center;
    gap: 16px;
    position: sticky;
    top: 0;
    z-index: 100;
}
.page-header .back-btn {
    display: flex; align-items: center; gap: 6px;
    color: var(--text-muted); text-decoration: none;
    font-size: 13px; font-weight: 500;
    padding: 6px 12px; border-radius: 8px;
    border: 1px solid var(--border);
    transition: all .15s;
}
.page-header .back-btn:hover { background: var(--surface-2); color: var(--text); }
.page-header h1 { font-size: 18px; font-weight: 700; margin: 0; }
.page-header .subtitle { color: var(--text-muted); font-size: 13px; margin: 0; }

/* ── ESTADO BADGE ── */
.estado-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 12px; border-radius: 20px;
    font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px;
}
.estado-abierto   { background: #dcfce7; color: #15803d; }
.estado-cerrado   { background: #fee2e2; color: #b91c1c; }
.estado-pendiente { background: #fef9c3; color: #a16207; }
.estado-confirmado{ background: #dcfce7; color: #15803d; }
.estado-cancelado { background: #fee2e2; color: #b91c1c; }
.estado-pagado    { background: #dcfce7; color: #15803d; }

/* ── LAYOUT ── */
.main-content { max-width: 1200px; margin: 0 auto; padding: 28px 24px 60px; }

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
.kb-card-header .section-icon {
    width: 28px; height: 28px; border-radius: 7px;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; color: #fff;
}
.kb-card-body { padding: 20px; }

/* ── STAT CARDS ── */
.stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; margin-bottom: 24px; }
.stat-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius);
    padding: 16px 20px;
    box-shadow: var(--shadow);
}
.stat-card .stat-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .7px; color: var(--text-muted); margin-bottom: 6px; }
.stat-card .stat-value { font-size: 22px; font-weight: 700; line-height: 1; }
.stat-card .stat-sub { font-size: 11px; color: var(--text-muted); margin-top: 4px; }

/* ── TABLES ── */
.kb-table { width: 100%; border-collapse: separate; border-spacing: 0; }
.kb-table thead th {
    background: var(--surface-2);
    padding: 10px 14px;
    font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .6px;
    color: var(--text-muted);
    border-bottom: 1px solid var(--border);
    white-space: nowrap;
}
.kb-table tbody td {
    padding: 12px 14px;
    border-bottom: 1px solid var(--border);
    vertical-align: middle;
}
.kb-table tbody tr:last-child td { border-bottom: none; }
.kb-table tbody tr:hover { background: #f8fafc; }

/* ── TOUR CARD ── */
.tour-card {
    border: 1px solid var(--border);
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 16px;
    background: var(--surface);
}
.tour-card-header {
    background: var(--brand);
    color: #fff;
    padding: 12px 18px;
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 8px;
}
.tour-card-header .tour-name { font-size: 14px; font-weight: 700; display: flex; align-items: center; gap: 8px; }
.tour-card-header .tour-meta { display: flex; gap: 12px; flex-wrap: wrap; }
.tour-card-header .tour-meta span { font-size: 12px; opacity: .9; display: flex; align-items: center; gap: 4px; }
.tour-card-body { padding: 16px 18px; }

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 14px;
}
.info-item { display: flex; flex-direction: column; gap: 3px; }
.info-item .info-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; color: var(--text-muted); }
.info-item .info-value { font-size: 13px; font-weight: 500; color: var(--text); }

/* ── ADICIONALES ── */
.adicionales-list { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
.adicional-chip {
    display: flex; align-items: center; gap: 6px;
    padding: 4px 10px; background: #fffbeb; border: 1px solid #fde68a;
    border-radius: 6px; font-size: 12px; font-weight: 500; color: #92400e;
}

/* ── PAGO BADGE ── */
.tipo-badge {
    display: inline-block; padding: 2px 8px;
    border-radius: 5px; font-size: 11px; font-weight: 600; text-transform: uppercase;
}
.tipo-tour     { background:#dbeafe; color:#1e40af; }
.tipo-adicional{ background:#fef3c7; color:#92400e; }
.tipo-cuenta   { background:#dcfce7; color:#15803d; }
.tipo-saldo    { background:#f3e8ff; color:#6b21a8; }
.tipo-reembolso{ background:#fee2e2; color:#b91c1c; }

/* ── MONTO ── */
.monto-val { font-family: 'DM Mono', monospace; font-weight: 500; font-size: 13px; }
.monto-soles   { color: #1e40af; }
.monto-dolares { color: #166534; }
.monto-neg     { color: #b91c1c; }

/* ── CLIENTE ROW ── */
.client-avatar {
    width: 34px; height: 34px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 700; color: #fff; flex-shrink: 0;
}
.client-name { font-weight: 600; font-size: 13px; }
.client-sub  { font-size: 11px; color: var(--text-muted); }

/* ── TOTALES ── */
.totales-box {
    display: flex; gap: 16px; flex-wrap: wrap;
    padding: 14px 18px;
    background: var(--surface-2);
    border-top: 1px solid var(--border);
}
.total-item { display: flex; flex-direction: column; gap: 2px; }
.total-item .t-label { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; color: var(--text-muted); }
.total-item .t-value { font-size: 17px; font-weight: 700; font-family: 'DM Mono', monospace; }

/* ── EMPTY ── */
.empty-state { text-align: center; padding: 32px; color: var(--text-muted); }
.empty-state i { font-size: 32px; opacity: .3; display: block; margin-bottom: 8px; }

/* ── PLANIFICACION ── */
.plan-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px; }
.plan-item { display: flex; align-items: center; gap: 10px; padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; }
.plan-item i { color: var(--brand); font-size: 18px; flex-shrink: 0; }
.plan-item .plan-role { font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: var(--text-muted); }
.plan-item .plan-name { font-weight: 600; font-size: 13px; }

@media (max-width: 640px) {
    .page-header { padding: 14px 16px; }
    .main-content { padding: 16px 12px 40px; }
    .kb-card-body { padding: 14px; }
}
</style>
</head>
<body>

<?php include '../../sidebar.php'; ?>

<!-- ════════════════════════════════════════
     CABECERA
═════════════════════════════════════════ -->
<div class="page-header">
    <a href="javascript:history.back()" class="back-btn">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
    <div>
        <h1 class="mb-0">
            <i class="bi bi-people-fill text-primary me-2"></i>
            <?= htmlspecialchars($grupo['nombre_grupo']) ?>
        </h1>
        <p class="subtitle mb-0">
            ID Grupo: #<?= $id_grupo ?>
            &nbsp;·&nbsp; Creado: <?= date('d/m/Y', strtotime($grupo['fecha_creacion'])) ?>
            <?php if ($id_operacion): ?>
                &nbsp;·&nbsp; Operación: #<?= $id_operacion ?>
            <?php endif; ?>
        </p>
    </div>
    <div class="ms-auto d-flex align-items-center gap-2">
        <span class="estado-badge estado-<?= $grupo['estado'] ?>">
            <i class="bi bi-circle-fill" style="font-size:8px"></i>
            <?= ucfirst($grupo['estado']) ?>
        </span>
        <?php if ($op): ?>
        <span class="estado-badge estado-<?= $op['estado'] ?? 'pendiente' ?>">
            <?= ucfirst($op['estado'] ?? 'pendiente') ?>
        </span>
        <?php endif; ?>
    </div>
</div>

<div class="main-content">

<!-- ════ STATS ════ -->
<?php
$n_tours = mysqli_num_rows(mysqli_query($conexion,"SELECT id_detalle FROM operaciones_detalle WHERE id_operaciones = $id_operacion"));
$n_pagos = count($todos_pagos);
?>
<div class="stats-row">
    <div class="stat-card">
        <div class="stat-label"><i class="bi bi-people me-1"></i>Clientes</div>
        <div class="stat-value text-primary"><?= $grupo['total_clientes'] ?? 0 ?></div>
        <div class="stat-sub">en el grupo</div>
    </div>
    <div class="stat-card">
        <div class="stat-label"><i class="bi bi-map me-1"></i>Tours</div>
        <div class="stat-value text-info"><?= $n_tours ?></div>
        <div class="stat-sub">servicios contratados</div>
    </div>
    <div class="stat-card">
        <div class="stat-label"><i class="bi bi-cash me-1"></i>Soles pagados</div>
        <div class="stat-value text-success">S/ <?= number_format($total_soles, 2) ?></div>
        <div class="stat-sub">total registrado</div>
    </div>
    <div class="stat-card">
        <div class="stat-label"><i class="bi bi-currency-dollar me-1"></i>Dólares pagados</div>
        <div class="stat-value text-success">$ <?= number_format($total_dolares, 2) ?></div>
        <div class="stat-sub">total registrado</div>
    </div>
    <?php if ($op): ?>
    <div class="stat-card">
        <div class="stat-label"><i class="bi bi-receipt me-1"></i>Total operación</div>
        <div class="stat-value"><?= number_format($op['total_operacion'] ?? 0, 2) ?></div>
        <div class="stat-sub"><?= $op['tipo_precio'] ?? '-' ?></div>
    </div>
    <?php endif; ?>
    <?php if ($grupo['hotel']): ?>
    <div class="stat-card">
        <div class="stat-label"><i class="bi bi-building me-1"></i>Hotel grupo</div>
        <div class="stat-value" style="font-size:15px"><?= htmlspecialchars($grupo['hotel']) ?></div>
        <div class="stat-sub">alojamiento</div>
    </div>
    <?php endif; ?>
</div>


<!-- ════ CLIENTES ════ -->
<div class="kb-card">
    <div class="kb-card-header">
        <div class="section-title">
            <span class="section-icon" style="background:#1a56db"><i class="bi bi-people-fill"></i></span>
            Clientes del Grupo
        </div>
        <span class="text-muted" style="font-size:12px"><?= $grupo['total_clientes'] ?? 0 ?> persona(s)</span>
    </div>
    <div class="kb-card-body p-0">
        <table class="kb-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cliente</th>
                    <th>DNI / Pasaporte</th>
                    <th>Nacionalidad</th>
                    <th>Tipo</th>
                    <th>Hotel</th>
                    <th>Comida</th>
                    <th>Pagador</th>
                </tr>
            </thead>
            <tbody>
            <?php $i = 1; while ($c = mysqli_fetch_assoc($qClientes)): ?>
            <?php $colors = ['#1a56db','#0891b2','#16a34a','#d97706','#7c3aed','#db2777']; $col = $colors[($i-1)%count($colors)]; ?>
            <tr>
                <td style="color:var(--text-muted);font-size:12px"><?= $i++ ?></td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="client-avatar" style="background:<?= $col ?>">
                            <?= strtoupper(substr($c['nombre'],0,1).substr($c['apellido'],0,1)) ?>
                        </div>
                        <div>
                            <div class="client-name"><?= htmlspecialchars($c['nombre'].' '.$c['apellido']) ?></div>
                            <div class="client-sub">
                                <?php if ($c['email']): ?><i class="bi bi-envelope"></i> <?= htmlspecialchars($c['email']) ?><?php endif; ?>
                                <?php if ($c['telefono']): ?> &nbsp;<i class="bi bi-telephone"></i> <?= htmlspecialchars($c['telefono']) ?><?php endif; ?>
                            </div>
                        </div>
                    </div>
                </td>
                <td><span style="font-family:'DM Mono',monospace;font-size:12px"><?= htmlspecialchars($c['dni'] ?? '—') ?></span></td>
                <td><?= htmlspecialchars($c['nacionalidad'] ?? '—') ?></td>
                <td>
                    <?php if ($c['tipo_cliente'] === 'ENDOSADOR'): ?>
                        <span class="tipo-badge tipo-adicional">Endosador</span>
                        <?php if ($c['empresa_endosadora']): ?>
                            <div style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($c['empresa_endosadora']) ?></div>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="tipo-badge tipo-tour">KB</span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($c['hotel'] ?? '—') ?></td>
                <td>
                    <?php if ($c['comida']): ?>
                        <span class="adicional-chip"><i class="bi bi-egg-fried"></i> <?= htmlspecialchars($c['comida']) ?></span>
                    <?php else: echo '—'; endif; ?>
                </td>
                <td>
                    <?php echo $c['es_pagador']
                        ? '<span class="estado-badge estado-confirmado"><i class="bi bi-check-circle-fill"></i> Sí</span>'
                        : '<span style="color:var(--text-muted);font-size:12px">No</span>';
                    ?>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>


<!-- ════ TOURS / DETALLE OPERACIONES ════ -->
<div class="kb-card">
    <div class="kb-card-header">
        <div class="section-title">
            <span class="section-icon" style="background:#0891b2"><i class="bi bi-map-fill"></i></span>
            Tours y Servicios Contratados
        </div>
        <?php if ($op): ?>
        <span style="font-size:12px;color:var(--text-muted)">
            Encargado: <strong><?= htmlspecialchars($op['encargado'] ?? '—') ?></strong>
            &nbsp;·&nbsp; Reserva: <?= $op['fecha_reserva'] ? date('d/m/Y', strtotime($op['fecha_reserva'])) : '—' ?>
        </span>
        <?php endif; ?>
    </div>
    <div class="kb-card-body">

    <?php if ($n_tours === 0): ?>
        <div class="empty-state"><i class="bi bi-map"></i>Sin tours registrados</div>
    <?php else:
        // Re-ejecutar query para iterar limpiamente
        $qDet2 = mysqli_query($conexion,"
            SELECT od.*, s.nombre AS nombre_servicio, s.duracion_dias,
                   DATEDIFF(od.fecha_retorno, od.fecha_salida) AS dias_calculados
            FROM operaciones_detalle od
            LEFT JOIN servicios s ON s.id_servicio = od.id_servicio
            WHERE od.id_operaciones = $id_operacion
            ORDER BY od.fecha_salida ASC
        ");
        $num_tour = 1;
        while ($d = mysqli_fetch_assoc($qDet2)):
    ?>
    <div class="tour-card">
        <div class="tour-card-header">
            <div class="tour-name">
                <i class="bi bi-compass-fill"></i>
                Tour <?= $num_tour++ ?> — <?= htmlspecialchars($d['nombre_servicio'] ?? 'Servicio #'.$d['id_servicio']) ?>
            </div>
            <div class="tour-meta">
                <?php if ($d['fecha_salida']): ?>
                <span><i class="bi bi-calendar-event"></i> Salida: <?= date('d/m/Y', strtotime($d['fecha_salida'])) ?></span>
                <?php endif; ?>
                <?php if ($d['fecha_retorno']): ?>
                <span><i class="bi bi-calendar-check"></i> Retorno: <?= date('d/m/Y', strtotime($d['fecha_retorno'])) ?></span>
                <?php endif; ?>
                <?php $dias = $d['duracion_dias'] ?? $d['dias_calculados']; ?>
                <?php if ($dias): ?>
                <span><i class="bi bi-clock"></i> <?= $dias ?> día(s)</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="tour-card-body">
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label"><i class="bi bi-train-front"></i> Modalidad retorno</span>
                    <span class="info-value">
                        <?php
                        $icon_modal = ['Carro'=>'bi-car-front','Tren'=>'bi-train-front','Caminata'=>'bi-person-walking'];
                        $im = $icon_modal[$d['modalidad_retorno']] ?? 'bi-arrow-return-left';
                        echo '<i class="bi '.$im.' me-1"></i>'.htmlspecialchars($d['modalidad_retorno'] ?? '—');
                        ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="bi bi-ticket-perforated"></i> Incluye ingreso</span>
                    <span class="info-value">
                        <?php if ($d['incluye_ingreso'] === 'SI'): ?>
                            <span class="estado-badge estado-confirmado"><i class="bi bi-check"></i> Sí incluye</span>
                        <?php else: ?>
                            <span class="estado-badge estado-cancelado"><i class="bi bi-x"></i> No incluye</span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="bi bi-currency-exchange"></i> Moneda</span>
                    <span class="info-value">
                        <?php echo $d['tipo_moneda'] === 'Dólares'
                            ? '<span class="monto-dolares"><i class="bi bi-currency-dollar"></i> Dólares</span>'
                            : '<span class="monto-soles"><i class="bi bi-cash"></i> Soles</span>';
                        ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="bi bi-tag"></i> Precio del tour</span>
                    <span class="info-value monto-val <?= $d['tipo_moneda']==='Dólares'?'monto-dolares':'monto-soles' ?>">
                        <?= $d['tipo_moneda']==='Dólares' ? '$ ' : 'S/ ' ?><?= number_format($d['precio'] ?? 0, 2) ?>
                    </span>
                </div>
            </div>

            <!-- Adicionales de este tour -->
            <?php if (!empty($adicionales[$d['id_detalle']])): ?>
            <div class="mt-3">
                <div class="info-label mb-1"><i class="bi bi-plus-circle"></i> Adicionales</div>
                <div class="adicionales-list">
                    <?php foreach ($adicionales[$d['id_detalle']] as $ad): ?>
                    <div class="adicional-chip">
                        <i class="bi bi-star-fill"></i>
                        <?= htmlspecialchars($ad['nombre']) ?>
                        <strong>
                            S/ <?= number_format($ad['precio'], 2) ?>
                        </strong>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Servicio adicional (texto libre de la columna) -->
            <?php if (!empty($d['servicio_adicional'])): ?>
            <div class="mt-3">
                <div class="info-label mb-1"><i class="bi bi-info-circle"></i> Nota adicional</div>
                <div style="background:#fefce8;border:1px solid #fde68a;border-radius:7px;padding:8px 12px;font-size:13px">
                    <?= htmlspecialchars($d['servicio_adicional']) ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endwhile; endif; ?>

    <?php if ($op && $op['observaciones']): ?>
    <div class="mt-2" style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:12px 16px;font-size:13px">
        <i class="bi bi-chat-left-text text-info me-2"></i>
        <strong>Observaciones:</strong> <?= htmlspecialchars($op['observaciones']) ?>
    </div>
    <?php endif; ?>
    </div>
</div>


<!-- ════ PAGOS COMPLETOS ════ -->
<div class="kb-card">
    <div class="kb-card-header">
        <div class="section-title">
            <span class="section-icon" style="background:#16a34a"><i class="bi bi-cash-stack"></i></span>
            Registro de Pagos
        </div>
        <span style="font-size:12px;color:var(--text-muted)"><?= $n_pagos ?> transacción(es)</span>
    </div>
    <?php if (empty($todos_pagos)): ?>
        <div class="kb-card-body"><div class="empty-state"><i class="bi bi-cash"></i>Sin pagos registrados</div></div>
    <?php else: ?>
    <div style="overflow-x:auto">
        <table class="kb-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Tipo</th>
                    <th>Método pago</th>
                    <th>Moneda</th>
                    <th>Monto</th>
                    <th>Tipo cambio</th>
                    <th>Monto conv.</th>
                    <th>Fecha</th>
                    <th>Observación</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($todos_pagos as $idx => $p): ?>
            <tr>
                <td style="color:var(--text-muted);font-size:12px"><?= $idx+1 ?></td>
                <td><span class="tipo-badge tipo-<?= $p['tipo'] ?>"><?= ucfirst($p['tipo']) ?></span></td>
                <td>
                    <?php
                    $icons_mp = ['Efectivo'=>'bi-cash','Transferencia'=>'bi-bank','Yape'=>'bi-phone','Plin'=>'bi-phone-fill','Tarjeta'=>'bi-credit-card'];
                    $ic = $icons_mp[$p['metodo_pago']] ?? 'bi-wallet2';
                    echo '<i class="bi '.$ic.' me-1"></i>'.htmlspecialchars($p['metodo_pago'] ?? '—');
                    ?>
                </td>
                <td><?= htmlspecialchars($p['moneda'] ?? '—') ?></td>
                <td>
                    <span class="monto-val <?= $p['moneda']==='Dólares'?'monto-dolares':'monto-soles' ?><?= $p['tipo']==='reembolso'?' monto-neg':'' ?>">
                        <?= $p['tipo']==='reembolso'?'-':'' ?>
                        <?= $p['moneda']==='Dólares'?'$ ':'S/ ' ?><?= number_format($p['monto'], 2) ?>
                    </span>
                </td>
                <td style="font-family:'DM Mono',monospace;font-size:12px;color:var(--text-muted)">
                    <?= $p['tipo_cambio'] != 1 ? number_format($p['tipo_cambio'], 3) : '—' ?>
                </td>
                <td>
                    <?php if ($p['monto_convertido'] && $p['monto_convertido'] != $p['monto']): ?>
                        <span class="monto-val monto-soles">S/ <?= number_format($p['monto_convertido'], 2) ?></span>
                    <?php else: echo '—'; endif; ?>
                </td>
                <td style="font-size:12px;color:var(--text-muted)">
                    <?= $p['fecha'] ? date('d/m/Y', strtotime($p['fecha'])) : '—' ?>
                </td>
                <td style="font-size:12px;color:var(--text-muted);max-width:180px">
                    <?= htmlspecialchars($p['observacion'] ?? '') ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="totales-box">
        <div class="total-item">
            <span class="t-label">Total en Soles</span>
            <span class="t-value monto-soles">S/ <?= number_format($total_soles, 2) ?></span>
        </div>
        <div class="total-item">
            <span class="t-label">Total en Dólares</span>
            <span class="t-value monto-dolares">$ <?= number_format($total_dolares, 2) ?></span>
        </div>
        <?php if ($op): ?>
        <div class="total-item ms-auto">
            <span class="t-label">Total operación</span>
            <span class="t-value"><?= number_format($op['total_operacion'] ?? 0, 2) ?></span>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>


<!-- ════ CONTABILIDAD ════ -->
<?php if ($op): ?>
<div class="kb-card">
    <div class="kb-card-header">
        <div class="section-title">
            <span class="section-icon" style="background:#374151"><i class="bi bi-file-earmark-text-fill"></i></span>
            Contabilidad
        </div>
        <?php if (!empty($conta['estado'])): ?>
        <span class="estado-badge estado-<?= $conta['estado'] ?>"><?= ucfirst($conta['estado']) ?></span>
        <?php endif; ?>
    </div>
    <div class="kb-card-body">
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label"><i class="bi bi-receipt"></i> Boleta a cuenta</span>
                <span class="info-value" style="font-family:'DM Mono',monospace">
                    <?= htmlspecialchars($conta['nro_boleta_cuenta'] ?? '—') ?>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label"><i class="bi bi-receipt-cutoff"></i> Boleta total</span>
                <span class="info-value" style="font-family:'DM Mono',monospace">
                    <?= htmlspecialchars($conta['nro_boleta_total'] ?? '—') ?>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label"><i class="bi bi-percent"></i> Detracción</span>
                <span class="info-value">
                    <?= $conta['detraccion'] ? 'S/ '.number_format($conta['detraccion'],2) : '—' ?>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label"><i class="bi bi-calculator"></i> IGV</span>
                <span class="info-value">
                    <?= $conta['igv'] ? 'S/ '.number_format($conta['igv'],2) : '—' ?>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label"><i class="bi bi-graph-up"></i> Comisión</span>
                <span class="info-value">
                    <?= $conta['comision'] ? 'S/ '.number_format($conta['comision'],2) : '—' ?>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label"><i class="bi bi-file-text"></i> Modalidad recibo</span>
                <span class="info-value"><?= htmlspecialchars($conta['modalidad_recibo'] ?? '—') ?></span>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>


<!-- ════ PLANIFICACION ════ -->
<?php if ($plan): ?>
<div class="kb-card">
    <div class="kb-card-header">
        <div class="section-title">
            <span class="section-icon" style="background:#7c3aed"><i class="bi bi-clipboard2-check-fill"></i></span>
            Equipo Operativo
        </div>
    </div>
    <div class="kb-card-body">
        <div class="plan-grid">
            <?php if ($plan['nombre_guia']): ?>
            <div class="plan-item">
                <i class="bi bi-person-badge"></i>
                <div>
                    <div class="plan-role">Guía</div>
                    <div class="plan-name"><?= htmlspecialchars($plan['nombre_guia']) ?></div>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($plan['nombre_cocinero']): ?>
            <div class="plan-item">
                <i class="bi bi-cup-hot"></i>
                <div>
                    <div class="plan-role">Cocinero</div>
                    <div class="plan-name"><?= htmlspecialchars($plan['nombre_cocinero']) ?></div>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($plan['nombre_asistente']): ?>
            <div class="plan-item">
                <i class="bi bi-person-check"></i>
                <div>
                    <div class="plan-role">Asistente</div>
                    <div class="plan-name"><?= htmlspecialchars($plan['nombre_asistente']) ?></div>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($plan['grupo_operativo']): ?>
            <div class="plan-item">
                <i class="bi bi-people"></i>
                <div>
                    <div class="plan-role">Grupo operativo</div>
                    <div class="plan-name"><?= htmlspecialchars($plan['grupo_operativo']) ?></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

</div><!-- /.main-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>