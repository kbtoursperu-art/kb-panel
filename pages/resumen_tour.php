<?php
include '../conexion.php';

$anio = $_GET['anio'] ?? date('Y');

$sql = "

SELECT

YEAR(o.fecha_salida) AS anio,
MONTH(o.fecha_salida) AS mes,

o.nombre_servicio,

COUNT(DISTINCT o.id_operaciones) AS total_operaciones,

COUNT(DISTINCT kb.id_cliente) AS total_kb,
COUNT(DISTINCT e.id_cliente) AS total_endosadores,

COUNT(DISTINCT o.id_cliente) AS total_pasajeros,

SUM(c.precio_servicio) AS total_precio,
SUM(c.pagado_a_cuenta) AS total_pagado,
SUM(c.saldo_pendiente) AS total_saldo,

SUM(c.precio_servicio_adicional) AS total_adicional,
SUM(c.saldo_adicional) AS saldo_adicional

FROM operaciones o

LEFT JOIN contabilidad c
ON c.id_operaciones = o.id_operaciones

LEFT JOIN clientes_kb kb
ON kb.id_cliente = o.id_cliente

LEFT JOIN clientes_endosadores e
ON e.id_cliente = o.id_cliente

WHERE YEAR(o.fecha_salida) = '$anio'

GROUP BY
YEAR(o.fecha_salida),
MONTH(o.fecha_salida),
o.nombre_servicio

ORDER BY
anio DESC,
mes DESC,
o.nombre_servicio

";

$res = mysqli_query($conexion,$sql);

?>

<!DOCTYPE html>
<html>
<head>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body>

<div class="container mt-4">

<h3>RESUMEN POR MES</h3>

<form>

<input type="number" name="anio"
value="<?= $anio ?>">

<button>Filtrar</button>

</form>

<table class="table table-bordered">

<tr>

<th>Año</th>
<th>Mes</th>
<th>Tour</th>

<th>KB</th>
<th>Endos</th>
<th>Total</th>

<th>Total $</th>
<th>Pagado</th>
<th>Saldo</th>

<th>Adicional</th>

</tr>

<?php while($r = mysqli_fetch_assoc($res)){ ?>

<tr>

<td><?= $r['anio'] ?></td>
<td><?= $r['mes'] ?></td>

<td><?= $r['nombre_servicio'] ?></td>

<td><?= $r['total_kb'] ?></td>
<td><?= $r['total_endosadores'] ?></td>
<td><?= $r['total_pasajeros'] ?></td>

<td><?= $r['total_precio'] ?></td>
<td><?= $r['total_pagado'] ?></td>
<td><?= $r['total_saldo'] ?></td>

<td><?= $r['total_adicional'] ?></td>

</tr>

<?php } ?>

</table>

</div>

</body>
</html>