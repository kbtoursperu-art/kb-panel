<?php
// pages/almace/devolver_action.php
include("../../conexion.php");
error_reporting(E_ALL);
ini_set('display_errors', 1);

$id_asignacion = intval($_POST['id_asignacion'] ?? 0);
$cantidad = intval($_POST['cantidad'] ?? 0);
$obs = trim($_POST['observacion'] ?? '');
$usuario = 'Administrador';

if (!$id_asignacion || $cantidad <= 0) {
    echo "❌ Datos inválidos.";
    exit;
}

// 🔹 Buscar asignación
$sql = "SELECT id_stock, cantidad, estado 
        FROM almacen_pasajeros 
        WHERE id_asignacion = ? 
        LIMIT 1";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_asignacion);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo "❌ Asignación no encontrada.";
    exit;
}

$asig = $res->fetch_assoc();

if ($asig['estado'] !== 'En uso') {
    echo "❌ Esta asignación ya fue devuelta o entregada.";
    exit;
}

if ($cantidad > $asig['cantidad']) {
    echo "❌ La cantidad a devolver no puede ser mayor a la asignada.";
    exit;
}

$id_stock = $asig['id_stock'];

// 🔹 Iniciar transacción
$conexion->begin_transaction();

try {
    // 🔸 Actualizar stock disponible
    $sql_stock = "UPDATE almacen_stock 
                  SET cantidad_disponible = cantidad_disponible + ? 
                  WHERE id_stock = ?";
    $stmt_stock = $conexion->prepare($sql_stock);
    $stmt_stock->bind_param("ii", $cantidad, $id_stock);
    $stmt_stock->execute();

    // 🔸 Actualizar asignación
    if ($cantidad == $asig['cantidad']) {
        // devolución total
        $sql_asig = "UPDATE almacen_pasajeros 
                     SET estado='Devuelto', 
                         fecha_retorno = NOW(), 
                         observacion = CONCAT(IFNULL(observacion,''), ' ', ?) 
                     WHERE id_asignacion = ?";
        $stmt_asig = $conexion->prepare($sql_asig);
        $stmt_asig->bind_param("si", $obs, $id_asignacion);
        $stmt_asig->execute();
    } else {
        // devolución parcial
        $restante = $asig['cantidad'] - $cantidad;
        $sql_asig = "UPDATE almacen_pasajeros 
                     SET cantidad = ?, 
                         fecha_retorno = NOW(),
                         observacion = CONCAT(IFNULL(observacion,''), ' ', ?)
                     WHERE id_asignacion = ?";
        $stmt_asig = $conexion->prepare($sql_asig);
        $stmt_asig->bind_param("isi", $restante, $obs, $id_asignacion);
        $stmt_asig->execute();
    }

    // 🔸 Registrar movimiento
    $sql_mov = "INSERT INTO almacen_movimientos 
                (id_stock, tipo_movimiento, cantidad, observacion, registrado_por)
                VALUES (?, 'Devolucion', ?, ?, ?)";
    $stmt_mov = $conexion->prepare($sql_mov);
    $stmt_mov->bind_param("iiss", $id_stock, $cantidad, $obs, $usuario);
    $stmt_mov->execute();

    // 🔸 Registrar historial
    $detalle = "Devuelto $cantidad unidad(es). $obs";
    $sql_hist = "INSERT INTO almacen_historial 
                 (id_asignacion, accion, detalles)
                 VALUES (?, 'Devolución', ?)";
    $stmt_hist = $conexion->prepare($sql_hist);
    $stmt_hist->bind_param("is", $id_asignacion, $detalle);
    $stmt_hist->execute();

    // 🔹 Confirmar
    $conexion->commit();
    echo "✅ Devolución registrada correctamente.";

} catch (Exception $e) {
    $conexion->rollback();
    echo "❌ Error en la devolución: " . $e->getMessage();
}
?>
