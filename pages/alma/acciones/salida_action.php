<?php
session_start();
include '../../../conexion.php';
mysqli_set_charset($conexion, 'utf8mb4');

function redir_error($msg) {
    header("Location: ../salida.php?error=" . urlencode($msg));
    exit;
}

// ============================
// 🔹 DATOS DEL FORMULARIO
// ============================
$id_stock    = intval($_POST['id_stock'] ?? 0);
$nombre_guia = trim($_POST['nombre_guia'] ?? '');
$cantidad    = intval($_POST['cantidad'] ?? 0);
$fecha       = $_POST['fecha_salida'] ?? '';
$observacion = trim($_POST['observacion'] ?? '');
$garantia_raw = $_POST['garantia'] ?? null; // 👈 nombre real del input

if ($id_stock <= 0 || $nombre_guia === '' || $cantidad <= 0 || $fecha === '') {
    redir_error("Faltan datos obligatorios.");
}

mysqli_begin_transaction($conexion);

try {
    // ============================
    // 🔹 VERIFICAR STOCK (con lock para evitar condiciones de carrera)
    // ============================
    $stmt = mysqli_prepare($conexion, "
        SELECT st.cantidad_disponible, i.tipo, i.nombre AS producto
        FROM almacen_stock st
        JOIN almacen_items i ON st.id_item = i.id_item
        WHERE st.id_stock = ?
        FOR UPDATE
    ");
    mysqli_stmt_bind_param($stmt, "i", $id_stock);
    mysqli_stmt_execute($stmt);
    $stock = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$stock) {
        throw new Exception("Producto no encontrado en stock.");
    }

    if ($cantidad > $stock['cantidad_disponible']) {
        throw new Exception("No hay suficiente stock disponible. Disponible: " . $stock['cantidad_disponible']);
    }

    // ============================
    // 🔹 GARANTÍA — solo si el tipo de producto es 'Garantia'
    // ============================
    $garantia = 0.00;
    if ($stock['tipo'] === 'Garantia') {
        if ($garantia_raw === null || $garantia_raw === '' || !is_numeric($garantia_raw)) {
            throw new Exception("Debe ingresar el monto de garantía para este producto.");
        }
        $garantia = floatval($garantia_raw);
        if ($garantia < 0) {
            throw new Exception("El monto de garantía no puede ser negativo.");
        }
    }

    // ============================
    // 🔹 REGISTRAR SALIDA
    // ============================
    $stmt = mysqli_prepare($conexion, "
        INSERT INTO almacen_salidas
        (id_stock, nombre_guia, cantidad, fecha_salida, garantia_original, observacion)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    mysqli_stmt_bind_param($stmt, "isisds", $id_stock, $nombre_guia, $cantidad, $fecha, $garantia, $observacion);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // ============================
    // 🔹 ACTUALIZAR STOCK
    // ============================
    $stmt = mysqli_prepare($conexion, "
        UPDATE almacen_stock
        SET cantidad_disponible = cantidad_disponible - ?
        WHERE id_stock = ?
    ");
    mysqli_stmt_bind_param($stmt, "ii", $cantidad, $id_stock);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // ============================
    // 🔹 REGISTRAR MOVIMIENTO
    // ============================
    $referencia = "Salida a guía " . $nombre_guia;
    $stmt = mysqli_prepare($conexion, "
        INSERT INTO almacen_movimientos
        (id_stock, tipo, cantidad, monto, referencia)
        VALUES (?, 'Salida', ?, ?, ?)
    ");
    mysqli_stmt_bind_param($stmt, "iids", $id_stock, $cantidad, $garantia, $referencia);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    mysqli_commit($conexion);

    header("Location: ../salida.php?ok=" . urlencode("Salida registrada correctamente."));
    exit;

} catch (Exception $e) {
    mysqli_rollback($conexion);
    redir_error($e->getMessage());
}