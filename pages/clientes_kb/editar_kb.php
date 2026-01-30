<?php
include('../../conexion.php');  

if (!isset($_GET['id_cliente'])) {
    die("ID de cliente no proporcionado.");
}

$id_cliente = intval($_GET['id_cliente']);

// Si el formulario fue enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $edad = $_POST['edad'];
    $genero = $_POST['genero'];
    $nro_pasaporte = $_POST['nro_pasaporte'];
    $nacionalidad = $_POST['nacionalidad'];
    $Comida = $_POST['Comida'];
    $nro_whatsapp = $_POST['nro_whatsapp'];
    $grupo = $_POST['grupo'];
    $hotel = $_POST['hotel'];

    // Manejo de la foto
// Manejo de la foto (actualiza y elimina la anterior si existe)
if (isset($_FILES['foto_pasaporte']) && $_FILES['foto_pasaporte']['error'] === 0) {
    // Eliminar foto anterior si existe y no es vacía
    if (!empty($_POST['foto_actual']) && file_exists('../../' . $_POST['foto_actual'])) {
        unlink('../../' . $_POST['foto_actual']);
    }

    // Carpeta destino para guardar la nueva imagen
    $carpeta_destino = '../../assets/images/fotos_pasaportes/';

    // Crear carpeta si no existe
    if (!is_dir($carpeta_destino)) {
        mkdir($carpeta_destino, 0777, true);
    }

    // Generar nombre único de archivo
    $nombreArchivo = time() . '_' . basename($_FILES['foto_pasaporte']['name']);
    $foto_path = 'assets/images/fotos_pasaportes/' . $nombreArchivo;

    // Mover imagen al servidor
    if (!move_uploaded_file($_FILES['foto_pasaporte']['tmp_name'], $carpeta_destino . $nombreArchivo)) {
        // Si falla, conservar la imagen anterior
        $foto_path = $_POST['foto_actual'] ?? null;
    }

} else {
    // Si no se subió una nueva, mantener la actual
    $foto_path = $_POST['foto_actual'] ?? null;
}


    // Actualizar Datos_clientes
    $query1 = "UPDATE Datos_clientes SET nombre=?, apellido=?, genero=?, nro_pasaporte=?, nacionalidad=?, Comida=? WHERE id_cliente=?";
    $stmt1 = mysqli_prepare($conexion, $query1);
    mysqli_stmt_bind_param($stmt1, "ssssssi", $nombre, $apellido, $genero, $nro_pasaporte, $nacionalidad, $Comida, $id_cliente);
    mysqli_stmt_execute($stmt1);

    // Actualizar Clientes_KB
    $query2 = "UPDATE Clientes_KB SET edad=?, nro_whatsapp=?, grupo=?, hotel=?, foto_pasaporte=? WHERE id_cliente=?";
    $stmt2 = mysqli_prepare($conexion, $query2);
    mysqli_stmt_bind_param($stmt2, "issssi", $edad, $nro_whatsapp, $grupo, $hotel, $foto_path, $id_cliente);
    mysqli_stmt_execute($stmt2);

    // Redirigir después de actualizar
    echo "<script>alert('Cliente actualizado exitosamente'); window.location.href='index.php';</script>";
    exit;
}

// Obtener los datos actuales
$sql = "
SELECT d.*, k.edad, k.foto_pasaporte, k.nro_whatsapp, k.grupo, k.hotel
FROM Datos_clientes d
JOIN Clientes_KB k ON d.id_cliente = k.id_cliente
WHERE d.id_cliente = ?
";

$stmt = mysqli_prepare($conexion, $sql);
mysqli_stmt_bind_param($stmt, "i", $id_cliente);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) === 0) {
    die("Cliente no encontrado.");
}

$cliente = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Cliente KB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 800px;
            background-color: #fff;
            padding: 30px;
        }

        h2 {
            font-weight: 600;
            color: #0d6efd;
            margin-bottom: 25px;
        }

        label {
            font-weight: 500;
            color: #333;
        }

        .form-control {
            border-radius: 8px;
            padding: 10px 15px;
            border: 1px solid #ccc;
        }

        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 5px rgba(13, 110, 253, 0.3);
        }

        .btn-primary {
            border-radius: 8px;
            background-color: #0d6efd;
            border: none;
            transition: 0.3s;
        }

        .btn-primary:hover {
            background-color: #084298;
        }

        .btn-secondary {
            border-radius: 8px;
        }

        img.rounded {
            border: 3px solid #0d6efd;
            padding: 3px;
        }

        .form-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
<?php include('./../sidebar.php');?>
<div class="container">
    <div class="card mx-auto">
        <h2 class="text-center">✏️ Editar Cliente KB</h2>

        <form action="" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id_cliente" value="<?= $cliente['id_cliente'] ?>">
            <input type="hidden" name="foto_actual" value="<?= htmlspecialchars($cliente['foto_pasaporte']) ?>">

            <div class="form-section">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" name="nombre" id="nombre" value="<?= htmlspecialchars($cliente['nombre']) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label for="apellido" class="form-label">Apellido</label>
                        <input type="text" class="form-control" name="apellido" id="apellido" value="<?= htmlspecialchars($cliente['apellido']) ?>" required>
                    </div>

                   <div class="col-md-4">
    <label for="fecha_nacimiento" class="form-label">Fecha de nacimiento</label>
    <input 
        type="date"
        class="form-control"
        name="fecha_nacimiento"
        id="fecha_nacimiento"
        value="<?= htmlspecialchars($cliente['fecha_nacimiento'] ?? '') ?>"
        required
    >
</div>


                    <div class="col-md-4">
                        <label for="genero" class="form-label">Género</label>
                        <select name="genero" id="genero" class="form-control" required>
                            <option value="Masculino" <?= $cliente['genero'] == 'Masculino' ? 'selected' : '' ?>>Masculino</option>
                            <option value="Femenino" <?= $cliente['genero'] == 'Femenino' ? 'selected' : '' ?>>Femenino</option>
                            <option value="Otro" <?= $cliente['genero'] == 'Otro' ? 'selected' : '' ?>>Otro</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="nro_pasaporte" class="form-label">N° Pasaporte</label>
                        <input type="text" class="form-control" name="nro_pasaporte" id="nro_pasaporte" value="<?= htmlspecialchars($cliente['nro_pasaporte']) ?>" required>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="nacionalidad" class="form-label">Nacionalidad</label>
                        <input type="text" class="form-control" name="nacionalidad" id="nacionalidad" value="<?= htmlspecialchars($cliente['nacionalidad']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="nacionalidad" class="form-label">Restringcion de comida</label>
                        <input type="text" class="form-control" name="Comida" id="Comida" value="<?= htmlspecialchars($cliente['Comida']) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label for="nro_whatsapp" class="form-label">N° WhatsApp</label>
                        <input type="text" class="form-control" name="nro_whatsapp" id="nro_whatsapp" value="<?= htmlspecialchars($cliente['nro_whatsapp']) ?>">
                    </div>

                    <div class="col-md-6">
                        <label for="grupo" class="form-label">Grupo</label>
                        <input type="text" class="form-control" name="grupo" id="grupo" value="<?= htmlspecialchars($cliente['grupo']) ?>">
                    </div>

                    <div class="col-md-6">
                        <label for="hotel" class="form-label">Hotel</label>
                        <input type="text" class="form-control" name="hotel" id="hotel" value="<?= htmlspecialchars($cliente['hotel']) ?>">
                    </div>
                </div>
            </div>

            <div class="form-section text-center">
                <label for="foto_pasaporte" class="form-label">Foto de Pasaporte</label><br>
                <?php if (!empty($cliente['foto_pasaporte'])) : ?>
                    <img src="<?= htmlspecialchars($cliente['foto_pasaporte']) ?>" alt="Foto Actual" width="120" class="rounded mb-3">
                <?php endif; ?>
                <input type="file" class="form-control" name="foto_pasaporte" id="foto_pasaporte">
            </div>

            <div class="d-flex justify-content-center gap-2 mt-3">
                <button type="submit" class="btn btn-primary px-4">💾 Actualizar</button>
                <a href="index.php" class="btn btn-secondary px-4">↩️ Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
