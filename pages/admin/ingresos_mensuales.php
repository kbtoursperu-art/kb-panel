<?php
include('../../conexion.php');

date_default_timezone_set('America/Lima');

/* ================================
   PARAMETROS
================================ */
$year   = $_GET['year'] ?? date('Y');
$month  = $_GET['month'] ?? '';
$estado = $_GET['estado'] ?? '';
$moneda = $_GET['moneda'] ?? '';

/* ================================
   QUERY
================================ */
$query = "
SELECT 
    MONTH(o.fecha_reserva) AS mes,

    SUM(COALESCE(c.pagado_a_cuenta,0))  AS ingreso_general,
    SUM(COALESCE(c.pagado_adicional,0)) AS ingreso_adicional,
    SUM(COALESCE(c.monto_pago_saldo,0)) AS ingreso_saldo,

    SUM(
        COALESCE(c.pagado_a_cuenta,0) +
        COALESCE(c.pagado_adicional,0) +
        COALESCE(c.monto_pago_saldo,0)
    ) AS ingreso_total,

    SUM(COALESCE(c.comision,0)) AS total_comisiones,
    SUM(COALESCE(c.saldo_pendiente,0)) AS total_saldo

FROM contabilidad c
INNER JOIN operaciones o 
    ON c.id_operaciones = o.id_operaciones

WHERE o.fecha_reserva IS NOT NULL
  AND YEAR(o.fecha_reserva) = ?
";

$params = [$year];
$types  = "i";

/* ================================
   FILTROS
================================ */
if ($month !== '') {
    $query .= " AND MONTH(o.fecha_reserva) = ?";
    $params[] = $month;
    $types .= "i";
}

if ($estado !== '') {
    $query .= " AND estado = ?";
    $params[] = $estado;
    $types .= "s";
}

if ($moneda !== '') {
    $query .= " AND tipo_moneda = ?";
    $params[] = $moneda;
    $types .= "s";
}

$query .= " GROUP BY mes ORDER BY mes";

/* ================================
   EJECUCION
================================ */
$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_all($res, MYSQLI_ASSOC);

/* ================================
   MESES
================================ */
$meses = [
1=>'Enero',2=>'Febrero',3=>'Marzo',4=>'Abril',
5=>'Mayo',6=>'Junio',7=>'Julio',8=>'Agosto',
9=>'Septiembre',10=>'Octubre',11=>'Noviembre',12=>'Diciembre'
];

/* ================================
   ARRAYS
================================ */
$general = $adicional = $saldoPago = $total = $comisiones = $saldoPend = array_fill(1,12,0);

foreach ($data as $d) {
    $m = (int)$d['mes'];
    $general[$m]    = (float)$d['ingreso_general'];
    $adicional[$m]  = (float)$d['ingreso_adicional'];
    $saldoPago[$m]  = (float)$d['ingreso_saldo'];
    $total[$m]      = (float)$d['ingreso_total'];
    $comisiones[$m] = (float)$d['total_comisiones'];
    $saldoPend[$m]  = (float)$d['total_saldo'];
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Ingresos Mensuales</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="css.css">

<style>
.content{ margin-left:260px; padding:25px }
@media(max-width:992px){ .content{ margin-left:0 } }
</style>
</head>

<body>
<?php include '../sidebar.php'; ?>
<div class="content p-4">
<div class="container-fluid">

<h3 class="fw-bold text-primary mb-4">📊 Ingresos Mensuales <?= $year ?></h3>

<!-- FILTROS -->
<form class="row g-2 mb-4">
    <div class="col-md-2">
        <label>Año</label>
        <input type="number" name="year" class="form-control" value="<?= $year ?>">
    </div>

    <div class="col-md-2">
        <label>Mes</label>
        <select name="month" class="form-select">
            <option value="">Todos</option>
            <?php foreach($meses as $n=>$m): ?>
                <option value="<?= $n ?>" <?= $month==$n?'selected':'' ?>><?= $m ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="col-md-2">
        <label>Estado</label>
        <select name="estado" class="form-select">
    <option value="">Todos</option>
    <option value="pagado" <?= $estado=='pagado'?'selected':'' ?>>Pagado</option>
    <option value="pendiente" <?= $estado=='pendiente'?'selected':'' ?>>Pendiente</option>
</select>

    </div>

    <div class="col-md-2">
        <label>Moneda</label>
        <select name="moneda" class="form-select">
    <option value="">Todas</option>
    <option value="Soles" <?= $moneda=='Soles'?'selected':'' ?>>Soles</option>
    <option value="Dólares" <?= $moneda=='Dólares'?'selected':'' ?>>Dólares</option>
</select>
    </div>

    <div class="col-md-2 align-self-end">
        <button class="btn btn-primary w-100">Filtrar</button>
    </div>
</form>

<!-- GRAFICO -->
<div class="card mb-4 shadow-sm">
    <div class="card-body" style="height:380px">
        <canvas id="grafico"></canvas>
    </div>
</div>

<!-- TABLA -->
<div class="card shadow-sm">
<div class="table-responsive">
<table class="table table-bordered table-striped mb-0">
<thead class="table-dark">
<tr>
    <th>Mes</th>
    <th>General</th>
    <th>Adicional</th>
    <th>Saldo</th>
    <th class="text-success">Total</th>
    <th>Comisión</th>
    <th class="text-danger">Saldo Pend.</th>
</tr>
</thead>
<tbody>
<?php foreach($meses as $n=>$m): ?>
<tr>
    <td><?= $m ?></td>
    <td>S/ <?= number_format($general[$n],2) ?></td>
    <td>S/ <?= number_format($adicional[$n],2) ?></td>
    <td>S/ <?= number_format($saldoPago[$n],2) ?></td>
    <td class="fw-bold text-success">S/ <?= number_format($total[$n],2) ?></td>
    <td>S/ <?= number_format($comisiones[$n],2) ?></td>
    <td class="text-danger">S/ <?= number_format($saldoPend[$n],2) ?></td>
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
new Chart(document.getElementById('grafico'),{
    type:'bar',
    data:{
        labels: <?= json_encode(array_values($meses)) ?>,
        datasets:[
            {label:'General', data:<?= json_encode(array_values($general)) ?>},
            {label:'Adicional', data:<?= json_encode(array_values($adicional)) ?>},
            {label:'Saldo', data:<?= json_encode(array_values($saldoPago)) ?>},
            {label:'Total', data:<?= json_encode(array_values($total)) ?>}
        ]
    },
    options:{ responsive:true, maintainAspectRatio:false }
});
</script>

</body>
</html>
