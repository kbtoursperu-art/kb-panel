<?php
include('../../conexion.php');

$id_cliente = $_GET['id_cliente'] ?? 0;
$query = "SELECT * FROM Historial_Viajes WHERE id_cliente = $id_cliente";
$result = mysqli_query($conexion, $query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Viajes</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-4">
    <h2 class="text-center">Historial de Viajes</h2>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Destino</th>
                <th>Tipo de Servicio</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                <tr>
                    <td><?= $row['fecha_viaje'] ?></td>
                    <td><?= $row['destino'] ?></td>
                    <td><?= $row['tipo_servicio'] ?></td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>
</body>
</html>
