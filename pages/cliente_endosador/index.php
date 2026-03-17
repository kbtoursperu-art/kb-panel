<?php
include '../../conexion.php';

/* ===================== IMPORTAR DESDE EXCEL ===================== */
require '../../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_excel'])) {

    $archivo = $_FILES['archivo_excel']['tmp_name'];
    if ($archivo) {

        try {
            $documento = IOFactory::load($archivo);
            $hoja = $documento->getActiveSheet();
            $filas = $hoja->toArray();

            $importados = 0;

            foreach ($filas as $i => $fila) {
                if ($i === 0) continue; // saltar encabezados

                $nombre      = trim($fila[0] ?? '');
                $apellido    = trim($fila[1] ?? '');
                $genero      = trim($fila[2] ?? '');
                $pasaporte   = trim($fila[3] ?? '');
                $empresa     = trim($fila[4] ?? '');
                $grupoNombre = trim($fila[5] ?? '');
                $contacto    = trim($fila[6] ?? '');
                $telefono    = trim($fila[7] ?? '');
                $email       = trim($fila[8] ?? '');

                if ($nombre === '' || $pasaporte === '') continue;

                // Validar pasaporte duplicado
                $v = $conexion->prepare("SELECT id_cliente FROM Datos_clientes WHERE nro_pasaporte=?");
                $v->bind_param("s", $pasaporte);
                $v->execute();
                $v->store_result();
                if ($v->num_rows > 0) continue;

                // ----------------------------
                // INSERTAR CLIENTE
                // ----------------------------
                $c = $conexion->prepare("
                    INSERT INTO Datos_clientes
                    (nombre, apellido, genero, nro_pasaporte, tipo_cliente)
                    VALUES (?, ?, ?, ?, 'END')
                ");
                $c->bind_param("ssss", $nombre, $apellido, $genero, $pasaporte);
                $c->execute();
                $id_cliente = $c->insert_id;

                // ----------------------------
                // ASIGNAR ID DE GRUPO
                // grupo END
$id_grupo = NULL;
if ($grupoNombre === '' || !str_starts_with($grupoNombre, 'C-END-')) {
    // crear un nuevo grupo END automáticamente
    $stmtNuevoGrupo = $conexion->prepare("
        INSERT INTO grupos (nombre_grupo, cantidad, registrados, estado)
        VALUES (?, 1, 0, 'abierto')
    ");
    // generar nombre temporal, luego actualizar
    $tempNombre = 'TEMP';
    $stmtNuevoGrupo->bind_param("s", $tempNombre);
    $stmtNuevoGrupo->execute();
    $id_grupo = $stmtNuevoGrupo->insert_id;

    $codigo_grupo = 'C-END-' . str_pad($id_grupo, 3, '0', STR_PAD_LEFT);
    $conexion->query("UPDATE grupos SET nombre_grupo='$codigo_grupo' WHERE id_grupo=$id_grupo");
} else {
    // usar grupo existente C-END
    $g = $conexion->prepare("SELECT id_grupo FROM grupos WHERE nombre_grupo=? LIMIT 1");
    $g->bind_param("s", $grupoNombre);
    $g->execute();
    $r = $g->get_result();
    if ($r->num_rows > 0) {
        $id_grupo = $r->fetch_assoc()['id_grupo'];
    }
}


                // ----------------------------
                // INSERTAR EN CLIENTES_ENDOSADORES
                // ----------------------------
                $e = $conexion->prepare("
                    INSERT INTO clientes_endosadores
                    (id_cliente, id_grupo, empresa_endosadora, contacto, telefono_contacto, email_contacto)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $e->bind_param("iissss", $id_cliente, $id_grupo, $empresa, $contacto, $telefono, $email);
                $e->execute();

                $importados++;
            }

            echo "<script>alert('Importados: $importados');location.href='index.php'</script>";
            exit;

        } catch (Exception $e) {
            die('Error Excel: '.$e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Clientes Endosadores</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
</head>

<body>

<?php include './../sidebar.php'; ?>

<div class="content p-4">
<div class="container-fluid">

<h3 class="mb-3">👥 Clientes Endosadores</h3>

<div class="d-flex gap-2 mb-3">
    <a href="agregar_endosador.php" class="btn btn-success">➕ Agregar</a>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#importar">
        📥 Importar Excel
    </button>
</div>

<!-- MODAL -->
<div class="modal fade" id="importar">
<div class="modal-dialog">
<div class="modal-content">
<form method="POST" enctype="multipart/form-data">
<div class="modal-header bg-primary text-white">
<h5>Importar Excel</h5>
</div>
<div class="modal-body">
<input type="file" name="archivo_excel" class="form-control" required>
<small class="text-muted">Grupo = nombre del grupo</small>
</div>
<div class="modal-footer">
<button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
<button class="btn btn-success">Importar</button>
</div>
</form>
</div>
</div>
</div>

<div class="card">
<div class="card-body table-responsive">

<table id="tabla" class="table table-bordered table-striped">
<thead class="table-dark text-center">
<tr>
<th>ID</th>
<th>Nombre</th>
<th>Apellido</th>
<th>Pasaporte</th>
<th>Empresa</th>
<th>Grupo</th>
<th>Contacto</th>
<th>Teléfono</th>
<th>Email</th>
<th>Acciones</th>
</tr>
</thead>

<tbody>
<?php
$sql = "
SELECT 
    d.id_cliente,
    d.nombre,
    d.apellido,
    d.nro_pasaporte,
    e.empresa_endosadora,
    IFNULL(g.nombre_grupo,'SIN GRUPO') AS grupo,
    e.contacto,
    e.telefono_contacto,
    e.email_contacto,
    e.id_grupo
FROM Datos_clientes d
JOIN clientes_endosadores e ON d.id_cliente = e.id_cliente
LEFT JOIN grupos g ON g.id_grupo = e.id_grupo
WHERE d.tipo_cliente='END'
ORDER BY d.id_cliente DESC
";

$r = mysqli_query($conexion, $sql);
while ($row = mysqli_fetch_assoc($r)) {
?>
<tr>
<td><?= $row['id_cliente'] ?></td>
<td><?= $row['nombre'] ?></td>
<td><?= $row['apellido'] ?></td>
<td><?= $row['nro_pasaporte'] ?></td>
<td><?= $row['empresa_endosadora'] ?></td>
<td><?= $row['grupo'] ?></td>
<td><?= $row['contacto'] ?></td>
<td><?= $row['telefono_contacto'] ?></td>
<td><?= $row['email_contacto'] ?></td>
<td class="text-center">
    <a href="editar_endosador.php?id_cliente=<?= $row['id_cliente'] ?>&id_grupo=<?= $row['id_grupo'] ?>"
       class="btn btn-sm btn-primary">✏️</a>

    <form method="POST" action="eliminar_endosador.php" style="display:inline"
          onsubmit="return confirm('¿Eliminar este cliente?')">
        <input type="hidden" name="id_cliente" value="<?= $row['id_cliente'] ?>">
        <button class="btn btn-sm btn-danger">🗑️</button>
    </form>
</td>
</tr>
<?php } ?>
</tbody>
</table>

</div>
</div>

</div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script>
$('#tabla').DataTable({
    language:{url:'https://cdn.datatables.net/plug-ins/1.11.5/i18n/Spanish.json'}
});
</script>

</body>
</html>
