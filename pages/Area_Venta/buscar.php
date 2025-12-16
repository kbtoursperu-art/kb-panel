<?php
include '../../conexion.php';

// Obtener parámetros de búsqueda y paginación de forma segura
$search = isset($_GET['search']) ? mysqli_real_escape_string($conexion, $_GET['search']) : '';
$search_date_from = isset($_GET['search_date_from']) ? mysqli_real_escape_string($conexion, $_GET['search_date_from']) : '';
$search_date_to = isset($_GET['search_date_to']) ? mysqli_real_escape_string($conexion, $_GET['search_date_to']) : '';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Consulta para contar registros totales
$total_query = mysqli_query($conexion, "SELECT COUNT(*) as total FROM Vista_Ventas");
$total_row = mysqli_fetch_assoc($total_query);
$total_rows = $total_row['total'];


// Construcción de la consulta de datos desde la vista Vista_Ventas
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

// Aplicar filtros de búsqueda
if (!empty($search)) {
    $query .= " AND (nombre_servicio LIKE '%$search%' 
                OR grupo LIKE '%$search%' 
                OR metodo_pago LIKE '%$search%')";
}
if (!empty($search_date_from) && !empty($search_date_to)) {
    $query .= " AND (fecha_reserva BETWEEN '$search_date_from' AND '$search_date_to')";
}

// Aplicar límites de paginación
$query .= " LIMIT $limit OFFSET $offset";
$result = mysqli_query($conexion, $query);

// Construcción del HTML
$html = "";
while ($row = mysqli_fetch_assoc($result)) {
    $html .= "<tr>
                <td>{$row['id_venta']}</td>
                <td>{$row['nombre_servicio']}</td>
                <td>{$row['fecha_reserva']}</td>
                <td>{$row['fecha_salida']}</td>
                <td>{$row['fecha_retorno']}</td>
                <td>{$row['grupo']}</td>
                <td>{$row['metodo_pago']}</td>
                <td>{$row['precio_servicio']}</td>
                <td>{$row['pagado_a_cuenta']}</td>


                

                <td>{$row['saldo_pendiente']}</td>
                <td>{$row['fecha_pago_saldo']}</td>
                <td>{$row['nro_voucher']}</td>
                <td>{$row['modalidad_pago']}</td>
                <td>";

    // Mostrar botones de acción (Editar, Eliminar o Agregar)
    if (!empty($row['id_venta'])) {
        $html .= "<a href='editar.php?id_venta={$row['id_venta']}' class='editar btn btn-warning btn-sm'>Editar</a> |
                  <a href='eliminar.php?id_venta={$row['id_venta']}' class='eliminar btn btn-danger btn-sm' onclick='return confirm(\"¿Eliminar esta venta?\")'>Eliminar</a>";
    } else {
        $html .= "<a href='agregar.php' class='agregar btn btn-success btn-sm'>Agregar</a>";
    }

    $html .= "</td></tr>";
}

// Crear estructura de paginación
$total_pages = ($total_rows > 0) ? ceil($total_rows / $limit) : 1;
$pagination_array = [];
for ($i = 1; $i <= $total_pages; $i++) {
    $pagination_array[] = [
        "page" => $i,
        "active" => ($i == $page)
    ];
}

// Respuesta JSON
echo json_encode(["html" => $html, "pagination" => $pagination_array]);

// Cerrar conexión
mysqli_close($conexion);
?>
