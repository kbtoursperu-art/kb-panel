<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $para = $_POST['correo'];
    $asunto = $_POST['asunto'];
    $mensaje = $_POST['mensaje'];
    $cabeceras = "From: tuempresa@example.com";

    if (mail($para, $asunto, $mensaje, $cabeceras)) {
        echo "<script>alert('Correo enviado con éxito');</script>";
    } else {
        echo "<script>alert('Error al enviar el correo');</script>";
    }
}

$id_cliente = $_GET['id_cliente'] ?? 0;
include('../../conexion.php');
$query = "SELECT correo FROM Vista_DatosClientes WHERE id_cliente = $id_cliente";
$result = mysqli_query($conexion, $query);
$cliente = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Enviar Correo</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-4">
    <h2 class="text-center">Enviar Correo</h2>
    <form method="post">
        <div class="mb-3">
            <label for="correo" class="form-label">Correo del Cliente</label>
            <input type="email" name="correo" id="correo" class="form-control" value="<?= $cliente['correo'] ?>" readonly>
        </div>
        <div class="mb-3">
            <label for="asunto" class="form-label">Asunto</label>
            <input type="text" name="asunto" id="asunto" class="form-control" required>
        </div>
        <div class="mb-3">
            <label for="mensaje" class="form-label">Mensaje</label>
            <textarea name="mensaje" id="mensaje" class="form-control" rows="5" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Enviar</button>
    </form>
</div>
</body>
</html>
