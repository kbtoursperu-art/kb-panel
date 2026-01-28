<?php
include '../../../conexion.php';

// 🔹 DATOS DEL FORMULARIO
$id_stock    = intval($_POST['id_stock']);
$nombre_guia = mysqli_real_escape_string($conexion, $_POST['nombre_guia']);
$cantidad    = intval($_POST['cantidad']);
$fecha       = $_POST['fecha_salida'];
$observacion = mysqli_real_escape_string($conexion, $_POST['observacion'] ?? '');

// ============================
// 🔹 VERIFICAR STOCK DISPONIBLE
// ============================
$stock = mysqli_fetch_assoc(mysqli_query($conexion,"
    SELECT st.cantidad_disponible, i.tipo, i.nombre AS producto
    FROM almacen_stock st
    JOIN almacen_items i ON st.id_item = i.id_item
    WHERE st.id_stock = $id_stock
"));

if(!$stock){
    die("❌ Producto no encontrado en stock.");
}

if($cantidad > $stock['cantidad_disponible']){
    die("❌ No hay suficiente stock disponible. Disponible: ".$stock['cantidad_disponible']);
}

// ============================
// 🔹 SOLO PRODUCTOS TIPO 'Garantia' TIENEN GARANTÍA
// ============================
$garantia = ($stock['tipo'] === 'Garantia') 
    ? floatval($_POST['garantia'] ?? 0) 
    : 0;

// ============================
// 🔹 REGISTRAR SALIDA (COLUMNA CORRECTA)
// ============================
mysqli_query($conexion,"
    INSERT INTO almacen_salidas
    (id_stock, nombre_guia, cantidad, fecha_salida, garantia_original, observacion)
    VALUES ($id_stock, '$nombre_guia', $cantidad, '$fecha', $garantia, '$observacion')
");

// ============================
// 🔹 ACTUALIZAR STOCK
// ============================
mysqli_query($conexion,"
    UPDATE almacen_stock
    SET cantidad_disponible = cantidad_disponible - $cantidad
    WHERE id_stock = $id_stock
");

// ============================
// 🔹 REGISTRAR MOVIMIENTO
// ============================
mysqli_query($conexion,"
    INSERT INTO almacen_movimientos
    (id_stock, tipo, cantidad, monto, referencia)
    VALUES ($id_stock, 'Salida', $cantidad, $garantia, 'Salida a guía $nombre_guia')
");

// ============================
// 🔹 REDIRECCIÓN
// ============================
header("Location: ../salida.php");
exit;
?>
