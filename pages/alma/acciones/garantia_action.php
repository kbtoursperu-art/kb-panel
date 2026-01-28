<?php
include '../../../conexion.php';

$id_guia = intval($_POST['id_guia']);
$monto = floatval($_POST['monto']);

// 🔹 MARCAR TODAS LAS SALIDAS DE PRODUCTOS CON GARANTÍA COMO DEVUELTAS
mysqli_query($conexion, "
UPDATE almacen_salidas s
JOIN almacen_stock st ON s.id_stock = st.id_stock
JOIN almacen_items i ON st.id_item = i.id_item
SET s.estado = 'Devuelto'
WHERE s.id_guia = $id_guia
AND i.tipo = 'Garantia'
");

// 🔹 REGISTRAR MOVIMIENTO DE DEVOLUCIÓN<?php
include '../../../conexion.php';

// 🔹 DATOS DEL FORMULARIO
$nombre_guia = mysqli_real_escape_string($conexion, $_POST['nombre_guia']);
$monto       = floatval($_POST['monto']);

// 🔹 Actualizar las salidas: restar la garantía devuelta
mysqli_query($conexion, "
    UPDATE almacen_salidas
    SET garantia = 0,
        estado = 'Devuelto'
    WHERE nombre_guia = '$nombre_guia'
      AND garantia > 0
");

// 🔹 Registrar movimiento de devolución de garantía
mysqli_query($conexion, "
    INSERT INTO almacen_movimientos
    (tipo, cantidad, monto, referencia)
    VALUES ('Garantia', 0, $monto, 'Devolución de garantía a $nombre_guia')
");

// 🔹 Redirigir al reporte
header("Location: ../reporte_garantias_guias.php");
exit;

mysqli_query($conexion, "
INSERT INTO almacen_movimientos
(id_stock, tipo, cantidad, monto, referencia)
SELECT s.id_stock, 'Garantia', 0, $monto, CONCAT('Devolución a guía ', p.nombre_guia)
FROM almacen_salidas s
JOIN Planificacion p ON s.id_guia = p.id_guia
JOIN almacen_stock st ON s.id_stock = st.id_stock
JOIN almacen_items i ON st.id_item = i.id_item
WHERE s.id_guia = $id_guia
AND i.tipo = 'Garantia'
GROUP BY s.id_guia
");

// 🔹 REDIRECCIONAR AL REPORTE
header("Location: ../reporte_garantias_guias.php");
exit;
?>
