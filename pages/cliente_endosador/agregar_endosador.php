<?php
include('../../conexion.php');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);

$mensaje = "";

// =====================================================
// 🔹 BUSCAR GRUPO ENDOSADOR ACTIVO
// =====================================================
$grupoActivo = mysqli_query($conexion, "
    SELECT *
    FROM grupos
    WHERE nombre_grupo LIKE 'C-END-%'
      AND registrados < cantidad
    ORDER BY id_grupo DESC
    LIMIT 1
");

$grupo = mysqli_fetch_assoc($grupoActivo);

// =====================================================
// 🔹 PROCESAR FORMULARIO
// =====================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    mysqli_begin_transaction($conexion);

    try {
        // =========================
        // DATOS CLIENTE
        // =========================
        $nombre        = trim($_POST['nombre']);
        $apellido      = trim($_POST['apellido']);
        $genero        = $_POST['genero'];
        $nro_pasaporte = trim($_POST['nro_pasaporte']);

        $empresa_endosadora = trim($_POST['empresa_endosadora']);
        $contacto           = trim($_POST['contacto']);
        $telefono_contacto  = trim($_POST['telefono_contacto']);
        $email_contacto     = trim($_POST['email_contacto']);

        // =====================================================
        // 🔹 CREAR GRUPO SI NO EXISTE
        // =====================================================
        if (!$grupo) {

            if (!isset($_POST['cantidad_grupo']) || intval($_POST['cantidad_grupo']) <= 0) {
                throw new Exception("Debe ingresar una cantidad válida");
            }

            $cantidad = intval($_POST['cantidad_grupo']);

            $stmtGrupo = mysqli_prepare($conexion, "
                INSERT INTO grupos (nombre_grupo, cantidad, registrados)
                VALUES ('TEMP', ?, 0)
            ");
            mysqli_stmt_bind_param($stmtGrupo, "i", $cantidad);
            mysqli_stmt_execute($stmtGrupo);

            $id_grupo = mysqli_insert_id($conexion);

            $codigo_grupo = 'C-END-' . str_pad($id_grupo, 3, '0', STR_PAD_LEFT);

            mysqli_query($conexion, "
                UPDATE grupos 
                SET nombre_grupo = '$codigo_grupo'
                WHERE id_grupo = $id_grupo
            ");
        } else {
            $id_grupo     = $grupo['id_grupo'];
            $codigo_grupo = $grupo['nombre_grupo'];
        }
                // =====================================================
        // 🔐 VALIDAR CUPO
        // =====================================================
        if (isset($registrados) && $registrados >= $cantidad) {
            throw new Exception("El grupo $codigo_grupo ya está completo.");
        }

        // =====================================================
        // 🚫 VALIDAR PASAPORTE DUPLICADO
        // =====================================================
        $check = mysqli_prepare($conexion, "
            SELECT id_cliente 
            FROM Datos_clientes 
            WHERE nro_pasaporte = ?
            LIMIT 1
        ");
        mysqli_stmt_bind_param($check, "s", $nro_pasaporte);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {
            throw new Exception("El número de pasaporte ya está registrado.");
        }
        // =====================================================
        // 🔹 INSERT DATOS_CLIENTES
        // =====================================================
        $stmtCliente = mysqli_prepare($conexion, "
            INSERT INTO Datos_clientes
            (nombre, apellido, genero, nro_pasaporte, tipo_cliente)
            VALUES (?, ?, ?, ?, 'END')
        ");
        mysqli_stmt_bind_param(
            $stmtCliente,
            "ssss",
            $nombre,
            $apellido,
            $genero,
            $nro_pasaporte
        );
        mysqli_stmt_execute($stmtCliente);

        $id_cliente = mysqli_insert_id($conexion);

        // =====================================================
        // 🔹 INSERT CLIENTES_ENDOSADORES
        // =====================================================
        $stmtEnd = mysqli_prepare($conexion, "
           INSERT INTO clientes_endosadores
(id_cliente, id_grupo, empresa_endosadora, contacto, telefono_contacto, email_contacto)
VALUES (?, ?, ?, ?, ?, ?)

        ");
        mysqli_stmt_bind_param(
    $stmtEnd,
    "iissss",
    $id_cliente,
    $id_grupo,
    $empresa_endosadora,
    $contacto,
    $telefono_contacto,
    $email_contacto
);

        mysqli_stmt_execute($stmtEnd);

        // =====================================================
        // 🔹 ACTUALIZAR REGISTRADOS
        // =====================================================
        mysqli_query($conexion, "
            UPDATE grupos
            SET registrados = registrados + 1
            WHERE id_grupo = $id_grupo
        ");

        mysqli_commit($conexion);

        // =====================================================
        // 🔹 MENSAJE
        // =====================================================
        $grupoActual = mysqli_fetch_assoc(mysqli_query(
            $conexion,
            "SELECT * FROM grupos WHERE id_grupo = $id_grupo"
        ));

        if ($grupoActual['registrados'] >= $grupoActual['cantidad']) {
            $mensaje = "✅ Grupo <b>$codigo_grupo</b> completado. El próximo registro creará un nuevo grupo.";
        } else {
            $faltan = $grupoActual['cantidad'] - $grupoActual['registrados'];
            $mensaje = "✅ Cliente agregado al grupo <b>$codigo_grupo</b>. Faltan <b>$faltan</b>.";
        }

    } catch (Exception $e) {
        mysqli_rollback($conexion);
        $mensaje = "❌ Error: " . $e->getMessage();
    }
}
// =====================================================
// 🔄 RECARGAR GRUPO ACTIVO PARA LA VISTA
// =====================================================
$grupoActivo = mysqli_query($conexion, "
    SELECT *
    FROM grupos
    WHERE nombre_grupo LIKE 'C-END-%'
      AND registrados < cantidad
    ORDER BY id_grupo DESC
    LIMIT 1
");
$grupo = mysqli_fetch_assoc($grupoActivo);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Agregar Cliente Endosador</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>

<body>
<?php include './../sidebar.php'; ?>

<div class="content p-4">

<div class="container-fluid">
<div class="row justify-content-center">
<div class="col-lg-8">

<!-- CARD PRINCIPAL -->
<div class="card shadow-sm border-0">

<div class="card-header bg-warning text-dark text-center fw-bold fs-5">
<i class="bi bi-person-plus-fill me-2"></i>
Agregar Cliente Endosador
</div>

<div class="card-body">

<?php if ($mensaje): ?>
<div class="alert alert-info text-center">
<?= $mensaje ?>
</div>
<?php endif; ?>

<form method="POST">

<!-- ========================= -->
<!-- GRUPO -->
<!-- ========================= -->
<?php if (!$grupo): ?>
<div class="mb-4">
<label class="form-label fw-semibold">
<i class="bi bi-people-fill me-1"></i>
Cantidad de clientes del grupo
</label>
<input type="number" name="cantidad_grupo" class="form-control form-control-lg" min="1" required>
</div>
<?php else: ?>
<div class="alert alert-success d-flex justify-content-between align-items-center">
<div>
<i class="bi bi-check-circle-fill me-1"></i>
Grupo activo: <b><?= $grupo['nombre_grupo'] ?></b>
</div>
<span class="badge bg-dark fs-6">
<?= $grupo['registrados'] ?> / <?= $grupo['cantidad'] ?>
</span>
</div>
<?php endif; ?>

<!-- ========================= -->
<!-- DATOS PERSONALES -->
<!-- ========================= -->
<h6 class="text-secondary fw-bold mb-3">
<i class="bi bi-person-lines-fill me-1"></i>
Datos del Cliente
</h6>

<div class="row g-3 mb-3">
<div class="col-md-6">
<label class="form-label">Nombre</label>
<input type="text" name="nombre" class="form-control" required>
</div>

<div class="col-md-6">
<label class="form-label">Apellido</label>
<input type="text" name="apellido" class="form-control" required>
</div>
</div>

<div class="row g-3 mb-4">
<div class="col-md-4">
<label class="form-label">Género</label>
<select name="genero" class="form-select" required>
<option value="">Seleccione</option>
<option>Masculino</option>
<option>Femenino</option>
<option>Otro</option>
</select>
</div>

<div class="col-md-4">
<label class="form-label">Pasaporte</label>
<input type="text" name="nro_pasaporte" class="form-control" required>
</div>
</div>

<hr>

<!-- ========================= -->
<!-- DATOS ENDOSADOR -->
<!-- ========================= -->
<h6 class="text-secondary fw-bold mb-3">
<i class="bi bi-building me-1"></i>
Datos de la Empresa Endosadora
</h6>

<div class="mb-3">
<label class="form-label">Empresa Endosadora</label>
<input type="text" name="empresa_endosadora" class="form-control" required>
</div>

<div class="mb-3">
<label class="form-label">Contacto</label>
<input type="text" name="contacto" class="form-control">
</div>

<div class="row g-3 mb-4">
<div class="col-md-6">
<label class="form-label">Teléfono</label>
<input type="text" name="telefono_contacto" class="form-control">
</div>

<div class="col-md-6">
<label class="form-label">Email</label>
<input type="email" name="email_contacto" class="form-control">
</div>
</div>

<!-- ========================= -->
<!-- BOTÓN -->
<!-- ========================= -->
<button class="btn btn-warning btn-lg w-100 fw-bold">
<i class="bi bi-save me-2"></i>
Guardar Endosador
</button>

</form>

</div>
</div>

</div>
</div>
</div>

</div>

</body>
</html>
