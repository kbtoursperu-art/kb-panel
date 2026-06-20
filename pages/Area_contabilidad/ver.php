<?php
include '../../conexion.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) die("ID inválido");

// ========================
// QUERY PRINCIPAL
// ========================
$q = mysqli_query($conexion, "
SELECT 
    c.*,
    o.id_operaciones,
    o.fecha_reserva,
    o.estado AS estado_operacion,

    dc.nombre AS cliente_nombre,
    dc.apellido AS cliente_apellido,

    g.nombre_grupo

FROM contabilidad c
LEFT JOIN operaciones o ON o.id_operaciones = c.id_operaciones
LEFT JOIN datos_clientes dc ON dc.id_cliente = o.id_cliente
LEFT JOIN grupos g ON g.id_grupo = o.id_grupo

WHERE c.id_contabilidad = $id
");

$data = mysqli_fetch_assoc($q);
if (!$data) die("No encontrado");
?>

<!DOCTYPE html>
<html lang="es">

<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="../../../../assets/css/style.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">

<style>
/* ===================== */
/* EXTRA PRO CONTABILIDAD */
/* ===================== */

*{box-sizing:border-box}

body{
font-family:'DM Sans',sans-serif;
background:var(--surface-2);
color:var(--text);
margin:0;
}

.main-content{
max-width:1200px;
margin:0 auto;
padding:28px 20px 60px;
}

.page-header{
background:var(--surface);
border-bottom:1px solid var(--border);
padding:18px 24px;
display:flex;
justify-content:space-between;
align-items:center;
position:sticky;
top:0;
z-index:10;
}

.page-header h1{font-size:18px;margin:0}
.page-header .subtitle{font-size:13px;color:var(--text-muted)}

.action-btn{
display:inline-flex;
align-items:center;
justify-content:center;
width:34px;height:34px;
border:1px solid var(--border);
border-radius:8px;
background:var(--surface);
color:var(--text);
text-decoration:none;
margin-left:6px;
}

.action-btn:hover{background:var(--surface-2)}

.stats-row{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(160px,1fr));
gap:14px;
margin:20px 0;
}

.stat-card{
background:var(--surface);
border:1px solid var(--border);
border-radius:10px;
padding:14px;
}

.stat-label{
font-size:11px;
text-transform:uppercase;
color:var(--text-muted);
font-weight:600;
margin-bottom:6px;
}

.stat-value{
font-size:18px;
font-weight:700;
}

.kb-card{
background:var(--surface);
border:1px solid var(--border);
border-radius:10px;
margin-top:18px;
overflow:hidden;
}

.kb-card-header{
padding:14px 18px;
border-bottom:1px solid var(--border);
background:var(--surface-2);
font-weight:700;
display:flex;
gap:8px;
align-items:center;
}

.kb-card-body{padding:18px}

.info-grid{
display:grid;
grid-template-columns:repeat(auto-fill,minmax(180px,1fr));
gap:14px;
}

.info-item{
display:flex;
flex-direction:column;
gap:3px;
}

.info-label{
font-size:11px;
text-transform:uppercase;
color:var(--text-muted);
}

.info-value{
font-size:13px;
font-weight:600;
}

.estado-badge{
display:inline-block;
padding:4px 10px;
border-radius:20px;
font-size:11px;
font-weight:700;
text-transform:uppercase;
}

.estado-no_pagado{background:#fef9c3;color:#a16207}
.estado-pagado{background:#dcfce7;color:#15803d}
.estado-reembolsado{background:#fee2e2;color:#b91c1c}

.monto-soles{color:#1e40af}
.monto-dolares{color:#166534}
</style>
</head>

<body>

<!-- HEADER -->
<div class="page-header">

    <div>
        <h1>Contabilidad #<?= $data['id_contabilidad'] ?></h1>
        <div class="subtitle">
            <?= htmlspecialchars($data['cliente_nombre'].' '.$data['cliente_apellido']) ?>
            · <?= htmlspecialchars($data['nombre_grupo'] ?? '-') ?>
        </div>
    </div>

    <div>
        <a href="index.php" class="action-btn"><i class="bi bi-arrow-left"></i></a>
        <a href="editar.php?id=<?= $id ?>" class="action-btn"><i class="bi bi-pencil"></i></a>
    </div>

</div>

<div class="main-content">

<!-- KPIs -->
<div class="stats-row">

    <div class="stat-card">
        <div class="stat-label">Estado financiero</div>
        <div class="stat-value">
            <span class="estado-badge estado-<?= $data['estado_financiero'] ?>">
                <?= $data['estado_financiero'] ?>
            </span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Comisión</div>
        <div class="stat-value monto-soles">
            S/ <?= number_format($data['comision'],2) ?>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-label">IGV</div>
        <div class="stat-value">
            S/ <?= number_format($data['igv'],2) ?>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-label">Detracción</div>
        <div class="stat-value">
            S/ <?= number_format($data['detraccion'],2) ?>
        </div>
    </div>

</div>

<!-- INFO -->
<div class="kb-card">

    <div class="kb-card-header">
        <i class="bi bi-receipt"></i>
        Información general
    </div>

    <div class="kb-card-body">

        <div class="info-grid">

            <div class="info-item">
                <div class="info-label">Cliente</div>
                <div class="info-value">
                    <?= htmlspecialchars($data['cliente_nombre'].' '.$data['cliente_apellido']) ?>
                </div>
            </div>

            <div class="info-item">
                <div class="info-label">Operación</div>
                <div class="info-value">#<?= $data['id_operaciones'] ?></div>
            </div>

            <div class="info-item">
                <div class="info-label">Grupo</div>
                <div class="info-value"><?= $data['nombre_grupo'] ?? '-' ?></div>
            </div>

            <div class="info-item">
                <div class="info-label">Estado operación</div>
                <div class="info-value"><?= $data['estado_operacion'] ?? '-' ?></div>
            </div>

            <div class="info-item">
                <div class="info-label">Boleta cuenta</div>
                <div class="info-value"><?= $data['nro_boleta_cuenta'] ?? '—' ?></div>
            </div>

            <div class="info-item">
                <div class="info-label">Boleta total</div>
                <div class="info-value"><?= $data['nro_boleta_total'] ?? '—' ?></div>
            </div>

            <div class="info-item">
                <div class="info-label">Modalidad</div>
                <div class="info-value"><?= $data['modalidad_recibo'] ?? '—' ?></div>
            </div>

            <div class="info-item">
                <div class="info-label">Fecha</div>
                <div class="info-value">
                    <?= $data['fecha_registro'] ? date('d/m/Y', strtotime($data['fecha_registro'])) : '-' ?>
                </div>
            </div>

        </div>

    </div>
</div>

</div>

</body>
</html>