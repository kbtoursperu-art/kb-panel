<?php
include '../../conexion.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Productos pendientes por devolver</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-4">
    <h3>📦 Productos pendientes por devolver</h3>

    <table id="tablaPendientes" class="table table-striped table-bordered">
        <thead class="table-dark">
            <tr>
                <th>Guía</th>
                <th>Producto</th>
                <th>Entregado</th>
                <th>Devuelto</th>
                <th>Pendiente</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
<?php
$sql = "
SELECT 
    s.id_salida,
    s.nombre_guia AS guia,
    i.nombre AS producto,
    s.cantidad,
    COALESCE(SUM(d.cantidad_devuelta),0) AS devuelto,
    (s.cantidad - COALESCE(SUM(d.cantidad_devuelta),0)) AS pendiente
FROM almacen_salidas s
JOIN almacen_stock st ON s.id_stock = st.id_stock
JOIN almacen_items i ON st.id_item = i.id_item
LEFT JOIN almacen_devoluciones d ON s.id_salida = d.id_salida
WHERE i.tipo IN ('Retornable','Garantia') -- 🔹 solo productos retornables o con garantía
GROUP BY s.id_salida, s.nombre_guia, i.nombre, s.cantidad
HAVING pendiente > 0
ORDER BY s.nombre_guia, i.nombre
";

$res = mysqli_query($conexion, $sql);
while($r = mysqli_fetch_assoc($res)):
?>
<tr>
    <td><?= htmlspecialchars($r['guia']) ?></td>
    <td><?= htmlspecialchars($r['producto']) ?></td>
    <td><?= $r['cantidad'] ?></td>
    <td><?= $r['devuelto'] ?></td>
    <td class="fw-bold text-danger"><?= $r['pendiente'] ?></td>
    <td>
        <?php if($r['pendiente'] > 0): ?>
        <a href="devolucion.php?id_salida=<?= $r['id_salida'] ?>&max=<?= $r['pendiente'] ?>" 
           class="btn btn-warning btn-sm">
           Devolver
        </a>
        <?php else: ?>
        -
        <?php endif; ?>
    </td>
</tr>
<?php endwhile; ?>
        </tbody>
    </table>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$('#tablaPendientes').DataTable({
    language:{ url:"//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" }
});
</script>

</body>
</html>
