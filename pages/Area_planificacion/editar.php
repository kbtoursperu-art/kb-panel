<?php
// Incluir la conexión a la base de datos
include '../../conexion.php';

// Obtener el ID de planificación desde la URL
if (!isset($_GET['id'])) {
    echo "<script>alert('ID de planificación no proporcionado'); window.location.href='index.php';</script>";
    exit;
}

$id_planificacion = $_GET['id'];

// Obtener los datos de la planificación a editar
$query = "SELECT * FROM Planificacion WHERE id_planificacion = $id_planificacion";
$result = mysqli_query($conexion, $query);

if (mysqli_num_rows($result) == 0) {
    echo "<script>alert('Planificación no encontrada'); window.location.href='index.php';</script>";
    exit;
}

$planificacion = mysqli_fetch_assoc($result);

// Obtener las operaciones existentes para la lista desplegable
$operaciones_query = "SELECT id_operaciones, nombre_servicio, fecha_salida FROM Operaciones";
$operaciones_result = mysqli_query($conexion, $operaciones_query);

// Procesar el formulario cuando se envíe
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_operaciones = $_POST['id_operaciones'];
    $nombre_guia = $_POST['nombre_guia'];
    $nombre_cocinero = $_POST['nombre_cocinero'];
    $nombre_asistente = $_POST['nombre_asistente'];

    // Validar que se seleccionó una operación
    if (empty($id_operaciones)) {
        echo "<script>alert('Debe seleccionar una operación'); window.history.back();</script>";
        exit;
    }

    // Actualizar los datos en la base de datos
    $update_query = "UPDATE Planificacion SET 
                     id_operaciones = '$id_operaciones', 
                     nombre_guia = '$nombre_guia', 
                     nombre_cocinero = '$nombre_cocinero', 
                     nombre_asistente = '$nombre_asistente'
                     WHERE id_planificacion = $id_planificacion";

    if (mysqli_query($conexion, $update_query)) {
        echo "<script>alert('Planificación actualizada con éxito'); window.location.href='index.php';</script>";
    } else {
        echo "<script>alert('Error al actualizar planificación: " . mysqli_error($conexion) . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Planificación</title>
    <link rel="stylesheet" href="../css/index.css"> <!-- Enlace al CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<?php include('../sidebar.php'); ?>
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow-lg">
                <div class="card-header bg-warning text-white text-center">
                    <h4>✏️ Editar Planificación</h4>
                </div>
                <div class="card-body">
                    <a href="index.php" class="btn btn-outline-secondary mb-3">⬅ Volver a la Lista</a>

                    <form action="editar.php?id=<?= $id_planificacion ?>" method="POST">
                        <div class="mb-3">
                            <label for="id_operaciones" class="form-label">Seleccionar Operación:</label>
                            <select name="id_operaciones" class="form-select" required>
                                <option value="">Seleccione una operación</option>
                                <?php while ($op = mysqli_fetch_assoc($operaciones_result)) : ?>
                                    <option value="<?= $op['id_operaciones'] ?>" <?= $op['id_operaciones'] == $planificacion['id_operaciones'] ? 'selected' : '' ?>>
                                        <?= $op['nombre_servicio'] . " - " . $op['fecha_salida'] ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="nombre_guia" class="form-label">Nombre del Guía:</label>
                            <input type="text" name="nombre_guia" class="form-control" value="<?= $planificacion['nombre_guia'] ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="nombre_cocinero" class="form-label">Nombre del Cocinero:</label>
                            <input type="text" name="nombre_cocinero" class="form-control" value="<?= $planificacion['nombre_cocinero'] ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="nombre_asistente" class="form-label">Nombre del Asistente:</label>
                            <input type="text" name="nombre_asistente" class="form-control" value="<?= $planificacion['nombre_asistente'] ?>" required>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">💾 Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
<?php
mysqli_close($conexion);
?>
