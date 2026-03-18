<?php
include '../../../conexion.php';

header('Content-Type: application/json');

$id_grupo = intval($_GET['id_grupo'] ?? 0);

if ($id_grupo <= 0) {
    echo json_encode([]);
    exit;
}

$clientes = [];


// ================= KB =================

$queryKB = "
SELECT 
    d.nombre,
    d.apellido,
    'KB' AS tipo,

    o.nombre_servicio,
    o.fecha_salida,
    o.fecha_retorno

FROM clientes_kb k

JOIN Datos_clientes d 
    ON d.id_cliente = k.id_cliente

LEFT JOIN operaciones o
    ON o.id_cliente = k.id_cliente

WHERE k.id_grupo = $id_grupo
";

$kb = mysqli_query($conexion, $queryKB);

if ($kb) {
    while ($row = mysqli_fetch_assoc($kb)) {

        $row['nombre_servicio'] = $row['nombre_servicio'] ?? '-';
        $row['fecha_salida'] = $row['fecha_salida'] ?? '-';
        $row['fecha_retorno'] = $row['fecha_retorno'] ?? '-';

        $clientes[] = $row;
    }
}


// ================= ENDOSADORES =================

$queryEnd = "
SELECT 
    d.nombre,
    d.apellido,
    'Endosador' AS tipo,

    o.nombre_servicio,
    o.fecha_salida,
    o.fecha_retorno

FROM clientes_endosadores e

JOIN datos_clientes d 
    ON d.id_cliente = e.id_cliente

LEFT JOIN operaciones o
    ON o.id_cliente = e.id_cliente

WHERE e.id_grupo = $id_grupo
";

$end = mysqli_query($conexion, $queryEnd);

if ($end) {
    while ($row = mysqli_fetch_assoc($end)) {

        $row['nombre_servicio'] = $row['nombre_servicio'] ?? '-';
        $row['fecha_salida'] = $row['fecha_salida'] ?? '-';
        $row['fecha_retorno'] = $row['fecha_retorno'] ?? '-';

        $clientes[] = $row;
    }
}


echo json_encode($clientes);
exit;