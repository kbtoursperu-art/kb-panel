<?php
include('../../conexion.php');

$id_cliente = $_GET['id_cliente'] ?? 0;
$query = "SELECT * FROM Vista_DatosClientes WHERE id_cliente = $id_cliente";
$result = mysqli_query($conexion, $query);
$cliente = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Perfil del Cliente</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-4">
    <h2 class="text-center">Perfil de Cliente</h2>
    <div class="card">
        <div class="card-body">
            <img src="<?= $cliente['foto_pasaporte'] ?>" class="rounded-circle mx-auto d-block" width="150">
            <h3 class="text-center"><?= $cliente['nombre'] . ' ' . $cliente['apellido'] ?></h3>
            <p><strong>Edad:</strong> <?= $cliente['edad'] ?></p>
            <p><strong>Género:</strong> <?= $cliente['genero'] ?></p>
            <p><strong>Pasaporte:</strong> <?= $cliente['nro_pasaporte'] ?></p>
            <p><strong>WhatsApp:</strong> <?= $cliente['nro_whatsapp'] ?></p>
            <p><strong>Nacionalidad:</strong> <?= $cliente['nacionalidad'] ?></p>
            <p><strong>Grupo Pax:</strong> <?= $cliente['grupo'] ?></p>
            <p><strong>Hotel:</strong> <?= $cliente['hotel'] ?></p>
        </div>
    </div>
</div>
</body>
</html>
