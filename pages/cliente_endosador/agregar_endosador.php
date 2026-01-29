<?php
include '../../conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $genero = $_POST['genero'];
    $nro_pasaporte = $_POST['nro_pasaporte'];
    $empresa_endosadora = $_POST['empresa_endosadora'];
    $grupo = $_POST['grupo'];
    $contacto = $_POST['contacto'];
    $telefono_contacto = $_POST['telefono_contacto'];
    $email_contacto = $_POST['email_contacto'];

    // Verificar si ya existe ese pasaporte
    $check_query = "SELECT id_cliente FROM Datos_clientes WHERE nro_pasaporte = '$nro_pasaporte'";
    $check_result = mysqli_query($conexion, $check_query);

    if (mysqli_num_rows($check_result) > 0) {
        echo "<div class='alert alert-danger m-4'>Error: El número de pasaporte ya está registrado.</div>";
    } else {

        // Insertar en Datos_clientes
        $sql1 = "INSERT INTO Datos_clientes 
            (nombre, apellido, genero, nro_pasaporte, tipo_cliente)
            VALUES 
            ('$nombre', '$apellido', '$genero', '$nro_pasaporte', 'Endosador')";

        if (mysqli_query($conexion, $sql1)) {

            $id_cliente = mysqli_insert_id($conexion);

            // Subida de imagen (opcional)
            $foto_documento = '';
            if (!empty($_FILES['foto_documento']['name'])) {
                $foto_documento = uniqid() . '_' . basename($_FILES["foto_documento"]["name"]);
                $ruta_destino = "uploads/" . $foto_documento;
                move_uploaded_file($_FILES["foto_documento"]["tmp_name"], $ruta_destino);
            }

            // ✅ INSERT CORRECTO EN Clientes_Endosadores
            $sql2 = "INSERT INTO Clientes_Endosadores 
                (id_cliente, empresa_endosadora, grupo, contacto, telefono_contacto, email_contacto)
                VALUES 
                ('$id_cliente', '$empresa_endosadora', '$grupo', '$contacto', '$telefono_contacto', '$email_contacto')";

            if (mysqli_query($conexion, $sql2)) {
                echo "<script>
                    alert('Cliente Endosador agregado correctamente');
                    window.location.href='" . $_SERVER['PHP_SELF'] . "';
                </script>";
            } else {
                echo "<div class='alert alert-danger m-4'>
                    Error al insertar en Clientes_Endosadores: " . mysqli_error($conexion) . "
                </div>";
            }

        } else {
            echo "<div class='alert alert-danger m-4'>
                Error al insertar en Datos_clientes: " . mysqli_error($conexion) . "
            </div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Cliente Endosador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 850px;
            background-color: #fff;
            padding: 30px;
            margin-top: 70px;
        }

        h2 {
            font-weight: 600;
            color: #0d6efd;
            text-align: center;
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
        }

        .btn-primary:hover {
            background-color: #084298;
        }

        hr {
            border-top: 2px solid #0d6efd3b;
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
<?php include('./../sidebar.php'); ?>

<div class="container">
    <div class="card mx-auto">
        <h2>➕ Agregar Cliente Endosador</h2>

        <form method="POST" enctype="multipart/form-data">

            <div class="form-section">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label>Nombre</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label>Apellido</label>
                        <input type="text" name="apellido" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label>Género</label>
                        <select name="genero" class="form-control" required>
                            <option value="">Seleccione</option>
                            <option value="Masculino">Masculino</option>
                            <option value="Femenino">Femenino</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label>N° Pasaporte</label>
                        <input type="text" name="nro_pasaporte" class="form-control" required>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label>Empresa Endosadora</label>
                        <input type="text" name="empresa_endosadora" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label>Nombre del Contacto</label>
                        <input type="text" name="contacto" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label>Teléfono de Contacto</label>
                        <input type="text" name="telefono_contacto" class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label>Grupo</label>
                        <input type="text" name="grupo" class="form-control" placeholder="Ej: WDG" required>
                    </div>

                    <div class="col-md-6">
                        <label>Email de Contacto</label>
                        <input type="email" name="email_contacto" class="form-control">
                    </div>
                </div>
            </div>

            <div class="form-section text-center">
                <label>Foto Documento (opcional)</label>
                <input type="file" name="foto_documento" class="form-control mt-2">
            </div>

            <div class="d-flex justify-content-center gap-3 mt-3">
                <button type="submit" class="btn btn-primary px-4">💾 Guardar</button>
                <a href="endosadores.php" class="btn btn-secondary px-4">↩️ Cancelar</a>
            </div>

        </form>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

