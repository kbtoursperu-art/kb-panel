<?php
include '../../conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $genero = $_POST['genero'];
    $nro_pasaporte = $_POST['nro_pasaporte'];
    $empresa_endosadora = $_POST['empresa_endosadora'];
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
        $sql1 = "INSERT INTO Datos_clientes (nombre, apellido, genero, nro_pasaporte, tipo_cliente)
                 VALUES ('$nombre', '$apellido', '$genero', '$nro_pasaporte', 'Endosador')";
        if (mysqli_query($conexion, $sql1)) {
            $id_cliente = mysqli_insert_id($conexion);

            // Subida de imagen (opcional)
            $foto_documento = '';
            if (!empty($_FILES['foto_documento']['name'])) {
                $foto_documento = uniqid() . '_' . basename($_FILES["foto_documento"]["name"]);
                $ruta_destino = "uploads/" . $foto_documento;
                move_uploaded_file($_FILES["foto_documento"]["tmp_name"], $ruta_destino);
            }

            // Insertar en Clientes_Endosadores
            $sql2 = "INSERT INTO Clientes_Endosadores (id_cliente, empresa_endosadora, contacto, telefono_contacto, email_contacto)
                     VALUES ('$id_cliente', '$empresa_endosadora', '$contacto', '$telefono_contacto', '$email_contacto')";

            if (mysqli_query($conexion, $sql2)) {
                echo "<script>alert('Cliente Endosador agregado correctamente'); window.location.href='".$_SERVER['PHP_SELF']."';</script>";
            } else {
                echo "<div class='alert alert-danger m-4'>Error al insertar en Clientes_Endosadores: " . mysqli_error($conexion) . "</div>";
            }
        } else {
            echo "<div class='alert alert-danger m-4'>Error al insertar en Datos_clientes: " . mysqli_error($conexion) . "</div>";
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
            margin-top: 70px;     /* altura header */

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
            transition: 0.3s;
        }

        .btn-primary:hover {
            background-color: #084298;
        }

        .btn-secondary {
            border-radius: 8px;
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
<?php include('./../sidebar.php');?>
<div class="container">
    <div class="card mx-auto">
        <h2>➕ Agregar Cliente Endosador</h2>

        <form method="POST" enctype="multipart/form-data">
            <!-- Datos personales -->
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

            <!-- Datos empresa -->
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
                    <div class="mb-3">
                        <label class="form-label">Grupo</label>
                        <input type="text" name="grupo" class="form-control" placeholder="Ej: WDG" required>
                    </div>

                    <div class="col-md-6">
                        <label>Email de Contacto</label>
                        <input type="email" name="email_contacto" class="form-control">
                    </div>
                </div>
            </div>

            <!-- Documento -->
            <div class="form-section text-center">
                <label>Foto Documento (opcional)</label><br>
                <input type="file" name="foto_documento" class="form-control mt-2">
            </div>

            <!-- Botones -->
            <div class="d-flex justify-content-center gap-3 mt-3">
                <button type="submit" class="btn btn-primary px-4">💾 Guardar</button>
                <a href="endosadores.php" class="btn btn-secondary px-4">↩️ Cancelar</a>
            </div>
        </form>

        <!-- 🚀 LISTA DE CLIENTES ENDOSADORES -->
        <hr class="mt-4">
        <h3 class="text-center text-primary mt-4">📋 Lista de Clientes Endosadores</h3>

        <?php
        $query_lista = "
            SELECT 
                dc.id_cliente,
                dc.nombre,
                dc.apellido,
                dc.genero,
                dc.nro_pasaporte,
                ce.empresa_endosadora,
                ce.contacto,
                ce.telefono_contacto,
                ce.email_contacto
            FROM Datos_clientes dc
            INNER JOIN Clientes_Endosadores ce ON dc.id_cliente = ce.id_cliente
            WHERE dc.tipo_cliente = 'Endosador'
            ORDER BY dc.id_cliente DESC
        ";

        $result_lista = mysqli_query($conexion, $query_lista);
        ?>

        <div class="table-responsive mt-3">
            <table class="table table-striped table-bordered">
                <thead class="table-primary">
                    <tr>
                        <th>Nombre</th>
                        <th>Apellido</th>
                        <th>Género</th>
                        <th>Pasaporte</th>
                        <th>Empresa</th>
                        <th>Contacto</th>
                        <th>Teléfono</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result_lista)) { ?>
                        <tr>
                            <td><?= $row['nombre'] ?></td>
                            <td><?= $row['apellido'] ?></td>
                            <td><?= $row['genero'] ?></td>
                            <td><?= $row['nro_pasaporte'] ?></td>
                            <td><?= $row['empresa_endosadora'] ?></td>
                            <td><?= $row['contacto'] ?></td>
                            <td><?= $row['telefono_contacto'] ?></td>
                            <td><?= $row['email_contacto'] ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php include('./../footer.php'); ?>
