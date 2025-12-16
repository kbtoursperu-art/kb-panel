<?php include '../../conexion.php'; ?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área de Operaciones</title>
    <link rel="stylesheet" href="../css/index.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<br><br><br>
<?php include $_SERVER['DOCUMENT_ROOT'] . '/templates/barra.php'; ?>

<div class="container mt-4">
    <h2 class="text-center">📊 Reporte de Operaciones</h2>
    
    <a href="agregar.php" class="btn btn-primary mb-3">Agregar Nueva Operación</a>

    <!-- 🔎 Formulario de búsqueda -->
    <form id="filtroForm" class="row g-3">
        <div class="col-md-3">
            <input type="text" id="servicio" class="form-control" placeholder="Buscar por nombre de tour">
        </div>
        <div class="col-md-2">
            <input type="date" id="fecha_inicio" class="form-control">
        </div>
        <div class="col-md-2">
            <input type="date" id="fecha_fin" class="form-control">
        </div>
        <div class="col-md-2">
            <select id="num_filas" class="form-select">
                <option value="5">Mostrar 5</option>
                <option value="10">Mostrar 10</option>
                <option value="20">Mostrar 20</option>
            </select>
        </div>
    </form>

    <div class="table-responsive shadow-sm">
        <table class="table table-striped table-hover table-bordered text-center align-middle">
            <thead class="table-dark">
                <tr>
                    <th>ID Operación</th>
                    <th>Servicio</th>
                    <th>Fecha Reserva</th>
                    <th>Fecha Salida</th>
                    <th>Fecha Retorno</th>
                    <th>Grupo Pax</th>
                    <th>Método de Pago</th>
                    <th>Precio Servicio</th>
                    <th>Pagado</th>
                    <th>Saldo Pendiente</th>
                    <th>Fecha Pago Saldo</th>
                    <th>Nro Voucher</th>
                    <th>Modalidad de Pago</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tablaResultados">
                <!-- Datos cargados por AJAX -->
            </tbody>
        </table>
    </div>

    <nav>
        <ul class="pagination justify-content-center" id="paginacion">
            <!-- Paginación cargada por AJAX -->
        </ul>
    </nav>
</div>

<script>
$(document).ready(function() {
    function cargarDatos(page = 1) {
        let servicio = $("#servicio").val();
        let fecha_inicio = $("#fecha_inicio").val();
        let fecha_fin = $("#fecha_fin").val();
        let num_filas = $("#num_filas").val();

        $.ajax({
            url: "buscar.php",
            type: "GET",
            data: { servicio, fecha_inicio, fecha_fin, num_filas, page },
            success: function(data) {
                let resultado = JSON.parse(data);
                $("#tablaResultados").html(resultado.tabla);
                $("#paginacion").html(resultado.paginacion);
            }
        });
    }
    
    $("#servicio, #fecha_inicio, #fecha_fin, #num_filas").on("input change", function() {
        cargarDatos();
    });

    $(document).on("click", ".page-link", function(e) {
        e.preventDefault();
        let page = $(this).data("page");
        cargarDatos(page);
    });

    cargarDatos();
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
