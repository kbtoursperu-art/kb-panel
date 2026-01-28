<?php include '../../conexion.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Dashboard Almacén</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="../../assets/css/almacen.css">

</head>

<body class="bg-light">
<div class="container mt-4">

<h3 class="mb-4">📊 Dashboard de Almacén</h3>

<?php
$total_stock = mysqli_fetch_row(mysqli_query($conexion,"
SELECT SUM(cantidad_total) FROM almacen_stock
"))[0] ?? 0;

$en_uso = mysqli_fetch_row(mysqli_query($conexion,"
SELECT SUM(cantidad)
FROM almacen_salidas
WHERE estado IN ('Pendiente','Parcial')
"))[0] ?? 0;


$pendientes = mysqli_fetch_row(mysqli_query($conexion,"
SELECT COUNT(*) FROM almacen_salidas WHERE estado!='Devuelto'
"))[0] ?? 0;

$garantias = mysqli_fetch_row(mysqli_query($conexion,"
SELECT SUM(garantia_original) 
FROM almacen_salidas 
WHERE estado!='Devuelto'
"))[0] ?? 0;

?>

<!-- 🔹 KPIs -->
<div class="row g-3 mb-4">

<div class="col-md-3">
<div class="card text-center p-3 shadow-sm">
<i class="bi bi-box-seam fs-3"></i>
<h6>Total stock</h6>
<h3><?= $total_stock ?></h3>
</div>
</div>

<div class="col-md-3">
<div class="card text-center p-3 shadow-sm">
<i class="bi bi-arrow-repeat fs-3"></i>
<h6>En uso</h6>
<h3><?= $en_uso ?></h3>
</div>
</div>

<div class="col-md-3">
<div class="card text-center p-3 shadow-sm">
<i class="bi bi-exclamation-triangle fs-3 text-warning"></i>
<h6>Salidas pendientes</h6>
<h3><?= $pendientes ?></h3>
</div>
</div>

<div class="col-md-3">
<div class="card text-center p-3 shadow-sm">
<i class="bi bi-cash-stack fs-3 text-danger"></i>
<h6>Garantía retenida</h6>
<h3>S/ <?= number_format($garantias,2) ?></h3>
</div>
</div>

</div>

<!-- 🔹 ALERTA -->
<?php if($pendientes > 0): ?>
<div class="alert alert-warning">
⚠️ Existen <strong><?= $pendientes ?></strong> salidas con productos pendientes de devolución.
</div>
<?php endif; ?>

<!-- 🔹 ACCESOS RÁPIDOS -->
<div class="row g-3">

<div class="col-md-3">
<a href="ingreso.php" class="text-decoration-none">
<div class="card p-3 text-center shadow-sm">
<i class="bi bi-plus-circle fs-2 text-success"></i>
<h6>Ingreso stock</h6>
</div>
</a>
</div>

<div class="col-md-3">
<a href="salida.php" class="text-decoration-none">
<div class="card p-3 text-center shadow-sm">
<i class="bi bi-box-arrow-right fs-2 text-primary"></i>
<h6>Salida a guías</h6>
</div>
</a>
</div>

<div class="col-md-3">
<a href="pendientes.php" class="text-decoration-none">
<div class="card p-3 text-center shadow-sm">
<i class="bi bi-clock-history fs-2 text-warning"></i>
<h6>Devoluciones pendientes</h6>
</div>
</a>
</div>

<div class="col-md-3">
<a href="reporte_garantias_guias.php" class="text-decoration-none">
<div class="card p-3 text-center shadow-sm">
<i class="bi bi-shield-lock fs-2 text-danger"></i>
<h6>Garantías por guía</h6>
</div>
</a>
</div>

</div>

<!-- 🔹 REPORTES -->
<div class="row g-3 mt-4">

<div class="col-md-6">
<a href="../historial_garantias.php" class="text-decoration-none">
<div class="card p-3 shadow-sm">
<h6>📜 Historial de devoluciones de garantía</h6>
</div>
</a>
</div>

<div class="col-md-6">
<a href="../reporte_garantias_mensual.php" class="text-decoration-none">
<div class="card p-3 shadow-sm">
<h6>📊 Reporte mensual de garantías</h6>
</div>
</a>
</div>

</div>
<!-- 🔹 STOCK GENERAL -->
<div class="row mt-5">
  <div class="col-12 card">
    <h5>📦 Stock general de productos</h5>
    <div class="table-responsive">
      <table class="table table-striped table-bordered table-wrapper">
        <thead class="table-dark">
          <tr>
            <th>Producto</th>
            <th>Talla</th>
            <th>Cantidad total</th>
            <th>Cantidad disponible</th>
          </tr>
        </thead>
        <tbody>
        <?php
        $stock = mysqli_query($conexion, "
          SELECT i.nombre AS producto, st.talla, st.cantidad_total, st.cantidad_disponible
          FROM almacen_stock st
          JOIN almacen_items i ON st.id_item = i.id_item
          ORDER BY i.nombre, st.talla
        ");

        while($s = mysqli_fetch_assoc($stock)):
        ?>
          <tr>
            <td><?= htmlspecialchars($s['producto']) ?></td>
            <td><?= htmlspecialchars($s['talla']) ?></td>
            <td><?= $s['cantidad_total'] ?></td>
            <td><?= $s['cantidad_disponible'] ?></td>
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
