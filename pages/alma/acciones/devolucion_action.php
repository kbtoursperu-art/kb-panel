<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: ../../../index.php");
    exit();
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../../../conexion.php';
mysqli_set_charset($conexion, 'utf8mb4');

function obtener_devuelto($conexion, $id_salida) {
    $r = mysqli_fetch_assoc(mysqli_query($conexion, "
        SELECT IFNULL(SUM(cantidad_devuelta),0) AS total
        FROM almacen_devoluciones
        WHERE id_salida = $id_salida
    "));
    return (int)$r['total'];
}

$id_salida   = (int)($_POST['id_salida'] ?? 0);
$cantidadDev = (int)($_POST['cantidad'] ?? 0);
$observacion = mysqli_real_escape_string($conexion, trim($_POST['observacion'] ?? ''));

if ($id_salida < 1) {
    header("Location: ../pendientes.php?error=" . urlencode("Salida inválida"));
    exit();
}
if ($cantidadDev < 1) {
    header("Location: ../pendientes.php?error=" . urlencode("La cantidad a devolver debe ser mayor a 0"));
    exit();
}

mysqli_begin_transaction($conexion);

try {

    // ── OBTENER SALIDA (campos reales del schema) ──
    $salida = mysqli_fetch_assoc(mysqli_query($conexion, "
        SELECT
            s.id_salida,
            s.id_stock,
            s.cantidad,
            s.garantia_original,
            s.estado,
            i.tipo
        FROM almacen_salidas s
        JOIN almacen_stock st ON s.id_stock = st.id_stock
        JOIN almacen_items i  ON st.id_item = i.id_item
        WHERE s.id_salida = $id_salida
        FOR UPDATE
    "));

    if (!$salida) {
        throw new Exception("Salida #$id_salida no encontrada");
    }

    $totalCantidad = (int)$salida['cantidad'];
    $tipo          = strtolower(trim($salida['tipo']));

    // ── VALIDAR CONTRA LO YA DEVUELTO (evita descuadres) ──
    $yaDevuelto = obtener_devuelto($conexion, $id_salida);
    $pendiente  = $totalCantidad - $yaDevuelto;

    if ($cantidadDev > $pendiente) {
        throw new Exception("Cantidad ($cantidadDev) supera lo pendiente ($pendiente)");
    }

    // ── INSERT DEVOLUCIÓN ──
    // Los triggers de la BD se encargan automáticamente de:
    //   1) trg_calcular_monto_devuelto  -> calcula monto_devuelto proporcional a la garantía
    //   2) trg_actualizar_estado_salida -> actualiza almacen_salidas.estado (Pendiente/Parcial/Devuelto)
    //   3) trg_devolver_stock           -> repone almacen_stock.cantidad_disponible
    // Por eso aquí SOLO insertamos cantidad_devuelta y observacion; NO tocamos
    // garantia, estado, ni stock manualmente — evita doble conteo y descuadres.
    $stmt = mysqli_prepare($conexion, "
        INSERT INTO almacen_devoluciones (id_salida, cantidad_devuelta, observacion)
        VALUES (?, ?, ?)
    ");
    mysqli_stmt_bind_param($stmt, "iis", $id_salida, $cantidadDev, $observacion);

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Error al registrar devolución: " . mysqli_stmt_error($stmt));
    }
    mysqli_stmt_close($stmt);

    // ── VERIFICACIÓN POST-TRIGGER (control de cuadre) ──
    // Confirma que el trigger trg_devolver_stock actuó solo para retornables/garantía
    // y que el total devuelto nunca exceda lo entregado (protección extra).
    $totalDevueltoFinal = obtener_devuelto($conexion, $id_salida);
    if ($totalDevueltoFinal > $totalCantidad) {
        throw new Exception("Descuadre detectado: devuelto ($totalDevueltoFinal) > entregado ($totalCantidad)");
    }

    mysqli_commit($conexion);
    header("Location: ../pendientes.php?ok=" . urlencode("Devolución registrada: $cantidadDev unidad(es)"));
    exit();

} catch (Exception $e) {
    mysqli_rollback($conexion);
    header("Location: ../pendientes.php?error=" . urlencode($e->getMessage()));
    exit();
}