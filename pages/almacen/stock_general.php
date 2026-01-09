<?php
// pages/almace/stock_general.php
include("../../conexion.php");
error_reporting(E_ALL); ini_set('display_errors',1);

// resumen rápido
function getValue($conexion, $q){ $r=mysqli_query($conexion,$q); return ($r? mysqli_fetch_assoc($r)['total']:0); }

$maletas_almacen = getValue($conexion, "SELECT COALESCE(SUM(s.cantidad_disponible),0) AS total FROM almacen_stock s JOIN almacen_items i ON s.id_item=i.id_item WHERE i.nombre LIKE '%maleta%'");
$bastones_en_uso = getValue($conexion, "SELECT COUNT(*) AS total FROM almacen_pasajeros p JOIN almacen_stock s ON p.id_stock=s.id_stock JOIN almacen_items i ON s.id_item=i.id_item WHERE i.nombre LIKE '%baston%' AND p.estado='En uso'");
$sleeping_en_uso = getValue($conexion, "SELECT COUNT(*) AS total FROM almacen_pasajeros p JOIN almacen_stock s ON p.id_stock=s.id_stock JOIN almacen_items i ON s.id_item=i.id_item WHERE i.nombre LIKE '%sleep%' AND p.estado='En uso'");
$clientes_registro = getValue($conexion, "SELECT COUNT(DISTINCT id_cliente) AS total FROM almacen_pasajeros");

// lista stock
$sql = "SELECT s.id_stock, i.nombre AS articulo, i.categoria, t.talla, s.numero_serie, s.color, s.cantidad_total, s.cantidad_disponible
        FROM almacen_stock s
        JOIN almacen_items i ON s.id_item = i.id_item
        LEFT JOIN almacen_tallas t ON s.id_talla = t.id_talla
        ORDER BY i.nombre, t.talla";
$res = mysqli_query($conexion, $sql);

// items para crear nuevo stock (modal)
$items = mysqli_query($conexion, "SELECT id_item, nombre, tiene_talla, tiene_color, tiene_serie FROM almacen_items ORDER BY nombre");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Stock General</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="stilo.css">
</head>
<body class="bg-light">
  <?php include '../sidebar.php';?>
  <br>
  <br>
  <br>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>📦 Stock General</h3>
    <div>
      <a href="asignaciones.php" class="btn btn-primary"><i class="bi bi-person-plus"></i> Asignaciones</a>
      <a href="historial.php" class="btn btn-outline-secondary"><i class="bi bi-clock-history"></i> Historial</a>
      <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalAgregar">+ Agregar Stock</button>
    </div>
  </div>

  <!-- resumen -->
  <div class="row mb-3">
    <div class="col"><div class="card p-2"><strong>Maletas (disp)</strong><div><?= $maletas_almacen ?></div></div></div>
    <div class="col"><div class="card p-2"><strong>Bastones en uso</strong><div><?= $bastones_en_uso ?></div></div></div>
    <div class="col"><div class="card p-2"><strong>Sleeping en uso</strong><div><?= $sleeping_en_uso ?></div></div></div>
    <div class="col"><div class="card p-2"><strong>Clientes activos</strong><div><?= $clientes_registro ?></div></div></div>
  </div>

  <div class="card">
    <div class="card-body">
      <table id="tablaStock" class="table table-striped">
        <thead class="table-dark">
          <tr><th>ID</th><th>Artículo</th><th>Cat.</th><th>Talla</th><th>Color</th><th>Serie</th><th>Total</th><th>Disp.</th><th>Acciones</th></tr>
        </thead>
        <tbody>
          <?php while($r=mysqli_fetch_assoc($res)): ?>
          <tr>
            <td><?= $r['id_stock'] ?></td>
            <td><?= htmlspecialchars($r['articulo']) ?></td>
            <td><?= $r['categoria'] ?></td>
            <td><?= $r['talla']?: '-' ?></td>
            <td><?= $r['color']?: '-' ?></td>
            <td><?= $r['numero_serie']?: '-' ?></td>
            <td><?= $r['cantidad_total'] ?></td>
            <td><?= $r['cantidad_disponible'] ?></td>
            <td>
              <button class="btn btn-sm btn-outline-primary btnEditar" data-id="<?= $r['id_stock'] ?>" data-total="<?= $r['cantidad_total'] ?>" data-disponible="<?= $r['cantidad_disponible'] ?>">Editar</button>
              <button class="btn btn-sm btn-outline-danger btnEliminar" data-id="<?= $r['id_stock'] ?>">Eliminar</button>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- modal agregar -->
<div class="modal fade" id="modalAgregar" tabindex="-1">
  <div class="modal-dialog">
    <form id="formAgregar" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Agregar stock</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="mb-2">
          <label>Artículo</label>
          <select name="id_item" id="id_item" class="form-select" required>
            <option value="">Seleccionar...</option>
            <?php mysqli_data_seek($items,0); while($it=mysqli_fetch_assoc($items)): ?>
              <option value="<?= $it['id_item'] ?>"
                data-talla="<?= $it['tiene_talla'] ?>"
                data-color="<?= $it['tiene_color'] ?>"
                data-serie="<?= $it['tiene_serie'] ?>">
                <?= htmlspecialchars($it['nombre']) ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="mb-2" id="divTalla" style="display:none;">
          <label>Talla</label>
          <select name="id_talla" id="id_talla" class="form-select"><option value="">Sin talla</option></select>
        </div>

        <div class="mb-2" id="divColor" style="display:none;">
          <label>Color</label>
          <input type="text" name="color" class="form-control">
        </div>

        <div class="mb-2" id="divSerie" style="display:none;">
          <label>N° Serie</label>
          <input type="text" name="numero_serie" class="form-control" placeholder="Sólo si aplica (maleta)">
        </div>

        <div class="mb-2">
          <label>Cantidad</label>
          <input type="number" name="cantidad" class="form-control" min="1" value="1" required>
        </div>

        <div class="mb-2">
          <label>Observación</label>
          <input type="text" name="observacion" class="form-control">
        </div>
      </div>
      <div class="modal-footer"><button class="btn btn-success">Guardar</button></div>
    </form>
  </div>
</div>

<!-- modal editar -->
<div class="modal fade" id="modalEditar" tabindex="-1">
  <div class="modal-dialog">
    <form id="formEditar" class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Editar stock</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" name="id_stock" id="edit_id_stock">
        <div class="mb-2"><label>Total</label><input type="number" name="cantidad_total" id="edit_total" class="form-control" required></div>
        <div class="mb-2"><label>Disponible</label><input type="number" name="cantidad_disponible" id="edit_disponible" class="form-control" required></div>
        <div class="mb-2"><label>Observación</label><input type="text" name="observacion" id="edit_obs" class="form-control"></div>
      </div>
      <div class="modal-footer"><button class="btn btn-primary">Guardar cambios</button></div>
    </form>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
$(function(){
  $('#tablaStock').DataTable({ language:{ url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" } });

  // carga tallas según item
  $('#id_item').on('change', function(){
    const id = $(this).val();
    const opt = $(this).find('option:selected');
    const tiene_talla = opt.data('talla') == 1;
    const tiene_color = opt.data('color') == 1;
    const tiene_serie = opt.data('serie') == 1;

    if(tiene_talla){
      $.get('stock_action.php', { action:'get_tallas', id_item: id }, function(resp){
        try{
          const data = JSON.parse(resp);
          let html = '<option value="">Sin talla</option>';
          data.forEach(t => html += `<option value="${t.id_talla}">${t.talla}</option>`);
          $('#id_talla').html(html); $('#divTalla').show();
        }catch(e){ $('#divTalla').hide(); }
      });
    } else { $('#divTalla').hide(); }

    $('#divColor').toggle(!!tiene_color);
    $('#divSerie').toggle(!!tiene_serie);
  });

  // submit agregar
  $('#formAgregar').on('submit', function(e){
    e.preventDefault();
    $.post('stock_action.php', $(this).serialize() + '&action=entrada', function(resp){
      alert(resp); location.reload();
    });
  });

  // editar
  $('.btnEditar').on('click', function(){
    $('#edit_id_stock').val($(this).data('id'));
    $('#edit_total').val($(this).data('total'));
    $('#edit_disponible').val($(this).data('disponible'));
    new bootstrap.Modal(document.getElementById('modalEditar')).show();
  });
  $('#formEditar').on('submit', function(e){ e.preventDefault(); $.post('stock_action.php', $(this).serialize() + '&action=edit', function(r){ alert(r); location.reload(); }); });

  // eliminar
  $('.btnEliminar').on('click', function(){
    if(!confirm('Eliminar stock? Esto borrará movimientos relacionados.')) return;
    $.post('stock_action.php', { action:'delete', id_stock: $(this).data('id') }, function(resp){ alert(resp); location.reload(); });
  });
});
</script>
</body>
</html>
