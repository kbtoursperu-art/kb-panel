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
SELECT 
d.nombre,
d.apellido,
t.es_pagador

FROM (

    SELECT id_cliente, id_grupo, es_pagador
    FROM clientes_kb

    UNION ALL

    SELECT id_cliente, id_grupo, es_pagador
    FROM clientes_endosadores

) t

JOIN datos_clientes d 
ON d.id_cliente = t.id_cliente

WHERE t.id_grupo = $id_grupo
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

$id_operacion = 0;

if ($op && isset($op['id_operaciones'])) {
    $id_operacion = $op['id_operaciones'];
}


/* ================= CONTABILIDAD ================= */

$conta = [];

if ($id_operacion > 0) {

    $qConta = mysqli_query($conexion,"
    SELECT *
   FROM contabilidad
WHERE id_grupo = $id_grupo
ORDER BY id_contabilidad DESC
LIMIT 1
    ");

    $conta = mysqli_fetch_assoc($qConta);

    if ($conta) {
        $op = array_merge($op, $conta);
    }
}
/* ================= DETALLE ================= */

$qDetalle = mysqli_query($conexion,"
SELECT *
FROM operaciones_detalle
WHERE id_operaciones = $id_operacion
");
/* ================= PAGOS OPERACION ================= */

$qPagos = mysqli_query($conexion,"
SELECT *
FROM pagos_operacion
WHERE id_operaciones = $id_operacion
ORDER BY id_pago ASC
");
/* ================= PAGOS SALDO ================= */

$qSaldo = mysqli_query($conexion,"
SELECT *
FROM pagos_operacion
WHERE id_operaciones = $id_operacion
AND tipo_pago = 'saldo'
ORDER BY id_pago ASC
");

if (!$qSaldo) {
    die("Error en saldo: " . mysqli_error($conexion));
}
?>
<!DOCTYPE html>
<html>
<head>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>

.card{
border-radius:12px;
}

.badge{
font-size:13px;
}

</style>

</head>
<body>
<?php include '../../sidebar.php'; ?>
<div class="container mt-4">

<h3 class="text-primary mb-3">
Grupo: <?= $grupo['nombre_grupo'] ?? '-' ?>
</h3>



<!-- ================= CLIENTES ================= -->

<div class="card mb-3">

<div class="card-header bg-primary text-white">
Clientes
</div>

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

<td>
<?= $c['nombre']." ".$c['apellido'] ?>
</td>

<td>

<?php
echo $c['es_pagador']
? '<span class="badge bg-success">SI</span>'
: '<span class="badge bg-secondary">NO</span>';
?>

</td>

</tr>

<?php endwhile; ?>

</table>

</div>
</div>



<!-- ================= TOURS ================= -->

<div class="card mb-3">

<div class="card-header bg-info text-white">
Tours
</div>

<div class="card-body">

<table class="table table-bordered">

<tr>
<th>Servicio</th>
<th>Salida</th>
<th>Retorno</th>
<th>Ingreso</th>
<th>Modalidad</th>
<th>Adicional</th>
</tr>

<?php while($d=mysqli_fetch_assoc($qDetalle)): ?>

<tr>

<td>
<span class="badge bg-primary">
<?= $d['nombre_servicio'] ?>
</span>
</td>

<td><?= $d['fecha_salida'] ?></td>

<td><?= $d['fecha_retorno'] ?></td>

<td><?= $d['incluye_ingreso'] ?></td>

<td><?= $d['modalidad_retorno'] ?></td>

<td>

<?php
if (!empty($d['servicio_adicional'])) {
echo '<span class="badge bg-warning text-dark">'
.$d['servicio_adicional'].
'</span>';
}
?>

</td>

</tr>

<?php endwhile; ?>

</table>

</div>
</div>



<!-- ================= PAGOS ================= -->

<div class="card mb-3">

<div class="card-header bg-success text-white">
Pagos
</div>

<div class="card-body">

<table class="table">

<tr>
<th>Total</th>
<th>Pagado</th>
<th>Saldo</th>
<th>Metodo</th>
<th>Moneda</th>
</tr>

<tr>

<td><?= $op['precio_servicio'] ?? 0 ?></td>

<td><?= $op['pagado_a_cuenta'] ?? 0 ?></td>

<td><?= $op['saldo_pendiente'] ?? 0 ?></td>

<td><?= $op['metodo_pago'] ?? '-' ?></td>

<td><?= $op['tipo_moneda'] ?? '-' ?></td>

</tr>

</table>

</div>
</div>



<!-- ================= SALDO ================= -->

<div class="card mb-3">

<div class="card-header bg-warning">
Pago saldo
</div>

<div class="card-body">

<table class="table table-bordered">

<tr>
<th>Método</th>
<th>Moneda</th>
<th>Monto</th>
<th>Fecha</th>
</tr>

<?php while($s=mysqli_fetch_assoc($qSaldo)): ?>

<tr>

<td><?= $s['metodo_pago'] ?></td>

<td><?= $s['tipo_moneda'] ?></td>

<td>
<span class="badge bg-success">
<?= $s['monto'] ?>
</span>
</td>

<td><?= $s['fecha_pago'] ?></td>

</tr>

<?php endwhile; ?>

</table>

</div>
</div>

<!-- ================= PAGOS REALIZADOS ================= -->

<div class="card mb-3">

<div class="card-header bg-secondary text-white">
Pagos realizados
</div>

<div class="card-body">

<table class="table table-bordered">

<tr>
<th>Tipo</th>
<th>Método</th>
<th>Moneda</th>
<th>Monto</th>
<th>Fecha</th>
</tr>

<?php while($p=mysqli_fetch_assoc($qPagos)): ?>

<tr>

<td><?= $p['tipo_pago'] ?></td>

<td><?= $p['metodo_pago'] ?></td>

<td><?= $p['tipo_moneda'] ?></td>

<td>
<span class="badge bg-success">
<?= $p['monto'] ?>
</span>
</td>

<td><?= $p['fecha_pago'] ?></td>

</tr>

<?php endwhile; ?>

</table>

</div>
</div>

<!-- ================= CONTABILIDAD ================= -->

<div class="card mb-3">

<div class="card-header bg-dark text-white">
Contabilidad
</div>

<div class="card-body">

<table class="table">

<tr>
<th>Estado</th>
<th>Boleta cuenta</th>
<th>Boleta total</th>
<th>Comp adicional</th>
<th>Detraccion</th>
</tr>

<tr>

<td><?= $op['estado'] ?? '-' ?></td>

<td><?= $op['nro_boleta_cuenta'] ?? '-' ?></td>

<td><?= $op['nro_boleta_total'] ?? '-' ?></td>

<td><?= $op['Nro_Comprobante_adicional'] ?? '-' ?></td>

<td><?= $op['detraccion'] ?? '-' ?></td>

</tr>

</table>

</div>
</div>



</div>

</body>
</html>