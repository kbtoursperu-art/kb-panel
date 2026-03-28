<?php
include('../../conexion.php');
date_default_timezone_set('America/Lima');

// ============================
// CONFIGURACIÓN DE FECHAS
// ============================
$tipo = $_GET['tipo'] ?? 'dia';
$hoy = date('Y-m-d');

switch($tipo){
    case 'semana':
        $desde = date('Y-m-d', strtotime('monday this week'));
        $hasta = date('Y-m-d', strtotime('sunday this week'));
        break;

    case 'mes':
        $desde = date('Y-m-01');
        $hasta = date('Y-m-t');
        break;

    case 'anio': // 🔥 NUEVO
        $desde = date('Y-01-01');
        $hasta = date('Y-12-31');
        break;

    default: // día
        $desde = $hoy;
        $hasta = $hoy;
        break;
}

// ============================
// TIPO DE CAMBIO (último)
// ============================
$tc = 3.80; // fallback
$qtc = mysqli_query($conexion,"SELECT cambio FROM tipo_cambio ORDER BY fecha DESC LIMIT 1");
if($r = mysqli_fetch_assoc($qtc)){
    $tc = (float)$r['cambio'];
}

// ============================
// QUERY COMPLETA DASHBOARD
// ============================
$sql = " 

/* =========================
   PAGOS REALES (o reserva si NULL)
========================= */
SELECT 
    p.id_operaciones,
    p.tipo_pago,
    p.metodo_pago,
    p.tipo_moneda,
    p.monto,
    COALESCE(p.fecha_pago, o.fecha_reserva) AS fecha_pago,
    o.fecha_reserva,
    CONCAT(d.nombre,' ',d.apellido) AS cliente,
    'PAGO' as origen
FROM pagos_operacion p
LEFT JOIN operaciones o ON o.id_operaciones = p.id_operaciones
LEFT JOIN datos_clientes d ON d.id_cliente = o.id_cliente
AND DATE(COALESCE(p.fecha_pago, o.fecha_reserva)) BETWEEN '$desde' AND '$hasta'

UNION ALL

/* =========================
   CONTABILIDAD (PAGADO A CUENTA o reserva si NULL)
========================= */
SELECT 
    c.id_operaciones,
    'cuenta' as tipo_pago,
    c.metodo_pago,
    c.tipo_moneda,
    c.pagado_a_cuenta as monto,
    COALESCE(c.fecha_pago_saldo, o.fecha_reserva) AS fecha_pago,
    o.fecha_reserva,
    CONCAT(d.nombre,' ',d.apellido) AS cliente,
    'CONTABILIDAD' as origen
FROM contabilidad c
LEFT JOIN operaciones o ON o.id_operaciones = c.id_operaciones
LEFT JOIN datos_clientes d ON d.id_cliente = o.id_cliente
WHERE c.pagado_a_cuenta > 0
AND DATE(COALESCE(c.fecha_pago_saldo, o.fecha_reserva)) BETWEEN '$desde' AND '$hasta'

UNION ALL

/* =========================
   CONTABILIDAD ADICIONAL (o reserva si NULL)
========================= */
SELECT 
    c.id_operaciones,
    'adicional' as tipo_pago,
    c.metodo_pago_adicional,
    c.tipo_moneda_adicional,
    c.pagado_adicional as monto,
    COALESCE(c.fecha_pago_saldo, o.fecha_reserva) AS fecha_pago,
    o.fecha_reserva,
    CONCAT(d.nombre,' ',d.apellido) AS cliente,
    'CONTABILIDAD_EXTRA' as origen
FROM contabilidad c
LEFT JOIN operaciones o ON o.id_operaciones = c.id_operaciones
LEFT JOIN datos_clientes d ON d.id_cliente = o.id_cliente
WHERE c.pagado_adicional > 0
  AND COALESCE(c.fecha_pago_saldo, o.fecha_reserva) BETWEEN '$desde' AND '$hasta'

ORDER BY fecha_pago DESC
";

$res = mysqli_query($conexion, $sql);
if (!$res) {
    die("Error en la consulta SQL: " . mysqli_error($conexion));
}

// ============================
// PROCESO DE DATOS
// ============================
$total_soles = 0;
$total_dolares = 0;
$total_convertido = 0;
$data = [];
$metodos = [];

while($row = mysqli_fetch_assoc($res)){
    $monto = (float)$row['monto'];
    $moneda = strtolower(trim($row['tipo_moneda'] ?? 'soles'));

    if(in_array($moneda, ['dólares','dolares','usd','$'])){
        $total_dolares += $monto;
        $total_convertido += $monto * $tc;
    } else { // Soles u otro
        $total_soles += $monto;
        $total_convertido += $monto;
    }

    $metodo = $row['metodo_pago'] ?? 'Otros';
    if(!isset($metodos[$metodo])) $metodos[$metodo] = 0;
    $metodos[$metodo] += $monto;

    $data[] = $row;
}
?>
<?php
$titulos = [
    'dia' => 'HOY',
    'semana' => 'SEMANA',
    'mes' => 'MES',
    'anio' => 'AÑO'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Dashboard Ingresos PRO</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.content{ margin-left:260px; padding:25px }
.card{ border-radius:12px }
</style>
</head>
<body>

<?php include '../sidebar.php'; ?>

<div class="content">
<div class="container-fluid">
<h3 class="fw-bold mb-4">
💰 Dashboard Ingresos (<?= $titulos[$tipo] ?? 'HOY' ?>)
</h3>

<!-- BOTONES -->
<div class="mb-3">
    <a href="?tipo=dia" class="btn btn-outline-primary">Hoy</a>
    <a href="?tipo=semana" class="btn btn-outline-primary">Semana</a>
    <a href="?tipo=mes" class="btn btn-outline-primary">Mes</a>
    <a href="?tipo=anio" class="btn btn-outline-primary">Año</a>

    <a href="?tipo=<?= $tipo ?>&excel=1" class="btn btn-success float-end">
        📥 Exportar Excel
    </a>
</div>

<!-- CARDS -->
<div class="row mb-4">
<div class="col-md-3">
<div class="card shadow text-center">
<div class="card-body">
<h6>Soles</h6>
<h4 class="text-success">S/ <?= number_format($total_soles,2) ?></h4>
</div>
</div>
</div>

<div class="col-md-3">
<div class="card shadow text-center">
<div class="card-body">
<h6>Dólares</h6>
<h4 class="text-warning">$ <?= number_format($total_dolares,2) ?></h4>
</div>
</div>
</div>

<div class="col-md-3">
<div class="card shadow text-center">
<div class="card-body">
<h6>Total en Soles</h6>
<h4 class="text-primary">S/ <?= number_format($total_convertido,2) ?></h4>
</div>
</div>
</div>

<div class="col-md-3">
<div class="card shadow text-center">
<div class="card-body">
<h6>Tipo Cambio</h6>
<h5><?= $tc ?></h5>
</div>
</div>
</div>
</div>

<!-- GRAFICO -->
<div class="card mb-4 shadow">
<div class="card-body">
<canvas id="graficoMetodo" style="max-height:300px;"></canvas>
</div>
</div>

<!-- TABLA -->
<div class="card shadow">
<div class="table-responsive">
<table class="table table-bordered table-striped">
<thead class="table-dark">
<tr>
<th>Fecha</th>
<th>Cliente</th>
<th>Operación</th>
<th>Tipo</th>
<th>Método</th>
<th>Moneda</th>
<th>Monto</th>
<th>Origen</th>
</tr>
</thead>
<tbody>
<?php foreach($data as $row): ?>
<tr>
<td><?= $row['fecha_pago'] ?></td>
<td><?= $row['cliente'] ?></td>
<td>#<?= $row['id_operaciones'] ?></td>
<td><?= $row['tipo_pago'] ?></td>
<td><?= $row['metodo_pago'] ?></td>
<td><?= $row['tipo_moneda'] ?></td>
<td>
<?= strtolower($row['tipo_moneda'])=='soles' ? 'S/' : '$' ?>
<?= number_format($row['monto'],2) ?>
</td>
<td>
<?php if($row['origen']=='PAGO'): ?>
    <span class="badge bg-success">Real</span>
<?php else: ?>
    <span class="badge bg-warning text-dark">Contabilidad</span>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>

</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('graficoMetodo'),{
type:'pie',
data:{
labels: <?= json_encode(array_keys($metodos)) ?>,
datasets:[{ data: <?= json_encode(array_values($metodos)) ?> }]
}
});
</script>

</body>
</html>