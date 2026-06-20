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
SELECT *
FROM grupos
WHERE id_grupo = $id_grupo
");
$grupo = mysqli_fetch_assoc($qGrupo);

/* ================= CLIENTES ================= */
$qClientes = mysqli_query($conexion,"
SELECT d.nombre,d.apellido,cg.es_pagador
FROM clientes_grupo cg
JOIN datos_clientes d ON d.id_cliente = cg.id_cliente
WHERE cg.id_grupo = $id_grupo
");

/* ================= OPERACION ================= */
$qOperacion = mysqli_query($conexion,"
SELECT *
FROM operaciones
WHERE id_grupo = $id_grupo
ORDER BY id_operaciones DESC
LIMIT 1
");

$op = mysqli_fetch_assoc($qOperacion);
$id_operacion = $op['id_operaciones'] ?? 0;

/* ================= DETALLE ================= */
$qDetalle = mysqli_query($conexion,"
SELECT od.*, s.nombre AS nombre_servicio
FROM operaciones_detalle od
LEFT JOIN servicios s ON s.id_servicio = od.id_servicio
WHERE od.id_operaciones = $id_operacion
");

/* ================= PAGOS ================= */
$qPagos = mysqli_query($conexion,"
SELECT *
FROM pagos
WHERE id_operaciones = $id_operacion
");

/* ================= CONTABILIDAD ================= */
$qConta = mysqli_query($conexion,"
SELECT *
FROM contabilidad
WHERE id_operaciones = $id_operacion
LIMIT 1
");
$cont = mysqli_fetch_assoc($qConta);

/* ================= CALCULOS ================= */
$total_soles = 0;
$total_dolares = 0;

while($d = mysqli_fetch_assoc($qDetalle)){
    if($d['tipo_moneda'] == 'Soles'){
        $total_soles += $d['precio'];
    } else {
        $total_dolares += $d['precio'];
    }
}

mysqli_data_seek($qDetalle, 0);

$pagado_soles = 0;
$pagado_dolares = 0;

while($p = mysqli_fetch_assoc($qPagos)){
    if($p['tipo'] == 'tour'){
        if($p['moneda'] == 'Soles'){
            $pagado_soles += $p['monto'];
        } else {
            $pagado_dolares += $p['monto'];
        }
    }
}

mysqli_data_seek($qPagos, 0);

$saldo_soles = $total_soles - $pagado_soles;
$saldo_dolares = $total_dolares - $pagado_dolares;

$estado = ($saldo_soles <= 0 && $saldo_dolares <= 0)
    ? 'PAGADO'
    : 'PENDIENTE';
?>


<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Vista de Grupo</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
<?php include '../../sidebar.php'; ?>

<div class="container mt-4">

<h3 class="text-primary">
Grupo: <?= $grupo['nombre_grupo'] ?? '-' ?>
</h3>

<!-- ================= CLIENTES ================= -->
<div class="card mb-3">
<div class="card-header bg-primary text-white">👥 Clientes</div>
<div class="card-body">

<table class="table table-sm">
<tr>
<th>#</th>
<th>Nombre</th>
<th>Pagador</th>
</tr>

<?php $i=1; while($c=mysqli_fetch_assoc($qClientes)): ?>
<tr>
<td><?= $i++ ?></td>
<td><?= $c['nombre']." ".$c['apellido'] ?></td>
<td>
<?= $c['es_pagador'] 
? '<span class="badge bg-success">SI</span>' 
: '<span class="badge bg-secondary">NO</span>' ?>
</td>
</tr>
<?php endwhile; ?>

</table>

</div>
</div>

<!-- ================= RESUMEN ================= -->
<div class="card mb-3">
<div class="card-header bg-dark text-white">💰 Resumen financiero</div>
<div class="card-body">

<table class="table table-bordered text-center">
<tr class="table-light">
<th></th>
<th>Soles (S/)</th>
<th>Dólares ($)</th>
</tr>

<tr>
<th>Total</th>
<td><?= number_format($total_soles,2) ?></td>
<td><?= number_format($total_dolares,2) ?></td>
</tr>

<tr>
<th>Pagado</th>
<td><?= number_format($pagado_soles,2) ?></td>
<td><?= number_format($pagado_dolares,2) ?></td>
</tr>

<tr>
<th>Saldo</th>
<td class="text-danger"><?= number_format($saldo_soles,2) ?></td>
<td class="text-danger"><?= number_format($saldo_dolares,2) ?></td>
</tr>

</table>

<h5>
Estado:
<span class="badge <?= $estado=='PAGADO'?'bg-success':'bg-warning' ?>">
<?= $estado ?>
</span>
</h5>

</div>
</div>

<!-- ================= TOURS ================= -->
<div class="card mb-3">
<div class="card-header bg-info text-white">🎯 Tours</div>
<div class="card-body">

<table class="table table-bordered">
<tr>
<th>Servicio</th>
<th>Precio</th>
<th>Moneda</th>
<th>Salida</th>
<th>Retorno</th>
<th>Modalidad</th>
</tr>

<?php while($d=mysqli_fetch_assoc($qDetalle)): ?>
<tr>
<td><strong><?= $d['nombre_servicio'] ?></strong></td>
<td><?= number_format($d['precio'],2) ?></td>
<td><?= $d['tipo_moneda']=='Soles'?'S/':'$' ?></td>
<td><?= $d['fecha_salida'] ?></td>
<td><?= $d['fecha_retorno'] ?></td>
<td><?= $d['modalidad_retorno'] ?></td>
</tr>
<?php endwhile; ?>

</table>

</div>
</div>

<!-- ================= PAGOS ================= -->
<div class="card mb-3">
<div class="card-header bg-success text-white">💳 Pagos</div>
<div class="card-body">

<table class="table table-bordered text-center">
<tr>
<th>Tipo</th>
<th>Método</th>
<th>Moneda</th>
<th>Monto</th>
<th>Fecha</th>
</tr>

<?php while($p=mysqli_fetch_assoc($qPagos)): ?>
<tr>
<td><?= strtoupper($p['tipo']) ?></td>
<td><?= $p['metodo_pago'] ?></td>
<td><?= $p['moneda']=='Soles'?'S/':'$' ?></td>
<td class="text-success"><?= number_format($p['monto'],2) ?></td>
<td><?= $p['fecha'] ?></td>
</tr>
<?php endwhile; ?>

</table>

</div>
</div>

<!-- ================= CONTABILIDAD ================= -->
<div class="card mb-3">
<div class="card-header bg-secondary text-white">📊 Contabilidad</div>
<div class="card-body">

<table class="table">
<tr>
<th>Comisión</th>
<th>Estado</th>
</tr>

<tr>
<td><?= number_format($cont['comision'] ?? 0,2) ?></td>
<td>
<span class="badge bg-info">
<?= $cont['estado'] ?? '-' ?>
</span>
</td>
</tr>

</table>

</div>
</div>

</div>
</body>
</html>