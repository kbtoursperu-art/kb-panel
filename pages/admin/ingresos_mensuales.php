<?php
include('../../conexion.php');
include './../header.php';
include './../sidebar.php';
include('./../footer.php');


$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? '';
$estado = $_GET['estado'] ?? '';
$moneda = $_GET['moneda'] ?? '';

$query = "SELECT MONTH(fecha_pago_saldo) as mes,
                 SUM(precio_servicio) as total_ingresos,
                 SUM(comision) as total_comisiones,
                 SUM(pagado_a_cuenta) as total_pagado,
                 SUM(saldo_pendiente) as total_saldo
          FROM Contabilidad
          WHERE YEAR(fecha_pago_saldo) = ?";

$params = [$year];
$types = "i";

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
  $query .= " AND modalidad_pago = ?";
  $params[] = $moneda;
  $types .= "s";
}

$query .= " GROUP BY mes ORDER BY mes";

$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);
$datos = mysqli_fetch_all($resultado, MYSQLI_ASSOC);

$meses = [1=>'Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
$ingresos = array_fill(1, 12, 0);
$comisiones = array_fill(1, 12, 0);
$pagado = array_fill(1, 12, 0);
$saldo = array_fill(1, 12, 0);

foreach ($datos as $d) {
  $ingresos[$d['mes']] = (float)$d['total_ingresos'];
  $comisiones[$d['mes']] = (float)$d['total_comisiones'];
  $pagado[$d['mes']] = (float)$d['total_pagado'];
  $saldo[$d['mes']] = (float)$d['total_saldo'];
}
?>

<div class="container mt-4">
  <h2 class="mb-4">Resumen de Ingresos por Mes - <?php echo $year; ?></h2>
  <form class="row g-2 mb-4" method="GET">
    <div class="col-md-2">
      <label class="form-label">Año</label>
      <input type="number" name="year" value="<?php echo $year; ?>" class="form-control">
    </div>
    <div class="col-md-2">
      <label class="form-label">Mes</label>
      <select name="month" class="form-select">
        <option value="">Todos</option>
        <?php foreach ($meses as $num => $nombre): ?>
          <option value="<?php echo $num; ?>" <?php if ($month == $num) echo 'selected'; ?>><?php echo $nombre; ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label">Estado</label>
      <select name="estado" class="form-select">
        <option value="">Todos</option>
        <option value="pagado" <?php if ($estado=='pagado') echo 'selected'; ?>>Pagado</option>
        <option value="pendiente" <?php if ($estado=='pendiente') echo 'selected'; ?>>Pendiente</option>
        <option value="reembolsado" <?php if ($estado=='reembolsado') echo 'selected'; ?>>Reembolsado</option>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label">Moneda</label>
      <select name="moneda" class="form-select">
        <option value="">Todas</option>
        <option value="Dolares" <?php if ($moneda=='Dolares') echo 'selected'; ?>>Dólares</option>
        <option value="Soles" <?php if ($moneda=='Soles') echo 'selected'; ?>>Soles</option>
      </select>
    </div>
    <div class="col-md-2 align-self-end">
      <button class="btn btn-primary w-100">Filtrar</button>
    </div>
  </form>

  <canvas id="graficoIngresos"></canvas>

  <div class="mt-4">
    <table id="tablaContabilidad" class="table table-bordered table-striped">
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
        <?php foreach ($meses as $num => $nombre): ?>
          <tr>
            <td><?php echo $nombre; ?></td>
            <td>S/ <?php echo number_format($ingresos[$num], 2); ?></td>
            <td>S/ <?php echo number_format($comisiones[$num], 2); ?></td>
            <td>S/ <?php echo number_format($pagado[$num], 2); ?></td>
            <td>S/ <?php echo number_format($saldo[$num], 2); ?></td>
            <td><a href="ver_detalles_contabilidad.php?mes=<?php echo $num; ?>&year=<?php echo $year; ?>" class="btn btn-sm btn-info">Ver detalles</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  const ctx = document.getElementById('graficoIngresos').getContext('2d');
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: <?php echo json_encode(array_values($meses)); ?>,
      datasets: [
        {
          label: 'Ingresos',
          data: <?php echo json_encode(array_values($ingresos)); ?>,
          backgroundColor: 'rgba(75, 192, 192, 0.6)',
          borderColor: 'rgba(75, 192, 192, 1)',
          borderWidth: 1
        },
        {
          label: 'Comisiones',
          data: <?php echo json_encode(array_values($comisiones)); ?>,
          backgroundColor: 'rgba(255, 159, 64, 0.6)',
          borderColor: 'rgba(255, 159, 64, 1)',
          borderWidth: 1
        },
        {
          label: 'Pagado',
          data: <?php echo json_encode(array_values($pagado)); ?>,
          backgroundColor: 'rgba(54, 162, 235, 0.6)',
          borderColor: 'rgba(54, 162, 235, 1)',
          borderWidth: 1
        },
        {
          label: 'Saldo',
          data: <?php echo json_encode(array_values($saldo)); ?>,
          backgroundColor: 'rgba(255, 99, 132, 0.6)',
          borderColor: 'rgba(255, 99, 132, 1)',
          borderWidth: 1
        }
      ]
    },
    options: {
      responsive: true,
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
  });
</script>
<?php include('./../footer.php'); ?>