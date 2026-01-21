<?php
include '../../conexion.php';

// ============================ IMPORTAR DESDE EXCEL ============================
require '../../vendor/autoload.php'; // Requiere PhpSpreadsheet
use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_excel'])) {
    $archivo = $_FILES['archivo_excel']['tmp_name'];

    if ($archivo) {
        try {
            $documento = IOFactory::load($archivo);
            $hoja = $documento->getActiveSheet();
            $filas = $hoja->toArray();

            $contador = 0;
            foreach ($filas as $index => $columna) {
                if ($index === 0) continue; // Saltar encabezado
                list($nombre, $apellido, $genero, $pasaporte, $empresa, $grupo, $contacto, $telefono, $email) = $columna;

                // Insertar en Datos_clientes
                $sql1 = "INSERT INTO Datos_clientes (nombre, apellido, genero, nro_pasaporte, tipo_cliente)
                         VALUES (?, ?, ?, ?, 'Endosador')";
                $stmt1 = $conexion->prepare($sql1);
                $stmt1->bind_param("ssss", $nombre, $apellido, $genero, $pasaporte);
                $stmt1->execute();
                $id_cliente = $stmt1->insert_id;

                // Insertar en Clientes_Endosadores
                $sql2 = "INSERT INTO Clientes_Endosadores 
                    (id_cliente, empresa_endosadora, grupo, contacto, telefono_contacto, email_contacto)
                    VALUES (?, ?, ?, ?, ?, ?)";

                $stmt2 = $conexion->prepare($sql2);
                $stmt2->bind_param("isssss", $id_cliente, $empresa, $grupo, $contacto, $telefono, $email);
                $stmt2->execute();

                $contador++;
            }

            echo "<script>alert('✅ Se importaron $contador registros correctamente.'); window.location='clientes_endosadores.php';</script>";
            exit;

        } catch (Exception $e) {
            echo "<script>alert('❌ Error al importar: " . addslashes($e->getMessage()) . "'); window.location='clientes_endosadores.php';</script>";
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Clientes Endosadores</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Estilos generales -->
    <link rel="stylesheet" href="stilo.css">

    <!-- DataTables + Buttons CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap5.min.css">
</head>

<body>
    <?php include './../sidebar.php';?>
    <div class="content p-4">
        <div class="container-fluid">
            <h2 class="titulo-seccion mb-4">👥 Clientes Endosadores</h2>

            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <a href="agregar_endosador.php" class="btn btn-success">
                    ➕ Agregar Cliente Endosador
                </a>

                <!-- Botón importar Excel -->
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalImportar">
                    📤 Importar Excel
                </button>
            </div>

            <!-- Modal Importar Excel -->
            <div class="modal fade" id="modalImportar" tabindex="-1" aria-labelledby="modalImportarLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title" id="modalImportarLabel">📤 Importar desde Excel</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted mb-2">
                                    Selecciona un archivo Excel (.xlsx o .xls) con las siguientes columnas:
                                </p>
                                <input type="file" name="archivo_excel" accept=".xlsx, .xls" class="form-control" required>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-success">Importar</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Tabla de Endosadores -->
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tablaEndosadores" class="table table-striped table-bordered nowrap">
                            <thead class="table-dark text-center">
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Apellido</th>
                                    <th>Género</th>
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
                                $sql_end = "SELECT 
                                                d.id_cliente, 
                                                d.nombre, 
                                                d.apellido, 
                                                d.genero, 
                                                d.nro_pasaporte,
                                                e.empresa_endosadora, 
                                                e.grupo,
                                                e.contacto, 
                                                e.telefono_contacto, 
                                                e.email_contacto
                                            FROM Datos_clientes d
                                            JOIN Clientes_Endosadores e ON d.id_cliente = e.id_cliente
                                            WHERE d.tipo_cliente = 'Endosador'";
                                $result_end = mysqli_query($conexion, $sql_end);
                                if (!$result_end) {
                                    die("Error en la consulta: " . mysqli_error($conexion));
                                }

                                while ($row = mysqli_fetch_assoc($result_end)) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['id_cliente']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['nombre']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['apellido']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['genero']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['nro_pasaporte']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['empresa_endosadora']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['grupo']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['contacto']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['telefono_contacto']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['email_contacto']) . "</td>";
                                    echo "<td class='text-center'>
                                            <a href='editar_endosador.php?id=" . $row['id_cliente'] . "' class='btn btn-primary btn-sm me-1'>✏️ Editar</a>
                                            <form action='eliminar_endosador.php' method='POST' style='display:inline;' onsubmit='return confirm(\"¿Seguro que deseas eliminar este cliente?\");'>
                                                <input type='hidden' name='id_cliente' value='" . $row['id_cliente'] . "'>
                                                <button type='submit' class='btn btn-danger btn-sm'>🗑️ Eliminar</button>
                                            </form>
                                          </td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ======================= SCRIPTS ======================= -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min.js"></script>

    <script>
        $(document).ready(function () {
            new DataTable('#tablaEndosadores', {
                dom: 'Bfrtip',
                buttons: [{
                    extend: 'excelHtml5',
                    text: '📊 Exportar a Excel',
                    className: 'btn btn-success mb-3',
                    title: 'Clientes_Endosadores',
                    exportOptions: { columns: ':visible:not(:last-child)' }
                }],
                language: { url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/Spanish.json' },
                pageLength: 10,
                order: [[0, "desc"]]
            });
        });
    </script>


</body>
</html>
