<?php
include('../../conexion.php');
include('../sidebar.php');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!$conexion) {
    die("Error de conexión: " . mysqli_connect_error());
}

/* ==========================
   MÉTRICAS PRINCIPALES
========================== */
$metrics = [
    'reservas_activas' => 0,
    'tours_programados' => 0,
    'nuevos_clientes' => 0,
    'ingresos_mes' => 0,
    'ingresos_dia' => 0,
    'saldo_pendiente_total' => 0,
    'gastos_mes' => 0,
    'balance_mes' => 0
];

/* 🔹 Reservas activas */
$sql = "
SELECT COUNT(*) total
FROM Operaciones
WHERE fecha_salida IS NOT NULL
AND fecha_salida >= CURDATE()
";
$metrics['reservas_activas'] = mysqli_fetch_assoc(mysqli_query($conexion, $sql))['total'];

/* 🔹 Tours programados */
$sql = "SELECT COUNT(*) total FROM Operaciones";
$metrics['tours_programados'] = mysqli_fetch_assoc(mysqli_query($conexion, $sql))['total'];

/* 🔹 Nuevos clientes (30 días) */
$sql = "
SELECT COUNT(*) total 
FROM Datos_clientes 
WHERE fecha_registro >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
";
$metrics['nuevos_clientes'] = mysqli_fetch_assoc(mysqli_query($conexion, $sql))['total'];

/* 🔹 Ingresos reales del MES */
$sql = "
SELECT 
SUM(
    IFNULL(pagado_a_cuenta,0) + 
    IF(estado='pagado', IFNULL(saldo_pendiente,0), 0)
) total
FROM Contabilidad
WHERE fecha_pago_saldo IS NOT NULL
AND MONTH(fecha_pago_saldo)=MONTH(CURDATE())
AND YEAR(fecha_pago_saldo)=YEAR(CURDATE())
";
$metrics['ingresos_mes'] = mysqli_fetch_assoc(mysqli_query($conexion, $sql))['total'] ?? 0;

/* 🔹 Ingresos reales del DÍA */
$sql = "
SELECT 
SUM(
    IFNULL(pagado_a_cuenta,0) + 
    IF(estado='pagado', IFNULL(saldo_pendiente,0), 0)
) total
FROM Contabilidad
WHERE fecha_pago_saldo = CURDATE()
";
$metrics['ingresos_dia'] = mysqli_fetch_assoc(mysqli_query($conexion, $sql))['total'] ?? 0;

/* 🔹 Saldo pendiente TOTAL */
$sql = "
SELECT 
SUM(IFNULL(saldo_pendiente,0)) total
FROM Contabilidad
WHERE estado = 'pendiente'
";
$metrics['saldo_pendiente_total'] = mysqli_fetch_assoc(mysqli_query($conexion, $sql))['total'] ?? 0;

/* 🔹 Gastos (no existen aún) */
$metrics['gastos_mes'] = 0;
$metrics['balance_mes'] = $metrics['ingresos_mes'] - $metrics['gastos_mes'];

/* ==========================
   PRÓXIMOS TOURS
========================== */
$sql = "
SELECT nombre_servicio, fecha_salida
FROM Operaciones
WHERE fecha_salida IS NOT NULL
AND fecha_salida >= CURDATE()
ORDER BY fecha_salida ASC
LIMIT 5
";
$eventos = mysqli_fetch_all(mysqli_query($conexion, $sql), MYSQLI_ASSOC);

/* ==========================
   NOTIFICACIONES
========================== */
$sql = "
SELECT observaciones mensaje, fecha_reserva
FROM Operaciones
WHERE observaciones IS NOT NULL
AND observaciones <> ''
AND fecha_reserva IS NOT NULL
ORDER BY fecha_reserva DESC
LIMIT 5
";
$notificaciones = mysqli_fetch_all(mysqli_query($conexion, $sql), MYSQLI_ASSOC);

/* ==========================
   ESTADÍSTICAS
========================== */
$sql = "
SELECT 
MONTH(o.fecha_salida) mes,
o.nombre_servicio,
COUNT(o.id_operaciones) cantidad,
ROUND(AVG(IFNULL(c.precio_servicio,0)),2) precio_promedio
FROM Operaciones o
LEFT JOIN Contabilidad c ON o.id_operaciones = c.id_operaciones
WHERE o.fecha_salida IS NOT NULL
GROUP BY mes, o.nombre_servicio
ORDER BY mes ASC
";
$estadisticas = mysqli_query($conexion, $sql);

$meses = [
    1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',
    5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',
    9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Dashboard General - KB Adventures</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="css.css">
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>

<body>
<div class="content p-4">
<div class="container-fluid">

<h2 class="text-center text-primary fw-bold mb-4">
📊 Panel de Control - KB Adventures
</h2>

<!-- MÉTRICAS PRINCIPALES -->
<div class="row row-cols-1 row-cols-md-3 g-4 mb-4 text-center">
    <a href="reservas-activas.php">
<div class="col">
<div class="card bg-primary text-white">
<div class="card-body">
<h5>Reservas Activas</h5>
<h3><?= $metrics['reservas_activas'] ?></h3>
</div>
</div>
</div>
</a>
<a href="dr.php">
<div class="col">
<div class="card bg-success text-white">
<div class="card-body">
<h5>Tours Programados</h5>
<h3><?= $metrics['tours_programados'] ?></h3>
</div>
</div>
</div>
</a>
<a href="h.php">
<div class="col">
<div class="card bg-warning text-dark">
<div class="card-body">
<h5>Nuevos Clientes</h5>
<h3><?= $metrics['nuevos_clientes'] ?></h3>
</div>
</div>
</div>
</a>
</div>

<!-- FINANZAS -->
<div class="row row-cols-1 row-cols-md-4 g-4 mb-4 text-center">
<a href="ingresos_mensuales.php">
<div class="col">
<div class="card bg-info text-white">
<div class="card-body">
<h5>Ingresos del Mes</h5>
<h3>S/. <?= number_format($metrics['ingresos_mes'],2) ?></h3>
</div>
</div>
</div>
</a>
<a href="a.php">
<div class="col">
<div class="card bg-success text-white">
<div class="card-body">
<h5>Ingresos del Día</h5>
<h3>S/. <?= number_format($metrics['ingresos_dia'],2) ?></h3>
</div>
</div>
</div>
</a>
<a href="s.php">
<div class="col">
<div class="card bg-warning text-dark">
<div class="card-body">
<h5>Saldos Pendientes</h5>
<h3>S/. <?= number_format($metrics['saldo_pendiente_total'],2) ?></h3>
</div>
</div>
</div>
</a>
<a href="b.php">
<div class="col">
<div class="card bg-secondary text-white">
<div class="card-body">
<h5>Balance General</h5>
<h3>S/. <?= number_format($metrics['balance_mes'],2) ?></h3>
</div>
</div>
</div>
</a>
</div>

<!-- EVENTOS Y NOTIFICACIONES -->
<div class="row mb-4">
<div class="col-md-6">
<div class="card h-100">
<div class="card-header bg-info text-white fw-bold">📅 Próximos Tours</div>
<ul class="list-group list-group-flush">
<?php foreach($eventos as $e): ?>
<li class="list-group-item">
<strong><?= date('d/m/Y',strtotime($e['fecha_salida'])) ?></strong>
- <?= $e['nombre_servicio'] ?>
</li>
<?php endforeach; ?>
</ul>
</div>
</div>

<div class="col-md-6">
<div class="card h-100">
<div class="card-header bg-warning text-white fw-bold">🔔 Notificaciones</div>
<ul class="list-group list-group-flush">
<?php foreach($notificaciones as $n): ?>
<li class="list-group-item">
<strong><?= date('d/m/Y',strtotime($n['fecha_reserva'])) ?>:</strong>
<?= htmlspecialchars($n['mensaje']) ?>
</li>
<?php endforeach; ?>
</ul>
</div>
</div>
</div>

<!-- ESTADÍSTICAS -->
<div class="card mb-4">
<div class="card-header bg-dark text-white fw-bold">
📈 Estadísticas por Mes y Tour
</div>
<div class="control">
  <a href="tours_mes.php">
    <button>Más Detalles</button>
  </a>
</div>

<div class="table-responsive">
<table class="table table-striped text-center">
<thead class="table-dark">
<tr>
<th>Mes</th>
<th>Servicio</th>
<th>Cantidad</th>
<th>Precio Promedio</th>
</tr>
</thead>
<tbody>
<?php while($row=mysqli_fetch_assoc($estadisticas)): ?>
<tr>
<td><?= $meses[$row['mes']] ?></td>
<td><?= htmlspecialchars($row['nombre_servicio']) ?></td>
<td><?= $row['cantidad'] ?></td>
<td>S/. <?= number_format($row['precio_promedio'],2) ?></td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
</div>
</div>

</div>
</div>
</body>
</html>
