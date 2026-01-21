<?php
include '../../../conexion.php';
include '../../sidebar.php';
require '../../../vendor/autoload.php'; // Librería PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\IOFactory;

// ====================== 🔹 IMPORTAR EXCEL ======================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['archivo_excel'])) {
    $file = $_FILES['archivo_excel']['tmp_name'];
    $nombre = $_FILES['archivo_excel']['name'];
    $ext = pathinfo($nombre, PATHINFO_EXTENSION);

    if (in_array($ext, ['xls', 'xlsx'])) {
        try {
            $spreadsheet = IOFactory::load($file);
            $hoja = $spreadsheet->getActiveSheet();
            $filas = $hoja->toArray(null, true, true, true);

            $total = 0;
            foreach ($filas as $index => $fila) {
                if ($index == 1) continue; // Saltar encabezado

                $cliente = trim($fila['A'] ?? '');
                $empresa = trim($fila['B'] ?? '');
                $servicio = trim($fila['C'] ?? '');
                $fecha = trim($fila['D'] ?? '');
                $encargado = trim($fila['E'] ?? '');
                $precio = trim($fila['F'] ?? 0);

                if (!empty($cliente)) {
                    $sql = "INSERT INTO Operaciones (nombre_servicio, fecha_reserva, Encargado) 
                            VALUES ('$servicio', '$fecha', '$encargado')";
                    mysqli_query($conexion, $sql);
                    $total++;
                }
            }
            echo "<script>alert('✅ Importación completada: $total registros añadidos');</script>";
        } catch (Exception $e) {
            echo "<script>alert('❌ Error al procesar el archivo: " . $e->getMessage() . "');</script>";
        }
    } else {
        echo "<script>alert('⚠️ Formato no válido. Solo se aceptan .xls o .xlsx');</script>";
    }
}

// ====================== 🔹 CONSULTA PRINCIPAL ======================
$query = "
SELECT 
    d.id_cliente,
    CONCAT(d.nombre, ' ', d.apellido) AS cliente,
    e.empresa_endosadora AS empresa,
    e.grupo AS grupo,
    o.id_operaciones,
    o.nombre_servicio,
    o.fecha_reserva,
    o.fecha_salida,
    o.fecha_retorno,
    o.incluye_ingreso,
    o.modalidad_retorno,
    o.servicio_adicional,
    o.observaciones,
    o.Encargado,
    c.metodo_pago,
    c.tipo_moneda,
    c.precio_servicio,
    c.pagado_a_cuenta,
    c.saldo_pendiente
FROM Datos_clientes d
INNER JOIN Clientes_Endosadores e ON d.id_cliente = e.id_cliente
LEFT JOIN Operaciones o ON d.id_cliente = o.id_cliente
LEFT JOIN Contabilidad c ON o.id_operaciones = c.id_operaciones
WHERE d.tipo_cliente = 'Endosador'
ORDER BY o.id_operaciones DESC
";

$resultado = mysqli_query($conexion, $query);
if (!$resultado) {
    die("Error en la consulta: " . mysqli_error($conexion));
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Operaciones - Endosadores</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- ====================== 🔹 CSS ====================== -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">
<link rel="stylesheet" href="stilo.css">

</head>
<body>
<div class="content p-4">
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="titulo-seccion">📋 Operaciones - Endosadores</h2>
        <div>
            <a href="../clientes_endosador/endosadores.php" class="btn btn-success me-2">
                ➕ Nuevo Endosador
            </a>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalImportar">
                📥 Importar Excel
            </button>
        </div>
    </div>

    <!-- ====================== 🔹 TABLA PRINCIPAL ====================== -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tablaOperaciones" class="table table-striped table-bordered nowrap align-middle w-100">
                    <thead class="table-dark text-center">
                        <tr>
                            <th>#</th>
                            <th>Cliente</th>
                            <th>Empresa</th>
                            <th>Grupo</th>
                            <th>Servicio</th>
                            <th>Fecha Reserva</th>
                            <th>Salida</th>
                            <th>Retorno</th>
                            <th>Ingreso</th>
                            <th>Modalidad</th>
                            <th>Adicional</th>
                            <th>Encargado</th>
                            <th>Método Pago</th>
                            <th>Precio</th>
                            <th>Pagado</th>
                            <th>Saldo</th>
                            <th>Observaciones</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if (mysqli_num_rows($resultado) > 0):
                            $i = 1;
                            while ($row = mysqli_fetch_assoc($resultado)): ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><?= htmlspecialchars($row['cliente']) ?></td>
                                    <td><?= htmlspecialchars($row['empresa'] ?? '—') ?></td>
                                    <td class="fw-bold text-center"><?= htmlspecialchars($row['grupo'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($row['nombre_servicio'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($row['fecha_reserva'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($row['fecha_salida'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($row['fecha_retorno'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($row['incluye_ingreso'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($row['modalidad_retorno'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($row['servicio_adicional'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($row['Encargado'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($row['metodo_pago'] ?? '—') ?></td>
                                    <td><?= isset($row['precio_servicio']) ? number_format($row['precio_servicio'], 2) : '—' ?></td>
                                    <td><?= isset($row['pagado_a_cuenta']) ? number_format($row['pagado_a_cuenta'], 2) : '—' ?></td>
                                    <td><?= isset($row['saldo_pendiente']) ? number_format($row['saldo_pendiente'], 2) : '—' ?></td>
                                    <td><?= htmlspecialchars($row['observaciones'] ?? '') ?></td>
                                    <td class="text-center">
                                        <?php if (!empty($row['id_operaciones'])): ?>
                                            <a href="ver.php?id=<?= $row['id_operaciones'] ?>" class="btn btn-info btn-sm">👁 Ver</a>
                                            <a href="editar.php?id=<?= $row['id_operaciones'] ?>" class="btn btn-warning btn-sm">✏️ Editar</a>
                                            <a href="eliminar.php?id=<?= $row['id_operaciones'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar esta operación?')">🗑 Eliminar</a>
                                        <?php else: ?>
                                            <a href="agregar.php?id_cliente=<?= $row['id_cliente'] ?>" class="btn btn-success btn-sm">➕ Registrar</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile;
                        else: ?>
                            <tr><td colspan="17" class="text-center text-muted">No hay registros de endosadores</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
</div>

<!-- ====================== 🔹 MODAL IMPORTAR EXCEL ====================== -->
<div class="modal fade" id="modalImportar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
        <div class="modal-header bg-primary text-white">
            <h5 class="modal-title">📥 Importar archivo Excel</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="modal-body">
                <div class="mb-3">
                    <label for="archivoExcel" class="form-label">Selecciona el archivo (.xlsx o .xls)</label>
                    <input type="file" class="form-control" name="archivo_excel" id="archivoExcel" accept=".xlsx,.xls" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success">Importar</button>
            </div>
        </form>
    </div>
  </div>
</div>

<!-- ====================== 🔹 JS ====================== -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
<script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

<script>
$(document).ready(function() {
    
    let table = new DataTable('#tablaOperaciones', {
        dom: 'Bfrtip',
        buttons: [
            {
                extend: 'excelHtml5',
                text: '📊 Exportar a Excel',
                className: 'btn btn-success mb-3',
                title: 'Operaciones_KB',
                exportOptions: { columns: ':visible:not(:last-child)' }
            },
            {
                extend: 'pdfHtml5',
                text: '📄 Exportar a PDF',
                className: 'btn btn-danger mb-3',
                title: 'Operaciones_Endosador',
                exportOptions: { columns: ':visible:not(:last-child)' }
            }
        ],
        language: { url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/Spanish.json' },
        pageLength: 10,
        order: [[0, "desc"]]
    });
});
</script>

<?php include '../../footer.php'; ?>
</body>
</html>
