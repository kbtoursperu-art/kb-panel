<?php
include('../../conexion.php');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);

/* =========================
   MÉTRICAS
========================= */
$metrics = [
    'reservas_activas' => 0,
    'tours_programados' => 0,
    'nuevos_clientes' => 0,
    'ingresos_dia' => 0,
    'saldo_pendiente_total' => 0
];

/* 🔹 Reservas activas */
$res = mysqli_query($conexion, "SELECT COUNT(*) total FROM operaciones_detalle WHERE fecha_salida >= CURDATE()");
$metrics['reservas_activas'] = mysqli_fetch_assoc($res)['total'];

/* 🔹 Tours */
$res = mysqli_query($conexion, "SELECT COUNT(*) total FROM operaciones_detalle");
$metrics['tours_programados'] = mysqli_fetch_assoc($res)['total'];

/* 🔹 Clientes */
$res = mysqli_query($conexion, "SELECT COUNT(*) total FROM datos_clientes WHERE fecha_registro >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$metrics['nuevos_clientes'] = mysqli_fetch_assoc($res)['total'];

/* 🔹 Ingresos día */
$res = mysqli_query($conexion,"
SELECT SUM(
COALESCE(c.pagado_a_cuenta,0)+
COALESCE(c.pagado_adicional,0)+
COALESCE(c.monto_pago_saldo,0)
) total
FROM contabilidad c
INNER JOIN operaciones o ON c.id_operaciones=o.id_operaciones
WHERE DATE(o.fecha_reserva)=CURDATE()
");
$metrics['ingresos_dia'] = mysqli_fetch_assoc($res)['total'] ?? 0;

/* 🔹 Saldos */
$res = mysqli_query($conexion,"SELECT SUM(IFNULL(saldo_pendiente,0)) total FROM contabilidad WHERE estado='pendiente'");
$metrics['saldo_pendiente_total'] = mysqli_fetch_assoc($res)['total'] ?? 0;

/* =========================
   GRÁFICO (7 días)
========================= */
$labels = [];
$data = [];

$q = mysqli_query($conexion,"
SELECT DATE(o.fecha_reserva) fecha,
SUM(
COALESCE(c.pagado_a_cuenta,0)+
COALESCE(c.pagado_adicional,0)+
COALESCE(c.monto_pago_saldo,0)
) total
FROM contabilidad c
INNER JOIN operaciones o ON c.id_operaciones=o.id_operaciones
WHERE o.fecha_reserva >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
GROUP BY fecha
ORDER BY fecha
");

while($row = mysqli_fetch_assoc($q)){
    $labels[] = date('d/m', strtotime($row['fecha']));
    $data[] = (float)$row['total'];
}

/* =========================
   TOP TOURS
========================= */
$top = mysqli_query($conexion,"
SELECT nombre_servicio, COUNT(*) total
FROM operaciones_detalle
GROUP BY nombre_servicio
ORDER BY total DESC
LIMIT 5
");

/* =========================
   EVENTOS
========================= */
$eventos = mysqli_fetch_all(mysqli_query($conexion,"
SELECT nombre_servicio, fecha_salida 
FROM operaciones_detalle 
WHERE fecha_salida >= CURDATE() 
ORDER BY fecha_salida ASC LIMIT 5
"), MYSQLI_ASSOC);

/* =========================
   NOTIFICACIONES
========================= */
$notificaciones = mysqli_fetch_all(mysqli_query($conexion,"
SELECT observaciones mensaje, fecha_reserva 
FROM operaciones 
WHERE observaciones <> '' 
ORDER BY fecha_reserva DESC LIMIT 5
"), MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Dashboard Gerencial</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
body { background:#f4f6f9; }
.card { border-radius:15px; }
</style>
</head>

<body>
<div class="container-fluid p-4">

<h3 class="fw-bold mb-4">📊 Panel Gerencial</h3>

<div class="row g-4">

<!-- RESERVAS -->
<div class="col-md-3">
<div class="card shadow border-0 h-100 text-center">
<div class="card-body">
<h6 class="text-muted">Reservas Activas</h6>
<h2 id="kpi_reservas" class="fw-bold text-primary">0</h2>
</div>
</div>
</div>

<!-- TOURS -->
<div class="col-md-3">
<div class="card shadow border-0 h-100 text-center">
<div class="card-body">
<h6 class="text-muted">Tours Programados</h6>
<h2 id="kpi_tours" class="fw-bold text-success">0</h2>
</div>
</div>
</div>

<!-- CLIENTES -->
<div class="col-md-3">
<div class="card shadow border-0 h-100 text-center">
<div class="card-body">
<h6 class="text-muted">Clientes Nuevos</h6>
<h2 id="kpi_clientes" class="fw-bold text-warning">0</h2>
</div>
</div>
</div>

<!-- INGRESOS CLICKABLE -->
<div class="col-md-3">
<a href="ingreso-dia.php" target="_blank" class="text-decoration-none">
<div class="card shadow border-0 h-100 bg-dark text-white text-center" style="cursor:pointer;">
<div class="card-body">
<h6 class="text-light">Ingresos del Día</h6>
<h2 id="kpi_ingresos" class="fw-bold text-success">S/. 0</h2>
<small>Ver detalle financiero →</small>
</div>
</div>
</a>
</div>

</div>

<!-- SEGUNDA FILA -->
<div class="row g-4 mt-2">

<!-- GRÁFICO -->
<div class="col-md-8">
<div class="card shadow border-0">
<div class="card-body">
<h6 class="fw-bold">Ingresos últimos 7 días</h6>
<canvas id="chart"></canvas>
</div>
</div>
</div>

<!-- SALDOS -->
<div class="col-md-4">
<div class="card shadow border-0 bg-warning text-center">
<div class="card-body">
<h6>Saldos Pendientes</h6>
<h2 id="kpi_saldos">S/. 0</h2>
</div>
</div>
</div>

</div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
let chart;

function initChart(labels, data){
    chart = new Chart(document.getElementById('chart'), {
        type:'line',
        data:{
            labels:labels,
            datasets:[{
                label:'Ingresos',
                data:data,
                tension:0.4
            }]
        }
    });
}

function loadDashboard(){

fetch('api_dashboard.php')
.then(r=>r.json())
.then(data=>{

    console.log(data);

    document.getElementById('kpi_reservas').innerText = data.reservas_activas;
    document.getElementById('kpi_tours').innerText = data.tours;
    document.getElementById('kpi_clientes').innerText = data.clientes;
    document.getElementById('kpi_ingresos').innerText = "S/. " + data.ingresos.toFixed(2);
    document.getElementById('kpi_saldos').innerText = "S/. " + data.saldos.toFixed(2);

    if(chart){
        chart.data.labels = data.labels;
        chart.data.datasets[0].data = data.values;
        chart.update();
    }else{
        initChart(data.labels, data.values);
    }

})
.catch(error=>{
    console.error("ERROR:", error);
});

}

loadDashboard();
setInterval(loadDashboard, 5000);
</script>

</body>
</html>