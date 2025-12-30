<?php
include('../../conexion.php');
include('../sidebar.php');

date_default_timezone_set('America/Lima');

/* ===========================
   SALDOS PENDIENTES
=========================== */
$sql = "
SELECT 
    c.id_contabilidad,
    c.id_operaciones,
    c.fecha_pago,
    c.saldo_pendiente,
    c.monto_pagado,
    c.metodo_pago,
    c.observacion,
    c.actualizado_en,
    CONCAT(d.nombre,' ',d.apellido) AS cliente
FROM Contabilidad c
LEFT JOIN Operaciones o ON o.id_operaciones = c.id_operaciones
LEFT JOIN Datos_clientes d ON d.id_cliente = o.id_cliente
WHERE c.saldo_pendiente > 0
ORDER BY c.fecha_pago ASC
";

$res = mysqli_query($conexion, $sql);

/* ===========================
   TOTAL DE SALDOS
=========================== */
$total_sql = "
SELECT SUM(saldo_pendiente) AS total_saldo
FROM Contabilidad
WHERE saldo_pendiente > 0
";
$total_saldo = mysqli_fetch_assoc(mysqli_query($conexion, $total_sql))['total_saldo'] ?? 0;
?>

<div class="container-fluid mt-4">
    <h3 class="mb-3">⚠️ Saldos Pendientes</h3>

    <!-- TOTAL -->
    <div class="alert alert-warning">
        <strong>Total adeudado:</strong> S/. <?= number_format($total_saldo,2) ?>
    </div>

    <!-- TABLA -->
    <div class="card shadow">
        <div class="card-body">
            <table id="tablaSaldos" class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Cliente</th>
                        <th>Operación</th>
                        <th>Saldo Pendiente</th>
                        <th>Pagado</th>
                        <th>Fecha Deuda</th>
                        <th>Días en Mora</th>
                        <th>Método</th>
                        <th>Observación</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($row = mysqli_fetch_assoc($res)): ?>
                    <?php
                        $fecha_deuda = new DateTime($row['fecha_pago']);
                        $hoy = new DateTime();
                        $dias = $fecha_deuda->diff($hoy)->days;

                        // Estado visual
                        if ($dias <= 3) {
                            $estado = '<span class="badge bg-info">Reciente</span>';
                        } elseif ($dias <= 7) {
                            $estado = '<span class="badge bg-warning text-dark">Pendiente</span>';
                        } else {
                            $estado = '<span class="badge bg-danger">Crítico</span>';
                        }
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($row['cliente']) ?></td>
                        <td>#<?= $row['id_operaciones'] ?></td>
                        <td>S/. <?= number_format($row['saldo_pendiente'],2) ?></td>
                        <td>S/. <?= number_format($row['monto_pagado'],2) ?></td>
                        <td><?= date('d/m/Y', strtotime($row['fecha_pago'])) ?></td>
                        <td><?= $dias ?> días</td>
                        <td><?= $row['metodo_pago'] ?></td>
                        <td><?= $row['observacion'] ?: '-' ?></td>
                        <td><?= $estado ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- DATATABLE -->
<script>
$(document).ready(function() {
    $('#tablaSaldos').DataTable({
        order: [[5, 'desc']],
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        }
    });
});
</script>
