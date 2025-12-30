<?php
include('../../conexion.php');
include('../sidebar.php');

date_default_timezone_set('America/Lima');
$hoy = date('Y-m-d');

/* ===========================
   INGRESOS DEL DÍA
=========================== */
$sql = "
SELECT 
    c.id_contabilidad,
    c.id_operaciones,
    DATE(c.fecha_pago) AS fecha,
    c.monto_pagado,
    c.saldo_pendiente,
    c.metodo_pago,
    c.observacion,
    c.actualizado_en,
    CONCAT(d.nombre,' ',d.apellido) AS cliente
FROM Contabilidad c
LEFT JOIN Operaciones o ON o.id_operaciones = c.id_operaciones
LEFT JOIN Datos_clientes d ON d.id_cliente = o.id_cliente
WHERE DATE(c.fecha_pago) = '$hoy'
ORDER BY c.fecha_pago DESC
";

$res = mysqli_query($conexion, $sql);

/* ===========================
   TOTAL DEL DÍA
=========================== */
$total_sql = "
SELECT SUM(monto_pagado) AS total_dia
FROM Contabilidad
WHERE DATE(fecha_pago) = '$hoy'
";
$total = mysqli_fetch_assoc(mysqli_query($conexion, $total_sql))['total_dia'] ?? 0;
?>

<div class="container-fluid mt-4">
    <h3 class="mb-3">💰 Ingresos del Día (<?= date('d/m/Y') ?>)</h3>

    <!-- TOTAL -->
    <div class="alert alert-success">
        <strong>Total del día:</strong> S/. <?= number_format($total,2) ?>
    </div>

    <!-- TABLA -->
    <div class="card shadow">
        <div class="card-body">
            <table id="tablaIngresos" class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Cliente</th>
                        <th>Operación</th>
                        <th>Monto Pagado</th>
                        <th>Saldo</th>
                        <th>Método</th>
                        <th>Observación</th>
                        <th>Último Cambio</th>
                        <th>Aviso</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($row = mysqli_fetch_assoc($res)): ?>
                    <?php
                        // Detectar cambios
                        $aviso = '';
                        if (!empty($row['observacion']) || $row['saldo_pendiente'] > 0) {
                            $aviso = '<span class="badge bg-warning text-dark">Saldo modificado</span>';
                        } else {
                            $aviso = '<span class="badge bg-success">Pago completo</span>';
                        }
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($row['cliente']) ?></td>
                        <td>#<?= $row['id_operaciones'] ?></td>
                        <td>S/. <?= number_format($row['monto_pagado'],2) ?></td>
                        <td>S/. <?= number_format($row['saldo_pendiente'],2) ?></td>
                        <td><?= $row['metodo_pago'] ?></td>
                        <td><?= $row['observacion'] ?: '-' ?></td>
                        <td><?= $row['actualizado_en'] ?></td>
                        <td><?= $aviso ?></td>
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
    $('#tablaIngresos').DataTable({
        order: [[6, 'desc']],
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        }
    });
});
</script>
