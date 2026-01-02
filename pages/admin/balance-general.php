<?php
include('../../conexion.php');
include('../sidebar.php');

date_default_timezone_set('America/Lima');

/* ==========================
   RESUMEN DEL MES ACTUAL
========================== */
$resumen_sql = "
SELECT
    SUM(c.pagado_a_cuenta + IF(c.estado='pagado', c.saldo_pendiente, 0)) AS total_pagado,
    SUM(c.saldo_pendiente) AS total_pendiente,
    SUM(c.precio_servicio) AS total_general
FROM Contabilidad c
WHERE MONTH(c.fecha_pago_saldo) = MONTH(CURDATE())
AND YEAR(c.fecha_pago_saldo) = YEAR(CURDATE())
";

$resumen = mysqli_fetch_assoc(mysqli_query($conexion, $resumen_sql));

$total_pagado    = $resumen['total_pagado'] ?? 0;
$total_pendiente = $resumen['total_pendiente'] ?? 0;
$total_general   = $resumen['total_general'] ?? 0;

/* ==========================
   DETALLE POR OPERACIÓN
========================== */
$detalle_sql = "
SELECT 
    o.id_operaciones,
    CONCAT(d.nombre,' ',d.apellido) AS cliente,
    c.precio_servicio,
    c.pagado_a_cuenta,
    c.saldo_pendiente,
    c.fecha_pago_saldo,
    c.estado
FROM Contabilidad c
LEFT JOIN Operaciones o ON o.id_operaciones = c.id_operaciones
LEFT JOIN Datos_clientes d ON d.id_cliente = o.id_cliente
WHERE MONTH(c.fecha_pago_saldo) = MONTH(CURDATE())
AND YEAR(c.fecha_pago_saldo) = YEAR(CURDATE())
ORDER BY c.fecha_pago_saldo DESC
";

$detalle = mysqli_query($conexion, $detalle_sql);
?>

<div class="content p-4">
<div class="container-fluid mt-4">

    <h3>📊 Balance General — Mes Actual</h3>

    <!-- RESUMEN -->
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card text-white bg-success shadow">
                <div class="card-body">
                    <h6>Ingresos Cobrados</h6>
                    <h4>S/. <?= number_format($total_pagado,2) ?></h4>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card text-dark bg-warning shadow">
                <div class="card-body">
                    <h6>Saldos Pendientes</h6>
                    <h4>S/. <?= number_format($total_pendiente,2) ?></h4>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card text-white bg-primary shadow">
                <div class="card-body">
                    <h6>Ingresos Totales (Tours)</h6>
                    <h4>S/. <?= number_format($total_general,2) ?></h4>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card text-white bg-dark shadow">
                <div class="card-body">
                    <h6>Balance Final</h6>
                    <h4>S/. <?= number_format($total_pagado - $total_pendiente,2) ?></h4>
                </div>
            </div>
        </div>
    </div>

    <!-- DETALLE -->
    <div class="card shadow mt-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">📋 Detalle Financiero por Operación</h5>
        </div>

        <div class="card-body">
            <table id="tablaBalance" class="table table-bordered table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Cliente</th>
                        <th>Operación</th>
                        <th>Precio Servicio</th>
                        <th>Pagado a Cuenta</th>
                        <th>Saldo Pendiente</th>
                        <th>Estado</th>
                        <th>Fecha Pago</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($row = mysqli_fetch_assoc($detalle)): ?>
                    <?php
                        $estado = ($row['estado'] === 'pagado') 
                            ? '<span class="badge bg-success">Pagado</span>'
                            : '<span class="badge bg-danger">Pendiente</span>';
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($row['cliente']) ?></td>
                        <td>#<?= $row['id_operaciones'] ?></td>
                        <td>S/. <?= number_format($row['precio_servicio'],2) ?></td>
                        <td>S/. <?= number_format($row['pagado_a_cuenta'],2) ?></td>
                        <td>S/. <?= number_format($row['saldo_pendiente'],2) ?></td>
                        <td><?= $estado ?></td>
                        <td><?= date('d/m/Y', strtotime($row['fecha_pago_saldo'])) ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
</div>

<script>
$(document).ready(function() {
    $('#tablaBalance').DataTable({
        order: [[6, 'desc']],
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        }
    });
});
</script>
