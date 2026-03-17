<?php
include '../../../conexion.php';

// ====================== 🔹 FILTRO DE FECHAS ======================
$search_from = $_GET['search_date_from'] ?? '';
$search_to = $_GET['search_date_to'] ?? '';

// ====================== 🔹 CONSULTA PRINCIPAL ======================
$query = "
SELECT 
    g.id_grupo,
    g.nombre_grupo,

   (
    SELECT COUNT(*)
    FROM clientes_kb kk
    WHERE kk.id_grupo = g.id_grupo
) AS pasajeros,

(
    SELECT kk.id_cliente
    FROM clientes_kb kk
    WHERE kk.id_grupo = g.id_grupo
    LIMIT 1
) AS primer_cliente_id,

(
    SELECT CONCAT(dd.nombre,' ',dd.apellido)
    FROM clientes_kb kk
    JOIN datos_clientes dd ON dd.id_cliente = kk.id_cliente
    WHERE kk.id_grupo = g.id_grupo
    LIMIT 1
) AS primer_cliente,

    MAX(o.id_operaciones) AS id_operaciones,
    MAX(o.nombre_servicio) AS nombre_servicio,
    MAX(o.servicio_adicional) AS servicio_adicional,
    MAX(o.empresa) AS empresa,
    MAX(o.fecha_reserva) AS fecha_reserva,
    MAX(o.fecha_salida) AS fecha_salida,
    MAX(o.fecha_retorno) AS fecha_retorno,
    MAX(o.incluye_ingreso) AS incluye_ingreso,
    MAX(o.modalidad_retorno) AS modalidad_retorno,
    MAX(o.observaciones) AS observaciones,
    MAX(o.Encargado) AS Encargado,

    MAX(c.metodo_pago) AS metodo_pago,
    MAX(c.tipo_moneda) AS tipo_moneda,
    MAX(c.modalidad_recibo) AS modalidad_recibo,
    MAX(c.estado) AS estado,

    MAX(c.precio_servicio) AS precio_servicio,
    MAX(c.pagado_a_cuenta) AS pagado_a_cuenta,
    MAX(c.saldo_pendiente) AS saldo_pendiente,
    MAX(c.comision) AS comision,

    MAX(c.precio_servicio_adicional) AS precio_servicio_adicional,
    MAX(c.pagado_adicional) AS pagado_adicional,
    MAX(c.saldo_adicional) AS saldo_adicional,

    MAX(c.metodo_pago_adicional) AS metodo_pago_adicional,
    MAX(c.tipo_moneda_adicional) AS tipo_moneda_adicional,

    MAX(c.metodo_pago_saldo) AS metodo_pago_saldo,
    MAX(c.tipo_moneda_saldo) AS tipo_moneda_saldo,
    MAX(c.monto_pago_saldo) AS monto_pago_saldo,
    MAX(c.fecha_pago_saldo) AS fecha_pago_saldo,
MAX(c.nro_boleta_cuenta) AS nro_boleta_cuenta,
MAX(c.nro_boleta_total) AS nro_boleta_total,
MAX(c.Nro_Comprobante_adicional) AS Nro_Comprobante_adicional,
MAX(c.detraccion) AS detraccion


FROM grupos g

JOIN clientes_kb k ON k.id_grupo = g.id_grupo
JOIN datos_clientes d ON d.id_cliente = k.id_cliente
LEFT JOIN operaciones o ON o.id_grupo = g.id_grupo
LEFT JOIN contabilidad c ON c.id_operaciones = o.id_operaciones

WHERE 1=1
";

if (!empty($search_from) && !empty($search_to)) {
    $query .= " AND o.fecha_salida BETWEEN '$search_from' AND '$search_to'";
}

$query .= "
GROUP BY g.id_grupo, o.id_operaciones
ORDER BY g.id_grupo ASC
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
<div class="content p-4">
<div class="container-fluid">

    <!-- ====================== Encabezado ====================== -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="text-primary mb-0">📋 Operaciones de KB</h3>
        <div>
            <button class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#modalImportar">📥 Importar Excel</button>
        </div>
    </div>

    <!-- ====================== Filtro por fechas ====================== -->
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

    <!-- ====================== Tabla Principal ====================== -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tablaOperaciones" class="table table-striped table-bordered nowrap align-middle" style="width:100%">
                    <thead>

<tr class="text-center">

<th rowspan="2">#</th>
<th rowspan="2">Grupo</th>
<th rowspan="2">Pasajeros</th>
<th rowspan="2">Cliente</th>

<th colspan="8" style="background:#d9edf7">OPERACIONES</th>

<th colspan="6" style="background:#dff0d8">SERVICIO PRINCIPAL</th>

<th colspan="6" style="background:#fcf8e3">SERVICIO ADICIONAL</th>

<th colspan="4" style="background:#f2dede">SALDO PAGADO</th>

<th colspan="6" style="background:goldenrod">CONTABILIDAD</th>
<th rowspan="2">Acciones</th>

</tr>


<tr class="table-dark text-center">
<th >Reserva</th>
<th>Servicio</th>
<th>Salida</th>
<th>Retorno</th>
<th>Ingreso</th>
<th>Mod.Retorno</th>
<th>Encargado</th>
<th>observaciones</th>

<th>Método</th>
<th>Moneda</th>
<th>Prec_total</th>
<th>Pagado</th>
<th>Saldo</th>
<th>Comisión</th>

<th>Servicio.Adicional</th>
<th>Monto</th>
<th>Pagado</th>
<th>Saldo</th>
<th>Metodo.Pago</th>
<th>Moneda</th>


<th>Método saldo</th>
<th>Moneda saldo</th>
<th>Monto saldo</th>
<th>Fecha saldo</th>

<th>Estado</th>
<th>Comprobante</th>
<th>Boleta Cuenta</th>
<th>Boleta Total</th>
<th>Comp. Adicional</th>
<th>Detracción</th>

</tr>

</thead>

                    <tbody>
<?php
$i = 1;
while ($row = mysqli_fetch_assoc($resultado)):
    $estado = $row['estado'] ?? 'pendiente';
    $badge = match ($estado) {
        'pagado'      => 'bg-success',
        'reembolsado' => 'bg-danger',
        'pendiente'   => 'bg-warning',
        default       => 'bg-secondary'
    };
?>
<tr>
    <td class="text-center"><?= $i++ ?></td>

    <!-- Grupo con modal -->
    <td class="fw-bold text-center">
        <a href="#" class="link-primary ver-clientes" 
           data-id="<?= $row['id_grupo'] ?>" 
           data-bs-toggle="modal" data-bs-target="#modalClientes">
           <?= htmlspecialchars($row['nombre_grupo']) ?> 
           <span class="text-success">(<?= (int)$row['pasajeros'] ?>)</span>
        </a>
    </td>

    <td class="text-center"><?= (int)$row['pasajeros'] ?></td>
    <td><?= htmlspecialchars($row['primer_cliente'] ?? '—') ?></td>
    <td><?= htmlspecialchars($row['fecha_reserva'] ?? '—') ?></td>
    <td><?= htmlspecialchars($row['nombre_servicio'] ?? '—') ?></td>
    <td><?= htmlspecialchars($row['fecha_salida'] ?? '—') ?></td>
    <td><?= htmlspecialchars($row['fecha_retorno'] ?? '—') ?></td>
    <td><?= htmlspecialchars($row['incluye_ingreso'] ?? '—') ?></td>
    <td><?= htmlspecialchars($row['modalidad_retorno'] ?? '—') ?></td>
    <td><?= htmlspecialchars($row['Encargado'] ?? '—') ?></td>
    <td><?= htmlspecialchars($row['observaciones'] ?? '—') ?></td>
    <td><?= htmlspecialchars($row['metodo_pago'] ?? '—') ?></td>
    <td><?= htmlspecialchars($row['tipo_moneda'] ?? '—') ?></td>
    <td class="text-end"><?= number_format($row['precio_servicio'] ?? 0, 2) ?></td>
    <td class="text-end"><?= number_format($row['pagado_a_cuenta'] ?? 0, 2) ?></td>
    <td class="text-end"><?= number_format($row['saldo_pendiente'] ?? 0, 2) ?></td>
    <td class="text-end"><?= number_format($row['comision'] ?? 0, 2) ?></td>

    <td><?= htmlspecialchars($row['servicio_adicional'] ?? '—') ?></td>
    <td class="text-end"><?= number_format($row['precio_servicio_adicional'] ?? 0, 2) ?></td>
    <td class="text-end"><?= number_format($row['pagado_adicional'] ?? 0, 2) ?></td>
    <td class="text-end"><?= number_format($row['saldo_adicional'] ?? 0, 2) ?></td>
     <td><?= htmlspecialchars($row['metodo_pago_adicional'] ?? '—') ?></td>
     <td><?= htmlspecialchars($row['tipo_moneda_adicional'] ?? '—') ?></td>
    <td><?= $row['metodo_pago_saldo'] ?></td>
    <td><?= $row['tipo_moneda_saldo'] ?></td>
    <td><?= number_format($row['monto_pago_saldo'],2) ?></td>
    <td><?= $row['fecha_pago_saldo'] ?></td>
     <!-- Estado con badge -->
    <td class="text-center">
        <span class="badge <?= $badge ?>"><?= strtoupper($estado) ?></span>
    </td>
      <td><?= $row['modalidad_recibo'] ?></td>
<td><?= $row['nro_boleta_cuenta'] ?></td>
<td><?= $row['nro_boleta_total'] ?></td>
<td><?= $row['Nro_Comprobante_adicional'] ?></td>
<td><?= $row['detraccion'] ?></td>
    <!-- Acciones -->
    <td class="text-center">
        <?php if (!empty($row['id_operaciones'])): ?>
            <a href="ver.php?id_grupo=<?= $row['id_grupo'] ?>" class="btn btn-info btn-sm">👁</a>
            <a href="editar.php?id=<?= $row['id_operaciones'] ?>" class="btn btn-warning btn-sm">✏️ Editar</a>
            <a href="eliminar.php?id=<?= $row['id_operaciones'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar esta operación?')">🗑 Eliminar</a>
        <?php else: ?>
            <a href="agregar.php?id_cliente=<?= $row['primer_cliente_id'] ?>" class="btn btn-success btn-sm">➕ Agregar</a>
        <?php endif; ?>
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

<!-- ====================== DataTables ====================== -->
<script>
$(document).ready(function() {
    $('#tablaOperaciones').DataTable({
        responsive: true,
        language: { url:'https://cdn.datatables.net/plug-ins/1.11.5/i18n/Spanish.json' },
        pageLength: 25,
        order: [[1, 'asc']], // Ordenar por Grupo
        columnDefs: [
            { className: "text-center", targets: [0,2,13,14,15,16,17,21,22] },
            { className: "text-end", targets: [14,15,16,17,21,22] }
        ]
    });
});
</script>


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
<!-- 🔹 Modal Clientes -->
<div class="modal fade" id="modalClientes" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">👥 Clientes del Grupo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <table class="table table-striped" id="tablaClientesGrupo">
          <thead>
            <tr>
              <th>#</th>
                <th>Nombre</th>
                <th>Tipo</th>
                <th>Tour</th>
                <th>Salida</th>
                <th>Retorno</th>
            </tr>
          </thead>
          <tbody>
            <!-- Aquí se cargará dinámicamente vía AJAX -->
          </tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>
<script>
$(document).ready(function() {
    $('.ver-clientes').on('click', function() {
        var id_grupo = $(this).data('id');

        $.ajax({
            url: 'obtener_clientes.php',
            data: { id_grupo: id_grupo },
            dataType: 'json',
            success: function(data) {
                var tbody = '';
                data.forEach(function(cliente, index) {
                    tbody += '<tr>';
                    tbody += '<td>' + (index+1) + '</td>';
                    tbody += '<td>' + cliente.nombre + ' ' + cliente.apellido + '</td>';
                    tbody += '<td>' + cliente.tipo + '</td>';
                    tbody += '<td>' + (cliente.nombre_servicio ?? '-') + '</td>';
                    tbody += '<td>' + (cliente.fecha_salida ?? '-') + '</td>';
                    tbody += '<td>' + (cliente.fecha_retorno ?? '-') + '</td>';
                                        tbody += '</tr>';
                });
                $('#tablaClientesGrupo tbody').html(tbody);
            }
        });
    });
});
</script>

</body>
</html>
