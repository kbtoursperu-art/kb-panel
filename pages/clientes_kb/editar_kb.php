<?php
include('../../conexion.php');

/* ===============================
   VALIDACIONES INICIALES
================================ */
if (!isset($_GET['id_grupo'])) {
    die("Falta el grupo");
}

$id_grupo = intval($_GET['id_grupo']);
$id_cliente = isset($_GET['id_cliente']) ? intval($_GET['id_cliente']) : null;
$cliente = null;

if ($id_cliente) {
    $sqlCliente = "
    SELECT d.*, k.fecha_nacimiento, k.foto_pasaporte, k.nro_whatsapp, k.id_grupo
    FROM datos_clientes d
    JOIN Clientes_KB k ON d.id_cliente = k.id_cliente
    WHERE d.id_cliente = ?
    ";

    $stmt = mysqli_prepare($conexion, $sqlCliente);
    mysqli_stmt_bind_param($stmt, "i", $id_cliente);
    mysqli_stmt_execute($stmt);
    $cliente = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if (!$cliente) {
        die("Cliente no encontrado");
    }
}

/* ===============================
   OBTENER DATOS DEL GRUPO
================================ */
$sqlGrupo = "
SELECT 
    g.id_grupo,
    g.nombre_grupo,
    g.cantidad,
    (
        SELECT COUNT(*) 
        FROM Clientes_KB 
        WHERE id_grupo = g.id_grupo
    ) AS ocupados
FROM grupos g
WHERE g.id_grupo = ?
";
$stmt = mysqli_prepare($conexion, $sqlGrupo);
mysqli_stmt_bind_param($stmt, 'i', $id_grupo);
mysqli_stmt_execute($stmt);
$grupo = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));



if (!$grupo) {
    die("Grupo no encontrado");
}

/* ===============================
   ACTUALIZAR CANTIDAD DEL GRUPO
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'editar_grupo') {

    $nuevaCantidad = intval($_POST['cantidad']);

    if ($nuevaCantidad < $grupo['ocupados']) {
        die("No puedes poner menos que los ocupados");
    }

    $updateGrupo = "UPDATE grupos SET cantidad = ? WHERE id_grupo = ?";
    $stmt = mysqli_prepare($conexion, $updateGrupo);
    mysqli_stmt_bind_param($stmt, "ii", $nuevaCantidad, $id_grupo);
    mysqli_stmt_execute($stmt);

header("Location: editar_kb.php?id_grupo=$id_grupo");
exit;


}

/* ===============================
   ACTUALIZAR CLIENTE
================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'editar_cliente') {

    $nombre            = $_POST['nombre'];
    $apellido          = $_POST['apellido'];
    $fecha_nacimiento  = $_POST['fecha_nacimiento'];
    $genero            = $_POST['genero'];
    $nro_pasaporte     = $_POST['nro_pasaporte'];
    $nacionalidad      = $_POST['nacionalidad'];
    $Comida            = $_POST['Comida'];
    $hotel             = $_POST['hotel'];
    $nro_whatsapp      = $_POST['nro_whatsapp'];
    $id_grupo          = intval($_POST['id_grupo']);

    /* ---- FOTO PASAPORTE ---- */
    if (!empty($_FILES['foto_pasaporte']['name'])) {

        if (!empty($_POST['foto_actual']) && file_exists('../../' . $_POST['foto_actual'])) {
            unlink('../../' . $_POST['foto_actual']);
        }

        $carpeta = '../../assets/images/fotos_pasaportes/';
        if (!is_dir($carpeta)) mkdir($carpeta, 0777, true);

        $nombreArchivo = time() . '_' . $_FILES['foto_pasaporte']['name'];
        move_uploaded_file($_FILES['foto_pasaporte']['tmp_name'], $carpeta . $nombreArchivo);

        $foto_path = 'assets/images/fotos_pasaportes/' . $nombreArchivo;

    } else {
        $foto_path = $_POST['foto_actual'];
    }

    /* ---- UPDATE DATOS_CLIENTES ---- */
    $sql1 = "UPDATE datos_clientes 
             SET nombre=?, apellido=?, genero=?, nro_pasaporte=?, nacionalidad=?, Comida=?, hotel=? 
             WHERE id_cliente=?";
    $stmt1 = mysqli_prepare($conexion, $sql1);
    mysqli_stmt_bind_param($stmt1, "sssssssi",
        $nombre, $apellido, $genero, $nro_pasaporte, $nacionalidad, $Comida, $hotel, $id_cliente
    );
    mysqli_stmt_execute($stmt1);

    /* ---- UPDATE CLIENTES_KB ---- */
    $sql2 = "UPDATE clientes_kb
             SET fecha_nacimiento=?, nro_whatsapp=?, id_grupo=?, foto_pasaporte=? 
             WHERE id_cliente=?";
    $stmt2 = mysqli_prepare($conexion, $sql2);
    mysqli_stmt_bind_param($stmt2, "ssisi",
        $fecha_nacimiento, $nro_whatsapp, $id_grupo, $foto_path, $id_cliente
    );
    mysqli_stmt_execute($stmt2);

    header("Location: index.php");
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'agregar_cliente') {

    $id_grupo = intval($_POST['id_grupo']);

    // 🔒 validar capacidad
    $cap = mysqli_fetch_assoc(mysqli_query(
        $conexion,
        "SELECT cantidad FROM grupos WHERE id_grupo=$id_grupo"
    ))['cantidad'];

    $ocup = mysqli_fetch_assoc(mysqli_query(
        $conexion,
        "SELECT COUNT(*) total FROM clientes_kb WHERE id_grupo=$id_grupo"
    ))['total'];

    if ($ocup >= $cap) {
        die("❌ El grupo está lleno");
    }

    $nombre       = $_POST['nombre'];
    $apellido     = $_POST['apellido'];
    $genero       = $_POST['genero'];
    $pasaporte    = $_POST['nro_pasaporte'];
    $nacionalidad = $_POST['nacionalidad'];
    $Comida       = $_POST['Comida'];
    $hotel        =$_POST['hotel'];
    $whatsapp     = $_POST['nro_whatsapp'];
    $fecha        = $_POST['fecha_nacimiento'];

    mysqli_query($conexion, "
        INSERT INTO datos_clientes 
        (nombre, apellido, genero, nro_pasaporte, nacionalidad, Comida, hotel, tipo_cliente)
        VALUES 
        ('$nombre','$apellido','$genero','$pasaporte','$nacionalidad','$Comida','$hotel','KB')
    ");

    $id_cliente_nuevo = mysqli_insert_id($conexion);

    mysqli_query($conexion, "
        INSERT INTO clientes_kb 
        (id_cliente, id_grupo, fecha_nacimiento, nro_whatsapp)
        VALUES 
        ($id_cliente_nuevo, $id_grupo, '$fecha', '$whatsapp')
    ");

    header("Location: editar_kb.php?id_cliente=$id_cliente_nuevo&id_grupo=$id_grupo");
    exit;
}
/* ===============================
   CLIENTES DEL GRUPO
================================ */
$sqlClientesGrupo = "
SELECT 
    d.id_cliente,
    d.nombre,
    d.apellido,
    d.genero,
    d.nro_pasaporte,
    d.nacionalidad,
    k.fecha_nacimiento,
    k.nro_whatsapp
FROM datos_clientes d
JOIN clientes_kb k ON d.id_cliente = k.id_cliente
WHERE k.id_grupo = ?
ORDER BY d.apellido, d.nombre
";

$stmt = mysqli_prepare($conexion, $sqlClientesGrupo);
mysqli_stmt_bind_param($stmt, 'i', $id_grupo);
mysqli_stmt_execute($stmt);
$clientesGrupo = mysqli_stmt_get_result($stmt);

?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Editar Cliente KB</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css" rel="stylesheet">

</head>

<body>
<?php include('../sidebar.php'); ?>

<div class="container mt-4">

<!-- ================= GRUPO ================= -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">👥 Grupo: <?= htmlspecialchars($grupo['nombre_grupo']) ?></h5>
        <span class="badge bg-info">
            <?= $grupo['ocupados'] ?> / <?= $grupo['cantidad'] ?> ocupados
        </span>
    </div>

    <div class="card-body">
        <form method="POST" class="row g-3">
            <input type="hidden" name="accion" value="editar_grupo">
              <div class="col-md-6">
                <label class="form-label">Grupo asignado</label>
                <input type="text" class="form-control"
                       value="<?= htmlspecialchars($grupo['nombre_grupo']) ?>" disabled>
            </div>
            <div class="col-md-6">
                <label class="form-label">Capacidad del grupo</label>
                <input type="number"
                       name="cantidad"
                       class="form-control"
                       min="<?= $grupo['ocupados'] ?>"
                       value="<?= $grupo['cantidad'] ?>"
                       required>
            </div>

            <div class="col-md-6 d-flex align-items-end gap-2">
                <button class="btn btn-warning w-100">
                    🔄 Actualizar capacidad
                </button>
                <button 
                    type="button"
                    class="btn btn-success"
                    data-bs-toggle="modal"
                    data-bs-target="#modalAgregarCliente">
                    ➕ Agregar cliente al grupo
                </button>


            </div>
        </form>
    </div>
</div>
<?php if ($id_cliente && $cliente): ?>
<!-- ================= CLIENTE ================= -->
<div class="card shadow-sm">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">✏️ Editar Cliente</h5>
    </div>

    <div class="card-body">
        <form method="POST" enctype="multipart/form-data" class="row g-3">
            <input type="hidden" name="accion" value="editar_cliente">
            <input type="hidden" name="foto_actual" value="<?= $cliente['foto_pasaporte'] ?>">
            <input type="hidden" name="id_grupo" value="<?= $grupo['id_grupo'] ?>">

            <div class="col-md-6">
                <label class="form-label">Nombre</label>
                <input class="form-control" name="nombre"
                       value="<?= htmlspecialchars($cliente['nombre']) ?>" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Apellido</label>
                <input class="form-control" name="apellido"
                       value="<?= htmlspecialchars($cliente['apellido']) ?>" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Fecha nacimiento</label>
                <input type="date" class="form-control" name="fecha_nacimiento"
                       value="<?= $cliente['fecha_nacimiento'] ?>" required>
            </div>

            <div class="col-md-4">
                <label class="form-label">Género</label>
                <select name="genero" class="form-control">
                    <option <?= $cliente['genero']=='Masculino'?'selected':'' ?>>Masculino</option>
                    <option <?= $cliente['genero']=='Femenino'?'selected':'' ?>>Femenino</option>
                    <option <?= $cliente['genero']=='Otro'?'selected':'' ?>>Otro</option>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label">N° Pasaporte</label>
                <input class="form-control" name="nro_pasaporte"
                       value="<?= htmlspecialchars($cliente['nro_pasaporte']) ?>">
            </div>

          <div class="col-md-4">
    <label>País</label>
    <select name="nacionalidad" id="nacionalidad" class="form-control select2" required>
        <!-- el país actual se inyecta por JS -->
    </select>
</div>


            <div class="col-md-4">
                <label class="form-label">WhatsApp</label>
                <input class="form-control" name="nro_whatsapp"
                       value="<?= htmlspecialchars($cliente['nro_whatsapp']) ?>">
            </div>

            <div class="col-md-4">
            <label class="form-label">Restricción comida</label>
            <input type="text"
                name="Comida"
                class="form-control"
                value="<?= htmlspecialchars($cliente['Comida'] ?? '') ?>">
        </div>

        <div class="col-md-4">
            <label class="form-label">Hotel</label>
            <input type="text"
                name="hotel"
                class="form-control"
                value="<?= htmlspecialchars($cliente['hotel'] ?? '') ?>">
        </div>

            <div class="col-md-6">
                <label class="form-label">Foto pasaporte</label>
                <input type="file" name="foto_pasaporte" class="form-control">
            </div>

            <div class="col-12 d-flex justify-content-end gap-2 mt-3">
                <button class="btn btn-primary">
                    💾 Guardar cambios
                </button>
                <a href="index.php" class="btn btn-outline-secondary">
                    ❌ Cancelar
                </a>
            </div>
        </form>
        <!-- ================= CLIENTES DEL GRUPO ================= -->
<div class="card shadow-sm mt-4">
  <div class="card-header bg-secondary text-white">
    <h5 class="mb-0">📋 Clientes del grupo</h5>
  </div>

  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-striped table-hover mb-0">
        <thead class="table-dark">
          <tr>
            <th>#</th>
            <th>Nombre</th>
            <th>Pasaporte</th>
            <th>País</th>
            <th>WhatsApp</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>

        <?php if (mysqli_num_rows($clientesGrupo) > 0): ?>
          <?php $i = 1; while ($c = mysqli_fetch_assoc($clientesGrupo)): ?>
            <tr>
              <td><?= $i++ ?></td>
              <td>
                <?= htmlspecialchars($c['nombre'].' '.$c['apellido']) ?>
              </td>
              <td><?= htmlspecialchars($c['nro_pasaporte']) ?></td>
              <td><?= htmlspecialchars($c['nacionalidad']) ?></td>
              <td><?= htmlspecialchars($c['nro_whatsapp']) ?></td>
              <td>
                <a href="editar_kb.php?id_grupo=<?= $id_grupo ?>&id_cliente=<?= $c['id_cliente'] ?>"
                   class="btn btn-sm btn-primary">
                   ✏️ Editar
                </a>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" class="text-center py-3">
              ⚠️ No hay clientes en este grupo
            </td>
          </tr>
        <?php endif; ?>

        </tbody>
      </table>
    </div>
  </div>
</div>

    </div>
</div>

<?php endif; ?>
</div>
<!-- ================= MODAL AGREGAR CLIENTE ================= -->
<div class="modal fade" id="modalAgregarCliente" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <form method="POST" enctype="multipart/form-data" class="modal-content">

      <!-- acciones -->
      <input type="hidden" name="accion" value="agregar_cliente">
      <input type="hidden" name="id_grupo" value="<?= $id_grupo ?>">

      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">➕ Agregar cliente al grupo</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        <!-- ================= NOMBRE / APELLIDO ================= -->
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

        <!-- ================= GENERO / PASAPORTE / FECHA ================= -->
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

        <!-- ================= WHATSAPP / PAIS / COMIDA ================= -->
        <div class="row mb-3">
          <div class="col-md-4">
            <label>WhatsApp</label>
            <input type="text" name="nro_whatsapp" class="form-control">
          </div>

          <div class="col-md-4">
            <label>País</label>
            <select name="nacionalidad" id="nacionalidad_modal" class="form-control select2" required>
              <option value="">Seleccionar país</option>
            </select>
          </div>

          <div class="col-md-4">
            <label>Restricción comida</label>
            <input type="text" name="Comida" class="form-control">
          </div>
        </div>

        <!-- ================= HOTEL ================= -->
        <div class="mb-3">
          <label>Hotel</label>
          <input type="text" name="hotel" class="form-control">
        </div>

        <!-- ================= FOTO PASAPORTE ================= -->
        <div class="mb-3">
          <label>Foto Pasaporte</label>
          <input type="file" name="foto_pasaporte" class="form-control">
        </div>

      </div>

      <div class="modal-footer">
        <button class="btn btn-primary w-100">
          💾 Guardar Cliente
        </button>
      </div>

    </form>
  </div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function () {

    const paisActual = <?= json_encode($cliente['nacionalidad']) ?>;
    const $select = $('#nacionalidad');

    $.ajax({
        url: '../../assets/json/paises.json',
        dataType: 'json',
        success: function (data) {

            $select.empty();

            if (paisActual) {
                $select.append(
                    $('<option>', {
                        value: paisActual,
                        text: paisActual,
                        selected: true
                    })
                );
            }

            data.forEach(function (pais) {
                if (pais !== paisActual) {
                    $select.append(
                        $('<option>', { value: pais, text: pais })
                    );
                }
            });

            $select.select2({
                placeholder: 'Seleccionar país',
                width: '100%'
            });
        },
        error: function (err) {
            console.error('❌ Error cargando países', err);
        }
    });

});
</script>
<script>
$(document).ready(function () {

  $('#modalAgregarCliente').on('shown.bs.modal', function () {

    const $select = $('#nacionalidad_modal');

    if ($select.children().length > 1) return; // evita recargar

    $.getJSON('../../assets/json/paises.json', function (data) {
      data.forEach(function (pais) {
        $select.append(
          $('<option>', { value: pais, text: pais })
        );
      });

      $select.select2({
        dropdownParent: $('#modalAgregarCliente'),
        width: '100%',
        placeholder: 'Seleccionar país'
      });
    });

  });

});
</script>

</body>
</html>
