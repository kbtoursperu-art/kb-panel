<?php
include '../../../conexion.php';
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

g.id_grupo,
g.nombre_grupo AS grupo,
MAX(g.cantidad) AS cantidad_grupo,

MAX(d.id_cliente) AS id_cliente,
MAX(CONCAT(d.nombre,' ',d.apellido)) AS primer_nombre,

MAX(e.empresa_endosadora) AS empresa,

MAX(o.id_operaciones) AS id_operaciones,
MAX(o.nombre_servicio) AS nombre_servicio,
MAX(o.fecha_reserva) AS fecha_reserva,
MAX(o.fecha_salida) AS fecha_salida,
MAX(o.fecha_retorno) AS fecha_retorno,
MAX(o.modalidad_retorno) AS modalidad_retorno,
MAX(o.incluye_ingreso) AS incluye_ingreso,
MAX(o.servicio_adicional) AS servicio_adicional,
MAX(o.observaciones) AS observaciones,
MAX(o.Encargado) AS Encargado,

MAX(c.metodo_pago) AS metodo_pago,
MAX(c.tipo_moneda) AS tipo_moneda,
MAX(c.precio_servicio) AS precio_servicio,
MAX(c.pagado_a_cuenta) AS pagado_a_cuenta,
MAX(c.saldo_pendiente) AS saldo_pendiente,

MAX(c.metodo_pago_adicional) AS metodo_pago_adicional,
MAX(c.tipo_moneda_adicional) AS tipo_moneda_adicional,
MAX(c.precio_servicio_adicional) AS precio_servicio_adicional,
MAX(c.pagado_adicional) AS pagado_adicional,
MAX(c.saldo_adicional) AS saldo_adicional,

MAX(c.comision) AS comision,
MAX(c.detraccion) AS detraccion,

MAX(c.metodo_pago_saldo) AS metodo_pago_saldo,
MAX(c.tipo_moneda_saldo) AS tipo_moneda_saldo,
MAX(c.monto_pago_saldo) AS monto_pago_saldo,
MAX(c.fecha_pago_saldo) AS fecha_pago_saldo,

MAX(c.estado) AS estado,
MAX(c.modalidad_recibo) AS modalidad_recibo,
MAX(c.nro_boleta_cuenta) AS nro_boleta_cuenta,
MAX(c.nro_boleta_total) AS nro_boleta_total,
MAX(c.Nro_Comprobante_adicional) AS Nro_Comprobante_adicional

FROM grupos g

INNER JOIN clientes_endosadores e
ON g.id_grupo = e.id_grupo

INNER JOIN Datos_clientes d
ON e.id_cliente = d.id_cliente

LEFT JOIN operaciones o
ON d.id_cliente = o.id_cliente

LEFT JOIN contabilidad c
ON o.id_operaciones = c.id_operaciones

WHERE d.tipo_cliente = 'END'
AND g.nombre_grupo LIKE 'C-END-%'

GROUP BY g.id_grupo

ORDER BY g.nombre_grupo ASC

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
    <?php include '../../sidebar.php'; ?>
<div class="content p-4">
    <div class="container-fluid">

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="titulo-seccion">📋 Operaciones - Endosadores</h2>
            <div>
                <a href="../clientes_endosador/endosadores.php" class="btn btn-success me-2">➕ Nuevo Endosador</a>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalImportar">📥 Importar Excel</button>
            </div>
        </div>

        <!-- ====================== 🔹 TABLA PRINCIPAL ====================== -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tablaOperaciones" class="table table-striped table-bordered align-middle w-100">
                        <thead>

<tr>
<th rowspan="2">id</th>
<th rowspan="2">Cliente</th>
<th rowspan="2">Cantidad</th>
<th rowspan="2">Empresa</th>
<th rowspan="2">Grupo</th>

<th colspan="9" style="background:#d9edf7">OPERACIONES</th>

<th colspan="6" style="background:#dff0d8">SERVICIO GENERAL</th>

<th colspan="5" style="background:#fcf8e3">SERVICIO ADICIONAL</th>

<th colspan="4" style="background:#f2dede">SALDO PAGADO </th>
<th colspan="6" style="background:goldenrod">CONTABILIDAD COMPROBANTE</th>

<th rowspan="1">Acciones</th>

</tr>


<tr>

<!-- OPERACIONES -->

<th>Servicio</th>
<th>Reserva</th>
<th>Salida</th>
<th>Retorno</th>
<th>Modalidad</th>
<th>Ingreso</th>
<th>Adicional</th>
<th>Encargado</th>
<th>observaciones</th>
<!-- SERVICIO -->

<th>Método</th>
<th>Moneda</th>
<th>Precio</th>
<th>Pagado</th>
<th>Saldo</th>
<th>Comisión</th>
<!-- ADICIONAL -->

<th>Método</th>
<th>Moneda</th>
<th>Precio</th>
<th>Pagado</th>
<th>Saldo</th>

<!-- SALDO PAGADO POR METODO -->


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
<?php if (mysqli_num_rows($resultado) > 0): ?>
<?php $i = 1; ?>
<?php while ($row = mysqli_fetch_assoc($resultado)): ?>
<tr>

<td><?= $i++ ?></td>

<td><?= $row['primer_nombre'] ?></td>
<td><?= $row['cantidad_grupo'] ?></td>
<td><?= $row['empresa'] ?></td>

<td>
<a href="#" class="ver-clientes"
data-id="<?= $row['id_grupo'] ?>">
<?= $row['grupo'] ?>
</a>
</td>

<td><?= $row['nombre_servicio'] ?></td>
<td><?= $row['fecha_reserva'] ?></td>
<td><?= $row['fecha_salida'] ?></td>
<td><?= $row['fecha_retorno'] ?></td>
<td><?= $row['modalidad_retorno'] ?></td>
<td><?= $row['incluye_ingreso'] ?></td>

<td><?= $row['servicio_adicional'] ?></td>

<td><?= $row['Encargado'] ?></td>
<td><?= $row['observaciones'] ?></td>

<td><?= $row['metodo_pago'] ?></td>
<td><?= $row['tipo_moneda'] ?></td>

<td><?= number_format($row['precio_servicio'],2) ?></td>
<td><?= number_format($row['pagado_a_cuenta'],2) ?></td>
<td><?= number_format($row['saldo_pendiente'],2) ?></td>
<td><?= $row['comision'] ?></td>

<td><?= $row['metodo_pago_adicional'] ?></td>
<td><?= $row['tipo_moneda_adicional'] ?></td>

<td><?= number_format($row['precio_servicio_adicional'],2) ?></td>
<td><?= number_format($row['pagado_adicional'],2) ?></td>
<td><?= number_format($row['saldo_adicional'],2) ?></td>


<td><?= $row['metodo_pago_saldo'] ?></td>
<td><?= $row['tipo_moneda_saldo'] ?></td>
<td><?= number_format($row['monto_pago_saldo'],2) ?></td>
<td><?= $row['fecha_pago_saldo'] ?></td>

<td>
<?php
$estado = $row['estado'] ?? 'pendiente';

$clase = match ($estado) {
'pagado' => 'bg-success',
'reembolsado' => 'bg-danger',
'pendiente' => 'bg-warning',
default => 'bg-secondary'
};
?>
<span class="badge <?= $clase ?>">
<?= strtoupper($estado) ?>
</span>
</td>

<td><?= $row['modalidad_recibo'] ?></td>
<td><?= $row['nro_boleta_cuenta'] ?></td>
<td><?= $row['nro_boleta_total'] ?></td>
<td><?= $row['Nro_Comprobante_adicional'] ?></td>
<td><?= $row['detraccion'] ?></td>

<td>

<?php if (!empty($row['id_operaciones'])): ?>

<a href="ver.php?id=<?= $row['id_operaciones'] ?>" class="btn btn-info btn-sm">👁</a>

<a href="editar.php?id=<?= $row['id_operaciones'] ?>" class="btn btn-warning btn-sm">✏️</a>

<a href="eliminar.php?id=<?= $row['id_operaciones'] ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('Eliminar?')">🗑</a>

<?php else: ?>

<a href="agregar.php?id_cliente=<?= $row['id_cliente'] ?>"
class="btn btn-success btn-sm">➕</a>

<?php endif; ?>

</td>

</tr>
<?php endwhile; ?>

<?php else: ?>

<tr>
<td colspan="40" class="text-center">
No hay registros
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
<div class="modal fade" id="modalGrupo" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title" id="tituloGrupo">Clientes del Grupo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <table class="table table-bordered table-striped" id="tablaGrupo">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nombre</th>
              <th>Apellido</th>
              <th>Pasaporte</th>
              <th>Empresa</th>
              <th>Tour</th>
            <th>Salida</th>
                <th>Retorno</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
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
$(document).on('click', '.ver-clientes', function(e){
    e.preventDefault();

    let id_grupo = parseInt($(this).data('id'));
    let nombre   = $(this).data('nombre');

    if(isNaN(id_grupo) || id_grupo <= 0){
        alert("❌ ID de grupo inválido");
        return;
    }

    $('#tituloGrupo').text('Clientes del Grupo: ' + nombre);

    $.ajax({
        url: 'ajax_grupo.php', // esto está bien solo si realmente existe en esa carpeta
        method: 'GET',
        data: {id_grupo: id_grupo},
        dataType: 'json',
        success: function(clientes){
            let tbody = $('#tablaGrupo tbody');
            tbody.empty();

            if(clientes.length === 0){
                tbody.append('<tr><td colspan="8" class="text-center text-muted">No hay clientes en este grupo</td></tr>');
            } else {
                clientes.forEach(c => {
                   tbody.append(`
<tr>
    <td>${c.id_cliente}</td>
    <td>${c.nombre}</td>
    <td>${c.apellido}</td>
    <td>${c.nro_pasaporte ?? '—'}</td>
    <td>${c.empresa_endosadora ?? '—'}</td>

    <td class="fw-bold text-success">
        ${c.nombre_servicio ?? '—'}
    </td>

    <td>
        ${c.fecha_salida ?? '—'}
    </td>

    <td>
        ${c.fecha_retorno ?? '—'}
    </td>
</tr>
`);
                });
            }

            // Destruir DataTable previo si existe
            if($.fn.DataTable.isDataTable('#tablaGrupo')){
                $('#tablaGrupo').DataTable().destroy();
            }

            // Inicializar DataTable
            $('#tablaGrupo').DataTable({
                language:{ url:'https://cdn.datatables.net/plug-ins/1.11.5/i18n/Spanish.json' },
                paging: true,
                searching: false,
                info: false,
                responsive: true
            });

            // Abrir modal
            new bootstrap.Modal(document.getElementById('modalGrupo')).show();
        },
        error: function(xhr, status, error){
            alert('❌ Error al cargar los clientes: ' + error);
            console.error(xhr.responseText);
        }
    });
});

</script>
<script>
$(document).ready(function() {
    $('#tablaOperaciones').DataTable({
    responsive: false,
    scrollX: false,
    autoWidth: false,
    language: { url:'https://cdn.datatables.net/plug-ins/1.11.5/i18n/Spanish.json' },
    pageLength: 25,
    order: [[4, 'asc']]
   });
});
</script>
</body>
</html>
