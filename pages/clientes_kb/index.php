<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include('../../conexion.php');
include('../sidebar.php');

// ==================== 🟩 IMPORTAR EXCEL ====================
require_once('../../vendor/autoload.php');
use PhpOffice\PhpSpreadsheet\IOFactory;

// Obtener grupos para el select
$grupos = mysqli_query($conexion, "SELECT id_grupo, nombre_grupo FROM grupos");

// ==================== IMPORTAR DESDE EXCEL ====================
if (isset($_POST['importar_excel'])) {
    $id_grupo = intval($_POST['id_grupo']);
    if ($id_grupo <= 0) die("Grupo inválido");

    if (isset($_FILES['archivo_excel']['tmp_name'])) {
        $archivo = $_FILES['archivo_excel']['tmp_name'];
        try {
            $documento = IOFactory::load($archivo);
            $hoja = $documento->getActiveSheet();
            $filas = $hoja->toArray();

            // Saltar encabezado
            for ($i = 1; $i < count($filas); $i++) {
                if (empty($filas[$i][0])) continue;

                $nombre       = mysqli_real_escape_string($conexion, $filas[$i][0]);
                $apellido     = mysqli_real_escape_string($conexion, $filas[$i][1]);
                $genero       = mysqli_real_escape_string($conexion, $filas[$i][2]);
                $pasaporte    = mysqli_real_escape_string($conexion, $filas[$i][3]);
                $fecha_nacimiento = mysqli_real_escape_string($conexion, $filas[$i][4]);
                $whatsapp     = mysqli_real_escape_string($conexion, $filas[$i][5]);
                $nacionalidad = mysqli_real_escape_string($conexion, $filas[$i][6]);
                $comida       = mysqli_real_escape_string($conexion, $filas[$i][7] ?? '');

                // Insertar en datos_clientes
                mysqli_query($conexion, "
                    INSERT INTO datos_clientes 
                    (nombre, apellido, genero, nro_pasaporte, Comida, tipo_cliente)
                    VALUES 
                    ('$nombre','$apellido','$genero','$pasaporte','$comida','KB')
                ");

                $id_cliente = mysqli_insert_id($conexion);

                // Insertar en clientes_kb
                mysqli_query($conexion, "
                    INSERT INTO clientes_kb 
                    (id_cliente, fecha_nacimiento, nro_whatsapp, id_grupo)
                    VALUES 
                    ($id_cliente,'$fecha_nacimiento','$whatsapp',$id_grupo)
                ");
            }
            echo "<script>alert('✅ Clientes importados correctamente');</script>";
        } catch (Exception $e) {
            echo "<script>alert('❌ Error: {$e->getMessage()}');</script>";
        }
    }
}

// ==================== CONSULTA CLIENTES KB ====================
$query_kb = "
SELECT 
    d.id_cliente,
    d.nombre,
    d.apellido,
    d.genero,
    d.nro_pasaporte,
    d.nacionalidad,
    d.Comida,
    k.fecha_nacimiento,
    k.nro_whatsapp,
    k.id_grupo,
    g.nombre_grupo,
    k.hotel,
    COUNT(k.id_cliente) OVER (PARTITION BY k.id_grupo) AS pasajeros
FROM clientes_kb k
JOIN datos_clientes d ON d.id_cliente = k.id_cliente
JOIN grupos g ON g.id_grupo = k.id_grupo
WHERE d.tipo_cliente = 'KB'
ORDER BY g.nombre_grupo, d.id_cliente DESC
";
$result_kb = mysqli_query($conexion, $query_kb);
if (!$result_kb) die("Error SQL: " . mysqli_error($conexion));
?>

<!-- ==================== CONTENIDO ==================== -->
<div class="content p-4">
    <div class="container-fluid">
        <h2 class="mb-4">👥 Clientes KB</h2>

        <div class="d-flex justify-content-between mb-3 flex-wrap">
            <a href="agregar_kb.php" class="btn btn-success mb-2">➕ Agregar Cliente KB</a>
            <button class="btn btn-primary mb-2" data-bs-toggle="modal" data-bs-target="#importModal">📥 Importar desde Excel</button>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <label><strong>Filtrar por grupo</strong></label>
                <select id="filtroGrupo" class="form-control">
                    <option value="">Todos los grupos</option>
                    <?php
                    mysqli_data_seek($grupos, 0);
                    while ($g = mysqli_fetch_assoc($grupos)) :
                    ?>
                        <option value="<?= htmlspecialchars($g['nombre_grupo']) ?>">
                            <?= htmlspecialchars($g['nombre_grupo']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="clientes-kb" class="table table-striped table-bordered">
                        <thead class="table-dark text-center">
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Apellido</th>
                                <th>F.Nacimiento</th>
                                <th>Género</th>
                                <th>Pasaporte</th>
                                <th>Nacionalidad</th>
                                <th>Comida</th>
                                <th>WhatsApp</th>
                                <th>Pasajeros</th>
                                <th>Grupo</th>
                                <th>Hotel</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($result_kb)) : ?>
                                <tr>
                                    <td><?= $row['id_cliente'] ?></td>
                                    <td><?= htmlspecialchars($row['nombre']) ?></td>
                                    <td><?= htmlspecialchars($row['apellido']) ?></td>
                                    <td><?= htmlspecialchars($row['fecha_nacimiento']) ?></td>
                                    <td><?= htmlspecialchars($row['genero']) ?></td>
                                    <td><?= htmlspecialchars($row['nro_pasaporte']) ?></td>
                                    <td><?= htmlspecialchars($row['nacionalidad']) ?></td>
                                    <td><?= htmlspecialchars($row['Comida']) ?></td>
                                    <td><?= htmlspecialchars($row['nro_whatsapp']) ?></td>
                                    <td><?= htmlspecialchars($row['pasajeros']) ?></td>
                                    <td><?= htmlspecialchars($row['nombre_grupo']) ?></td>
                                    <td><?= htmlspecialchars($row['hotel']) ?></td>
                                    <td class="text-center">
                                        <a href="editar_kb.php?id_cliente=<?= $row['id_cliente'] ?>&id_grupo=<?= $row['id_grupo'] ?>" class="btn btn-warning btn-sm">✏️ Editar</a>
                                        <form method="POST" action="eliminar_kb.php" style="display:inline;">
                                            <input type="hidden" name="id_cliente" value="<?= $row['id_cliente'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Seguro que deseas eliminar este cliente?');">🗑 Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ==================== MODAL IMPORTAR EXCEL ==================== -->
<div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" enctype="multipart/form-data" class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">📥 Importar Clientes KB desde Excel</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        <select name="id_grupo" class="form-control mb-3" required>
            <option value="">Seleccionar grupo</option>
            <?php mysqli_data_seek($grupos, 0); while ($g = mysqli_fetch_assoc($grupos)) : ?>
                <option value="<?= $g['id_grupo'] ?>"><?= htmlspecialchars($g['nombre_grupo']) ?></option>
            <?php endwhile; ?>
        </select>
      </div>
      <div class="modal-body">
        <input type="file" name="archivo_excel" class="form-control" accept=".xlsx,.xls" required>
      </div>
      <div class="modal-footer">
        <button type="submit" name="importar_excel" class="btn btn-success">Importar</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<!-- ==================== DATATABLES ==================== -->
<link rel="stylesheet" href="stilo.css">
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

<script>
$(function () {
    const tabla = new DataTable('#clientes-kb', {
        dom: 'Bfrtip',
        buttons: [{
            extend: 'excelHtml5',
            text: '📊 Exportar a Excel',
            className: 'btn btn-success mb-3',
            title: 'clientes-kb',
            exportOptions: { columns: ':visible:not(:last-child)' }
        }],
        language: { url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/Spanish.json' },
        pageLength: 10,
        order: [[0, "desc"]],
        scrollX: true
    });

    $('#filtroGrupo').on('change', function () {
        tabla.column(10).search(this.value).draw(); // columna del grupo
    });
});
</script>
