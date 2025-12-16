<?php
// pages/almace/stock_action.php
include("../../conexion.php");
error_reporting(E_ALL); ini_set('display_errors',1);
$action = $_REQUEST['action'] ?? '';

if($action=='get_tallas'){
    $id_item = intval($_GET['id_item'] ?? 0);
    $res = mysqli_query($conexion, "SELECT id_talla, talla FROM almacen_tallas WHERE id_item=$id_item ORDER BY talla");
    $arr=[]; while($r=mysqli_fetch_assoc($res)) $arr[]=$r;
    echo json_encode($arr);
    exit;
}

if($action=='entrada'){
    $id_item = intval($_POST['id_item'] ?? 0);
    $id_talla = !empty($_POST['id_talla']) ? intval($_POST['id_talla']) : "NULL";
    $color = mysqli_real_escape_string($conexion, $_POST['color'] ?? '');
    $numero_serie = mysqli_real_escape_string($conexion, $_POST['numero_serie'] ?? '');
    $cantidad = intval($_POST['cantidad'] ?? 1);
    $obs = mysqli_real_escape_string($conexion, $_POST['observacion'] ?? '');

    // crear nuevo stock (por simplicidad cada entrada crea un registro; si prefieres combinar, hay que buscar coincidencias)
    $sql = "INSERT INTO almacen_stock (id_item, id_talla, color, numero_serie, cantidad_total, cantidad_disponible)
            VALUES ($id_item, $id_talla, '$color', '$numero_serie', $cantidad, $cantidad)";
    if(mysqli_query($conexion,$sql)){
        $id_stock = mysqli_insert_id($conexion);
        mysqli_query($conexion, "INSERT INTO almacen_movimientos (id_stock, tipo_movimiento, cantidad, observacion) VALUES ($id_stock,'Entrada',$cantidad,'$obs')");
        echo "✅ Stock agregado.";
    } else echo "❌ Error: ".mysqli_error($conexion);
    exit;
}

if($action=='edit'){
    $id_stock = intval($_POST['id_stock'] ?? 0);
    $total = intval($_POST['cantidad_total'] ?? 0);
    $disp = intval($_POST['cantidad_disponible'] ?? 0);
    $obs = mysqli_real_escape_string($conexion, $_POST['observacion'] ?? '');
    $sql = "UPDATE almacen_stock SET cantidad_total=$total, cantidad_disponible=$disp WHERE id_stock=$id_stock";
    if(mysqli_query($conexion,$sql)){
        mysqli_query($conexion, "INSERT INTO almacen_movimientos (id_stock, tipo_movimiento, cantidad, observacion) VALUES ($id_stock,'Salida',0,'Edición: $obs')");
        echo "✅ Actualizado.";
    } else echo "❌ Error: ".mysqli_error($conexion);
    exit;
}

if($action=='delete'){
    $id_stock = intval($_POST['id_stock'] ?? 0);
    if(mysqli_query($conexion, "DELETE FROM almacen_stock WHERE id_stock=$id_stock")){
        echo "🗑️ Eliminado.";
    } else echo "❌ Error: ".mysqli_error($conexion);
    exit;
}

echo "Acción no válida.";
