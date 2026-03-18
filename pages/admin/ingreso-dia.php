<?php
include('../../conexion.php');
include('../sidebar.php');

date_default_timezone_set('America/Lima');
$hoy = date('Y-m-d');

/* ===============================
   INGRESOS DEL DÍA
================================ */
$sql = "
SELECT 
    c.id_operaciones,
    c.fecha_pago_saldo,
    CONCAT(d.nombre,' ',d.apellido) AS cliente,

    c.metodo_pago,
    c.tipo_moneda,
    IFNULL(c.pagado_a_cuenta,0) AS pagado_general,

    c.metodo_pago_adicional,
    c.tipo_moneda_adicional,
    IFNULL(c.pagado_adicional,0) AS pagado_adicional,

    c.metodo_pago_saldo,
    c.tipo_moneda_saldo,
    IFNULL(c.monto_pago_saldo,0) AS pagado_saldo

FROM contabilidad c
LEFT JOIN operaciones o ON o.id_operaciones = c.id_operaciones
LEFT JOIN datos_clientes d ON d.id_cliente = o.id_cliente
WHERE DATE(c.fecha_pago_saldo) = '$hoy'
ORDER BY c.fecha_pago_saldo DESC
";

$res = mysqli_query($conexion, $sql);

/* ===============================
   TOTALES
================================ */
$total_soles   = 0;
$total_dolares = 0;
?>

<div class="content p-4">
<div class="container-fluid">

<h3 class="mb-3 fw-bold">💰 Ingresos del Día (<?= date('d/m/Y') ?>)</h3>

<div class="row mb-3">
    <div class="col-md-6">
        <div class="alert alert-success shadow-sm">
            <strong>Total Soles:</strong> S/. <?= number_format($total_soles,2) ?>
        </div>
    </div>
    <div class="col-md-6">
        <div class="alert alert-primary shadow-sm">
            <strong>Total Dólares:</strong> $ <?= number_format($total_dolares,2) ?>
        </div>
    </div>
</div>

<div class="card shadow-sm">
<div class="card-body">

<div class="table-responsive">
<table id="tablaIngresos" class="table table-bordered table-striped">

<thead class="table-dark text-center">
<tr>
    <th>Cliente</th>
    <th>ID Operación</th>
    <th>Tipo</th>
    <th>Método</th>
    <th>Moneda</th>
    <th>Monto</th>
</tr>
</thead>

<tbody>
<?php while($row = mysqli_fetch_assoc($res)): ?>

<?php
$pagos = [
    ['GENERAL',   $row['metodo_pago'],          $row['tipo_moneda'],          $row['pagado_general']],
    ['ADICIONAL', $row['metodo_pago_adicional'], $row['tipo_moneda_adicional'], $row['pagado_adicional']],
    ['SALDO',     $row['metodo_pago_saldo'],     $row['tipo_moneda_saldo'],     $row['pagado_saldo']],
];

foreach ($pagos as $p) {

    if ($p[3] <= 0) continue;

    if ($p[2] === 'Soles') {
        $total_soles += $p[3];
        $simbolo = 'S/.';
    } elseif ($p[2] === 'Dólares') {
        $total_dolares += $p[3];
        $simbolo = '$';
    } else {
        continue;
    }

    echo "
    <tr>
        <td>".htmlspecialchars($row['cliente'])."</td>
        <td>#{$row['id_operaciones']}</td>
        <td>{$p[0]}</td>
        <td>{$p[1]}</td>
        <td>{$p[2]}</td>
        <td class='text-end'>{$simbolo} ".number_format($p[3],2)."</td>
    </tr>
    ";
}
?>

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
        order: [[5, 'desc']],
        language: {
            url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
        }
    });
});
</script>
