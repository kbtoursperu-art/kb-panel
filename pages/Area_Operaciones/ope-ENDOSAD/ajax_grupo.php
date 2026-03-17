<?php
include '../../../conexion.php';
header('Content-Type: application/json');

$id_grupo = intval($_GET['id_grupo'] ?? 0);
$clientes = [];

if($id_grupo > 0){

    $sql = "

    SELECT 
        d.id_cliente, 
        d.nombre, 
        d.apellido, 
        d.nro_pasaporte,

        e.empresa_endosadora,

        o.nombre_servicio,
        o.fecha_salida,
        o.fecha_retorno

    FROM clientes_endosadores e

    INNER JOIN datos_clientes d 
        ON e.id_cliente = d.id_cliente

    LEFT JOIN operaciones o 
        ON o.id_cliente = (
            SELECT id_cliente
            FROM clientes_endosadores
            WHERE id_grupo = ?
            LIMIT 1
        )

    WHERE e.id_grupo = ?
      AND d.tipo_cliente='END'

    ORDER BY o.fecha_salida ASC, d.nombre ASC

    ";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ii", $id_grupo, $id_grupo);
    $stmt->execute();
    $res = $stmt->get_result();

    while($row = $res->fetch_assoc()){
        $clientes[] = $row;
    }
}

echo json_encode($clientes, JSON_UNESCAPED_UNICODE);