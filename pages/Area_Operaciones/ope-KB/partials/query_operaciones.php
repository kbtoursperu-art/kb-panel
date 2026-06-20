<?php

$search_from = $_GET['search_date_from'] ?? '';
$search_to   = $_GET['search_date_to'] ?? '';

$query = "
SELECT 
    g.id_grupo,
    g.nombre_grupo,

    COUNT(DISTINCT cg.id_cliente) AS pasajeros,
    MIN(cg.id_cliente) AS primer_cliente_id,

    MIN(CONCAT(dc.nombre,' ',dc.apellido)) AS primer_cliente,

    op.id_operaciones,
    op.observaciones,
    op.encargado,
    op.estado,

    det.nombre_servicio,
    det.fecha_salida,
    det.fecha_retorno,

    COALESCE(det.total_soles,0) AS total_soles,
    COALESCE(det.total_dolares,0) AS total_dolares,

    COALESCE(pag.pagado_soles,0) AS pagado_soles,
    COALESCE(pag.pagado_dolares,0) AS pagado_dolares,

    (COALESCE(det.total_soles,0) - COALESCE(pag.pagado_soles,0)) AS saldo_soles,
    (COALESCE(det.total_dolares,0) - COALESCE(pag.pagado_dolares,0)) AS saldo_dolares

FROM grupos g

JOIN clientes_grupo cg 
    ON cg.id_grupo = g.id_grupo
    AND cg.tipo_cliente = 'KB'

LEFT JOIN datos_clientes dc
    ON dc.id_cliente = cg.id_cliente

LEFT JOIN (
    SELECT 
        o.id_grupo,
        MAX(o.id_operaciones) AS id_operaciones,
        MAX(o.observaciones) AS observaciones,
        MAX(o.encargado) AS encargado,
        MAX(o.estado) AS estado
    FROM operaciones o
    GROUP BY o.id_grupo
) op ON op.id_grupo = g.id_grupo

LEFT JOIN (
    SELECT 
        x.id_grupo,

        GROUP_CONCAT(s.nombre SEPARATOR '<br>') AS nombre_servicio,
        GROUP_CONCAT(x.fecha_salida SEPARATOR '<br>') AS fecha_salida,
        GROUP_CONCAT(x.fecha_retorno SEPARATOR '<br>') AS fecha_retorno,

        SUM(CASE WHEN x.tipo_moneda = 'Soles' THEN x.precio ELSE 0 END) AS total_soles,
        SUM(CASE WHEN x.tipo_moneda = 'Dólares' THEN x.precio ELSE 0 END) AS total_dolares

    FROM (
        SELECT 
            o.id_grupo,
            od.fecha_salida,
            od.fecha_retorno,
            od.precio,
            od.tipo_moneda,
            od.id_servicio

        FROM operaciones o

        INNER JOIN operaciones_detalle od 
            ON od.id_operaciones = o.id_operaciones
    ) x

    LEFT JOIN servicios s 
        ON s.id_servicio = x.id_servicio

    GROUP BY x.id_grupo

) det ON det.id_grupo = g.id_grupo

LEFT JOIN (
    SELECT 
        o.id_grupo,

        SUM(
            CASE 
                WHEN p.moneda = 'Soles'
                     AND p.tipo = 'tour'
                    THEN p.monto

                WHEN p.moneda = 'Soles'
                     AND p.tipo = 'reembolso'
                    THEN -p.monto

                ELSE 0
            END
        ) AS pagado_soles,

        SUM(
            CASE 
                WHEN p.moneda = 'Dólares'
                     AND p.tipo = 'tour'
                    THEN p.monto

                WHEN p.moneda = 'Dólares'
                     AND p.tipo = 'reembolso'
                    THEN -p.monto

                ELSE 0
            END
        ) AS pagado_dolares

    FROM operaciones o

    LEFT JOIN pagos p 
        ON p.id_operaciones = o.id_operaciones

    GROUP BY o.id_grupo

) pag ON pag.id_grupo = g.id_grupo

WHERE 1=1
";

if (!empty($search_from) && !empty($search_to)) {

    $query .= " AND EXISTS (
        SELECT 1 
        FROM operaciones o2
        WHERE o2.id_grupo = g.id_grupo
        AND DATE(o2.fecha_reserva)
            BETWEEN '$search_from' AND '$search_to'
    )";
}

$query .= "
GROUP BY g.id_grupo
ORDER BY g.id_grupo ASC
";