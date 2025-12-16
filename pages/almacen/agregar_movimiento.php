<?php
include("../../conexion.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_stock = $_POST['id_stock'];
    $tipo = $_POST['tipo_movimiento'];
    $cantidad = $_POST['cantidad'];
    $monto = $_POST['monto'] ?? 0;
    $observacion = $_POST['observacion'] ?? '';
    $registrado_por = 'Administrador';

    // Registrar el movimiento
    $insert = "INSERT INTO almacen_movimientos (id_stock, tipo_movimiento, cantidad, monto, observacion, registrado_por)
               VALUES ('$id_stock', '$tipo', '$cantidad', '$monto', '$observacion', '$registrado_por')";
    mysqli_query($conexion, $insert);

    // Actualizar stock general
    if ($tipo == 'Entrada') {
        $update = "UPDATE almacen_stock SET cantidad_total = cantidad_total + $cantidad,
                   cantidad_disponible = cantidad_disponible + $cantidad WHERE id_stock = '$id_stock'";
    } elseif ($tipo == 'Salida') {
        $update = "UPDATE almacen_stock SET cantidad_disponible = cantidad_disponible - $cantidad WHERE id_stock = '$id_stock'";
    }
    mysqli_query($conexion, $update);

    // Registrar en historial
    $detalles = "Movimiento de tipo $tipo con cantidad $cantidad.";
    mysqli_query($conexion, "INSERT INTO almacen_historial (accion, detalles) VALUES ('$tipo', '$detalles')");

    header("Location: almacen_control.php");
    exit;
}
?>
