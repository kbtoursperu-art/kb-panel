<?php
include("../../conexion.php");
error_reporting(E_ALL);
ini_set('display_errors', 1);

$id_cliente = intval($_POST['id_cliente'] ?? 0);
$id_servicio = !empty($_POST['id_servicio']) ? intval($_POST['id_servicio']) : 'NULL';
$id_stock = intval($_POST['id_stock'] ?? 0);
$cantidad = intval($_POST['cantidad'] ?? 1);
$tipo_uso = mysqli_real_escape_string($conexion, $_POST['tipo_uso'] ?? 'Uso');
$monto = floatval($_POST['monto'] ?? 0);
$fecha_salida = !empty($_POST['fecha_salida']) ? "'".date('Y-m-d H:i:s', strtotime($_POST['fecha_salida']))."'" : "NOW()";
$fecha_retorno = !empty($_POST['fecha_retorno']) ? "'".date('Y-m-d H:i:s', strtotime($_POST['fecha_retorno']))."'" : "NULL";
$numero_serie = mysqli_real_escape_string($conexion, $_POST['numero_serie'] ?? '');
$color = mysqli_real_escape_string($conexion, $_POST['color'] ?? '');
$observacion = mysqli_real_escape_string($conexion, $_POST['observacion'] ?? '');
$talla = mysqli_real_escape_string($conexion, $_POST['talla'] ?? '');
$incluido = $_POST['incluido'] ?? 'no'; // para sleeping bag

mysqli_begin_transaction($conexion);

try {
    // 🔍 Verificar stock
    $res = mysqli_query($conexion, "
        SELECT s.*, i.nombre AS tipo_articulo, i.tiene_talla, i.tiene_color, i.tiene_serie
        FROM almacen_stock s
        JOIN almacen_items i ON s.id_item = i.id_item
        WHERE s.id_stock = $id_stock
        FOR UPDATE
    ");
    if (!$res || mysqli_num_rows($res) == 0) throw new Exception("Stock no encontrado.");
    $stock = mysqli_fetch_assoc($res);

    $disponible = intval($stock['cantidad_disponible']);
    $tipo_articulo = strtolower($stock['tipo_articulo']);

    if ($disponible < $cantidad) throw new Exception("No hay suficiente stock disponible. Disponible: $disponible");

    // 🧩 Ajustes según tipo de artículo
    switch ($tipo_articulo) {
        case 'maleta':
            if (empty($numero_serie) || empty($color)) throw new Exception("Debes especificar número de serie y color para la maleta.");
            $tipo_uso = 'Uso';
            $monto = 0;
            $fecha_retorno = "NULL";
            break;

        case 'bastón':
        case 'baston trekking':
            if ($monto <= 0) throw new Exception("Debe ingresar monto para el alquiler de bastones.");
            break;

        case 'dafor':
            if ($monto <= 0) throw new Exception("Debe ingresar monto de garantía para el dafor.");
            break;

        case 'polo':
            if (empty($talla) || empty($color)) throw new Exception("Debes especificar talla y color para el polo.");
            $monto = 0;
            $fecha_retorno = "NULL";
            break;

        case 'sleeping bag':
        case 'sleeping':
            if ($incluido == 'si') $monto = 0; // incluido con el tour
            break;

        default:
            // otros artículos simples
            break;
    }

    // 🧾 Insertar asignación
    $sql = "INSERT INTO almacen_pasajeros 
            (id_cliente, id_servicio, id_stock, cantidad, tipo_articulo, tipo_uso, monto, fecha_salida, fecha_retorno, estado, observacion)
            VALUES 
            ($id_cliente, $id_servicio, $id_stock, $cantidad, '".mysqli_real_escape_string($conexion, ucfirst($tipo_articulo))."', 
             '$tipo_uso', $monto, $fecha_salida, $fecha_retorno, 'En uso', '$observacion')";
    if (!mysqli_query($conexion, $sql)) throw new Exception("Error al guardar asignación: " . mysqli_error($conexion));

    $id_asig = mysqli_insert_id($conexion);

    // ➖ Actualizar stock
    $upd = "UPDATE almacen_stock SET cantidad_disponible = cantidad_disponible - $cantidad WHERE id_stock = $id_stock";
    if (!mysqli_query($conexion, $upd)) throw new Exception("Error al actualizar stock: " . mysqli_error($conexion));

    // 📜 Registrar movimiento
    $mov = "INSERT INTO almacen_movimientos (id_stock, tipo_movimiento, cantidad, monto, observacion, registrado_por)
            VALUES ($id_stock, 'Salida', $cantidad, $monto, '$observacion', 'Sistema')";
    if (!mysqli_query($conexion, $mov)) throw new Exception("Error al registrar movimiento: " . mysqli_error($conexion));

    // 🕒 Registrar historial
    $det = "Entrega de $tipo_articulo - Cliente:$id_cliente - Cantidad:$cantidad - Uso:$tipo_uso - Monto:$monto";
    $hist = "INSERT INTO almacen_historial (id_asignacion, accion, detalles) 
             VALUES ($id_asig, 'Entrega', '".mysqli_real_escape_string($conexion, $det)."')";
    if (!mysqli_query($conexion, $hist)) throw new Exception("Error al registrar historial: " . mysqli_error($conexion));

    mysqli_commit($conexion);
    echo "✅ Asignación de $tipo_articulo registrada correctamente.";
} catch (Exception $e) {
    mysqli_rollback($conexion);
    echo "❌ " . $e->getMessage();
}
?>
