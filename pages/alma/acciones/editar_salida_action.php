<?php
session_start();
include '../../../conexion.php';
mysqli_set_charset($conexion, 'utf8mb4');

function redir_error($msg) {
    header("Location: ../pendientes.php?error=" . urlencode($msg));
    exit;
}

$id_salida    = intval($_POST['id_salida'] ?? 0);
$nombre_guia  = trim($_POST['nombre_guia'] ?? '');
$cantidad_new = intval($_POST['cantidad'] ?? 0);
$fecha        = $_POST['fecha_salida'] ?? '';
$observacion  = trim($_POST['observacion'] ?? '');
$garantia_raw = $_POST['garantia'] ?? null;

if ($id_salida <= 0 || $nombre_guia === '' || $cantidad_new <= 0 || $fecha === '') {
    redir_error("Faltan datos obligatorios.");
}

mysqli_begin_transaction($conexion);

try {
    // ============================
    // 🔹 OBTENER SALIDA + STOCK (con lock)
    // ============================
    $stmt = mysqli_prepare($conexion, "
        SELECT s.id_stock, s.cantidad AS cantidad_actual, s.estado,
               st.cantidad_disponible, i.tipo
        FROM almacen_salidas s
        JOIN almacen_stock st ON st.id_stock = s.id_stock
        JOIN almacen_items i ON i.id_item = st.id_item
        WHERE s.id_salida = ?
        FOR UPDATE
    ");
    mysqli_stmt_bind_param($stmt, "i", $id_salida);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$row) {
        throw new Exception("Salida no encontrada.");
    }

    // ============================
    // 🔹 NO PERMITIR EDITAR CANTIDAD SI YA HAY DEVOLUCIONES
    // ============================
    $stmt = mysqli_prepare($conexion, "
        SELECT COALESCE(SUM(cantidad_devuelta),0) AS total_devuelto
        FROM almacen_devoluciones
        WHERE id_salida = ?
    ");
    mysqli_stmt_bind_param($stmt, "i", $id_salida);
    mysqli_stmt_execute($stmt);
    $devuelto = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total_devuelto'];
    mysqli_stmt_close($stmt);

    if ($devuelto > 0 && $cantidad_new < $devuelto) {
        throw new Exception("No puedes bajar la cantidad a $cantidad_new: ya se devolvieron $devuelto unidades de esta salida.");
    }

    // ============================
    // 🔹 CALCULAR DIFERENCIA DE STOCK
    // ============================
    $diferencia = $cantidad_new - $row['cantidad_actual']; // positivo = sale más stock, negativo = se devuelve stock

    if ($diferencia > 0 && $diferencia > $row['cantidad_disponible']) {
        throw new Exception("No hay suficiente stock disponible para aumentar la cantidad. Disponible: " . $row['cantidad_disponible']);
    }

    // ============================
    // 🔹 GARANTÍA
    // ============================
    $garantia = 0.00;
    if ($row['tipo'] === 'Garantia') {
        if ($garantia_raw === null || $garantia_raw === '' || !is_numeric($garantia_raw)) {
            throw new Exception("Debe ingresar el monto de garantía para este producto.");
        }
        $garantia = floatval($garantia_raw);
        if ($garantia < 0) {
            throw new Exception("El monto de garantía no puede ser negativo.");
        }
    }

    // ============================
    // 🔹 ACTUALIZAR SALIDA
    // ============================
    $stmt = mysqli_prepare($conexion, "
        UPDATE almacen_salidas
        SET nombre_guia = ?, cantidad = ?, fecha_salida = ?, garantia_original = ?, observacion = ?
        WHERE id_salida = ?
    ");
    mysqli_stmt_bind_param($stmt, "sisdsi", $nombre_guia, $cantidad_new, $fecha, $garantia, $observacion, $id_salida);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // ============================
    // 🔹 AJUSTAR STOCK SEGÚN LA DIFERENCIA
    // ============================
    if ($diferencia !== 0) {
        $stmt = mysqli_prepare($conexion, "
            UPDATE almacen_stock
            SET cantidad_disponible = cantidad_disponible - ?
            WHERE id_stock = ?
        ");
        mysqli_stmt_bind_param($stmt, "ii", $diferencia, $row['id_stock']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Registrar movimiento de ajuste
        $tipo_mov = $diferencia > 0 ? 'Salida' : 'Devolucion';
        $referencia = "Ajuste por edición de salida #$id_salida ($nombre_guia)";
        $cantidad_mov = abs($diferencia);
        $stmt = mysqli_prepare($conexion, "
            INSERT INTO almacen_movimientos (id_stock, tipo, cantidad, monto, referencia)
            VALUES (?, ?, ?, 0.00, ?)
        ");
        mysqli_stmt_bind_param($stmt, "isis", $row['id_stock'], $tipo_mov, $cantidad_mov, $referencia);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    mysqli_commit($conexion);
    header("Location: ../pendientes.php?ok=" . urlencode("Salida #$id_salida actualizada correctamente."));
    exit;

} catch (Exception $e) {
    mysqli_rollback($conexion);
    redir_error($e->getMessage());
}