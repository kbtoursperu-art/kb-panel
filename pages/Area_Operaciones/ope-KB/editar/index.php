<?php
ob_start();

include '../../../../conexion.php';

if (!isset($_GET['id'])) {
    die("Falta ID");
}

$id_operacion = (int) $_GET['id'];

/*
|--------------------------------------------------------------------------
| GUARDAR
|--------------------------------------------------------------------------
*/
include 'actions/guardar.php';

/*
|--------------------------------------------------------------------------
| CARGAR DATOS
|--------------------------------------------------------------------------
*/
include 'queries/cargar_operacion.php';
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <?php include 'partials/head.php'; ?>
</head>

<body class="bg-light">

    <?php include '../../../sidebar.php'; ?>

    <div class="kb-content">

        <div class="card shadow border-0">

            <div class="card-body">

                <form method="POST" id="formOp">

                    <input type="hidden"
                           name="id_operacion"
                           value="<?= $id_operacion ?>">
                    <?php include 'partials/encabezado.php'; ?>
                    <?php include 'partials/datos_generales.php'; ?>
 
                    <?php include 'partials/tabla_tours.php'; ?>
                    <?php include 'partials/resumen.php'; ?>
                   <?php include 'partials/tabla_pagos.php'; ?>

                    <?php include 'partials/templates.php'; ?>
                    

                    <?php include 'partials/footer_botones.php'; ?>
                </form>

            </div>

        </div>

    </div>

    <?php include 'partials/scripts.php'; ?>

</body>
</html>