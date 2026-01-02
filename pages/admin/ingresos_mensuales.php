<?php
include('../../conexion.php');
include('../header.php');
include('../sidebar.php');

$year   = $_GET['year'] ?? date('Y');
$month  = $_GET['month'] ?? '';
$estado = $_GET['estado'] ?? '';
$moneda = $_GET['moneda'] ?? '';

$query = "
    SELECT 
        MONTH(fecha_pago_saldo) AS mes,
        SUM(precio_servicio) AS total_ingresos,
        SUM(comision) AS total_comisiones,
        SUM(pagado_a_cuenta) AS total_pagado,
        SUM(saldo_pendiente) AS total_saldo
    FROM Contabilidad
    WHERE YEAR(fecha_pago_saldo) = ?
";

$params = [$year];
$types  = "i";

if ($month !== '') {
    $query .= " AND MONTH(fecha_pago_saldo) = ?";
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

$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
$datos = mysqli_fetch_all($resultado, MYSQLI_ASSOC);

$meses = [
    1=>'Enero','Febrero','Marzo','Abril','Mayo','Junio',
    'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'
];

$ingresos   = array_fill(1, 12, 0);
$comisiones = array_fill(1, 12, 0);
$pagado     = array_fill(1, 12, 0);
$saldo      = array_fill(1, 12, 0);

foreach ($datos as $d) {
    $ingresos[$d['mes']]   = (float)$d['total_ingresos'];
    $comisiones[$d['mes']] = (float)$d['total_comisiones'];
    $pagado[$d['mes']]     = (float)$d['total_pagado'];
    $saldo[$d['mes']]      = (float)$d['total_saldo'];
}
?>

<div class="container mt-4">
  <h2 class="mb-4">Resumen de Ingresos por Mes - <?= $year ?></h2>

  <form class="row g-2 mb-4" method="GET">
    <div class="col-md-2">
      <label>Año</label>
      <input type="number" name="year" class="form-control" value="<?= $year ?>">
    </div>

    <div class="col-md-2">
      <label>Mes</label>
      <select name="month" class="form-select">
        <option value="">Todos</option>
        <?php foreach ($meses as $n => $m): ?>
          <option value="<?= $n ?>" <?= ($month==$n?'selected':'') ?>><?= $m ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-2">
      <label>Estado</label>
      <select name="estado" class="form-select">
        <option value="">Todos</option>
        <option value="pagado" <?= ($estado=='pagado'?'selected':'') ?>>Pagado</option>
        <option value="pendiente" <?= ($estado=='pendiente'?'selected':'') ?>>Pendiente</option>
        <option value="reembolsado" <?= ($estado=='reembolsado'?'selected':'') ?>>Reembolsado</option>
      </select>
    </div>

    <div class="col-md-2">
      <label>Moneda</label>
      <select name="moneda" class="form-select">
        <option value="">Todas</option>
        <option value="Soles" <?= ($moneda=='Soles'?'selected':'') ?>>Soles</option>
        <option value="Dólares" <?= ($moneda=='Dólares'?'selected':'') ?>>Dólares</option>
      </select>
    </div>

    <div class="col-md-2 align-self-end">
      <button class="btn btn-primary w-100">Filtrar</button>
    </div>
  </form>

  <canvas id="graficoIngresos"></canvas>

  <table class="table table-bordered table-striped mt-4">
    <thead>
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
          <a href="ver_detalles_contabilidad.php?year=<?= $year ?>&mes=<?= $n ?>"
             class="btn btn-info btn-sm">Ver detalles</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

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
    scales: { y: { beginAtZero: true } }
  }
});
</script>

<?php include('../footer.php'); ?>
