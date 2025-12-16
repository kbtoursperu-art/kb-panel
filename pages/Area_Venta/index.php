<?php
include '../../conexion.php';

// Obtener parámetros de búsqueda de fechas de forma segura
$search_date_from = isset($_GET['search_date_from']) ? mysqli_real_escape_string($conexion, $_GET['search_date_from']) : '';
$search_date_to = isset($_GET['search_date_to']) ? mysqli_real_escape_string($conexion, $_GET['search_date_to']) : '';

// Consulta base desde la vista Vista_Ventas
$query = "SELECT 
            id_venta, 
            nombre_servicio, 
            fecha_reserva, 
            fecha_salida, 
            fecha_retorno, 
            grupo, 
            metodo_pago, 
            precio_servicio, 
            pagado_a_cuenta, 
            saldo_pendiente, 
            fecha_pago_saldo,
            nro_voucher,
            modalidad_pago
        FROM Vista_Ventas
        WHERE 1=1";

// Aplicar filtro de fechas
if (!empty($search_date_from) && !empty($search_date_to)) {
    $query .= " AND (fecha_reserva BETWEEN '$search_date_from' AND '$search_date_to')";
}

$result = mysqli_query($conexion, $query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Ventas</title>
    <link rel="stylesheet" href="stilo.css">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
</head>
<body>

<?php include('../../templates/barra.php'); ?>
<!-- Botón para mostrar el sidebar en móviles -->
<div >
<div class="content">
    
    <!-- Tabla de resultados -->
    <div class="table-container">
        <h2 class="text-center text-white fw-bold p-2" style="background-color: #007bff;">Gestión de Ventas</h2>

    <!-- Formulario de búsqueda solo por fechas -->
    <form id="filtroForm" class="row g-3">
        <div class="col-md-6">
            <input type="date" id="search_date_from" class="form-control">
        </div>
        <div class="col-md-6">
            <input type="date" id="search_date_to" class="form-control">
        </div>
    </form>
<br>
        <table id="datatableventa" class="display">
            <thead>
                <tr>
                    <th>ID Venta</th>
                    <th>Servicio</th>
                    <th>Fecha Reserva</th>
                    <th>Fecha Salida</th>
                    <th>Fecha Retorno</th>
                    <th>Grupo</th>
                    <th>Método de Pago</th>
                    <th>Precio</th>
                    <th>Pagado</th>
                    <th>Saldo</th>
                    <th>Fecha Pago Saldo</th>
                    <th>Nro Voucher</th>
                    <th>Modalidad Pago</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tablaResultados">
                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                    <tr>
                        <td><?= $row['id_venta'] ?></td>
                        <td><?= $row['nombre_servicio'] ?></td>
                        <td><?= $row['fecha_reserva'] ?></td>
                        <td><?= $row['fecha_salida'] ?></td>
                        <td><?= $row['fecha_retorno'] ?></td>
                        <td><?= $row['grupo'] ?></td>
                        <td><?= $row['metodo_pago'] ?></td>
                        <td><?= $row['precio_servicio'] ?></td>
                        <td><?= $row['pagado_a_cuenta'] ?></td>
                        <td><?= $row['saldo_pendiente'] ?></td>
                        <td><?= $row['fecha_pago_saldo'] ?></td>
                        <td><?= $row['nro_voucher'] ?></td>
                        <td><?= $row['modalidad_pago'] ?></td>
                        <td>
                            <a href='editar.php?id_venta=<?= $row['id_venta'] ?>' class='btn btn-warning btn-sm'>Editar</a> |
                            <a href='eliminar.php?id_venta=<?= $row['id_venta'] ?>' class='btn btn-danger btn-sm' onclick='return confirm("¿Eliminar esta venta?")'>Eliminar</a>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        
    </div>
    </div>
</div>


<script>
$(document).ready(function() {
    // Inicializar DataTable
    let table = new DataTable('#datatableventa', {
        "language": {
            "url": "https://cdn.datatables.net/plug-ins/1.11.5/i18n/Spanish.json"
        }
    });

    // Cuando cambian las fechas, recargar la página con los filtros aplicados
    $("#search_date_from, #search_date_to").on("change", function() {
        var dateFrom = $("#search_date_from").val();
        var dateTo = $("#search_date_to").val();
        location.href = "?search_date_from=" + dateFrom + "&search_date_to=" + dateTo;
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php mysqli_close($conexion); ?>
