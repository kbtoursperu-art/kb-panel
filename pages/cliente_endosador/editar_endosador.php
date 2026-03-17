<?php
include('../../conexion.php');

/* ===============================
   VALIDACIONES INICIALES
================================ */
$id_cliente = isset($_GET['id_cliente']) ? intval($_GET['id_cliente']) : null;
$cliente = null;

if ($id_cliente) {
    $sqlCliente = "
    SELECT d.*, e.empresa_endosadora, e.contacto, e.telefono_contacto, e.email_contacto, e.id_grupo
    FROM datos_clientes d
    JOIN clientes_endosadores e ON d.id_cliente = e.id_cliente
    WHERE d.id_cliente = ?
    ";

    $stmt = mysqli_prepare($conexion, $sqlCliente);
    mysqli_stmt_bind_param($stmt, "i", $id_cliente);
    mysqli_stmt_execute($stmt);
    $cliente = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$cliente) {
        die("❌ Cliente endosador no encontrado");
    }
}

/* ===============================
   GRUPOS
================================ */
$grupos = mysqli_query($conexion, "SELECT id_grupo, nombre_grupo FROM grupos ORDER BY nombre_grupo");

/* ===============================
   ACTUALIZAR CLIENTE ENDOSADOR
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'editar_cliente') {

    $nombre       = $_POST['nombre'];
    $apellido     = $_POST['apellido'];
    $genero       = $_POST['genero'];
    $pasaporte    = $_POST['nro_pasaporte'];
    $nacionalidad = $_POST['nacionalidad'];
    $empresa      = $_POST['empresa'];
    $contacto     = $_POST['contacto'];
    $telefono     = $_POST['telefono'];
    $email        = $_POST['email'];
    $id_grupo     = !empty($_POST['id_grupo']) ? intval($_POST['id_grupo']) : null;

    // Validar pasaporte duplicado
    $v = $conexion->prepare("SELECT id_cliente FROM datos_clientes WHERE nro_pasaporte=? AND id_cliente!=?");
    $v->bind_param("si", $pasaporte, $id_cliente);
    $v->execute();
    if ($v->get_result()->num_rows > 0) {
        die("❌ Pasaporte ya registrado");
    }

    // Actualizar Datos_clientes
    $u1 = $conexion->prepare("
        UPDATE datos_clientes
        SET nombre=?, apellido=?, genero=?, nro_pasaporte=?, nacionalidad=?
        WHERE id_cliente=?
    ");
    $u1->bind_param("sssssi", $nombre, $apellido, $genero, $pasaporte, $nacionalidad, $id_cliente);
    $u1->execute();

    // Actualizar clientes_endosadores
    $u2 = $conexion->prepare("
        UPDATE clientes_endosadores
        SET empresa_endosadora=?, contacto=?, telefono_contacto=?, email_contacto=?, id_grupo=?
        WHERE id_cliente=?
    ");
    $u2->bind_param("ssssii", $empresa, $contacto, $telefono, $email, $id_grupo, $id_cliente);
    $u2->execute();

    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Editar Cliente Endosador</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css" rel="stylesheet">
</head>
<body>
<?php include './../sidebar.php'; ?>

<div class="container mt-4">

<?php if ($cliente): ?>
<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">✏️ Editar Endosador</h5>
    </div>

    <div class="card-body">
        <form method="POST" class="row g-3">
            <input type="hidden" name="accion" value="editar_cliente">

            <div class="col-md-6">
                <label class="form-label">Nombre</label>
                <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($cliente['nombre']) ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Apellido</label>
                <input type="text" name="apellido" class="form-control" value="<?= htmlspecialchars($cliente['apellido']) ?>" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Género</label>
                <select name="genero" class="form-control">
                    <option value="Masculino" <?= $cliente['genero']=='Masculino'?'selected':'' ?>>Masculino</option>
                    <option value="Femenino" <?= $cliente['genero']=='Femenino'?'selected':'' ?>>Femenino</option>
                    <option value="Otro" <?= $cliente['genero']=='Otro'?'selected':'' ?>>Otro</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">Pasaporte</label>
                <input type="text" name="nro_pasaporte" class="form-control" value="<?= htmlspecialchars($cliente['nro_pasaporte']) ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label">País</label>
                <select name="nacionalidad" id="nacionalidad" class="form-control select2" required>
                    <!-- Se llena por JS -->
                </select>
            </div>

            <div class="col-md-6">
                <label class="form-label">Empresa Endosadora</label>
                <input type="text" name="empresa" class="form-control" value="<?= htmlspecialchars($cliente['empresa_endosadora']) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Contacto</label>
                <input type="text" name="contacto" class="form-control" value="<?= htmlspecialchars($cliente['contacto']) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Teléfono</label>
                <input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($cliente['telefono_contacto']) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($cliente['email_contacto']) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Grupo</label>
                <select name="id_grupo" class="form-control select2">
                    <option value="">-- Sin grupo --</option>
                    <?php while($g=mysqli_fetch_assoc($grupos)): ?>
                    <option value="<?= $g['id_grupo'] ?>" <?= $cliente['id_grupo']==$g['id_grupo']?'selected':'' ?>>
                        <?= htmlspecialchars($g['nombre_grupo']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="col-12 d-flex justify-content-end gap-2 mt-3">
                <button class="btn btn-primary">💾 Guardar</button>
                <a href="index.php" class="btn btn-outline-secondary">❌ Cancelar</a>
            </div>

        </form>
    </div>
</div>
<?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function () {

    const paisActual = <?= json_encode($cliente['nacionalidad']) ?>;
    const $select = $('#nacionalidad');

    $.getJSON('../../assets/json/paises.json', function(data){
        $select.empty();
        if(paisActual){
            $select.append($('<option>', {value: paisActual, text: paisActual, selected: true}));
        }
        data.forEach(function(pais){
            if(pais !== paisActual){
                $select.append($('<option>', {value: pais, text: pais}));
            }
        });
        $select.select2({placeholder:'Seleccionar país', width:'100%'});
    });

});
</script>

</body>
</html>
