<?php
include '../../../conexion.php';

// ====================== 🔹 FILTRO DE FECHAS ======================
$search_from = $_GET['search_date_from'] ?? '';
$search_to = $_GET['search_date_to'] ?? '';

// ====================== 🔹 CONSULTA PRINCIPAL ======================
$query = "
SELECT 
    d.id_cliente,
    CONCAT(d.nombre, ' ', d.apellido) AS cliente,
    d.nro_pasaporte,
    kb.grupo,
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
    c.modalidad_recibo,      -- 👈 NUEVO
    c.estado,                 -- ✅ AQUI
    c.precio_servicio,
    c.precio_servicio_adicional,
    c.tipo_moneda_adicional,
    c.pagado_a_cuenta,
    c.saldo_pendiente,
    c.comision
FROM Datos_clientes d
INNER JOIN Clientes_KB kb ON d.id_cliente = kb.id_cliente
LEFT JOIN Operaciones o ON d.id_cliente = o.id_cliente
LEFT JOIN Contabilidad c ON o.id_operaciones = c.id_operaciones
WHERE d.tipo_cliente = 'KB'
";

if (!empty($search_from) && !empty($search_to)) {
    $query .= " AND o.fecha_salida BETWEEN '$search_from' AND '$search_to'";
}

$query .= " ORDER BY o.id_operaciones DESC";

$resultado = mysqli_query($conexion, $query);
if (!$resultado) {
    die("Error en la consulta: " . mysqli_error($conexion));
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Operaciones KB</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- 🔹 ESTILOS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
<link rel="stylesheet" href="stilo.css">
</head>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const toggleBtn = document.getElementById("toggle-sidebar");
    const body = document.body;

    if (toggleBtn) {
        toggleBtn.addEventListener("click", function () {
            body.classList.toggle("sidebar-collapsed");
        });
    }
});
</script>

<body class="layout-kb">
    <?php include '../../sidebar.php'; ?>
<div class="content">
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="text-primary mb-0">📋 Operaciones de KB</h3>
        <div>
            <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#modalImportar">📥 Importar Excel</button>
        </div>
    </div>

    <!-- 🔹 Filtro por fechas -->
    <div class="row mb-3">
        <div class="col-md-3">
            <label>Desde:</label>
            <input type="date" id="search_date_from" class="form-control" value="<?= htmlspecialchars($search_from) ?>">
        </div>
        <div class="col-md-3">
            <label>Hasta:</label>
            <input type="date" id="search_date_to" class="form-control" value="<?= htmlspecialchars($search_to) ?>">
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tablaOperaciones" class="table table-striped table-bordered nowrap" style="width:100%">
                    <thead class="table-dark text-center">
                        <tr>
                            <th>#</th>
                            <th>Cliente</th>
                            <th>Pasaporte</th>
                            <th>Grupo</th>
                            <th>Servicio</th>
                            <th>Fecha Reserva</th>
                            <th>Salida</th>
                            <th>Retorno</th>
                            <th>Ingreso</th>
                            <th>Modalidad</th>
                            <th>Adicional</th>
                            <th>Precio</th>
                            <th>Moneda</th>
                            <th>Encargado</th>
                            <th>Método Pago</th>
                            <th>Tipo Moneda</th>
                            <th>Comprobante</th> <!-- 👈 NUEVO -->
                            <th>Estado</th>
                            <th>Precio</th>
                            <th>Pagado</th>
                            <th>Saldo</th>
                            <th>Comisión</th>
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
                                <td><?= htmlspecialchars($row['nro_pasaporte'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($row['grupo'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($row['nombre_servicio'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($row['fecha_reserva'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($row['fecha_salida'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($row['fecha_retorno'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($row['incluye_ingreso'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($row['modalidad_retorno'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($row['servicio_adicional'] ?? '—') ?></td>
                                <td>
                                    <?= isset($row['precio_servicio_adicional']) 
                                        ? number_format($row['precio_servicio_adicional'], 2) 
                                        : '—' ?>
                                </td>

                                <td><?= htmlspecialchars($row['tipo_moneda_adicional'] ?? '—') ?></td>

                                <td><?= htmlspecialchars($row['Encargado'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($row['metodo_pago'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($row['tipo_moneda'] ?? '—') ?></td>
                                <td><?= htmlspecialchars($row['modalidad_recibo'] ?? '—') ?></td> <!-- ✅ -->
                                <td>
                                    <?php
                                    $estado = $row['estado'] ?? 'pendiente';

                                    $clase = match ($estado) {
                                        'pagado'      => 'bg-success',   // 🟢 VERDE
                                        'reembolsado' => 'bg-danger',    // 🔴 ROJO (cancelado)
                                        'pendiente'   => 'bg-warning',   // 🟠 NARANJA
                                        default       => 'bg-secondary'
                                    };
                                    ?>

                                    <span class="badge <?= $clase ?>">
                                        <?= strtoupper($estado) ?>
                                    </span>
                                </td>
                                <td><?= isset($row['precio_servicio']) ? number_format($row['precio_servicio'], 2) : '—' ?></td>
                                <td><?= isset($row['pagado_a_cuenta']) ? number_format($row['pagado_a_cuenta'], 2) : '—' ?></td>
                                <td><?= isset($row['saldo_pendiente']) ? number_format($row['saldo_pendiente'], 2) : '—' ?></td>
                                <td><?= isset($row['comision']) ? number_format($row['comision'], 2) : '—' ?></td>
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
                        <tr><td colspan="21" class="text-center text-muted">No hay clientes KB registrados</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
</div>

<!-- 🔹 Modal Importar Excel -->
<div class="modal fade" id="modalImportar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="importar_excel.php" method="POST" enctype="multipart/form-data">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title">📥 Importar Operaciones desde Excel</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <label for="archivo_excel" class="form-label">Selecciona el archivo Excel (.xlsx o .csv)</label>
          <input type="file" name="archivo_excel" id="archivo_excel" class="form-control" accept=".xlsx, .xls, .csv" required>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Importar</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>

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
                title: 'Operaciones_KB',
                exportOptions: { columns: ':visible:not(:last-child)' }
            }
        ],
        language: { url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/Spanish.json' },
        pageLength: 10,
        order: [[0, "desc"]]
    });

    $("#search_date_from, #search_date_to").on("change", function() {
        var from = $("#search_date_from").val();
        var to = $("#search_date_to").val();
        location.href = "?search_date_from=" + from + "&search_date_to=" + to;
    });
});
document.addEventListener("DOMContentLoaded", function () {
    const toggleBtn = document.getElementById("toggle-sidebar");
    const body = document.body;

    if (!toggleBtn) return;

    toggleBtn.addEventListener("click", function () {
        // Desktop: mover sidebar
        body.classList.toggle("sidebar-collapsed");

        // Móviles: abrir/cerrar menú
        body.classList.toggle("sidebar-open");
    });
});
</script>

<?php include '../../footer.php'; ?>
</body>
</html>
