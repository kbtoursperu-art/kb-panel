<?php
// pages/almace/asignaciones.php
include("../../conexion.php");
if (isset($_GET['get_tour']) && isset($_GET['id_cliente'])) {
    $id_cliente = intval($_GET['id_cliente']);

    $sql = "
        SELECT 
            o.id_operaciones AS id_servicio,
            o.nombre_servicio
        FROM Operaciones o
        WHERE o.id_cliente = $id_cliente
        ORDER BY o.fecha_reserva DESC
        LIMIT 1
    ";
    $res = mysqli_query($conexion, $sql);

    $data = mysqli_fetch_assoc($res) ?: [];
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

/*
 * Endpoint interno para devolver stock por item en JSON
 * Llamado por AJAX: asignaciones.php?get_stock=1&id_item=XX
 */
if (isset($_GET['get_stock']) && isset($_GET['id_item'])) {
    $id_item = intval($_GET['id_item']);
    $res = mysqli_query($conexion, "
        SELECT st.id_stock, st.id_talla, COALESCE(t.talla,'') AS talla,
               st.cantidad_disponible, st.color, st.numero_serie
        FROM almacen_stock st
        LEFT JOIN almacen_tallas t ON st.id_talla = t.id_talla
        WHERE st.id_item = $id_item AND st.cantidad_disponible > 0
        ORDER BY st.id_stock
    ");
    $out = [];
    if ($res) {
        while ($r = mysqli_fetch_assoc($res)) $out[] = $r;
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($out);
    exit;
}


/* ========== Datos para la página ========== */

// asignaciones existentes
$asigs = mysqli_query($conexion, "
  SELECT p.*, 
         c.nombre AS cliente, 
         i.nombre AS articulo,
         COALESCE(t.talla,'') AS talla,
         st.color, 
         st.numero_serie, 
         sv.nombre_servicio
  FROM almacen_pasajeros p
  JOIN Datos_clientes c ON p.id_cliente = c.id_cliente
  JOIN almacen_stock st ON p.id_stock = st.id_stock
  JOIN almacen_items i ON st.id_item = i.id_item
  LEFT JOIN almacen_tallas t ON st.id_talla = t.id_talla
  LEFT JOIN Operaciones sv ON p.id_servicio = sv.id_operaciones
  ORDER BY p.fecha_salida DESC
");

// items (artículos base) — traemos flags para lógica UI
$items = mysqli_query($conexion, "
  SELECT id_item, nombre, categoria, tiene_talla, tiene_color, tiene_serie
  FROM almacen_items
  ORDER BY nombre
");

// clientes y servicios para el modal
$clientes = mysqli_query($conexion, "SELECT id_cliente, nombre FROM Datos_clientes ORDER BY nombre");
$servicios = mysqli_query($conexion, "SELECT id_operaciones AS id_servicio, nombre_servicio FROM Operaciones ORDER BY nombre_servicio");
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Asignaciones</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-4">
  <div class="d-flex justify-content-between mb-3">
    <h3>Asignaciones</h3>
    <div>
      <a href="stock_general.php" class="btn btn-outline-secondary">Volver stock</a>
      <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalAsignar">+ Asignar</button>
    </div>
  </div>

  <div class="card"><div class="card-body table-responsive">
    <table id="tablaAsig" class="table table-striped">
      <thead class="table-dark">
        <tr>
          <th>ID</th><th>Cliente</th><th>Tour</th><th>Artículo</th><th>Talla</th><th>Serie/Color</th><th>Cant</th><th>Tipo uso</th><th>Monto</th><th>Salida</th><th>Retorno</th><th>Estado</th><th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php while($r=mysqli_fetch_assoc($asigs)): ?>
        <tr>
          <td><?= $r['id_asignacion'] ?></td>
          <td><?= htmlspecialchars($r['cliente']) ?></td>
          <td><?= htmlspecialchars($r['nombre_servicio'] ?? '-') ?></td>
          <td><?= htmlspecialchars($r['articulo']) ?></td>
          <td><?= $r['talla'] ?: '-' ?></td>
          <td><?= ($r['numero_serie'] ?: '-') . ' ' . ($r['color'] ?: '') ?></td>
          <td><?= $r['cantidad'] ?></td>
          <td><?= $r['tipo_uso'] ?></td>
          <td><?= number_format($r['monto'],2) ?></td>
          <td><?= date('d/m/Y H:i', strtotime($r['fecha_salida'])) ?></td>
          <td><?= $r['fecha_retorno'] ? date('d/m/Y H:i', strtotime($r['fecha_retorno'])) : '-' ?></td>
          <td><?= $r['estado'] ?></td>
          <td>
            <?php if($r['estado']=='En uso'): ?>
              <button class="btn btn-sm btn-warning btnDevolver" data-id="<?= $r['id_asignacion'] ?>" data-cantidad="<?= $r['cantidad'] ?>">Devolver</button>
            <?php else: ?>
              <span class="text-muted">-</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div></div>
</div>

<!-- ========== MODAL ASIGNAR ========== -->
<div class="modal fade" id="modalAsignar" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <form id="formAsignar" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Asignar artículo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="row g-2">
          <!-- Cliente -->
          <div class="col-md-6">
            <label>Cliente</label>
            <select name="id_cliente" class="form-select" required>
              <option value="">Seleccionar</option>
              <?php while($c=mysqli_fetch_assoc($clientes)): ?>
              <option value="<?= $c['id_cliente'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>

          <!-- Tour/Servicio -->
          <div class="col-md-6">
            <label>Tour / Servicio</label>
            <select name="id_servicio" class="form-select">
              <option value="">Sin tour</option>
              <?php while($s=mysqli_fetch_assoc($servicios)): ?>
              <option value="<?= $s['id_servicio'] ?>"><?= htmlspecialchars($s['nombre_servicio']) ?></option>
              <?php endwhile; ?>
            </select>
          </div>

          <!-- Artículo (item base) -->
          <div class="col-md-6">
            <label>Artículo</label>
            <select name="id_item" id="id_item" class="form-select" required>
              <option value="">Seleccionar artículo</option>
              <?php
              // Rewind items result and render
              mysqli_data_seek($items, 0);
              while($it = mysqli_fetch_assoc($items)):
                // guardamos flags en data-*
              ?>
                <option value="<?= $it['id_item'] ?>"
                        data-categoria="<?= htmlspecialchars($it['categoria']) ?>"
                        data-tiene-talla="<?= intval($it['tiene_talla']) ?>"
                        data-tiene-color="<?= intval($it['tiene_color']) ?>"
                        data-tiene-serie="<?= intval($it['tiene_serie']) ?>">
                  <?= htmlspecialchars($it['nombre']) ?> (<?= htmlspecialchars($it['categoria']) ?>)
                </option>
              <?php endwhile; ?>
            </select>
          </div>

          <!-- STOCK VARIACIONES (se llena por AJAX al elegir item) -->
          <div class="col-md-6">
            <label>Variación de stock</label>
            <select name="id_stock" id="id_stock" class="form-select" required>
              <option value="">Seleccionar variación (talla / serie)...</option>
              <!-- Opciones agregadas por JS -->
            </select>
            <div class="form-text" id="stock_help">Seleccione el artículo primero para ver las unidades disponibles.</div>
          </div>

          <!-- Cantidad -->
          <div class="col-md-3">
            <label>Cantidad</label>
            <input type="number" name="cantidad" id="cantidad" class="form-control" min="1" value="1" required>
          </div>

          <!-- Tipo de uso -->
          <div class="col-md-3">
            <label>Tipo uso</label>
            <select name="tipo_uso" id="tipo_uso" class="form-select" required>
              <option value="Uso">Uso</option>
              <option value="Alquiler">Alquiler</option>
              <option value="Garantía">Garantía</option>
              <option value="Regalo">Regalo</option>
            </select>
          </div>

          <!-- Monto -->
          <div class="col-md-4">
            <label>Monto (S/)</label>
            <input type="number" step="0.01" name="monto" class="form-control" value="0">
          </div>

          <!-- Fechas -->
          <div class="col-md-6">
            <label>Fecha salida</label>
            <input type="datetime-local" name="fecha_salida" class="form-control">
            <div class="form-text">Fecha/hora de entrega al pasajero (si aplica).</div>
          </div>
          <div class="col-md-6">
            <label>Fecha retorno</label>
            <input type="datetime-local" name="fecha_retorno" class="form-control">
          </div>

          <!-- Campos que se muestran condicionalmente -->
          <div class="col-md-6 d-none" id="wrap_numserie">
            <label>N° Serie</label>
            <input type="text" name="numero_serie" id="numero_serie" class="form-control" placeholder="Número de serie (si aplica)">
          </div>

          <div class="col-md-6 d-none" id="wrap_color">
            <label>Color</label>
            <input type="text" name="color" id="color" class="form-control" placeholder="Color (si aplica)">
          </div>

          <div class="col-md-6 d-none" id="wrap_talla">
            <label>Talla</label>
            <input type="text" id="talla_display" class="form-control" readonly>
          </div>

          <!-- Observación -->
          <div class="col-12">
            <label>Observación</label>
            <input type="text" name="observacion" class="form-control">
          </div>

        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-success" id="btnAsignar">Asignar</button>
      </div>
    </form>
  </div>
</div>

<!-- ========== MODAL DEVOLVER ========== -->
<div class="modal fade" id="modalDevolver" tabindex="-1">
  <div class="modal-dialog">
    <form id="formDevolver" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Registrar devolución</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" name="id_asignacion" id="dev_id">
        <div class="mb-2"><label>Cantidad a devolver</label><input type="number" name="cantidad" id="dev_cant" class="form-control" min="1" required></div>
        <div class="mb-2"><label>Observación</label><input type="text" name="observacion" class="form-control"></div>
      </div>
      <div class="modal-footer"><button class="btn btn-warning">Registrar devolución</button></div>
    </form>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
$(function(){
  $('#tablaAsig').DataTable({ language:{ url:"//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" } });

  // Al cambiar artículo (item) -> pedimos las variaciones de stock (id_stock)
  $('#id_item').on('change', function(){
    const id_item = $(this).val();
    // reset campos visuales
    $('#id_stock').html('<option value="">Seleccionar variación (talla / serie)...</option>');
    $('#stock_help').text('Cargando unidades disponibles...');
    $('#wrap_numserie, #wrap_color, #wrap_talla').addClass('d-none');
    $('#numero_serie, #color').val('');
    $('#talla_display').val('');

    if(!id_item) {
      $('#stock_help').text('Seleccione el artículo primero para ver las unidades disponibles.');
      return;
    }
    
    // obtenemos info de flags del option seleccionado (tiene_talla, tiene_color, tiene_serie)
    const opt = $('#id_item option:selected');
    const tiene_talla = opt.data('tiene-talla') == 1;
    const tiene_color = opt.data('tiene-color') == 1;
    const tiene_serie = opt.data('tiene-serie') == 1;

    // AJAX a la misma página para obtener stock por item
    $.getJSON('<?= basename(__FILE__) ?>', { get_stock: 1, id_item: id_item }, function(data){
      $('#id_stock').empty().append('<option value="">Seleccionar variación (talla / serie)...</option>');
      if(!data || data.length === 0){
        $('#stock_help').text('No hay unidades disponibles para este artículo.');
        return;
      }
      data.forEach(function(row){
        let label = row.talla ? row.talla : '';
        if(row.numero_serie) label += (label? ' - ':'') + 'S:' + row.numero_serie;
        if(row.color) label += (label? ' - ':'') + row.color;
        label = label ? ' (' + label + ') - Disp: ' + row.cantidad_disponible : ' (Disp: ' + row.cantidad_disponible + ')';
        const opt = $('<option>')
            .val(row.id_stock)
            .attr('data-talla', row.talla)
            .attr('data-serie', row.numero_serie)
            .attr('data-color', row.color)
            .attr('data-disp', row.cantidad_disponible)
            .text( ($('#id_item option:selected').text().trim()) + label );
        $('#id_stock').append(opt);
      });
      $('#stock_help').text('Seleccione la variación de stock.');
      // mostrar inputs según flags del item (si el item tiene serie o color o talla)
      if(tiene_serie) $('#wrap_numserie').removeClass('d-none'); else $('#wrap_numserie').addClass('d-none');
      if(tiene_color) $('#wrap_color').removeClass('d-none'); else $('#wrap_color').addClass('d-none');
      if(tiene_talla) $('#wrap_talla').removeClass('d-none'); else $('#wrap_talla').addClass('d-none');
    }).fail(function(jqxhr, textStatus, error){
      $('#stock_help').text('Error cargando stock: ' + error);
    });
  });

  // Cuando se cambia la variación (id_stock), autocompleta serie/color/talla y ajusta cantidad máxima
  $('#id_stock').on('change', function(){
    const opt = $(this).find('option:selected');
    const serie = opt.data('serie') || '';
    const color = opt.data('color') || '';
    const talla = opt.data('talla') || '';
    const disp = parseInt(opt.data('disp') || 0, 10);

    $('#numero_serie').val(serie);
    $('#color').val(color);
    $('#talla_display').val(talla);
    $('#cantidad').attr('max', disp > 0 ? disp : 1);
  });

  // submit del formulario de asignar: validaciones adicionales
  $('#formAsignar').on('submit', function(e){
    e.preventDefault();
    // validar que se haya seleccionado una variación de stock
    if(!$('#id_stock').val()){
      alert('Seleccione una variación de stock (talla/serie) antes de asignar.');
      return;
    }
    // validación cantidad <= disponible
    const max = parseInt($('#id_stock option:selected').data('disp') || 0, 10);
    const qty = parseInt($('#cantidad').val() || 0, 10);
    if(qty <= 0 || qty > max){
      alert('Cantidad inválida. Disponible: ' + max);
      return;
    }

    // Enviar al action por POST (utilizamos el mismo endpoint que tenías: asignar_action.php)
    $.post('asignar_action.php', $(this).serialize(), function(resp){
      alert(resp);
      location.reload();
    }).fail(function(xhr){
      alert('Error en la petición: ' + xhr.responseText);
    });
  });

  // Devolución: mantiene comportamiento previo
  $('.btnDevolver').on('click', function(){
    $('#dev_id').val($(this).data('id'));
    $('#dev_cant').val($(this).data('cantidad'));
    new bootstrap.Modal(document.getElementById('modalDevolver')).show();
  });

  $('#formDevolver').on('submit', function(e){
    e.preventDefault();
    $.post('devolver_action.php', $(this).serialize(), function(resp){
      alert(resp); location.reload();
    });
  });
   // Al cambiar el cliente, obtener su tour automáticamente
  $('#formAsignar select[name="id_cliente"]').on('change', function() {
    const id_cliente = $(this).val();
    if (!id_cliente) return;

    $.getJSON('<?= basename(__FILE__) ?>', { get_tour: 1, id_cliente: id_cliente }, function(data) {
      if (data && data.id_servicio) {
        $('#formAsignar select[name="id_servicio"]').val(data.id_servicio);
      } else {
        $('#formAsignar select[name="id_servicio"]').val('');
      }
    }).fail(function(xhr) {
      console.error('Error obteniendo tour:', xhr.responseText);
    });
  });
});
</script>
</body>
</html>
