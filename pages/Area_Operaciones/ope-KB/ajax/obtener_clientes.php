<?php
include '../../../conexion.php';

// ============================
// FILTROS
// ============================
$desde = $_GET['desde'] ?? date('Y-m-01');
$hasta = $_GET['hasta'] ?? date('Y-m-d');
$tourFiltro = $_GET['tour'] ?? '';

$filtroSQL = "";
if(!empty($tourFiltro)){
    $tourFiltro = intval($tourFiltro);
    $filtroSQL = "AND od.id_servicio = $tourFiltro";
}

// ============================
// VENTAS
// ============================
$qVentas = mysqli_query($conexion,"
SELECT SUM(total_operacion) total 
FROM operaciones 
WHERE fecha_reserva BETWEEN '$desde' AND '$hasta'
");
$totalVentas = mysqli_fetch_assoc($qVentas)['total'] ?? 0;

$qPagado = mysqli_query($conexion,"
SELECT SUM(monto) total 
FROM pagos 
WHERE tipo='tour' 
AND fecha BETWEEN '$desde' AND '$hasta'
");
$pagado = mysqli_fetch_assoc($qPagado)['total'] ?? 0;

$pendiente = $totalVentas - $pagado;

// ============================
// KPIs POR TOUR
// ============================
$qKPITour = mysqli_query($conexion,"
SELECT 
COUNT(*) as total_tours,
SUM(od.precio) as total_ventas,
SUM(CASE WHEN p.tipo='tour' THEN p.monto ELSE 0 END) as total_pagado
FROM operaciones_detalle od
JOIN operaciones o ON o.id_operaciones = od.id_operaciones
LEFT JOIN pagos p ON p.id_operaciones = o.id_operaciones
WHERE o.fecha_reserva BETWEEN '$desde' AND '$hasta'
$filtroSQL
");

$dataTour = mysqli_fetch_assoc($qKPITour);

$totalTours = $dataTour['total_tours'] ?? 0;
$totalVentasTour = $dataTour['total_ventas'] ?? 0;
$totalPagadoTour = $dataTour['total_pagado'] ?? 0;
$pendienteTour = $totalVentasTour - $totalPagadoTour;

// ============================
// CLIENTES CON DEUDA
// ============================
$qDeuda = mysqli_query($conexion,"
SELECT d.nombre, d.apellido,
(o.total_operacion - IFNULL(SUM(p.monto),0)) as deuda
FROM operaciones o
JOIN datos_clientes d ON d.id_cliente = o.id_cliente
LEFT JOIN pagos p ON p.id_operaciones = o.id_operaciones
GROUP BY o.id_operaciones
HAVING deuda > 0
LIMIT 5
");

// ============================
// TOP TOURS
// ============================
$qTop = mysqli_query($conexion,"
SELECT s.nombre, COUNT(*) total
FROM operaciones_detalle od
JOIN servicios s ON s.id_servicio = od.id_servicio
GROUP BY od.id_servicio
ORDER BY total DESC
LIMIT 5
");

// ============================
// TOURS DEL MES
// ============================
$qToursMes = mysqli_query($conexion,"
SELECT s.nombre, COUNT(*) total
FROM operaciones_detalle od
JOIN operaciones o ON o.id_operaciones = od.id_operaciones
JOIN servicios s ON s.id_servicio = od.id_servicio
WHERE o.fecha_reserva BETWEEN '$desde' AND '$hasta'
GROUP BY s.nombre
ORDER BY total DESC
");

// ============================
// SALKANTAY
// ============================
$qSalkantay = mysqli_query($conexion,"
SELECT COUNT(*) total
FROM operaciones_detalle od
JOIN operaciones o ON o.id_operaciones = od.id_operaciones
JOIN servicios s ON s.id_servicio = od.id_servicio
WHERE s.nombre LIKE '%Salkantay%'
AND s.nombre LIKE '%4%'
AND o.fecha_reserva BETWEEN '$desde' AND '$hasta'
");

$salkantayTotal = mysqli_fetch_assoc($qSalkantay)['total'] ?? 0;

// ============================
// CONTABILIDAD
// ============================
$qConta = mysqli_query($conexion,"
SELECT estado, COUNT(*) total 
FROM contabilidad 
GROUP BY estado
");

$estados = [];
while($row = mysqli_fetch_assoc($qConta)){
    $estados[$row['estado']] = $row['total'];
}
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>CRM ERP PRO</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body class="bg-light">
<?php include '../../sidebar.php'; ?>

<div class="container mt-4">

<h3 class="mb-3">🚀 CRM + ERP PRO</h3>

<!-- FILTROS -->
<form class="row g-2 mb-4">

<div class="col-md-3">
<input type="date" name="desde" value="<?= $desde ?>" class="form-control">
</div>

<div class="col-md-3">
<input type="date" name="hasta" value="<?= $hasta ?>" class="form-control">
</div>

<div class="col-md-3">
<select name="tour" class="form-select">
<option value="">-- Todos los tours --</option>
<?php
$qServiciosFiltro = mysqli_query($conexion,"SELECT id_servicio,nombre FROM servicios WHERE activo=1");
while($s=mysqli_fetch_assoc($qServiciosFiltro)){
    $selected = ($_GET['tour'] ?? '') == $s['id_servicio'] ? 'selected' : '';
    echo "<option value='{$s['id_servicio']}' $selected>{$s['nombre']}</option>";
}
?>
</select>
</div>

<div class="col-md-2">
<button class="btn btn-primary w-100">Filtrar</button>
</div>

</form>

<!-- KPIs -->
<div class="row g-3 mb-4">
<div class="col-md-3"><div class="card p-3 text-center shadow"><h6>Ventas</h6><h4>S/ <?= number_format($totalVentas,2) ?></h4></div></div>
<div class="col-md-3"><div class="card p-3 text-center shadow"><h6>Pagado</h6><h4>S/ <?= number_format($pagado,2) ?></h4></div></div>
<div class="col-md-3"><div class="card p-3 text-center shadow"><h6>Pendiente</h6><h4>S/ <?= number_format($pendiente,2) ?></h4></div></div>
<div class="col-md-3"><div class="card p-3 text-center shadow border-success"><h6>Salkantay 4D</h6><h4 class="text-success"><?= $salkantayTotal ?></h4></div></div>
</div>

<div class="row">

<!-- IZQUIERDA -->
<div class="col-md-8">

<div class="row mb-4">
<div class="col-md-6">
<div class="card shadow p-3">
<h6>Contabilidad</h6>
<canvas id="chartConta"></canvas>
</div>
</div>

<div class="col-md-6">
<div class="card shadow p-3">
<h6>Top Tours</h6>
<canvas id="chartTop"></canvas>
</div>
</div>
</div>

<div class="card shadow p-3 mb-4">
<h6>Tours del mes</h6>
<canvas id="chartTours"></canvas>
</div>

</div>

<!-- DERECHA -->
<div class="col-md-4">

<div class="card shadow border-dark mb-4">
<div class="card-header bg-dark text-white">📊 Resumen del Tour</div>
<div class="card-body">

<p><strong>Total Tours:</strong> <?= $totalTours ?></p>

<p><strong>Ventas:</strong>
<span class="badge bg-primary">S/ <?= number_format($totalVentasTour,2) ?></span>
</p>

<p><strong>Pagado:</strong>
<span class="badge bg-success">S/ <?= number_format($totalPagadoTour,2) ?></span>
</p>

<p><strong>Pendiente:</strong>
<span class="badge bg-danger">S/ <?= number_format($pendienteTour,2) ?></span>
</p>

</div>
</div>

</div>

</div>

<!-- CLIENTES CON DEUDA -->
<div class="card shadow mb-4">
<div class="card-header bg-danger text-white">Clientes con deuda</div>
<div class="card-body">
<table class="table">
<tr><th>Cliente</th><th>Deuda</th></tr>
<?php while($d=mysqli_fetch_assoc($qDeuda)): ?>
<tr>
<td><?= $d['nombre'].' '.$d['apellido'] ?></td>
<td><span class="badge bg-danger">S/ <?= $d['deuda'] ?></span></td>
</tr>
<?php endwhile; ?>
</table>
</div>
</div>

<!-- TABLA TOURS -->
<div class="card shadow mb-4">
<div class="card-header bg-info text-white">Tours realizados</div>
<div class="card-body">
<table class="table table-bordered">
<tr><th>Tour</th><th>Cantidad</th></tr>
<?php while($t=mysqli_fetch_assoc($qToursMes)): ?>
<tr>
<td><?= $t['nombre'] ?></td>
<td><span class="badge bg-primary"><?= $t['total'] ?></span></td>
</tr>
<?php endwhile; ?>
</table>
</div>
</div>

</div>

<script>
new Chart(document.getElementById('chartConta'),{
 type:'pie',
 data:{labels:['Pendiente','Pagado','Cancelado'],
 datasets:[{data:[<?= $estados['pendiente']??0 ?>,<?= $estados['pagado']??0 ?>,<?= $estados['cancelado']??0 ?>]}]}
});

new Chart(document.getElementById('chartTop'),{
 type:'bar',
 data:{
 labels:[<?php mysqli_data_seek($qTop,0); while($t=mysqli_fetch_assoc($qTop)){ echo "'".$t['nombre']."',"; } ?>],
 datasets:[{data:[<?php mysqli_data_seek($qTop,0); while($t=mysqli_fetch_assoc($qTop)){ echo $t['total'].","; } ?>]}]
 }
});

new Chart(document.getElementById('chartTours'),{
 type:'bar',
 data:{
 labels:[<?php mysqli_data_seek($qToursMes,0); while($t=mysqli_fetch_assoc($qToursMes)){ echo "'".$t['nombre']."',"; } ?>],
 datasets:[{data:[<?php mysqli_data_seek($qToursMes,0); while($t=mysqli_fetch_assoc($qToursMes)){ echo $t['total'].","; } ?>]}]
 }
});
</script>

</body>
</html>