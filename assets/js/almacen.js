
/* Ejemplos: inicializar DataTables y check de stock via AJAX */
$(document).ready(function(){
$('#itemsTable').DataTable();
// ejemplo: evento para chequear stock
$('#id_stock').change(function(){
var id = $(this).val();
$.getJSON('ajax_almacen.php?action=check_stock&id_stock='+id, function(data){
$('#stockDisponible').text(data.disponible);
});
});
});