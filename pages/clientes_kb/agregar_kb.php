<?php
include('../../conexion.php');

// Mostrar errores
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

    // =========================
    // INSERT Datos_clientes
    // =========================
    $sql_cliente = "INSERT INTO Datos_clientes 
    (nombre, apellido, genero, nro_pasaporte, tipo_cliente, nacionalidad, Comida)
    VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = mysqli_prepare($conexion, $sql_cliente);
    mysqli_stmt_bind_param(
        $stmt,
        "sssssss",
        $nombre,
        $apellido,
        $genero,
        $nro_pasaporte,
        $tipo_cliente,
        $nacionalidad,
        $Comida
    );

    if (mysqli_stmt_execute($stmt)) {

        $id_cliente = mysqli_insert_id($conexion);
        $edad = $_POST['edad'] ?? null;
        $nro_whatsapp = $_POST['nro_whatsapp'];
        $grupo = $_POST['grupo'];
        $hotel = $_POST['hotel'];

        // =========================
        // FOTO PASAPORTE
        // =========================
        $foto_pasaporte = '';

        if (!empty($_FILES['foto_pasaporte']['name'])) {

            $carpeta = '../../assets/images/fotos_pasaportes/';
            if (!file_exists($carpeta)) {
                mkdir($carpeta, 0777, true);
            }

            $foto_nombre = time() . '_' . basename($_FILES['foto_pasaporte']['name']);
            $ruta = $carpeta . $foto_nombre;

            if (move_uploaded_file($_FILES['foto_pasaporte']['tmp_name'], $ruta)) {
                $foto_pasaporte = $foto_nombre;
            }
        }

        // =========================
        // INSERT Clientes_KB
        // =========================
        $sql_kb = "INSERT INTO Clientes_KB 
        (id_cliente, edad, foto_pasaporte, nro_whatsapp, grupo, hotel)
        VALUES (?, ?, ?, ?, ?, ?)";

        $stmt_kb = mysqli_prepare($conexion, $sql_kb);
        mysqli_stmt_bind_param(
            $stmt_kb,
            "iissss",
            $id_cliente,
            $edad,
            $foto_pasaporte,
            $nro_whatsapp,
            $grupo,
            $hotel
        );

        if (mysqli_stmt_execute($stmt_kb)) {
            $mensaje = "✅ Cliente agregado correctamente";
        } else {
            $mensaje = "❌ Error al guardar cliente KB";
        }

    } else {
        $mensaje = "❌ Error al guardar datos del cliente";
    }
}

// =========================
// LISTAR CLIENTES
// =========================
$clientes_sql = "
SELECT 
    d.nombre,
    d.apellido,
    d.genero,
    d.nro_pasaporte,
    d.nacionalidad,
    d.Comida,
    k.edad,
    k.nro_whatsapp,
    k.grupo,
    k.hotel,
    k.foto_pasaporte
FROM Datos_clientes d
INNER JOIN Clientes_KB k ON d.id_cliente = k.id_cliente
ORDER BY d.id_cliente DESC
";
$clientes_res = mysqli_query($conexion, $clientes_sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Cliente KB</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <style>
        .main-content {
            margin-left: 250px;
            padding: 20px;
            margin-top: 70px;     /* altura header */
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>

<body class="bg-light">

<?php include('../sidebar.php'); ?>

<div class="main-content">
<div class="container-fluid">

    <h3 class="text-center text-white bg-primary p-2 rounded mb-4">
        Agregar Cliente KB
    </h3>

    <?php if ($mensaje): ?>
        <div class="alert alert-success"><?= $mensaje ?></div>
    <?php endif; ?>

    <!-- ================= FORMULARIO ================= -->
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
                    <option>Masculino</option>
                    <option>Femenino</option>
                    <option>Otro</option>
                </select>
            </div>

            <div class="col-md-4">
                <label>Nro Pasaporte</label>
                <input type="text" name="nro_pasaporte" class="form-control" required>
            </div>

            <div class="col-md-4">
                <label>Fecha de nacimiento</label>
                <input type="date" name="fecha_nacimiento" class="form-control" max="<?= date('Y-m-d'); ?>">
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <label>WhatsApp</label>
                <input type="text" name="nro_whatsapp" class="form-control">
            </div>

            <div class="col-md-4">
                <label>Nacionalidad</label>
                <select name="nacionalidad" id="nacionalidad" class="form-control select2" required>
                    <option value="">Seleccionar país</option>
                </select>
            </div>

            <div class="col-md-4">
                <label>Restricción comida</label>
                <input type="text" name="Comida" class="form-control">
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

        <div class="mb-3">
            <label>Foto Pasaporte</label>
            <input type="file" name="foto_pasaporte" class="form-control">
        </div>

        <div class="text-center">
            <button class="btn btn-primary">Guardar Cliente</button>
        </div>
    </form>

    <hr class="my-4">

    <!-- ================= TABLA ================= -->
    <h5>Clientes KB Registrados</h5>

    <div class="table-responsive">
        <table class="table table-bordered table-striped mt-3">
            <thead class="table-primary">
                <tr>
                    <th>Cliente</th>
                    <th>Pasaporte</th>
                    <th>Edad</th>
                    <th>WhatsApp</th>
                    <th>Nacionalidad</th>
                    <th>Comida</th>
                    <th>Grupo</th>
                    <th>Hotel</th>
                    <th>Foto</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($clientes_res)): ?>
                <tr>
                    <td><?= $row['nombre'].' '.$row['apellido'] ?></td>
                    <td><?= $row['nro_pasaporte'] ?></td>
                    <td><?= $row['edad'] ?></td>
                    <td><?= $row['nro_whatsapp'] ?></td>
                    <td><?= $row['nacionalidad'] ?></td>
                    <td><?= $row['Comida'] ?></td>
                    <td><?= $row['grupo'] ?></td>
                    <td><?= $row['hotel'] ?></td>
                    <td>
                        <?php if ($row['foto_pasaporte']): ?>
                            <img src="../../assets/images/fotos_pasaportes/<?= $row['foto_pasaporte'] ?>" width="50">
                        <?php else: ?> —
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

</div>
</div>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
fetch('../../../assets/json/paises.json')
.then(res => res.json())
.then(paises => {
    const select = document.getElementById('nacionalidad');
    paises.forEach(pais => {
        const option = document.createElement('option');
        option.value = pais;
        option.textContent = pais;
        select.appendChild(option);
    });

    $('#nacionalidad').select2({
        placeholder: 'Escribe para buscar país',
        width: '100%'
    });
});
</script>

</body>
</html>
