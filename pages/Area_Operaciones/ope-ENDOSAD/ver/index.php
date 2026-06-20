<?php
include '../../../../conexion.php';
include 'queries/cargar_datos.php';
?>

<!DOCTYPE html>
<html lang="es">

<?php include 'partials/head.php'; ?>

<body>

<?php include '../../../sidebar.php'; ?>

<div class="kb-content">

    <div class="main-content">

<?php include __DIR__ . '/partials/cabecera.php'; ?>
<?php include __DIR__ . '/partials/resumen.php'; ?>
<?php include __DIR__ . '/partials/clientes.php'; ?>

<?php include __DIR__ . '/partials/tours.php'; ?>

<?php include __DIR__ . '/partials/pagos.php'; ?>

<?php include __DIR__ . '/partials/contabilidad.php'; ?>

<?php include __DIR__ . '/partials/planificacion.php'; ?>

    </div>

</div>

</body>
</html>