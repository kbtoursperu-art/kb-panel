<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../../../conexion.php'; // aquí ya existe $conexion

mysqli_begin_transaction($conexion);

try {

    $id_salida   = (int)$_POST['id_salida'];
    $cantidadDev = (int)$_POST['cantidad'];
    $observacion = mysqli_real_escape_string($conexion, $_POST['observacion'] ?? '');

    // OBTENER SALIDA
    $salida = mysqli_fetch_assoc(mysqli_query($conexion,"
        SELECT 
            s.id_stock,
            s.cantidad,
            s.garantia,
            s.garantia_original,
            i.tipo
        FROM almacen_salidas s
        JOIN almacen_stock st ON s.id_stock = st.id_stock
        JOIN almacen_items i ON st.id_item = i.id_item
        WHERE s.id_salida = $id_salida
    "));

    if (!$salida) {
        throw new Exception("Salida no encontrada");
    }

    $totalCantidad = (int)$salida['cantidad'];
    $id_stock = (int)$salida['id_stock'];
    $tipo = strtolower(trim($salida['tipo']));

    // YA DEVUELTO
    $yaDevuelto = obtener_devuelto($conexion, $id_salida);
    $pendiente = $totalCantidad - $yaDevuelto;

    if ($cantidadDev <= 0 || $cantidadDev > $pendiente) {
        throw new Exception("Cantidad inválida");
    }

    // GARANTÍA
    $garantiaDevuelta = 0;
    if ($tipo === 'garantia') {
        $garantiaUnit = round($salida['garantia_original'] / $totalCantidad, 2);
        $garantiaDevuelta = round($garantiaUnit * $cantidadDev, 2);

        mysqli_query($conexion,"
            UPDATE almacen_salidas
            SET garantia = garantia - $garantiaDevuelta
            WHERE id_salida = $id_salida
        ");
    }

    // INSERT DEVOLUCIÓN
    mysqli_query($conexion,"
        INSERT INTO almacen_devoluciones
        (id_salida, cantidad_devuelta, monto_devuelto, observacion, )
        VALUES ($id_salida, $cantidadDev, $garantiaDevuelta, '$observacion')
    ");

    // DEVOLVER STOCK
    if ($tipo === 'retornable') {
        mysqli_query($conexion,"
            UPDATE almacen_stock
            SET cantidad_disponible = cantidad_disponible + $cantidadDev
            WHERE id_stock = $id_stock
        ");
    }

    // ESTADO
    $estado = ($cantidadDev + $yaDevuelto >= $totalCantidad) ? 'Devuelto' : 'Parcial';

    mysqli_query($conexion,"
        UPDATE almacen_salidas
        SET estado = '$estado'
        WHERE id_salida = $id_salida
    ");

    // MOVIMIENTO
    mysqli_query($conexion,"
        INSERT INTO almacen_movimientos
        (id_stock, tipo, cantidad, monto, referencia)
        VALUES ($id_stock, 'Devolucion', $cantidadDev, $garantiaDevuelta, 'Devolución')
    ");

    mysqli_commit($conexion);
    header("Location: ../pendientes.php");
    exit;

} catch (Exception $e) {
    mysqli_rollback($conexion);
    die("ERROR: " . $e->getMessage());
}

function obtener_devuelto($conexion, $id_salida) {
    $r = mysqli_fetch_assoc(mysqli_query($conexion,"
        SELECT IFNULL(SUM(cantidad_devuelta),0) total
        FROM almacen_devoluciones
        WHERE id_salida = $id_salida
    "));
    return (int)$r['total'];
}
