<?php
include '../../conexion.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Garantías por Guía</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
<div class="container mt-4">

<h3>💰 Garantías por guía</h3>

<table class="table table-bordered table-striped">
<thead class="table-dark">
<tr>
  <th>Guía</th>
  <th>Total entregado</th>
  <th>Total devuelto</th>
  <th>Pendiente</th>
  <th>Estado</th>
</tr>
</thead>
<tbody>

<?php
$res = mysqli_query($conexion, "SELECT * FROM vista_garantias_guias ORDER BY guia");

if(!$res){
    die("Error en la vista: " . mysqli_error($conexion));
}

while($r = mysqli_fetch_assoc($res)):
?>
<tr>
    <td><?= htmlspecialchars($r['guia']) ?></td>

    <td>S/ <?= number_format($r['total_entregado'], 2) ?></td>

    <td class="text-success">
        S/ <?= number_format($r['total_devuelto'], 2) ?>
    </td>

    <td class="<?= $r['pendiente'] > 0 ? 'text-danger fw-bold' : 'text-success fw-bold' ?>">
        S/ <?= number_format($r['pendiente'], 2) ?>
    </td>
    

    <td>
        <?php if($r['pendiente'] <= 0): ?>
            <span class="badge bg-success">Todo devuelto</span>
        <?php else: ?>
            <span class="badge bg-warning text-dark">Pendiente</span>
        <?php endif; ?>
    </td>
</tr>
<?php endwhile; ?>

</tbody>
</table>

</div>
</body>
</html>
