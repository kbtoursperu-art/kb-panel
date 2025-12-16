<?php
include('../../conexion.php');
include('../sidebar.php');

// Mostrar errores para depurar
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);

$mensaje = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $genero = $_POST['genero'];
    $nro_pasaporte = $_POST['nro_pasaporte'];
    $tipo_cliente = 'KB';
    $nacionalidad = $_POST['nacionalidad'];
    $Comida = $_POST['Comida'];

    // 🟢 Insertar en Datos_clientes
    $sql_cliente = "INSERT INTO Datos_clientes (nombre, apellido, genero, nro_pasaporte, tipo_cliente, nacionalidad, Comida)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conexion, $sql_cliente);
    mysqli_stmt_bind_param($stmt, "sssssss", $nombre, $apellido, $genero, $nro_pasaporte, $tipo_cliente, $nacionalidad, $Comida);

    if (mysqli_stmt_execute($stmt)) {
        $id_cliente = mysqli_insert_id($conexion);

        $edad = $_POST['edad'];
        $nro_whatsapp = $_POST['nro_whatsapp'];
        $grupo = $_POST['grupo'];
        $hotel = $_POST['hotel'];

        // 🟢 Guardar foto del pasaporte
        $foto_pasaporte = '';

        if (!empty($_FILES['foto_pasaporte']['name'])) {
            // Carpeta donde se guardarán las imágenes
            $carpeta_destino = '../../assets/images/fotos_pasaportes/';

            // Crear carpeta si no existe
            if (!file_exists($carpeta_destino)) {
                mkdir($carpeta_destino, 0777, true);
            }

            // Nombre único para evitar conflictos
            $foto_nombre = time() . '_' . basename($_FILES['foto_pasaporte']['name']);
            $ruta_destino = $carpeta_destino . $foto_nombre;

            // Intentar mover la imagen
            if (move_uploaded_file($_FILES['foto_pasaporte']['tmp_name'], $ruta_destino)) {
                $foto_pasaporte = $foto_nombre; // Guardamos solo el nombre (no la ruta completa)
            } else {
                $mensaje = "⚠️ Error al mover el archivo de imagen.";
            }
        }

        // 🟢 Insertar en Clientes_KB
        $sql_kb = "INSERT INTO Clientes_KB (id_cliente, edad, foto_pasaporte, nro_whatsapp, grupo, hotel)
                   VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_kb = mysqli_prepare($conexion, $sql_kb);
        mysqli_stmt_bind_param($stmt_kb, "iissss", $id_cliente, $edad, $foto_pasaporte, $nro_whatsapp, $grupo, $hotel);

        if (mysqli_stmt_execute($stmt_kb)) {
            echo "<script>window.location.href='index.php?mensaje=success';</script>";
            exit();
        } else {
            $mensaje = "❌ Error al guardar en Clientes_KB: " . mysqli_error($conexion);
        }
    } else {
        $mensaje = "❌ Error al guardar en Datos_clientes: " . mysqli_error($conexion);
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Cliente KB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="stilo.css">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <h2 class="mb-4 text-center text-white bg-primary p-2 rounded">Agregar Cliente KB</h2>

        <?php if ($mensaje): ?>
            <div class="alert alert-info"> <?= $mensaje ?> </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label>Nombre</label>
                    <input type="text" name="nombre" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label>Apellido</label>
                    <input type="text" name="apellido" class="form-control" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label>Género</label>
                    <select name="genero" class="form-control" required>
                        <option value="">Seleccionar</option>
                        <option value="Masculino">Masculino</option>
                        <option value="Femenino">Femenino</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label>Nro. Pasaporte</label>
                    <input type="text" name="nro_pasaporte" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label>Edad</label>
                    <input type="number" name="edad" class="form-control" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label>Nro. WhatsApp</label>
                    <input type="text" name="nro_whatsapp" class="form-control">
                </div>
                <div class="col-md-4">
                    <label>Nacionalidad</label>
                    <input type="text" name="nacionalidad" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label>Restrincion de comida</label>
                    <input type="text" name="Comida" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label>Foto Pasaporte</label>
                    <input type="file" name="foto_pasaporte" class="form-control">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label>Grupo</label>
                    <input type="text" name="grupo" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label>Hotel</label>
                    <input type="text" name="hotel" class="form-control">
                </div>
            </div>

            <div class="text-center">
                <button type="submit" class="btn btn-primary">Guardar Cliente</button>
                <a href="clientes_kb.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</body>
</html>
