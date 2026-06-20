<?php
include '../../../conexion.php';

// QUERY
include 'partials/query_operaciones.php';
?>

<!DOCTYPE html>
<html lang="es">

<?php include 'partials/head.php'; ?>

<body>

<?php include '../../sidebar.php'; ?>

<main class="kb-content">

    <div class="container">

        <!-- HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-4">

            <h3 class="mb-0">
                📋 Operaciones de Endosadores
            </h3>

            <button class="btn btn-success"
                    data-bs-toggle="modal"
                    data-bs-target="#modalImportar">
                📥 Importar Excel
            </button>

        </div>

        <!-- FILTROS -->
        <?php include 'partials/filtros.php'; ?>

        <!-- TABLA -->
        <div class="card shadow-sm border-0">

            <div class="card-body">

                <?php include 'partials/tabla_operaciones.php'; ?>

            </div>

        </div>

    </div>

</main>

<!-- MODAL -->
<?php include 'partials/modal_importar.php'; ?>

<!-- SCRIPTS -->
<?php include 'partials/scripts.php'; ?>

</body>
</html>