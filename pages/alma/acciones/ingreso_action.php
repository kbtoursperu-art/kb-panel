<?php
include '../../../conexion.php';

// ========================
// 🔹 DATOS
// ========================
$id_stock = intval($_POST['id_stock']);
$cantidad = intval($_POST['cantidad']);

// ========================
// 🔹 AUMENTAR STOCK
// ========================
mysqli_query($conexion, "
    UPDATE almacen_stock
    SET 
        cantidad_total = cantidad_total + $cantidad,
        cantidad_disponible = cantidad_disponible + $cantidad
    WHERE id_stock = $id_stock
");

// ========================
// 🔹 REGISTRAR MOVIMIENTO (CORRECTO)
// ========================
mysqli_query($conexion, "
    INSERT INTO almacen_movimientos
    (id_stock, tipo, cantidad, referencia)
    VALUES ($id_stock, 'Ingreso', $cantidad, 'Ingreso a almacén')
");

// ========================
// 🔹 REDIRIGIR
// ========================
header("Location: ../ingreso.php");
exit;
