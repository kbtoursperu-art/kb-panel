<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../../../../conexion.php';

if (!isset($_GET['id_grupo'])) {
    die("Falta id_grupo");
}

$id_grupo = intval($_GET['id_grupo']);

/* ================= GRUPO ================= */
$qGrupo = mysqli_query($conexion,"
    SELECT g.*, 
           COUNT(DISTINCT cg.id_cliente) AS total_clientes
    FROM grupos g
    LEFT JOIN clientes_grupo cg ON cg.id_grupo = g.id_grupo
    WHERE g.id_grupo = $id_grupo
    GROUP BY g.id_grupo
");
$grupo = mysqli_fetch_assoc($qGrupo);
if (!$grupo) die("Grupo no encontrado.");

/* ================= CLIENTES ================= */
$qClientes = mysqli_query($conexion,"
    SELECT 
        d.id_cliente,
        d.nombre,
        d.apellido,
        d.dni,
        d.nro_pasaporte,
        d.telefono,
        d.email,
        d.nacionalidad,
        d.hotel,
        d.comida,
        d.genero,
        d.fecha_nacimiento,
        cg.es_pagador,
        cg.tipo_cliente,
        cg.empresa_endosadora
    FROM clientes_grupo cg
    JOIN datos_clientes d ON d.id_cliente = cg.id_cliente
    WHERE cg.id_grupo = $id_grupo
    ORDER BY cg.es_pagador DESC, d.apellido ASC
");

/* ================= ULTIMA OPERACION ================= */
$qOperacion = mysqli_query($conexion,"
    SELECT o.*,
           dc.nombre AS nombre_cliente,
           dc.apellido AS apellido_cliente
    FROM operaciones o
    JOIN datos_clientes dc ON dc.id_cliente = o.id_cliente
    WHERE o.id_grupo = $id_grupo
    ORDER BY o.id_operaciones DESC
    LIMIT 1
");
$op = mysqli_fetch_assoc($qOperacion);
$id_operacion = $op ? intval($op['id_operaciones']) : 0;

/* ================= CONTABILIDAD ================= */
$conta = [];
if ($id_operacion > 0) {
    $qConta = mysqli_query($conexion,"
        SELECT * FROM contabilidad
        WHERE id_operaciones = $id_operacion
        ORDER BY id_contabilidad DESC
        LIMIT 1
    ");
    $conta = mysqli_fetch_assoc($qConta) ?: [];
    if ($conta) $op = array_merge($op, $conta);
}

/* ================= DETALLE DE TOURS ================= */
$qDetalle = mysqli_query($conexion,"
    SELECT 
        od.*,
        s.nombre AS nombre_servicio,
        s.duracion_dias,
        DATEDIFF(od.fecha_retorno, od.fecha_salida) AS dias_calculados
    FROM operaciones_detalle od
    LEFT JOIN servicios s ON s.id_servicio = od.id_servicio
    WHERE od.id_operaciones = $id_operacion
    ORDER BY od.fecha_salida ASC
");

/* ================= ADICIONALES POR DETALLE ================= */
$adicionales = [];
if ($id_operacion > 0) {
    $qAdi = mysqli_query($conexion,"
        SELECT ad.*, od.id_operaciones
        FROM adicionales_detalle ad
        JOIN operaciones_detalle od ON od.id_detalle = ad.id_detalle
        WHERE od.id_operaciones = $id_operacion
    ");
    while ($a = mysqli_fetch_assoc($qAdi)) {
        $adicionales[$a['id_detalle']][] = $a;
    }
}

/* ================= PAGOS (todos) ================= */
$qPagos = mysqli_query($conexion,"
    SELECT * FROM pagos
    WHERE id_operaciones = $id_operacion
    ORDER BY fecha ASC, id_pago ASC
");
$todos_pagos = [];
while ($p = mysqli_fetch_assoc($qPagos)) {
    $todos_pagos[] = $p;
}

/* ================= TOTALES ================= */
$total_pagado = 0;
$total_soles   = 0;
$total_dolares = 0;
foreach ($todos_pagos as $p) {
    if (!in_array($p['tipo'], ['reembolso'])) {
        if ($p['moneda'] === 'Soles')   $total_soles   += $p['monto'];
        if ($p['moneda'] === 'Dólares') $total_dolares += $p['monto'];
    }
}

/* ================= PLANIFICACION ================= */
$qPlan = mysqli_query($conexion,"
    SELECT * FROM planificacion
    WHERE id_grupo = $id_grupo
    ORDER BY id_planificacion DESC
    LIMIT 1
");
$plan = mysqli_fetch_assoc($qPlan) ?: [];
?>