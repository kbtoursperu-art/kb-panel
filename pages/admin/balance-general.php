<?php
include('../../conexion.php');
include('../sidebar.php');

date_default_timezone_set('America/Lima');

/* ==========================
   RESUMEN DEL MES ACTUAL
========================== */
$resumen_sql = "
SELECT
    SUM(monto_pagado) AS total_pagado,
    SUM(saldo_pendiente) AS total_pendiente,
    SUM(monto_pagado + saldo_pendiente) AS total_general
FROM Contabilidad
WHERE MONTH(fecha_pago) = MONTH(CURDATE())
AND YEAR(fecha_pago) = YEAR(CURDATE())
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
    c.id_contabilidad,
    o.id_operaciones,
    CONCAT(d.nombre,' ',d.apellido) AS cliente,
    c.monto_pagado,
    c.saldo_pendiente,
    (c.monto_pagado + c.saldo_pendiente) AS total_operacion,
    c.fecha_pago,
    c.actualizado_en
FROM Contabilidad c
LEFT JOIN Operaciones o ON o.id_operaciones = c.id_operaciones
LEFT JOIN Datos_clientes d ON d.id_cliente = o.id_cliente
WHERE MONTH(c.fecha_pago) = MONTH(CURDATE())
AND YEAR(c.fecha_pago) = YEAR(CURDATE())
ORDER BY c.fecha_pago DESC
";

$detalle = mysqli_query($conexion, $detalle_sql);
?>

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
                    <h6>Ingresos Totales</h6>
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
                        <th>Total Tour</th>
                        <th>Pagado</th>
                        <th>Pendiente</th>
                        <th>Estado</th>
                        <th>Última Actualización</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($row = mysqli_fetch_assoc($detalle)): ?>
                    <?php
                        if ($row['saldo_pendiente'] == 0) {
                            $estado = '<span class="badge bg-success">Pagado</span>';
                        } else {
                            $estado = '<span class="badge bg-danger">Pendiente</span>';
                        }
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($row['cliente']) ?></td>
                        <td>#<?= $row['id_operaciones'] ?></td>
                        <td>S/. <?= number_format($row['total_operacion'],2) ?></td>
                        <td>S/. <?= number_format($row['monto_pagado'],2) ?></td>
                        <td>S/. <?= number_format($row['saldo_pendiente'],2) ?></td>
                        <td><?= $estado ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($row['actualizado_en'])) ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
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
