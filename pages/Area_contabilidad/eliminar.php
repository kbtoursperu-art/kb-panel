<?php
include('../../conexion.php');

$id_contabilidad = isset($_GET['id_contabilidad']) ? $_GET['id_contabilidad'] : '';

if (!$id_contabilidad) {
    die("Error: No se especificó un ID de contabilidad.");
}

// Verificar si el registro existe antes de eliminarlo
$query_check = "SELECT id_contabilidad FROM contabilidad WHERE id_contabilidad = ?";
$stmt = mysqli_prepare($conexion, $query_check);
mysqli_stmt_bind_param($stmt, "i", $id_contabilidad);
mysqli_stmt_execute($stmt);
$resultado = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($resultado) == 0) {
    die("Error: No se encontró la contabilidad a eliminar.");
}

// Si se confirma la eliminación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar']))
{

    $query_delete = "DELETE FROM contabilidad WHERE id_contabilidad = ?";
    $stmt = mysqli_prepare($conexion, $query_delete);
    mysqli_stmt_bind_param($stmt, "i", $id_contabilidad);

    if (mysqli_stmt_execute($stmt)) {
        header('Location: index.php?mensaje = Eliminado');
        exit();
    } else {
        echo "Error al eliminar: " . mysqli_error($conexion);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminar Contabilidad</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<?php include('../../templates/barra.php'); ?>
<div class="container mt-5">
    <h2 class="text-center text-danger">Eliminar Contabilidad</h2>
    <div class="card shadow-sm p-4">
        <p class="text-center fs-5">¿Estás seguro de que deseas eliminar este registro?</p>
        <form method="POST" class="text-center">
            <button type="submit" name="confirmar" class="btn btn-danger">Sí, eliminar</button>
            <a href="index.php" class="btn btn-secondary">Cancelar</a>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
