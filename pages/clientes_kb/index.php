<?php
include('../../conexion.php');
include('../sidebar.php');

// ==================== 🟩 IMPORTAR EXCEL ====================
require_once('../../vendor/autoload.php');
use PhpOffice\PhpSpreadsheet\IOFactory;

if (isset($_POST['importar_excel'])) {
    if (isset($_FILES['archivo_excel']['tmp_name'])) {
        $archivo = $_FILES['archivo_excel']['tmp_name'];

        try {
            $documento = IOFactory::load($archivo);
            $hoja = $documento->getActiveSheet();
            $filas = $hoja->toArray();

            // Saltar encabezado
            
            for ($i = 1; $i < count($filas); $i++) {
                $fila = $filas[$i];
                if (empty($fila[0])) continue; // si la fila está vacía

                $nombre = mysqli_real_escape_string($conexion, $fila[0]);
                $apellido = mysqli_real_escape_string($conexion, $fila[1]);
                $genero = mysqli_real_escape_string($conexion, $fila[2]);
                $pasaporte = mysqli_real_escape_string($conexion, $fila[3]);
                $edad = mysqli_real_escape_string($conexion, $fila[4]);
                $whatsapp = mysqli_real_escape_string($conexion, $fila[5]);
                $nacionalidad = mysqli_real_escape_string($conexion, $fila[6]);
                $grupo = mysqli_real_escape_string($conexion, $fila[7]);
                $hotel = mysqli_real_escape_string($conexion, $fila[8]);

                // Insertar en Datos_clientes
                $query_cliente = "INSERT INTO Datos_clientes (nombre, apellido, genero, nro_pasaporte, nacionalidad, tipo_cliente)
                                  VALUES ('$nombre', '$apellido', '$genero', '$pasaporte', '$nacionalidad', 'KB')";
                mysqli_query($conexion, $query_cliente);
                $id_cliente = mysqli_insert_id($conexion);

                // Insertar en Clientes_KB
                $query_kb = "INSERT INTO Clientes_KB (id_cliente, edad, nro_whatsapp, grupo, hotel)
                             VALUES ('$id_cliente', '$edad', '$whatsapp', '$grupo', '$hotel')";
                mysqli_query($conexion, $query_kb);
            }

            echo "<script>alert('✅ Clientes importados correctamente.');</script>";
        } catch (Exception $e) {
            echo "<script>alert('❌ Error al importar: " . $e->getMessage() . "');</script>";
        }
    }
}

// ==================== 🟦 CONSULTA CLIENTES KB ====================
$query_kb = "
SELECT d.id_cliente, d.nombre, d.apellido, d.genero, d.nro_pasaporte,
       k.edad, k.foto_pasaporte, k.nro_whatsapp,
       d.nacionalidad, d.Comida, k.grupo, k.hotel
FROM Datos_clientes d
JOIN Clientes_KB k ON d.id_cliente = k.id_cliente
WHERE d.tipo_cliente = 'KB'
";
$result_kb = mysqli_query($conexion, $query_kb);
if (!$result_kb) {
    die("Error en la consulta: " . mysqli_error($conexion)); 
}
?>

<!-- ==================== 🧭 CONTENIDO PRINCIPAL ==================== -->
<div class="content p-4">
    <div class="container-fluid">
        <h2 class="mb-4">👥 Clientes KB</h2>

        <div class="d-flex justify-content-between mb-3 flex-wrap">
            <a href="agregar_kb.php" class="btn btn-success mb-2">
                ➕ Agregar Cliente KB
            </a>
            <button class="btn btn-primary mb-2" data-bs-toggle="modal" data-bs-target="#importModal">
                📥 Importar desde Excel
            </button>
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
                                <th>Edad</th>
                                <th>Género</th>
                                <th>Pasaporte</th>
                                <th>Nacionalidad</th>
                                <th>Comida</th>
                                <th>Foto</th>
                                <th>WhatsApp</th>
                                <th>Grupo</th>
                                <th>Hotel</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = mysqli_fetch_assoc($result_kb)) : ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['id_cliente']) ?></td>
                                    <td><?= htmlspecialchars($row['nombre']) ?></td>
                                    <td><?= htmlspecialchars($row['apellido']) ?></td>
                                    <td><?= htmlspecialchars($row['edad']) ?></td>
                                    <td><?= htmlspecialchars($row['genero']) ?></td>
                                    <td><?= htmlspecialchars($row['nro_pasaporte']) ?></td>
                                    <td><?= htmlspecialchars($row['nacionalidad']) ?></td>
                                    <td><?= htmlspecialchars($row['Comida']) ?></td>
                                    <td class="text-center">
                                        <?php if (!empty($row['foto_pasaporte'])) : ?>
                                            <img src="<?= htmlspecialchars($row['foto_pasaporte']) ?>" alt="Foto" width="50" class="rounded">
                                        <?php else : ?>
                                            <span class="text-muted">Sin foto</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['nro_whatsapp']) ?></td>
                                    <td><?= htmlspecialchars($row['grupo']) ?></td>
                                    <td><?= htmlspecialchars($row['hotel']) ?></td>
                                    <td class="text-center">
                                        <a href="editar_kb.php?id_cliente=<?= $row['id_cliente'] ?>" class="btn btn-success btn-sm mb-1">Editar</a>
                                        <form action="eliminar_kb.php" method="POST" style="display:inline;" onsubmit="return confirm('¿Seguro que deseas eliminar este cliente?');">
                                            <input type="hidden" name="id_cliente" value="<?= $row['id_cliente'] ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
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

<!-- ==================== 📥 MODAL IMPORTAR EXCEL ==================== -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" enctype="multipart/form-data" class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="importModalLabel">📥 Importar Clientes KB desde Excel</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p>Selecciona un archivo Excel (.xlsx o .xls) con los datos de clientes KB:</p>
        <input type="file" name="archivo_excel" class="form-control" accept=".xlsx,.xls" required>
      </div>
      <div class="modal-footer">
        <button type="submit" name="importar_excel" class="btn btn-success">Importar</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </form>
  </div>
</div>

<link rel="stylesheet" href="stilo.css">

<!-- ==================== 📊 DATATABLES Y BOTONES ==================== -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>

<script>
$(function () {
    new DataTable('#clientes-kb', {
        dom: 'Bfrtip',
        buttons: [{
            extend: 'excelHtml5',
            text: '📊 Exportar a Excel',
            className: 'btn btn-success mb-3',
            title: 'clientes-kb',
            exportOptions: { columns: ':visible:not(:last-child)' }
        }],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/Spanish.json'
        },
        pageLength: 10,
        order: [[0, "desc"]],
        scrollX: true   // 👈 ESTO es el scroll correcto
    });
});
</script>


<!-- Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
