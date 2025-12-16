<?php
include '../../conexion.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (isset($_GET['id_venta']) && is_numeric($_GET['id_venta'])) {
    $id_venta = $_GET['id_venta'];

    // Eliminar de la tabla real "Venta" en lugar de "Vista_Ventas"
    $stmt = $conexion->prepare("DELETE FROM Venta WHERE id_venta = ?");
    $stmt->bind_param("i", $id_venta);

    if ($stmt->execute()) {
        header("Location: index.php?mensaje=Venta eliminada correctamente");
        exit();
    } else {
        header("Location: index.php?error=No se pudo eliminar la venta");
        exit();
    }
} else {
    header("Location: index.php?error=ID de venta inválido");
    exit();
}
?>
