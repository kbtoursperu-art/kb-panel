<?php
include '../../conexion.php';

// Charset seguro
if (function_exists('mysqli_set_charset')) {
    mysqli_set_charset($conexion, 'utf8mb4');
}

// ✅ Validar ID de contabilidad
if (!isset($_GET['id'])) {
    die("❌ Falta el ID de contabilidad");
}

$id_contabilidad = (int)$_GET['id'];

// ====================== CONSULTA PRINCIPAL ======================
$query = "

SELECT 

c.id_contabilidad,

o.id_operaciones,
o.id_grupo,
o.Encargado,
o.observaciones,

g.nombre_grupo,

CONCAT(d.nombre,' ',d.apellido) AS cliente,
d.nro_pasaporte,

od.nombre_servicio,
od.fecha_salida,
od.fecha_retorno,
od.modalidad_retorno,
od.incluye_ingreso,
od.servicio_adicional,

c.precio_servicio,
c.precio_servicio_adicional,
c.pagado_a_cuenta,
c.pagado_adicional,
c.saldo_adicional,
c.saldo_pendiente,
c.estado,
c.nro_boleta_total,
c.metodo_pago,
c.metodo_pago_adicional,
c.tipo_moneda,
c.tipo_moneda_adicional,
c.fecha_pago_saldo,
c.monto_pago_saldo

FROM contabilidad c

LEFT JOIN operaciones o 
    ON o.id_operaciones = c.id_operaciones

LEFT JOIN operaciones_detalle od
    ON od.id_operaciones = o.id_operaciones

LEFT JOIN grupos g
    ON g.id_grupo = o.id_grupo

LEFT JOIN datos_clientes d
    ON d.id_cliente = o.id_cliente

WHERE c.id_contabilidad = $id_contabilidad

ORDER BY od.fecha_salida ASC
LIMIT 1
";
$resultado = mysqli_query($conexion, $query);
if (!$resultado) die("Error en la consulta: " . mysqli_error($conexion));

$row = mysqli_fetch_assoc($resultado);
$id_grupo = $row['id_grupo'] ?? 0;

$clientes = [];

if ($id_grupo > 0) {

$qClientes = mysqli_query($conexion, "

SELECT 
d.nombre,
d.apellido,
d.nro_pasaporte

FROM clientes_kb kb

LEFT JOIN datos_clientes d
ON d.id_cliente = kb.id_cliente

WHERE kb.id_grupo = $id_grupo

ORDER BY d.nombre ASC

");

while ($c = mysqli_fetch_assoc($qClientes)) {
    $clientes[] = $c;
}

}
if (!$row) die("❌ No se encontró la operación");

// Calcular totales
$total_servicio = ($row['precio_servicio'] ?? 0) + ($row['precio_servicio_adicional'] ?? 0);
$total_pagado = ($row['pagado_a_cuenta'] ?? 0) + ($row['pagado_adicional'] ?? 0) + ($row['saldo_adicional'] ?? 0);
$saldo = $total_servicio - $total_pagado;

// Estado
$badge = match ($row['estado']) {
    'pagado' => 'bg-success',
    'pendiente' => 'bg-warning text-dark',
    'reembolsado' => 'bg-danger',
    default => 'bg-secondary'
};
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Ver Operación - KB Adventures</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.label-box { max-height: 60px; overflow: auto; white-space: normal; }
</style>
</head>
<body>
<div class="container py-4">
    <h3 class="mb-4">👁 Ver Operación</h3>
    
    <div class="card mb-3">
        <div class="card-header bg-primary text-white">Datos del Cliente</div>
        <div class="card-body">
            <p><strong>Clientes del grupo:</strong></p>

<?php if (!empty($clientes)) { ?>

<ul class="mb-2">

<?php foreach ($clientes as $c) { ?>

<li>
<?= htmlspecialchars($c['nombre'] . ' ' . $c['apellido']) ?>
- <?= htmlspecialchars($c['nro_pasaporte'] ?? '-') ?>
</li>

<?php } ?>

</ul>

<?php } else { ?>

-

<?php } ?>

            <p><strong>Grupo:</strong> <?= htmlspecialchars($row['nombre_grupo'] ?? '-') ?></p>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-info text-white">Detalles de la Operación</div>
        <div class="card-body">
            <p><strong>Servicio Principal:</strong> <?= htmlspecialchars($row['nombre_servicio'] ?? '-') ?></p>
            <p><strong>Servicio Adicional:</strong> <?= htmlspecialchars($row['servicio_adicional'] ?? '-') ?></p>
            <p><strong>Salida:</strong> <?= $row['fecha_salida'] ?? '-' ?></p>
            <p><strong>Retorno:</strong> <?= $row['fecha_retorno'] ?? '-' ?> (<?= $row['modalidad_retorno'] ?? '-' ?>)</p>
            <p><strong>Incluye Ingreso:</strong> <?= $row['incluye_ingreso'] ?? '-' ?></p>
            <p><strong>Encargado:</strong> <?= $row['Encargado'] ?? '-' ?></p>
            <p><strong>Observaciones:</strong> <div class="label-box"><?= htmlspecialchars($row['observaciones'] ?? '-') ?></div></p>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-success text-white">Contabilidad</div>
        <div class="card-body">
            <p><strong>Total Servicio:</strong> <?= number_format($total_servicio,2) ?></p>
            <p><strong>Total Pagado:</strong> <?= number_format($total_pagado,2) ?></p>
            <p><strong>Saldo:</strong> <?= number_format($saldo,2) ?></p>
            <p><strong>Estado:</strong> <span class="badge <?= $badge ?>"><?= strtoupper($row['estado']) ?></span></p>
            <p><strong>Comprobante:</strong> <?= htmlspecialchars($row['nro_boleta_total'] ?? '-') ?></p>
            <p><strong>Método de Pago:</strong> <?= htmlspecialchars($row['metodo_pago'] ?? '-') ?> / <?= htmlspecialchars($row['metodo_pago_adicional'] ?? '-') ?></p>
            <p><strong>Tipo de Moneda:</strong> <?= htmlspecialchars($row['tipo_moneda'] ?? '-') ?> / <?= htmlspecialchars($row['tipo_moneda_adicional'] ?? '-') ?></p>
            <p><strong>Fecha de Pago de Saldo:</strong> <?= $row['fecha_pago_saldo'] ?? '-' ?></p>
            <p><strong>Monto Pagado de Saldo:</strong> <?= number_format($row['monto_pago_saldo'] ?? 0,2) ?></p>
        </div>
    </div>

    <a href="index.php" class="btn btn-secondary">⬅ Volver</a>
</div>
</body>
</html>