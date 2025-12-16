<?php
// pages/almace/historial.php
include("../../conexion.php");
error_reporting(E_ALL); ini_set('display_errors',1);

$sql = "SELECT h.id_historial, h.accion, h.fecha, h.detalles,
        p.id_asignacion, p.id_cliente,
        c.nombre AS cliente,
        i.nombre AS articulo
        FROM almacen_historial h
        LEFT JOIN almacen_pasajeros p ON h.id_asignacion = p.id_asignacion
        LEFT JOIN Datos_clientes c ON p.id_cliente = c.id_cliente
        LEFT JOIN almacen_stock s ON p.id_stock = s.id_stock
        LEFT JOIN almacen_items i ON s.id_item = i.id_item
        ORDER BY h.fecha DESC";
$res = mysqli_query($conexion, $sql);
?>
<!DOCTYPE html>
<html lang="es">
<head><meta charset="utf-8"><title>Historial</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>📜 Historial (acciones del almacén)</h3>
    <a href="stock_general.php" class="btn btn-outline-secondary">Volver</a>
  </div>

  <div class="card shadow-sm"><div class="card-body">
    <table id="tablaHist" class="table table-striped">
      <thead class="table-dark"><tr><th>ID</th><th>Acción</th><th>Fecha</th><th>Cliente</th><th>Artículo</th><th>Detalles</th></tr></thead>
      <tbody>
        <?php while($r=mysqli_fetch_assoc($res)): ?>
          <tr>
            <td><?= $r['id_historial'] ?></td>
            <td><?= htmlspecialchars($r['accion']) ?></td>
            <td><?= date("d/m/Y H:i", strtotime($r['fecha'])) ?></td>
            <td><?= htmlspecialchars($r['cliente'] ?? '-') ?></td>
            <td><?= htmlspecialchars($r['articulo'] ?? '-') ?></td>
            <td><?= nl2br(htmlspecialchars($r['detalles'])) ?></td>
          </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div></div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(function(){ $('#tablaHist').DataTable({ language:{url:"//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"} }); });
</script>
</body>
</html>
