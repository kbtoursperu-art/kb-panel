<?php
include '../conexion.php';

date_default_timezone_set("America/Lima");

$hoy = date("Y-m-d");


// ===============================
# TOTAL OPERACIONES
// ===============================

$qTotalOp = mysqli_query($conexion,"
SELECT COUNT(*) total
FROM operaciones
");

$totalOp = mysqli_fetch_assoc($qTotalOp)['total'];


// ===============================
# TOTAL PASAJEROS
// ===============================

$qPax = mysqli_query($conexion,"
SELECT COUNT(*) total
FROM clientes_kb
");

$totalPax = mysqli_fetch_assoc($qPax)['total'];

// ===============================
// 💰 RESUMEN DEL DÍA POR MONEDA
// ===============================

$qResumenHoy = mysqli_query($conexion,"
SELECT 
c.tipo_moneda,

SUM(c.pagado_a_cuenta) as total_cobrado,
SUM(c.saldo_pendiente) as total_saldo

FROM contabilidad c
JOIN operaciones o 
ON o.id_operaciones = c.id_operaciones

WHERE o.fecha_reserva = CURDATE()

GROUP BY c.tipo_moneda
");
// ===============================
# TOURS HOY
// ===============================

$qHoy = mysqli_query($conexion,"
SELECT 
od.nombre_servicio,
od.fecha_salida,
o.Encargado,
o.empresa

FROM operaciones_detalle od
JOIN operaciones o 
ON o.id_operaciones = od.id_operaciones

WHERE od.fecha_salida = '$hoy'
");

$toursHoy = mysqli_num_rows($qHoy);
// ===============================
// LISTA TOURS HOY
// ===============================

$qListaHoy = mysqli_query($conexion,"
SELECT 
od.nombre_servicio,
od.fecha_salida,
o.Encargado,
o.empresa

FROM operaciones_detalle od
JOIN operaciones o 
ON o.id_operaciones = od.id_operaciones

WHERE od.fecha_salida = CURDATE()

ORDER BY od.nombre_servicio
");

// ===============================
// TOURS MAÑANA
// ===============================

$qManana = mysqli_query($conexion,"
SELECT 
od.nombre_servicio,
od.fecha_salida,
o.Encargado,
o.empresa

FROM operaciones_detalle od
JOIN operaciones o 
ON o.id_operaciones = od.id_operaciones

WHERE od.fecha_salida = DATE_ADD(CURDATE(), INTERVAL 1 DAY)
");
// ===============================
// TOURS POR EMPRESA
// ===============================

$qEmpresa = mysqli_query($conexion,"
SELECT 
empresa,
COUNT(*) total

FROM operaciones

GROUP BY empresa
");
// ===============================
// TOURS POR MES
// ===============================

$qMes = mysqli_query($conexion,"
SELECT 
DATE_FORMAT(fecha_salida,'%Y-%m') mes,
COUNT(*) total

FROM operaciones_detalle

GROUP BY mes
ORDER BY mes DESC
");
// ===============================
// TOURS POR TIPO
// ===============================

$qTipo = mysqli_query($conexion,"
SELECT 
nombre_servicio,
COUNT(*) total

FROM operaciones_detalle

GROUP BY nombre_servicio
ORDER BY total DESC
");
// ===============================
# TOURS PROXIMOS 3 DIAS
// ===============================

$qProx = mysqli_query($conexion,"
SELECT COUNT(*) total
FROM operaciones_detalle
WHERE fecha_salida
BETWEEN CURDATE()
AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
");

$toursProx = mysqli_fetch_assoc($qProx)['total'];


// ===============================
# DINERO COBRADO
// ===============================

$qCobrado = mysqli_query($conexion,"
SELECT SUM(pagado_a_cuenta) total
FROM contabilidad
");

$cobrado = mysqli_fetch_assoc($qCobrado)['total'] ?? 0;


// ===============================
# SALDO PENDIENTE
// ===============================

$qSaldo = mysqli_query($conexion,"
SELECT SUM(saldo_pendiente) total
FROM contabilidad
");

$saldo = mysqli_fetch_assoc($qSaldo)['total'] ?? 0;


// ===============================
# ARTICULOS EN USO
// ===============================

$qUso = mysqli_query($conexion,"
SELECT SUM(cantidad) total
FROM almacen_salidas
WHERE estado NOT IN ('Devuelto','devuelto')
");

$uso = mysqli_fetch_assoc($qUso)['total'] ?? 0;


// ===============================
# OPERACIONES POR ENCARGADO
// ===============================

$qEnc = mysqli_query($conexion,"
SELECT 
IFNULL(NULLIF(Encargado,''),'SIN ENCARGADO') as Encargado,
COUNT(*) total

FROM operaciones

GROUP BY Encargado
ORDER BY total DESC
");


// ===============================
# ULTIMAS OPERACIONES
// ===============================

$qUlt = mysqli_query($conexion,"
SELECT 
o.id_operaciones,
o.empresa,
o.Encargado,
o.fecha_reserva

FROM operaciones o
ORDER BY o.id_operaciones DESC
LIMIT 5
");
// ===============================
# ULTIMAS OBSERVACIONES
// ===============================
$qObs = mysqli_query($conexion,"
SELECT 
o.id_operaciones,
o.empresa,
o.observaciones,
o.fecha_reserva

FROM operaciones o

WHERE o.observaciones IS NOT NULL
AND o.observaciones <> ''

ORDER BY o.id_operaciones DESC
LIMIT 5
");

?>
<!DOCTYPE html>
<html>
<head>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

</head>
<body>
<?php include 'sidebar.php'; ?>

<div class="container mt-4">

<h3 class="mb-4 fw-bold text-primary">📊 Dashboard General</h3>
<!-- KPI CARDS -->
<div class="row g-3">

<?php
function card($titulo,$valor,$color){
echo "
<div class='col-md-3'>
<div class='card shadow-sm border-0 bg-$color text-white'>
<div class='card-body'>
<h6 class='text-uppercase'>$titulo</h6>
<h3 class='fw-bold'>$valor</h3>
</div>
</div>
</div>
";
}
card("Operaciones",$totalOp,"primary");
card("Pasajeros",$totalPax,"success");
card("Tours Hoy",$toursHoy,"warning");
card("Tours Próximos",$toursProx,"info");
card("Cobrado",number_format($cobrado,2),"success");
card("Saldo",number_format($saldo,2),"danger");
card("En Uso",$uso,"secondary");
?>

</div>

<hr>
<div class="card mb-4 shadow-sm">
<div class="card-header bg-success text-white">
💰 Resumen del Día (por moneda)
</div>

<table class="table table-bordered mb-0 text-center">

<tr>
<th>Moneda</th>
<th>Cobrado</th>
<th>Saldo</th>
</tr>

<?php while($r=mysqli_fetch_assoc($qResumenHoy)): ?>

<tr>
<td>
<strong><?= $r['tipo_moneda'] ?></strong>
</td>

<td class="text-success">
S/ <?= number_format($r['total_cobrado'],2) ?>
</td>

<td class="text-danger">
S/ <?= number_format($r['total_saldo'],2) ?>
</td>

</tr>

<?php endwhile; ?>

</table>
</div>
<h5>📝 Últimas observaciones</h5>

<table class="table table-sm table-bordered">

<tr>
<th>ID</th>
<th>Empresa</th>
<th>Observación</th>
<th>Fecha</th>
</tr>

<?php while($o=mysqli_fetch_assoc($qObs)): ?>
<tr>
<td><?= $o['id_operaciones'] ?></td>
<td><?= $o['empresa'] ?></td>
<td><?= $o['observaciones'] ?></td>
<td><?= $o['fecha_reserva'] ?></td>
</tr>
<?php endwhile; ?>

</table>
<!-- TOURS HOY -->
<div class="card mb-4 shadow-sm">
<div class="card-header bg-primary text-white">
📍 Tours Hoy
</div>

<div class="table-responsive">
<table class="table table-hover mb-0">
<thead class="table-light">
<tr>
<th>Servicio</th>
<th>Encargado</th>
<th>Empresa</th>
</tr>
</thead>

<tbody>
<?php while($t=mysqli_fetch_assoc($qListaHoy)): ?>
<tr>
<td><?= $t['nombre_servicio'] ?></td>
<td><?= $t['Encargado'] ?: '—' ?></td>
<td>
<span class="badge bg-dark">
<?= $t['empresa'] ?? 'SIN EMPRESA' ?>
</span>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</div>

<!-- TOURS MAÑANA -->
<div class="card mb-4 shadow-sm">
<div class="card-header bg-info text-white">
📅 Tours Mañana
</div>

<div class="table-responsive">
<table class="table table-hover mb-0">
<thead class="table-light">
<tr>
<th>Servicio</th>
<th>Encargado</th>
<th>Empresa</th>
</tr>
</thead>

<tbody>
<?php while($m=mysqli_fetch_assoc($qManana)): ?>
<tr>
<td><?= $m['nombre_servicio'] ?></td>
<td><?= $m['Encargado'] ?: '—' ?></td>
<td>
<span class="badge bg-secondary">
<?= $m['empresa'] ?? 'SIN EMPRESA' ?>
</span>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</div>

<!-- POR EMPRESA -->
<div class="card mb-4 shadow-sm">
<div class="card-header bg-dark text-white">
🏢 Tours por Empresa
</div>

<table class="table table-bordered mb-0 text-center">
<tr>
<th>Empresa</th>
<th>Total</th>
</tr>

<?php while($e=mysqli_fetch_assoc($qEmpresa)): ?>
<tr>
<td><?= $e['empresa'] ?? 'SIN EMPRESA' ?></td>
<td><strong><?= $e['total'] ?></strong></td>
</tr>
<?php endwhile; ?>

</table>
</div>

<!-- POR MES -->
<div class="card mb-4 shadow-sm">
<div class="card-header bg-success text-white">
📊 Tours por Mes
</div>

<table class="table table-striped mb-0 text-center">

<tr>
<th>Mes</th>
<th>Total</th>
</tr>

<?php while($m=mysqli_fetch_assoc($qMes)): ?>
<tr>
<td><?= $m['mes'] ?></td>
<td><strong><?= $m['total'] ?></strong></td>
</tr>
<?php endwhile; ?>

</table>
</div>

<!-- POR TIPO -->
<div class="card mb-4 shadow-sm">
<div class="card-header bg-warning">
🎯 Tours por Tipo
</div>

<table class="table table-hover mb-0">

<tr>
<th>Servicio</th>
<th>Total</th>
</tr>

<?php while($t=mysqli_fetch_assoc($qTipo)): ?>
<tr>
<td><?= $t['nombre_servicio'] ?></td>
<td><strong><?= $t['total'] ?></strong></td>
</tr>
<?php endwhile; ?>

</table>
</div>

<!-- ENCARGADOS -->
<div class="card mb-4 shadow-sm">
<div class="card-header bg-secondary text-white">
👤 Operaciones por Encargado
</div>

<table class="table table-bordered mb-0">

<tr>
<th>Encargado</th>
<th>Total</th>
</tr>

<?php while($e=mysqli_fetch_assoc($qEnc)): ?>
<tr>
<td><?= $e['Encargado'] ?></td>
<td><strong><?= $e['total'] ?></strong></td>
</tr>
<?php endwhile; ?>

</table>
</div>

<!-- ÚLTIMAS -->
<div class="card mb-4 shadow-sm">
<div class="card-header bg-dark text-white">
🕒 Últimas Operaciones
</div>

<table class="table table-striped mb-0">

<tr>
<th>ID</th>
<th>Empresa</th>
<th>Encargado</th>
<th>Fecha</th>
</tr>

<?php while($u=mysqli_fetch_assoc($qUlt)): ?>
<tr>
<td>#<?= $u['id_operaciones'] ?></td>
<td><?= $u['empresa'] ?? 'SIN EMPRESA' ?></td>
<td><?= $u['Encargado'] ?></td>
<td><?= $u['fecha_reserva'] ?></td>
</tr>
<?php endwhile; ?>

</table>
</div>

</div>
</body>
</html>