<?php
include('../../conexion.php');
include('../sidebar.php');

date_default_timezone_set('America/Lima');
$hoy = date('Y-m-d');

/* ======================================
   INGRESOS DEL DÍA
======================================= */
$sql = "
SELECT 
    c.id_contabilidad,
    c.id_operaciones,
    c.fecha_pago_saldo AS fecha,
    c.pagado_a_cuenta,
    c.saldo_pendiente,
    c.metodo_pago,
    CONCAT(d.nombre, ' ', d.apellido) AS cliente
FROM Contabilidad c
LEFT JOIN Operaciones o ON o.id_operaciones = c.id_operaciones
LEFT JOIN Datos_clientes d ON d.id_cliente = o.id_cliente
WHERE DATE(c.fecha_pago_saldo) = '$hoy'
ORDER BY c.fecha_pago_saldo DESC
";

$res = mysqli_query($conexion, $sql);

/* ======================================
   TOTAL DEL DÍA
======================================= */
$total_sql = "
SELECT SUM(pagado_a_cuenta) AS total_dia
FROM Contabilidad
WHERE DATE(fecha_pago_saldo) = '$hoy'
";
$total = mysqli_fetch_assoc(mysqli_query($conexion, $total_sql))['total_dia'] ?? 0;
?>

<div class="content p-4">
<div class="container-fluid">

    <h3 class="mb-3 fw-bold">💰 Ingresos del Día (<?= date('d/m/Y') ?>)</h3>

    <div class="alert alert-success shadow-sm">
        <strong>Total del día:</strong> S/. <?= number_format($total,2) ?>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">

            <div class="table-responsive">
                <table id="tablaIngresos" class="table table-bordered table-striped">
                    <thead class="table-dark text-center">
                        <tr>
                            <th>Cliente</th>
                            <th>ID Operación</th>
                            <th>Pagado</th>
                            <th>Saldo Pendiente</th>
                            <th>Método</th>
                            <th>Aviso</th>
                        </tr>
                    </thead>
                    <tbody>

                    <?php while($row = mysqli_fetch_assoc($res)): ?>

                        <?php
                        $aviso = ($row['saldo_pendiente'] > 0)
                            ? '<span class="badge bg-warning text-dark">Saldo pendiente</span>'
                            : '<span class="badge bg-success">Pago completo</span>';
                        ?>

                        <tr>
                            <td><?= htmlspecialchars($row['cliente']) ?></td>
                            <td>#<?= $row['id_operaciones'] ?></td>
                            <td>S/. <?= number_format($row['pagado_a_cuenta'],2) ?></td>
                            <td>S/. <?= number_format($row['saldo_pendiente'],2) ?></td>
                            <td><?= $row['metodo_pago'] ?></td>
                            <td class="text-center"><?= $aviso ?></td>
                        </tr>

                    <?php endwhile; ?>

                    </tbody>
                </table>
            </div>

        </div>
    </div>

</div>
</div>

<script>
$(document).ready(function() {
    $('#tablaIngresos').DataTable({
        order: [[2, 'desc']],
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        }
    });
});
</script>
