<?php
include '../../../conexion.php';

/* =========================
   QUERY PRINCIPAL
========================= */
include 'partials/query_operaciones.php';

/* =========================
   EJECUTAR QUERY
========================= */
$resultado = mysqli_query($conexion, $query);

if (!$resultado) {
    die("Error SQL: " . mysqli_error($conexion));
}
?>

<!DOCTYPE html>
<html lang="es">

<?php include 'partials/head.php'; ?>

<body>

<?php include '../../sidebar.php'; ?>
<main class="kb-content">


    <div class="kb-page">

        <!-- HEADER -->
        <div class="page-header">

            <div>
                <h1 class="page-title">
                    <i class="fas fa-tasks"></i>
                    Operaciones KB
                </h1>

                <p class="page-subtitle">
                    Gestión de operaciones y reservas
                </p>
            </div>

            <button
                class="kb-btn kb-btn-success"
                data-bs-toggle="modal"
                data-bs-target="#modalImportar">

                <i class="fas fa-file-excel"></i>
                Importar Excel

            </button>

        </div>

        <!-- FILTROS -->
        <?php include 'partials/filtros.php'; ?>

        <!-- TABLA -->
        <div class="kb-card">

            <div class="kb-card-header">
                <h5 class="mb-0">
                    Lista de Operaciones
                </h5>
            </div>

            <div class="kb-card-body">

                <?php include 'partials/tabla_operaciones.php'; ?>

            </div>

        </div>

    </div>

</main>

<?php include 'partials/modal_importar.php'; ?>

<?php include 'partials/scripts.php'; ?>

</body>
</html>