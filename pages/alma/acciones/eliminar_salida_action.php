<?php
session_start();
include '../../../conexion.php';
mysqli_set_charset($conexion, 'utf8mb4');

function redir_error($msg) {
    header("Location: ../pendientes.php?error=" . urlencode($msg));
    exit;
}

$id_salida = intval($_POST['id_salida'] ?? 0);

if ($id_salida <= 0) {
    redir_error("Salida inválida.");
}

mysqli_begin_transaction($conexion);

try {
    // ============================
    // 🔹 OBTENER SALIDA (con lock)
    // ============================
    $stmt = mysqli_prepare($conexion, "
        SELECT id_stock, cantidad, nombre_guia
        FROM almacen_salidas
        WHERE id_salida = ?
        FOR UPDATE
    ");
    mysqli_stmt_bind_param($stmt, "i", $id_salida);
    mysqli_stmt_execute($stmt);
    $salida = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$salida) {
        throw new Exception("Salida no encontrada.");
    }

    // ============================
    // 🔹 BLOQUEAR SI YA TIENE DEVOLUCIONES REGISTRADAS
    // ============================
    $stmt = mysqli_prepare($conexion, "
        SELECT COUNT(*) AS total FROM almacen_devoluciones WHERE id_salida = ?
    ");
    mysqli_stmt_bind_param($stmt, "i", $id_salida);
    mysqli_stmt_execute($stmt);
    $tiene_devoluciones = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt))['total'];
    mysqli_stmt_close($stmt);

    if ($tiene_devoluciones > 0) {
        throw new Exception("No se puede eliminar: esta salida ya tiene devoluciones registradas. Elimina primero las devoluciones asociadas.");
    }

    // ============================
    // 🔹 RESTAURAR STOCK
    // ============================
    $stmt = mysqli_prepare($conexion, "
        UPDATE almacen_stock
        SET cantidad_disponible = cantidad_disponible + ?
        WHERE id_stock = ?
    ");
    mysqli_stmt_bind_param($stmt, "ii", $salida['cantidad'], $salida['id_stock']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // ============================
    // 🔹 REGISTRAR MOVIMIENTO DE REVERSIÓN
    // ============================
    $referencia = "Eliminación de salida #$id_salida (" . $salida['nombre_guia'] . ")";
    $stmt = mysqli_prepare($conexion, "
        INSERT INTO almacen_movimientos (id_stock, tipo, cantidad, monto, referencia)
        VALUES (?, 'Devolucion', ?, 0.00, ?)
    ");
    mysqli_stmt_bind_param($stmt, "iis", $salida['id_stock'], $salida['cantidad'], $referencia);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // ============================
    // 🔹 ELIMINAR SALIDA
    // ============================
    $stmt = mysqli_prepare($conexion, "DELETE FROM almacen_salidas WHERE id_salida = ?");
    mysqli_stmt_bind_param($stmt, "i", $id_salida);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    mysqli_commit($conexion);
    header("Location: ../pendientes.php?ok=" . urlencode("Salida #$id_salida eliminada y stock restaurado."));
    exit;

} catch (Exception $e) {
    mysqli_rollback($conexion);
    redir_error($e->getMessage());
}