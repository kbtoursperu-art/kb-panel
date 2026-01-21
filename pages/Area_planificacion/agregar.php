<?php
include '../../conexion.php';


// ✅ Verificar que venga el ID de la operación
if (!isset($_GET['id_operaciones'])) {
    echo "<div class='alert alert-danger'>No se especificó una operación válida.</div>";
    exit;
}

$id_operaciones = $_GET['id_operaciones'];

// ✅ Obtener los datos de la operación
$query_operacion = "SELECT * FROM Operaciones WHERE id_operaciones = '$id_operaciones'";
$result_operacion = mysqli_query($conexion, $query_operacion);
$operacion = mysqli_fetch_assoc($result_operacion);

if (!$operacion) {
    echo "<div class='alert alert-danger'>Operación no encontrada.</div>";
    exit;
}

// ✅ Si se envía el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_guia = mysqli_real_escape_string($conexion, $_POST['nombre_guia']);
    $nombre_cocinero = mysqli_real_escape_string($conexion, $_POST['nombre_cocinero']);
    $nombre_asistente = mysqli_real_escape_string($conexion, $_POST['nombre_asistente']);

    // Insertar en tabla Planificacion
    $insert = "
        INSERT INTO Planificacion (
    id_operaciones,
    grupo_operativo,
    nombre_guia,
    nombre_cocinero,
    nombre_asistente
) VALUES (
    '$id_operaciones',
    '$grupo_operativo',
    '$nombre_guia',
    '$nombre_cocinero',
    '$nombre_asistente'
)
    ";

    if (mysqli_query($conexion, $insert)) {
        echo "<script>
                alert('✅ Planificación agregada correctamente');
                window.location.href='index.php';
              </script>";
        exit;
    } else {
        echo "<div class='alert alert-danger'>Error al registrar la planificación: " . mysqli_error($conexion) . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>➕ Agregar Planificación</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f9f9f9; }
        .form-container {
            max-width: 700px;
            margin: 40px auto;
            margin-top: 70px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            padding: 25px;
        }
        .titulo-seccion {
            font-size: 1.5rem;
            font-weight: 600;
            color: #007bff;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<?php include('../sidebar.php'); ?>
<div class="form-container">
    <h2 class="titulo-seccion">➕ Agregar Planificación</h2>

    <div class="mb-3">
        <label class="form-label fw-bold">Servicio:</label>
        <input type="text" class="form-control" value="<?= htmlspecialchars($operacion['nombre_servicio']) ?>" disabled>
    </div>

    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Nombre del Guía:</label>
            <input type="text" name="nombre_guia" class="form-control" placeholder="Ejemplo: Juan Pérez" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Nombre del Cocinero:</label>
            <input type="text" name="nombre_cocinero" class="form-control" placeholder="Ejemplo: Carlos Gómez">
        </div>

        <div class="mb-3">
            <label class="form-label">Nombre del Asistente:</label>
            <input type="text" name="nombre_asistente" class="form-control" placeholder="Ejemplo: Ana Ruiz">
        </div>

        <div class="text-center">
            <button type="submit" class="btn btn-success px-4">💾 Guardar</button>
            <a href="index.php" class="btn btn-secondary px-4">⬅️ Volver</a>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
