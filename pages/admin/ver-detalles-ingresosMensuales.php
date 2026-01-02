<?php
include('../../conexion.php');
include('../sidebar.php');

$year = $_GET['year'] ?? date('Y');
$mes  = $_GET['mes'] ?? '';

if ($mes == '') {
    echo "<div class='alert alert-danger'>Mes no válido</div>";
    exit;
}

$meses = [
  1=>'Enero','Febrero','Marzo','Abril','Mayo','Junio',
  'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'
];

$sql = "
SELECT 
    o.id_operaciones,
    d.nombre,
    d.apellido,
    o.nombre_servicio,
    c.precio_servicio,
    c.pagado_a_cuenta,
    c.saldo_pendiente,
    c.comision,
    c.estado,
    c.tipo_moneda,
    c.fecha_pago_saldo
FROM Contabilidad c
INNER JOIN Operaciones o ON c.id_operaciones = o.id_operaciones
INNER JOIN Datos_clientes d ON o.id_cliente = d.id_cliente
WHERE MONTH(c.fecha_pago_saldo) = ?
  AND YEAR(c.fecha_pago_saldo) = ?
ORDER BY c.fecha_pago_saldo DESC
";

$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($stmt, "ii", $mes, $year);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
?>

<div class="container mt-4">
  <h3>
    Detalle de Contabilidad – <?= $meses[$mes] ?> <?= $year ?>
  </h3>

  <table class="table table-bordered table-striped mt-3">
    <thead class="table-dark">
      <tr>
        <th>Cliente</th>
        <th>Servicio</th>
        <th>Precio</th>
        <th>Pagado</th>
        <th>Saldo</th>
        <th>Comisión</th>
        <th>Moneda</th>
        <th>Estado</th>
        <th>Fecha</th>
      </tr>
    </thead>
    <tbody>
    <?php 
    $totalPrecio = $totalPagado = $totalSaldo = $totalComision = 0;

    while ($row = mysqli_fetch_assoc($res)):
        $totalPrecio   += $row['precio_servicio'];
        $totalPagado   += $row['pagado_a_cuenta'];
        $totalSaldo    += $row['saldo_pendiente'];
        $totalComision += $row['comision'];
    ?>
      <tr>
        <td><?= $row['nombre'].' '.$row['apellido'] ?></td>
        <td><?= $row['nombre_servicio'] ?></td>
        <td><?= number_format($row['precio_servicio'],2) ?></td>
        <td><?= number_format($row['pagado_a_cuenta'],2) ?></td>
        <td><?= number_format($row['saldo_pendiente'],2) ?></td>
        <td><?= number_format($row['comision'],2) ?></td>
        <td><?= $row['tipo_moneda'] ?></td>
        <td><?= ucfirst($row['estado']) ?></td>
        <td><?= $row['fecha_pago_saldo'] ?></td>
      </tr>
    <?php endwhile; ?>
    </tbody>

    <tfoot class="table-secondary">
      <tr>
        <th colspan="2">TOTALES</th>
        <th><?= number_format($totalPrecio,2) ?></th>
        <th><?= number_format($totalPagado,2) ?></th>
        <th><?= number_format($totalSaldo,2) ?></th>
        <th><?= number_format($totalComision,2) ?></th>
        <th colspan="3"></th>
      </tr>
    </tfoot>
  </table>

  <a href="ingresos_mensuales.php?year=<?= $year ?>" class="btn btn-secondary">
    ⬅ Volver
  </a>
</div>

<?php include('../footer.php'); ?>
