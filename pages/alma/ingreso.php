<?php include '../../conexion.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Ingreso de Stock</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-4">
<h4>📦 Ingreso de productos</h4>

<form action="acciones/ingreso_action.php" method="POST" class="card p-3">

<div class="mb-3">
<label>Producto</label>
<select name="id_stock" class="form-select" required>
<option value="">Seleccione</option>
<?php
$res = mysqli_query($conexion,"
SELECT st.id_stock, i.nombre, st.talla
FROM almacen_stock st
JOIN almacen_items i ON st.id_item=i.id_item
");
while($r=mysqli_fetch_assoc($res)):
?>
<option value="<?= $r['id_stock'] ?>">
<?= $r['nombre'] ?> <?= $r['talla'] ? '(Talla '.$r['talla'].')' : '' ?>
</option>
<?php endwhile; ?>
</select>
</div>

<div class="mb-3">
<label>Cantidad</label>
<input type="number" name="cantidad" class="form-control" min="1" required>
</div>

<button class="btn btn-success">Registrar ingreso</button>

</form>
</div>
</body>
</html>
