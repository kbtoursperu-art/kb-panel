<?php
include '../../conexion.php';

$id_cliente = $_GET['id'] ?? '';

if (!$id_cliente || !is_numeric($id_cliente)) {
    echo "<script>alert('ID inválido'); window.location='endosadores.php';</script>";
    exit;
}

// Obtener datos actuales del cliente
$sql = "SELECT 
            d.*, 
            e.empresa_endosadora, 
            e.grupo,
            e.contacto, 
            e.telefono_contacto, 
            e.email_contacto
        FROM Datos_clientes d
        JOIN Clientes_Endosadores e ON d.id_cliente = e.id_cliente
        WHERE d.id_cliente = $id_cliente";

$result = mysqli_query($conexion, $sql);
$cliente = mysqli_fetch_assoc($result);

if (!$cliente) {
    echo "<script>alert('Cliente no encontrado'); window.location='endosadores.php';</script>";
    exit;
}

// Procesar formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $genero = $_POST['genero'];
    $nro_pasaporte = $_POST['nro_pasaporte'];
    $empresa = $_POST['empresa'];
    $grupo = $_POST['grupo'];
    $contacto = $_POST['contacto'];
    $telefono = $_POST['telefono'];
    $email = $_POST['email'];
    $nacionalidad = $_POST['nacionalidad'];

    // Si hay nueva foto
    if (!empty($_FILES["foto_documento"]["name"])) {
        $foto = $_FILES["foto_documento"]["name"];
        $temp = $_FILES["foto_documento"]["tmp_name"];
        $carpeta = "uploads/";
        move_uploaded_file($temp, $carpeta . $foto);

        // Actualizar también en Datos_clientes
        $updateFoto = "UPDATE Datos_clientes SET foto_documento='$foto' WHERE id_cliente=$id_cliente";
        mysqli_query($conexion, $updateFoto);
    }

    $updateDatos = "UPDATE Datos_clientes 
                    SET nombre='$nombre', apellido='$apellido', genero='$genero', 
                        nro_pasaporte='$nro_pasaporte', nacionalidad='$nacionalidad'
                    WHERE id_cliente=$id_cliente";

    $updateEndosador = "UPDATE Clientes_Endosadores 
                    SET empresa_endosadora='$empresa',
                        grupo='$grupo',
                        contacto='$contacto',
                        telefono_contacto='$telefono',
                        email_contacto='$email'
                    WHERE id_cliente=$id_cliente";


    if (mysqli_query($conexion, $updateDatos) && mysqli_query($conexion, $updateEndosador)) {
        echo "<script>alert('Cliente actualizado correctamente'); window.location='index.php';</script>";
        exit;
    } else {
        echo "<script>alert('Error al actualizar');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Cliente Endosador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        /* ======== ESTILO UNIFICADO PARA FORMULARIOS ENDOSADORES ======== */
        body {
            background-color: #f5f6fa;
            font-family: 'Poppins', sans-serif;
        }

        .container {
            background: #fff;
            border-radius: 15px;
            padding: 35px;
            margin-top: 60px;
            margin-bottom: 40px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            max-width: 900px;
        }

        h2 {
            font-weight: 600;
            color: #002147;
            text-align: center;
            margin-bottom: 30px;
        }

        label {
            font-weight: 500;
            color: #333;
        }

        input, select {
            border-radius: 8px !important;
            padding: 10px !important;
            border: 1px solid #ccc !important;
            transition: all 0.2s ease;
        }

        input:focus, select:focus {
            border-color: #007bff !important;
            box-shadow: 0 0 6px rgba(0, 123, 255, 0.25) !important;
        }

        img {
            border-radius: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
        }

        .btn-primary {
            background: #007bff;
            border: none;
            padding: 10px 22px;
            border-radius: 10px;
            transition: background 0.3s ease;
        }

        .btn-primary:hover {
            background: #0056b3;
        }

        .btn-secondary {
            padding: 10px 22px;
            border-radius: 10px;
            background: #6c757d;
            border: none;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
<?php include './../sidebar.php'; ?>
<div class="container">
    <h2>Editar Cliente Endosador</h2>
    <form method="POST" enctype="multipart/form-data">
        <div class="row mb-3">
            <div class="col-md-6">
                <label>Nombre</label>
                <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($cliente['nombre']) ?>" required>
            </div>
            <div class="col-md-6">
                <label>Apellido</label>
                <input type="text" name="apellido" class="form-control" value="<?= htmlspecialchars($cliente['apellido']) ?>" required>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label>Género</label>
                <select name="genero" class="form-control" required>
                    <option value="Masculino" <?= $cliente['genero'] == 'Masculino' ? 'selected' : '' ?>>Masculino</option>
                    <option value="Femenino" <?= $cliente['genero'] == 'Femenino' ? 'selected' : '' ?>>Femenino</option>
                    <option value="Otro" <?= $cliente['genero'] == 'Otro' ? 'selected' : '' ?>>Otro</option>
                </select>
            </div>
            <div class="col-md-6">
                <label>N° Pasaporte</label>
                <input type="text" name="nro_pasaporte" class="form-control" value="<?= htmlspecialchars($cliente['nro_pasaporte']) ?>" required>
            </div>
        </div>

        <hr>

        <div class="row mb-3">
            <div class="col-md-6">
                <label>Empresa Endosadora</label>
                <input type="text" name="empresa" class="form-control" value="<?= htmlspecialchars($cliente['empresa_endosadora']) ?>">
            </div>
            <div class="col-md-6">
                <label>Nombre del Contacto</label>
                <input type="text" name="contacto" class="form-control" value="<?= htmlspecialchars($cliente['contacto']) ?>">
            </div>
        </div>
        <div class="col-md-6">
    <label>Grupo</label>
    <input 
        type="text" 
        name="grupo" 
        class="form-control"
        value="<?= htmlspecialchars($cliente['grupo']) ?>"
        required
    >
</div>


        <div class="row mb-3">
            <div class="col-md-6">
                <label>Teléfono de Contacto</label>
                <input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($cliente['telefono_contacto']) ?>">
            </div>
            <div class="col-md-6">
                <label>Email de Contacto</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($cliente['email_contacto']) ?>">
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label>Nacionalidad</label>
                <input type="text" name="nacionalidad" class="form-control" value="<?= htmlspecialchars($cliente['nacionalidad']) ?>">
            </div>
            <div class="col-md-6">
                <label>Foto Documento (opcional)</label><br>
                <?php if (!empty($cliente['foto_documento'])): ?>
                    <img src="uploads/<?= htmlspecialchars($cliente['foto_documento']) ?>" width="100">
                <?php endif; ?>
                <input type="file" name="foto_documento" class="form-control mt-2">
            </div>
        </div>

        <div class="text-center mt-4">
            <button type="submit" class="btn btn-primary me-2">Guardar Cambios</button>
            <a href="endosadores.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
