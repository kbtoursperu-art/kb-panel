<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include '../../conexion.php';
mysqli_set_charset($conexion, 'utf8mb4');

// ====================== FILTRO DE FECHAS ======================
$search_from = $_GET['search_date_from'] ?? '';
$search_to = $_GET['search_date_to'] ?? '';

// ====================== CONSULTA PRINCIPAL ======================
$query = "
SELECT 
    g.id_grupo,
    g.nombre_grupo,

    -- PRIMER CLIENTE (KB + ENDOSADOR)
    (
        SELECT CONCAT(d.nombre,' ',d.apellido)
        FROM (
            SELECT id_cliente, id_grupo FROM clientes_kb
            UNION ALL
            SELECT id_cliente, id_grupo FROM clientes_endosadores
        ) t
        LEFT JOIN datos_clientes d 
            ON d.id_cliente = t.id_cliente
        WHERE t.id_grupo = g.id_grupo
        LIMIT 1
    ) AS primer_cliente,

    -- PASAJEROS
    (
        SELECT COUNT(*)
        FROM (
            SELECT id_cliente, id_grupo FROM clientes_kb
            UNION ALL
            SELECT id_cliente, id_grupo FROM clientes_endosadores
        ) t
        WHERE t.id_grupo = g.id_grupo
    ) AS pasajeros,


    -- Operaciones
    MAX(o.id_operaciones) AS id_operaciones,
    GROUP_CONCAT(DISTINCT od.nombre_servicio SEPARATOR '<br>') AS nombre_servicio,
    GROUP_CONCAT(DISTINCT od.servicio_adicional SEPARATOR '<br>') AS servicio_adicional,
    MAX(o.fecha_salida) AS fecha_salida,
    MAX(o.observaciones) AS observaciones,
    MAX(o.Encargado) AS Encargado,

    -- Contabilidad
    MAX(c.id_contabilidad) AS id_contabilidad,
    MAX(c.precio_servicio) AS precio_servicio,
    MAX(c.pagado_a_cuenta) AS pagado_a_cuenta,
    MAX(c.saldo_pendiente) AS saldo_pendiente,
    MAX(c.estado) AS estado,
    MAX(c.nro_boleta_total) AS nro_boleta_total

FROM grupos g

LEFT JOIN clientes_kb k ON k.id_grupo = g.id_grupo
LEFT JOIN datos_clientes d ON d.id_cliente = k.id_cliente
LEFT JOIN operaciones o ON o.id_grupo = g.id_grupo
LEFT JOIN operaciones_detalle od ON od.id_operaciones = o.id_operaciones
LEFT JOIN contabilidad c ON c.id_operaciones = o.id_operaciones

WHERE 1=1
";

if (!empty($search_from) && !empty($search_to)) {
    $query .= " AND o.fecha_salida BETWEEN '$search_from' AND '$search_to'";
}

$query .= "
GROUP BY g.id_grupo
ORDER BY g.id_grupo DESC
";

$resultado = mysqli_query($conexion, $query);
if (!$resultado) die("Error en la consulta: " . mysqli_error($conexion));
$datos = mysqli_fetch_all($resultado, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Contabilidad KB</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Bootstrap & DataTables -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
<style>
/* Observaciones con scroll */
.observaciones-box {
    max-height: 60px;
    overflow: auto;
    white-space: normal;
}
</style>
</head>
<body>
<?php include './../sidebar.php'; ?>

<div class="content p-4">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="text-primary mb-0">📊 Contabilidad KB</h3>
            <div>
                <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#modalImportar">📥 Importar Excel</button>
            </div>
        </div>

        <!-- Filtro fechas -->
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

        <!-- Tabla principal -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tablaContabilidad" class="table table-striped table-bordered nowrap align-middle" style="width:100%">
                        <thead class="table-dark text-center">
                            <tr>
                                <th>#</th>
                                <th>Cliente</th>
                                <th>Grupo</th>
                                <th>Servicio</th>
                                <th>Salida</th>
                                <th>Observaciones</th>
                                <th>Total</th>
                                <th>Pagado</th>
                                <th>Saldo</th>
                                <th>Estado</th>
                                <th>Comprobante</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
<?php
$i = 1;
foreach ($datos as $row):
    $estado = $row['estado'] ?? 'pendiente';
    $badge = match($estado) {
        'pagado' => 'bg-success',
        'pendiente' => 'bg-warning text-dark',
        'reembolsado' => 'bg-danger',
        default => 'bg-secondary'
    };
?>
<tr>
    <td><?= $i++ ?></td>
    <td><?= htmlspecialchars($row['primer_cliente'] ?? '-') ?></td>
    <td><?= htmlspecialchars($row['nombre_grupo'] ?? '-') ?></td>
    <td>
<?php
$colores = ["primary","success","danger","warning","info","secondary","dark"];
$servicios = explode("<br>", $row['nombre_servicio'] ?? '');
$fechas = explode("<br>", $row['fecha_salida'] ?? '');
for ($x=0; $x<count($servicios); $x++) {
    $color = $colores[$x % count($colores)];
    $serv = $servicios[$x] ?? '';
    $fecha = $fechas[$x] ?? '';
    echo '<div class="mb-1"><span class="badge bg-'.$color.'">'.$serv.' '.$fecha.'</span></div>';
}
?>
    </td>
    <td><?= $row['fecha_salida'] ?? '-' ?></td>
    <td><div class="observaciones-box"><?= htmlspecialchars($row['observaciones'] ?? '-') ?></div></td>
    <td class="text-end"><?= number_format($row['precio_servicio'] ?? 0, 2) ?></td>
    <td class="text-end"><?= number_format($row['pagado_a_cuenta'] ?? 0, 2) ?></td>
    <td class="text-end"><?= number_format($row['saldo_pendiente'] ?? 0, 2) ?></td>
    <td class="text-center"><span class="badge <?= $badge ?>"><?= strtoupper($estado) ?></span></td>
    <td><?= htmlspecialchars($row['nro_boleta_total'] ?? '-') ?></td>
    <td class="text-center">
        <?php if (!empty($row['id_operaciones'])): ?>
            <a href="ver.php?id=<?= $row['id_contabilidad'] ?>" class="btn btn-info btn-sm">👁</a>
            <a href="editar.php?id=<?= $row['id_contabilidad'] ?>" class="btn btn-warning btn-sm">✏</a>
        <?php else: ?>
            <a href="../Area_Operaciones/ope-KB/agregar.php?id_operaciones=<?= $row['id_operaciones'] ?>" class="btn btn-success btn-sm">➕</a>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal Importar Excel -->
<div class="modal fade" id="modalImportar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="importar_excel.php" method="POST" enctype="multipart/form-data">
        <div class="modal-header bg-success text-white">
          <h5 class="modal-title">📥 Importar Operaciones desde Excel</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="file" name="archivo_excel" class="form-control" accept=".xlsx,.xls,.csv" required>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Importar</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- JS -->
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
    $('#tablaContabilidad').DataTable({
        responsive: true,
        dom: 'Bfrtip',
        buttons: [
            { extend: 'excelHtml5', text: '📊 Exportar a Excel', className: 'btn btn-success mb-3', exportOptions: { columns: ':visible:not(:last-child)' } },
            { extend: 'pdfHtml5', text: '📄 Exportar a PDF', className: 'btn btn-danger mb-3', exportOptions: { columns: ':visible:not(:last-child)' } }
        ],
        language: { url:'https://cdn.datatables.net/plug-ins/1.11.5/i18n/Spanish.json' },
        pageLength: 10,
        order: [[0,"asc"]]
    });

    $("#search_date_from, #search_date_to").on("change", function() {
        var from = $("#search_date_from").val();
        var to = $("#search_date_to").val();
        location.href = "?search_date_from=" + from + "&search_date_to=" + to;
    });
});
</script>
</body>
</html>