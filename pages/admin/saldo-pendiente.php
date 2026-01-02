<?php
include('../../conexion.php');
include('../sidebar.php');

date_default_timezone_set('America/Lima');

$sql = "
SELECT 
    c.id_contabilidad,
    c.id_operaciones,
    c.fecha_pago_saldo,
    c.saldo_pendiente,
    c.pagado_a_cuenta,
    c.metodo_pago,
    CONCAT(d.nombre,' ',d.apellido) AS cliente
FROM Contabilidad c
LEFT JOIN Operaciones o ON o.id_operaciones = c.id_operaciones
LEFT JOIN Datos_clientes d ON d.id_cliente = o.id_cliente
WHERE c.saldo_pendiente > 0
ORDER BY c.fecha_pago_saldo ASC
";

$res = mysqli_query($conexion, $sql);

$total_sql = "
SELECT SUM(saldo_pendiente) AS total_saldo
FROM Contabilidad
WHERE saldo_pendiente > 0
";
$total_saldo = mysqli_fetch_assoc(mysqli_query($conexion, $total_sql))['total_saldo'] ?? 0;
?>

<div class="content p-4">
<div class="container-fluid mt-4">

    <h3 class="mb-3">⚠️ Saldos Pendientes</h3>

    <div class="alert alert-warning">
        <strong>Total adeudado:</strong> S/. <?= number_format($total_saldo,2) ?>
    </div>

    <div class="card shadow">
        <div class="card-body">
            <table id="tablaSaldos" class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Cliente</th>
                        <th>Operación</th>
                        <th>Saldo Pendiente</th>
                        <th>Pagado a Cuenta</th>
                        <th>Fecha Deuda</th>
                        <th>Días en Mora</th>
                        <th>Método</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($row = mysqli_fetch_assoc($res)): ?>
                    <?php
                        $fecha_deuda = new DateTime($row['fecha_pago_saldo']);
                        $hoy = new DateTime();
                        $dias = $fecha_deuda->diff($hoy)->days;

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
                        <td>S/. <?= number_format($row['pagado_a_cuenta'],2) ?></td>
                        <td><?= date('d/m/Y', strtotime($row['fecha_pago_saldo'])) ?></td>
                        <td><?= $dias ?> días</td>
                        <td><?= $row['metodo_pago'] ?></td>
                        <td><?= $estado ?></td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>
</div>
