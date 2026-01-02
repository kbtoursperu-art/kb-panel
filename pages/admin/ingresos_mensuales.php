<?php
// ================================
// INCLUDES
// ================================
include('../../conexion.php');

// ================================
// PARAMETROS
// ================================
$year   = $_GET['year'] ?? date('Y');
$month  = $_GET['month'] ?? '';
$estado = $_GET['estado'] ?? '';
$moneda = $_GET['moneda'] ?? '';

// ================================
// QUERY BASE
// ================================
$query = "
    SELECT 
        MONTH(fecha_pago_saldo) AS mes,
        SUM(COALESCE(precio_servicio,0)) AS total_ingresos,
        SUM(COALESCE(comision,0)) AS total_comisiones,
        SUM(COALESCE(pagado_a_cuenta,0)) AS total_pagado,
        SUM(COALESCE(saldo_pendiente,0)) AS total_saldo
    FROM Contabilidad
    WHERE fecha_pago_saldo IS NOT NULL
      AND YEAR(fecha_pago_saldo) = ?
";

$params = [$year];
$types  = "i";

if ($month !== '') {
    $query .= " AND MONTH(fecha_pago_saldo) = ?";
    $params[] = $month;  $types .= "i";
}
if ($estado !== '') {
    $query .= " AND estado = ?";
    $params[] = $estado; $types .= "s";
}
if ($moneda !== '') {
    $query .= " AND tipo_moneda = ?";
    $params[] = $moneda; $types .= "s";
}

$query .= " GROUP BY mes ORDER BY mes";

// ================================
// EJECUCION
// ================================
$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
$datos = mysqli_fetch_all($resultado, MYSQLI_ASSOC);

// ================================
// MESES
// ================================
$meses = [
    1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',5=>'Mayo',6=>'Junio',
    7=>'Julio',8=>'Agosto',9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'
];

$ingresos = $comisiones = $pagado = $saldo = array_fill(1, 12, 0);

foreach ($datos as $d) {
    $ingresos[$d['mes']]   = (float)$d['total_ingresos'];
    $comisiones[$d['mes']] = (float)$d['total_comisiones'];
    $pagado[$d['mes']]     = (float)$d['total_pagado'];
    $saldo[$d['mes']]      = (float)$d['total_saldo'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ingresos Mensuales - KB Adventures</title>

<!-- BOOTSTRAP -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- CSS GENERAL DEL SISTEMA -->
<link rel="stylesheet" href="css.css">

<!-- AJUSTE para evitar que se distorsione el sidebar -->
<style>
.content {
    margin-left: 260px !important; 
    padding: 25px;
}
@media (max-width: 992px){
    .content {
        margin-left: 0 !important;
    }
}
</style>

</head>
<body>
<?php 
include('../sidebar.php'); 
 ?>
<!-- ============================
     CONTENIDO
============================= -->
<div class="content">
<div class="container-fluid">

    <h2 class="fw-bold text-primary mb-4">Resumen de Ingresos por Mes - <?= $year ?></h2>

    <!-- FILTROS -->
    <form class="row g-2 mb-4" method="GET">

        <div class="col-md-2 col-6">
            <label class="fw-semibold">Año</label>
            <input type="number" name="year" class="form-control" value="<?= $year ?>">
        </div>

        <div class="col-md-2 col-6">
            <label class="fw-semibold">Mes</label>
            <select name="month" class="form-select">
                <option value="">Todos</option>
                <?php foreach ($meses as $n => $m): ?>
                    <option value="<?= $n ?>" <?= ($month==$n?'selected':'') ?>><?= $m ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-md-2 col-6">
            <label class="fw-semibold">Estado</label>
            <select name="estado" class="form-select">
                <option value="">Todos</option>
                <option value="pagado" <?= ($estado=='pagado'?'selected':'') ?>>Pagado</option>
                <option value="pendiente" <?= ($estado=='pendiente'?'selected':'') ?>>Pendiente</option>
                <option value="reembolsado" <?= ($estado=='reembolsado'?'selected':'') ?>>Reembolsado</option>
            </select>
        </div>

        <div class="col-md-2 col-6">
            <label class="fw-semibold">Moneda</label>
            <select name="moneda" class="form-select">
                <option value="">Todas</option>
                <option value="Soles" <?= ($moneda=='Soles'?'selected':'') ?>>Soles</option>
                <option value="Dólares" <?= ($moneda=='Dólares'?'selected':'') ?>>Dólares</option>
            </select>
        </div>

        <div class="col-md-2 col-12 align-self-end">
            <button class="btn btn-primary w-100">Filtrar</button>
        </div>

    </form>

    <!-- GRAFICO -->
    <div class="card shadow-sm mb-4">
        <div class="card-body" style="height: 400px;">
            <canvas id="graficoIngresos"></canvas>
        </div>
    </div>

    <!-- TABLA -->
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-bordered table-striped mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Mes</th>
                        <th>Ingresos</th>
                        <th>Comisiones</th>
                        <th>Pagado</th>
                        <th>Saldo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>

                <tbody>
                <?php foreach ($meses as $n=>$m): ?>
                    <tr>
                        <td><?= $m ?></td>
                        <td>S/ <?= number_format($ingresos[$n],2) ?></td>
                        <td>S/ <?= number_format($comisiones[$n],2) ?></td>
                        <td>S/ <?= number_format($pagado[$n],2) ?></td>
                        <td>S/ <?= number_format($saldo[$n],2) ?></td>
                        <td>
                            <a href="ver-detalles-ingresosMensuales.php?year=<?= $year ?>&mes=<?= $n ?>" 
                               class="btn btn-info btn-sm">
                                Ver detalles
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>

            </table>
        </div>
    </div>

</div>
</div>

<!-- CHART JS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('graficoIngresos'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_values($meses)) ?>,
        datasets: [
            { label:'Ingresos', data:<?= json_encode(array_values($ingresos)) ?> },
            { label:'Comisiones', data:<?= json_encode(array_values($comisiones)) ?> },
            { label:'Pagado', data:<?= json_encode(array_values($pagado)) ?> },
            { label:'Saldo', data:<?= json_encode(array_values($saldo)) ?> }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales:{ y:{ beginAtZero:true } }
    }
});
</script>

</body>
</html>
