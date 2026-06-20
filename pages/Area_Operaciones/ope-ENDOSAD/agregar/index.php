<?php
ob_start();
include '../../../../conexion.php';

if (!isset($_GET['id_cliente'])) {
    die("Error: Falta el ID del cliente.");
}

$id_cliente = (int) $_GET['id_cliente'];

// Datos
include 'actions/cargar_datos.php';

// Guardar
include 'actions/guardar.php';
include 'partials/helpers.php';
?>

<!DOCTYPE html>
<html lang="es">

<?php include 'partials/head.php'; ?>

<body>

<?php include '../../../sidebar.php'; ?>

<div class="kb-content">
<?php include 'partials/encabezado.php'; ?>

<form method="POST" id="formOp">

    <?php include 'partials/datos_generales.php'; ?>

    <?php include 'partials/tabla_tours.php'; ?>

    <?php include 'partials/resumen.php'; ?>

    <?php include 'partials/tabla_pagos.php'; ?>

    <button type="submit" class="kb-submit">

        Guardar operación

    </button>

</form>

</div>

<?php include 'partials/scripts.php'; ?>

</body>
</html>