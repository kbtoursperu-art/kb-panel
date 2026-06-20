<?php
include '../../conexion.php';
ini_set('display_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set("America/Lima");

// ══════════════════════════════════════
// 1. GUARDAR (POST)
// ══════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id               = intval($_POST['id_contabilidad']);
    $estado           = mysqli_real_escape_string($conexion, $_POST['estado']           ?? '');
    $modalidad_recibo = mysqli_real_escape_string($conexion, $_POST['modalidad_recibo'] ?? '');
    $nro_boleta_cuenta= mysqli_real_escape_string($conexion, $_POST['nro_boleta_cuenta']?? '');
    $nro_boleta_total = mysqli_real_escape_string($conexion, $_POST['nro_boleta_total'] ?? '');
    $detraccion       = floatval($_POST['detraccion']  ?? 0);
    $igv              = floatval($_POST['igv']         ?? 0);
    $comision         = floatval($_POST['comision']    ?? 0);
    $observaciones    = mysqli_real_escape_string($conexion, $_POST['observaciones']    ?? '');

    mysqli_query($conexion,"
        UPDATE contabilidad SET
            estado            = '$estado',
            modalidad_recibo  = '$modalidad_recibo',
            nro_boleta_cuenta = '$nro_boleta_cuenta',
            nro_boleta_total  = '$nro_boleta_total',
            detraccion        = $detraccion,
            igv               = $igv,
            comision          = $comision
        WHERE id_contabilidad = $id
    ");

    // Observaciones en operaciones
    mysqli_query($conexion,"
        UPDATE operaciones SET observaciones = '$observaciones'
        WHERE id_operaciones = (SELECT id_operaciones FROM contabilidad WHERE id_contabilidad = $id)
    ");

    header("Location: index.php?update=1");
    exit;
}

// ══════════════════════════════════════
// 2. CARGAR DATOS (GET)
// ══════════════════════════════════════
if (!isset($_GET['id'])) die("Falta ID.");
$id = intval($_GET['id']);

// Contabilidad + operación
$row = mysqli_fetch_assoc(mysqli_query($conexion,"
    SELECT c.*, o.observaciones, o.id_grupo, o.encargado,
           o.estado AS op_estado, o.total_operacion, o.tipo_precio,
           o.fecha_reserva
    FROM contabilidad c
    LEFT JOIN operaciones o ON o.id_operaciones = c.id_operaciones
    WHERE c.id_contabilidad = $id
    LIMIT 1
"));
if (!$row) die("Registro no encontrado.");

$id_op    = intval($row['id_operaciones']);
$id_grupo = intval($row['id_grupo']);

// Cliente principal del grupo
$cRow = mysqli_fetch_assoc(mysqli_query($conexion,"
    SELECT dc.nombre, dc.apellido, dc.dni, dc.telefono, dc.email,
           g.nombre_grupo
    FROM clientes_grupo cg
    JOIN datos_clientes dc ON dc.id_cliente = cg.id_cliente
    JOIN grupos g ON g.id_grupo = cg.id_grupo
    WHERE cg.id_grupo = $id_grupo
    ORDER BY cg.es_pagador DESC, cg.id ASC
    LIMIT 1
")) ?: [];

// Tours de la operación
$qTours = mysqli_query($conexion,"
    SELECT od.*, IFNULL(s.nombre,'Sin servicio') nombre_servicio, s.duracion_dias
    FROM operaciones_detalle od
    LEFT JOIN servicios s ON s.id_servicio = od.id_servicio
    WHERE od.id_operaciones = $id_op
    ORDER BY od.fecha_salida ASC
");

// Pagos de la operación
$qPagos = mysqli_query($conexion,"
    SELECT * FROM pagos WHERE id_operaciones = $id_op ORDER BY fecha ASC
");
$pagos = [];
$tot_soles = 0; $tot_dol = 0; $tot_reembolso = 0;
while ($p = mysqli_fetch_assoc($qPagos)) {
    $pagos[] = $p;
    if ($p['tipo'] !== 'reembolso') {
        if ($p['moneda'] === 'Soles')   $tot_soles += $p['monto'];
        if ($p['moneda'] === 'Dólares') $tot_dol   += $p['monto'];
    } else {
        $tot_reembolso += $p['monto'];
    }
}

include '../sidebar.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Editar Contabilidad #<?= $id ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root{
    --brand:#1a56db;--brand-dark:#1e40af;
    --surface:#fff;--surface-2:#f8fafc;--border:#e2e8f0;
    --text:#0f172a;--text-muted:#64748b;
    --success:#16a34a;--warning:#d97706;--danger:#dc2626;
    --radius:12px;--shadow:0 1px 3px rgba(0,0,0,.06),0 4px 16px rgba(0,0,0,.04);
}
*,*::before,*::after{box-sizing:border-box}
body{font-family:'DM Sans',sans-serif;background:var(--surface-2);color:var(--text);font-size:14px}

/* HEADER */
.page-header{background:var(--surface);border-bottom:1px solid var(--border);padding:16px 32px;display:flex;align-items:center;gap:14px;position:sticky;top:0;z-index:100}
.back-btn{display:flex;align-items:center;gap:6px;color:var(--text-muted);text-decoration:none;font-size:13px;font-weight:500;padding:6px 12px;border-radius:8px;border:1px solid var(--border);transition:all .15s}
.back-btn:hover{background:var(--surface-2);color:var(--text)}
.page-header h1{font-size:17px;font-weight:700;margin:0}
.page-header .subtitle{color:var(--text-muted);font-size:12px;margin:0}

/* LAYOUT */
.main-content{max-width:1100px;margin:0 auto;padding:28px 24px 60px}
.two-col{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.three-col{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px}

/* CARDS */
.kb-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow);margin-bottom:20px;overflow:hidden}
.kb-card-header{display:flex;align-items:center;justify-content:space-between;padding:13px 18px;border-bottom:1px solid var(--border);background:var(--surface-2)}
.section-title{display:flex;align-items:center;gap:9px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--text)}
.section-icon{width:26px;height:26px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:13px;color:#fff}
.kb-card-body{padding:18px}

/* FORM */
.field-group{display:flex;flex-direction:column;gap:5px;margin-bottom:0}
.field-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted)}
.field-label .required{color:#dc2626;margin-left:2px}
.field-input{background:var(--surface-2);border:1.5px solid var(--border);border-radius:9px;padding:9px 13px;font-size:13px;font-family:'DM Sans',sans-serif;color:var(--text);width:100%;outline:none;transition:border-color .15s,background .15s}
.field-input:focus{border-color:var(--brand);background:#fff;box-shadow:0 0 0 3px rgba(26,86,219,.07)}
.field-input:disabled{opacity:.6;cursor:not-allowed;background:var(--surface-2)}
select.field-input{cursor:pointer}
textarea.field-input{resize:vertical;min-height:80px}
.field-hint{font-size:11px;color:var(--text-muted);margin-top:2px}

/* READONLY DISPLAY */
.info-display{background:var(--surface-2);border:1px solid var(--border);border-radius:9px;padding:9px 13px;font-size:13px;min-height:38px;display:flex;align-items:center}
.info-display.mono{font-family:'DM Mono',monospace;font-weight:500}

/* STATES */
.estado-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;text-transform:uppercase}
.estado-pagado{background:#dcfce7;color:#15803d}
.estado-pendiente{background:#fef9c3;color:#a16207}
.estado-cancelado{background:#fee2e2;color:#b91c1c}
.tipo-badge{display:inline-block;padding:2px 8px;border-radius:5px;font-size:11px;font-weight:600;text-transform:uppercase}
.tipo-tour{background:#dbeafe;color:#1e40af}
.tipo-cuenta{background:#dcfce7;color:#15803d}
.tipo-saldo{background:#f3e8ff;color:#6b21a8}
.tipo-reembolso{background:#fee2e2;color:#b91c1c}
.tipo-adicional{background:#fef3c7;color:#92400e}

/* MONEY */
.monto-val{font-family:'DM Mono',monospace;font-weight:500;font-size:13px}
.monto-soles{color:#1e40af}
.monto-dolares{color:#166534}

/* TOUR CARD */
.tour-item{border:1px solid var(--border);border-radius:9px;padding:12px 14px;margin-bottom:10px;background:var(--surface-2)}
.tour-item:last-child{margin-bottom:0}
.tour-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px}
.tour-name{font-weight:700;font-size:13px;display:flex;align-items:center;gap:6px}

/* TABLE SIMPLE */
.simple-table{width:100%;border-collapse:separate;border-spacing:0}
.simple-table th{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted);padding:8px 12px;background:var(--surface-2);border-bottom:1px solid var(--border);white-space:nowrap}
.simple-table td{padding:9px 12px;border-bottom:1px solid var(--border);vertical-align:middle;font-size:13px}
.simple-table tr:last-child td{border-bottom:none}
.simple-table tr:hover td{background:var(--surface-2)}

/* RESUMEN FINANCIERO */
.fin-grid{display:grid;grid-template-columns:repeat(3,1fr);border-top:1px solid var(--border)}
.fin-item{padding:14px 18px;border-right:1px solid var(--border);display:flex;flex-direction:column;gap:3px}
.fin-item:last-child{border-right:none}
.fin-label{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted)}
.fin-value{font-size:18px;font-weight:700;font-family:'DM Mono',monospace}

/* FOOTER ACTIONS */
.form-footer{position:sticky;bottom:0;background:var(--surface);border-top:1px solid var(--border);padding:14px 24px;display:flex;align-items:center;justify-content:space-between;z-index:50}
.btn-save{display:flex;align-items:center;gap:8px;background:var(--brand);color:#fff;border:none;padding:10px 24px;border-radius:9px;font-size:14px;font-weight:600;cursor:pointer;transition:background .15s;font-family:'DM Sans',sans-serif}
.btn-save:hover{background:var(--brand-dark)}
.btn-cancel{display:flex;align-items:center;gap:6px;background:transparent;color:var(--text-muted);border:1px solid var(--border);padding:10px 18px;border-radius:9px;font-size:13px;font-weight:500;cursor:pointer;text-decoration:none;transition:all .15s;font-family:'DM Sans',sans-serif}
.btn-cancel:hover{background:var(--surface-2);color:var(--text)}

/* ALERT */
.alert-success-kb{background:#dcfce7;border:1px solid #bbf7d0;color:#15803d;padding:10px 16px;border-radius:9px;font-size:13px;font-weight:500;display:flex;align-items:center;gap:8px;margin-bottom:16px}

@media(max-width:768px){
    .two-col,.three-col{grid-template-columns:1fr}
    .fin-grid{grid-template-columns:1fr 1fr}
    .page-header{padding:13px 16px}
    .main-content{padding:14px 12px 80px}
}
</style>
</head>
<body>
<div class="kb-content">
<!-- ════ HEADER ════ -->
<div class="page-header">
    <a href="index.php" class="back-btn">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
    <div>
        <h1><i class="bi bi-pencil-square text-primary me-2"></i>Editar Contabilidad
            <span style="font-family:'DM Mono',monospace;color:var(--text-muted);font-size:14px">#<?= $id ?></span>
        </h1>
        <p class="subtitle">
            <?= htmlspecialchars(($cRow['nombre'] ?? '').' '.($cRow['apellido'] ?? '')) ?>
            · <?= htmlspecialchars($cRow['nombre_grupo'] ?? '—') ?>
            · Op. #<?= $id_op ?>
        </p>
    </div>
    <div class="ms-auto">
        <span class="estado-badge estado-<?= $row['estado'] ?? 'pendiente' ?>">
            <i class="bi bi-circle-fill" style="font-size:7px"></i>
            <?= ucfirst($row['estado'] ?? 'pendiente') ?>
        </span>
    </div>
</div>

<div class="main-content">

<?php if (isset($_GET['saved'])): ?>
<div class="alert-success-kb"><i class="bi bi-check-circle-fill"></i> Cambios guardados correctamente.</div>
<?php endif; ?>

<form method="POST">
<input type="hidden" name="id_contabilidad" value="<?= $id ?>">

<!-- ════ INFO SOLO LECTURA ════ -->
<div class="kb-card">
    <div class="kb-card-header">
        <div class="section-title">
            <span class="section-icon" style="background:#1a56db"><i class="bi bi-person-fill"></i></span>
            Información del Grupo (solo lectura)
        </div>
        <a href="../grupos/ver.php?id_grupo=<?= $id_grupo ?>" style="font-size:12px;color:var(--brand);text-decoration:none;font-weight:600">
            <i class="bi bi-box-arrow-up-right me-1"></i>Ver grupo completo
        </a>
    </div>
    <div class="kb-card-body">
        <div class="three-col" style="gap:14px">
            <div class="field-group">
                <span class="field-label"><i class="bi bi-person me-1"></i>Cliente principal</span>
                <div class="info-display">
                    <?php if (!empty($cRow['nombre'])): ?>
                    <div style="display:flex;align-items:center;gap:8px">
                        <div style="width:28px;height:28px;border-radius:50%;background:#1a56db;display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:700;color:#fff;flex-shrink:0">
                            <?= strtoupper(substr($cRow['nombre'],0,1).substr($cRow['apellido']??'',0,1)) ?>
                        </div>
                        <?= htmlspecialchars($cRow['nombre'].' '.($cRow['apellido'] ?? '')) ?>
                    </div>
                    <?php else: echo '—'; endif; ?>
                </div>
            </div>
            <div class="field-group">
                <span class="field-label"><i class="bi bi-collection me-1"></i>Grupo</span>
                <div class="info-display"><?= htmlspecialchars($cRow['nombre_grupo'] ?? '—') ?></div>
            </div>
            <div class="field-group">
                <span class="field-label"><i class="bi bi-person-badge me-1"></i>Encargado</span>
                <div class="info-display"><?= htmlspecialchars($row['encargado'] ?? '—') ?></div>
            </div>
            <div class="field-group">
                <span class="field-label"><i class="bi bi-calendar me-1"></i>Fecha reserva</span>
                <div class="info-display mono">
                    <?= $row['fecha_reserva'] ? date('d/m/Y', strtotime($row['fecha_reserva'])) : '—' ?>
                </div>
            </div>
            <div class="field-group">
                <span class="field-label"><i class="bi bi-graph-up me-1"></i>Total operación</span>
                <div class="info-display mono monto-soles">
                    <?= number_format($row['total_operacion'] ?? 0, 2) ?> · <?= $row['tipo_precio'] ?? '—' ?>
                </div>
            </div>
            <div class="field-group">
                <span class="field-label"><i class="bi bi-flag me-1"></i>Estado operación</span>
                <div class="info-display">
                    <span class="estado-badge estado-<?= $row['op_estado'] ?? 'pendiente' ?>"><?= ucfirst($row['op_estado'] ?? 'pendiente') ?></span>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- ════ TOURS ════ -->
<div class="kb-card">
    <div class="kb-card-header">
        <div class="section-title">
            <span class="section-icon" style="background:#0891b2"><i class="bi bi-compass-fill"></i></span>
            Tours de la Operación
        </div>
    </div>
    <div class="kb-card-body">
    <?php
    $tour_colors = ['#1a56db','#16a34a','#d97706','#dc2626','#7c3aed'];
    $ti = 0;
    while ($t = mysqli_fetch_assoc($qTours)):
        $tc = $tour_colors[$ti++ % count($tour_colors)];
    ?>
    <div class="tour-item">
        <div class="tour-header">
            <div class="tour-name">
                <i class="bi bi-compass" style="color:<?= $tc ?>"></i>
                <?= htmlspecialchars($t['nombre_servicio']) ?>
            </div>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                <?php if ($t['fecha_salida']): ?>
                <span style="font-size:12px;color:var(--text-muted)"><i class="bi bi-calendar-event me-1"></i><?= date('d/m/Y',strtotime($t['fecha_salida'])) ?></span>
                <?php endif; ?>
                <?php if ($t['fecha_retorno']): ?>
                <span style="font-size:12px;color:var(--text-muted)"><i class="bi bi-calendar-check me-1"></i><?= date('d/m/Y',strtotime($t['fecha_retorno'])) ?></span>
                <?php endif; ?>
                <span class="monto-val <?= $t['tipo_moneda']==='Dólares'?'monto-dolares':'monto-soles' ?>">
                    <?= $t['tipo_moneda']==='Dólares'?'$':'S/' ?> <?= number_format($t['precio']??0,2) ?>
                </span>
            </div>
        </div>
        <div style="display:flex;gap:16px;flex-wrap:wrap">
            <?php if ($t['modalidad_retorno']): ?>
            <span style="font-size:12px;color:var(--text-muted)">
                <?php $im=['Carro'=>'bi-car-front','Tren'=>'bi-train-front','Caminata'=>'bi-person-walking'][$t['modalidad_retorno']]??'bi-arrow-right'; ?>
                <i class="bi <?= $im ?> me-1"></i><?= $t['modalidad_retorno'] ?>
            </span>
            <?php endif; ?>
            <span style="font-size:12px;color:var(--text-muted)">
                Ingreso:
                <?php echo $t['incluye_ingreso']==='SI'
                    ? '<span class="estado-badge estado-pagado" style="font-size:10px;padding:1px 7px">Sí</span>'
                    : '<span class="estado-badge estado-cancelado" style="font-size:10px;padding:1px 7px">No</span>'; ?>
            </span>
            <?php if (!empty($t['servicio_adicional'])): ?>
            <span style="font-size:12px;color:#d97706"><i class="bi bi-plus-circle me-1"></i><?= htmlspecialchars($t['servicio_adicional']) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <?php endwhile; ?>
    </div>
</div>


<!-- ════ RESUMEN PAGOS ════ -->
<div class="kb-card">
    <div class="kb-card-header">
        <div class="section-title">
            <span class="section-icon" style="background:#16a34a"><i class="bi bi-cash-stack"></i></span>
            Resumen de Pagos Registrados
        </div>
        <a href="../pagos/index.php?id_operaciones=<?= $id_op ?>" style="font-size:12px;color:var(--brand);text-decoration:none;font-weight:600">
            Ver todos <i class="bi bi-arrow-right"></i>
        </a>
    </div>

    <?php if (!empty($pagos)): ?>
    <div style="overflow-x:auto">
    <table class="simple-table">
        <thead>
            <tr><th>Tipo</th><th>Método</th><th>Moneda</th><th>Monto</th><th>T/C</th><th>Convertido</th><th>Fecha</th></tr>
        </thead>
        <tbody>
        <?php foreach ($pagos as $p): ?>
        <tr>
            <td><span class="tipo-badge tipo-<?= $p['tipo'] ?>"><?= ucfirst($p['tipo']) ?></span></td>
            <td><?= htmlspecialchars($p['metodo_pago'] ?? '—') ?></td>
            <td><?= htmlspecialchars($p['moneda'] ?? '—') ?></td>
            <td>
                <span class="monto-val <?= $p['moneda']==='Dólares'?'monto-dolares':'monto-soles' ?><?= $p['tipo']==='reembolso'?' monto-neg':'' ?>">
                    <?= $p['tipo']==='reembolso'?'-':'' ?><?= $p['moneda']==='Dólares'?'$':'S/' ?> <?= number_format($p['monto'],2) ?>
                </span>
            </td>
            <td style="font-family:'DM Mono',monospace;font-size:12px;color:var(--text-muted)">
                <?= ($p['tipo_cambio'] && $p['tipo_cambio']!=1) ? number_format($p['tipo_cambio'],3) : '—' ?>
            </td>
            <td>
                <?php if ($p['monto_convertido'] && $p['monto_convertido']!=$p['monto']): ?>
                    <span class="monto-val monto-soles">S/ <?= number_format($p['monto_convertido'],2) ?></span>
                <?php else: echo '—'; endif; ?>
            </td>
            <td style="font-size:12px;color:var(--text-muted)"><?= $p['fecha'] ? date('d/m/Y',strtotime($p['fecha'])) : '—' ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <div class="fin-grid">
        <div class="fin-item">
            <span class="fin-label">Total en Soles</span>
            <span class="fin-value monto-soles">S/ <?= number_format($tot_soles,2) ?></span>
        </div>
        <div class="fin-item">
            <span class="fin-label">Total en Dólares</span>
            <span class="fin-value monto-dolares">$ <?= number_format($tot_dol,2) ?></span>
        </div>
        <div class="fin-item">
            <span class="fin-label">Reembolsos</span>
            <span class="fin-value monto-neg">-S/ <?= number_format($tot_reembolso,2) ?></span>
        </div>
    </div>
    <?php else: ?>
    <div style="text-align:center;padding:24px;color:var(--text-muted)"><i class="bi bi-cash" style="font-size:24px;opacity:.3;display:block;margin-bottom:6px"></i>Sin pagos registrados</div>
    <?php endif; ?>
</div>


<!-- ════ FORM EDITABLE ════ -->
<div class="kb-card">
    <div class="kb-card-header">
        <div class="section-title">
            <span class="section-icon" style="background:#374151"><i class="bi bi-pencil-fill"></i></span>
            Datos Contables — Editables
        </div>
        <span style="font-size:12px;color:var(--text-muted)">Los campos con <span style="color:#dc2626">*</span> son obligatorios</span>
    </div>
    <div class="kb-card-body">

        <!-- Fila 1: Estado + Comprobante + Boletas -->
        <div class="three-col" style="margin-bottom:16px">

            <div class="field-group">
                <label class="field-label" for="estado">
                    <i class="bi bi-flag me-1"></i>Estado<span class="required">*</span>
                </label>
                <select class="field-input" name="estado" id="estado">
                    <?php foreach(['pendiente'=>'Pendiente','pagado'=>'Pagado','cancelado'=>'Cancelado'] as $v=>$l): ?>
                    <option value="<?= $v ?>" <?= $row['estado']===$v?'selected':'' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field-group">
                <label class="field-label" for="modalidad_recibo">
                    <i class="bi bi-file-earmark me-1"></i>Tipo de comprobante<span class="required">*</span>
                </label>
                <select class="field-input" name="modalidad_recibo" id="modalidad_recibo">
                    <?php
                    $tipos_comp = ['FACTURA'=>'Factura','FAC_EXPORTACION'=>'Fac. Exportación','BV_INTANGIBLE'=>'B/V Intangible','BV_IGV'=>'B/V con IGV'];
                    foreach($tipos_comp as $v=>$l):
                    ?>
                    <option value="<?= $v ?>" <?= $row['modalidad_recibo']===$v?'selected':'' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="field-group">
                <label class="field-label" for="nro_boleta_cuenta">
                    <i class="bi bi-receipt me-1"></i>N° Comprobante a cuenta
                </label>
                <input type="text" class="field-input" name="nro_boleta_cuenta" id="nro_boleta_cuenta"
                    placeholder="Ej: B001-00123"
                    value="<?= htmlspecialchars($row['nro_boleta_cuenta'] ?? '') ?>">
            </div>

        </div>

        

        <!-- Fila 2: Boleta total + IGV + Detracción + Comisión -->
        <div class="three-col" style="margin-bottom:16px">

            <div class="field-group">
                <label class="field-label" for="nro_boleta_total">
                    <i class="bi bi-receipt-cutoff me-1"></i>N° Comprobante total
                </label>
                <input type="text" class="field-input" name="nro_boleta_total" id="nro_boleta_total"
                    placeholder="Ej: B001-00124"
                    value="<?= htmlspecialchars($row['nro_boleta_total'] ?? '') ?>">
            </div>

            <div class="field-group">
                <label class="field-label" for="igv">
                    <i class="bi bi-calculator me-1"></i>IGV (S/)
                </label>
                <input type="number" class="field-input" step="0.01" min="0" name="igv" id="igv"
                    placeholder="0.00"
                    value="<?= htmlspecialchars($row['igv'] ?? '0') ?>">
                <span class="field-hint">Impuesto general a las ventas</span>
            </div>

            <div class="field-group">
                <label class="field-label" for="detraccion">
                    <i class="bi bi-percent me-1"></i>Detracción (S/)
                </label>
                <input type="number" class="field-input" step="0.01" min="0" name="detraccion" id="detraccion"
                    placeholder="0.00"
                    value="<?= htmlspecialchars($row['detraccion'] ?? '0') ?>">
                <span class="field-hint">Sistema de pago de obligaciones</span>
            </div>

        </div>

        <!-- Fila 3: Comisión -->
        <div class="two-col" style="margin-bottom:16px">
            <div class="field-group">
                <label class="field-label" for="comision">
                    <i class="bi bi-graph-up me-1"></i>Comisión (S/)
                </label>
                <input type="number" class="field-input" step="0.01" min="0" name="comision" id="comision"
                    placeholder="0.00"
                    value="<?= htmlspecialchars($row['comision'] ?? '0') ?>">
                <span class="field-hint">Comisión de la operación</span>
            </div>
            <div class="field-group">
                <label class="field-label">
                    <i class="bi bi-info-circle me-1"></i>Fecha de registro
                </label>
                <div class="info-display mono">
                    <?= $row['fecha_conta'] ? date('d/m/Y H:i', strtotime($row['fecha_conta'])) : '—' ?>
                </div>
            </div>
        </div>

        <!-- Observaciones -->
        <div class="field-group">
            <label class="field-label" for="observaciones">
                <i class="bi bi-chat-left-text me-1"></i>Observaciones
            </label>
            <textarea class="field-input" name="observaciones" id="observaciones"
                placeholder="Notas adicionales sobre esta operación..."
                rows="3"><?= htmlspecialchars($row['observaciones'] ?? '') ?></textarea>
            <span class="field-hint">Se guarda en el registro de la operación</span>
        </div>

    </div>
</div>

<!-- ════ FOOTER STICKY ════ -->
<div class="form-footer">
    <a href="index.php" class="btn-cancel">
        <i class="bi bi-x-circle"></i> Cancelar
    </a>
    <div style="display:flex;align-items:center;gap:10px">
        <span style="font-size:12px;color:var(--text-muted)">
            <i class="bi bi-shield-check text-success me-1"></i>Los cambios se aplican inmediatamente
        </span>
        <button type="submit" class="btn-save">
            <i class="bi bi-floppy-fill"></i> Guardar Cambios
        </button>
    </div>
</div>

</form>
</div><!-- /.main-content -->
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Highlight on change
document.querySelectorAll('.field-input:not(:disabled)').forEach(el => {
    el.addEventListener('change', function() {
        this.style.borderColor = '#16a34a';
        this.style.background  = '#f0fdf4';
    });
});
</script>
</body>
</html>