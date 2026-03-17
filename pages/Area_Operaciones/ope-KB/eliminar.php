<?php
include '../../../conexion.php';

if (!isset($_GET['id'])) {
    die("ID no recibido");
}

$id = (int)$_GET['id'];


// 🔹 obtener cliente del tour
$q = mysqli_query($conexion, "
SELECT id_cliente
FROM Operaciones
WHERE id_operaciones = $id
");

if (!$q || mysqli_num_rows($q) == 0) {
    die("Tour no encontrado");
}

$d = mysqli_fetch_assoc($q);
$id_cliente = $d['id_cliente'];


// 🔹 ver si este tour tiene contabilidad
$qConta = mysqli_query($conexion, "
SELECT *
FROM Contabilidad
WHERE id_operaciones = $id
");

$tieneConta = mysqli_num_rows($qConta);


// 🔹 eliminar tour
mysqli_query($conexion, "
DELETE FROM Operaciones
WHERE id_operaciones = $id
");


// 🔹 si tenía contabilidad → mover al siguiente tour
if ($tieneConta) {

    $qNext = mysqli_query($conexion, "
    SELECT id_operaciones
    FROM Operaciones
    WHERE id_cliente = $id_cliente
    ORDER BY id_operaciones ASC
    LIMIT 1
    ");

    if (mysqli_num_rows($qNext) > 0) {

        $n = mysqli_fetch_assoc($qNext);
        $nuevo = $n['id_operaciones'];

        mysqli_query($conexion, "
        UPDATE Contabilidad
        SET id_operaciones = $nuevo
        WHERE id_operaciones = $id
        ");

    } else {

        // si ya no hay tours → borrar contabilidad

        mysqli_query($conexion, "
        DELETE FROM Contabilidad
        WHERE id_operaciones = $id
        ");

    }
}


// volver a editar cliente
header("Location: index.php");
exit;