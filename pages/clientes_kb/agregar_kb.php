<?php
include('../../conexion.php');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);

$mensaje = '';

/**
 * =====================================================
 * 🔹 BUSCAR GRUPO ACTIVO (registrados < cantidad)
 * =====================================================
 */
$grupoActivo = mysqli_query($conexion, "
    SELECT * FROM grupos
    WHERE registrados < cantidad
    ORDER BY id_grupo DESC
    LIMIT 1
");
$grupo = mysqli_fetch_assoc($grupoActivo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    mysqli_begin_transaction($conexion);

    try {
        // =========================
        // DATOS DEL CLIENTE
        // =========================
        $nombre        = $_POST['nombre'];
        $apellido      = $_POST['apellido'];
        $genero        = $_POST['genero'];
        $nro_pasaporte = $_POST['nro_pasaporte'];
        $nacionalidad  = $_POST['nacionalidad'];
        $Comida        = $_POST['Comida'];
        $fecha_nacimiento = $_POST['fecha_nacimiento'] ?? null;
        $nro_whatsapp  = $_POST['nro_whatsapp'];
        $hotel         = $_POST['hotel'];

        // =====================================================
        // 🔹 CREAR GRUPO SI NO HAY UNO ACTIVO
        // =====================================================
        if (!$grupo) {
            $cantidad_clientes = intval($_POST['cantidad_grupo']); // total de clientes del grupo

            // Crear grupo temporal
            $stmtGrupo = mysqli_prepare($conexion, "
                INSERT INTO grupos (nombre_grupo, hotel, cantidad, registrados)
                VALUES ('TEMP', ?, ?, 0)
            ");
            mysqli_stmt_bind_param($stmtGrupo, "si", $hotel, $cantidad_clientes);
            mysqli_stmt_execute($stmtGrupo);

            $id_grupo = mysqli_insert_id($conexion);
            $codigo_grupo = 'C-KB-' . str_pad($id_grupo, 4, '0', STR_PAD_LEFT);

            mysqli_query($conexion, "
                UPDATE grupos
                SET nombre_grupo = '$codigo_grupo'
                WHERE id_grupo = $id_grupo
            ");
        } else {
            // Usar grupo existente
            $id_grupo     = $grupo['id_grupo'];
            $codigo_grupo = $grupo['nombre_grupo'];
            $cantidad_clientes = $grupo['cantidad']; // total planeado para el grupo
        }

        // =====================================================
        // 🔹 INSERTAR UN CLIENTE
        // =====================================================
        // Datos_cliente
        $stmt = mysqli_prepare($conexion, "
            INSERT INTO datos_clientes
            (nombre, apellido, genero, nro_pasaporte, tipo_cliente, nacionalidad, Comida)
            VALUES (?, ?, ?, ?, 'KB', ?, ?)
        ");
        mysqli_stmt_bind_param($stmt, "ssssss", $nombre, $apellido, $genero, $nro_pasaporte, $nacionalidad, $Comida);
        mysqli_stmt_execute($stmt);
        $id_cliente = mysqli_insert_id($conexion);

        // Foto pasaporte
        $foto_pasaporte = null;
        if (!empty($_FILES['foto_pasaporte']['name'])) {
            $carpeta = '../../assets/images/fotos_pasaportes/';
            if (!is_dir($carpeta)) mkdir($carpeta, 0777, true);

            $foto_pasaporte = time().'_'.$_FILES['foto_pasaporte']['name'];
            move_uploaded_file($_FILES['foto_pasaporte']['tmp_name'], $carpeta.$foto_pasaporte);
        }

        // Clientes_KB
        $stmtKB = mysqli_prepare($conexion, "
            INSERT INTO clientes_kb
            (id_cliente, fecha_nacimiento, foto_pasaporte, nro_whatsapp, id_grupo, hotel)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        mysqli_stmt_bind_param($stmtKB, "iissis", $id_cliente, $fecha_nacimiento, $foto_pasaporte, $nro_whatsapp, $id_grupo, $hotel);
        mysqli_stmt_execute($stmtKB);

        // Aumentar registrados
        mysqli_query($conexion, "UPDATE grupos SET registrados = registrados + 1 WHERE id_grupo = $id_grupo");

        mysqli_commit($conexion);

        // =====================================================
        // 🔹 Mensaje y recarga si se completa el grupo
        // =====================================================
        // Obtener los registrados actualizados
        $grupo = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT * FROM grupos WHERE id_grupo = $id_grupo"));

        if ($grupo['registrados'] >= $grupo['cantidad']) {
            // Grupo completo → recargar para nuevo grupo
            $mensaje = "✅ Cliente agregado. El grupo <b>$codigo_grupo</b> se completó. Preparando nuevo grupo...";
            echo "<script>
                setTimeout(function(){
                    window.location.href = window.location.href;
                }, 1500);
            </script>";
        } else {
            // Grupo aún no completo
            $faltan = $grupo['cantidad'] - $grupo['registrados'];
            $mensaje = "✅ Cliente agregado al grupo <b>$codigo_grupo</b>. Faltan $faltan cliente(s) para completar el grupo.";
        }

    } catch (Exception $e) {
        mysqli_rollback($conexion);
        $mensaje = "❌ Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Agregar Cliente KB</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
</head>
<body class="bg-light">
<?php include './../sidebar.php'; ?>
<div class="content p-4">
<div class="container-fluid">

<h3 class="text-center bg-primary text-white p-2 rounded mb-4">Agregar Cliente KB</h3>

<?php if ($mensaje): ?>
<div class="alert alert-info"><?= $mensaje ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">

<?php if (!$grupo): ?>
<div class="mb-3">
    <label>Grupo asignado</label>
    <input type="text" class="form-control" value="Se generará automáticamente" readonly>
</div>
<div class="mb-3">
    <label>Cantidad de pasajeros del grupo</label>
    <input type="number" name="cantidad_grupo" class="form-control" min="1" required>
</div>
<?php else: ?>
<div class="alert alert-success">
    Grupo activo: <b><?= htmlspecialchars($grupo['nombre_grupo']) ?></b><br>
    Pasajeros: <?= $grupo['registrados'] ?> / <?= $grupo['cantidad'] ?>
</div>
<?php endif; ?>

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
        <label>Fecha Nacimiento</label>
        <input type="date" name="fecha_nacimiento" class="form-control">
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

<div class="mb-3">
    <label>Hotel</label>
    <input type="text" name="hotel" class="form-control">
</div>

<div class="mb-3">
    <label>Foto Pasaporte</label>
    <input type="file" name="foto_pasaporte" class="form-control">
</div>

<div class="text-center">
    <button class="btn btn-primary w-100">Guardar Cliente</button>
</div>

</form>
</div>
</div>

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
    $('#nacionalidad').select2({placeholder: 'Buscar país', width: '100%'});
});
</script>
</body>
</html>
