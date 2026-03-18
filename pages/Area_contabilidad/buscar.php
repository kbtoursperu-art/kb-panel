<?php
include('../../conexion.php');

$search_term = isset($_GET['search']) ? trim($_GET['search']) : '';
$registros_por_pagina = isset($_GET['rows']) ? (int)$_GET['rows'] : 10;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Consulta total de registros con filtro
$total_query = "SELECT COUNT(*) AS total FROM datos_clientes d
                LEFT JOIN operaciones o ON d.id_cliente = o.id_cliente
                LEFT JOIN contabilidad c ON o.id_operaciones = c.id_operaciones";
                
if ($search_term) {
    $total_query .= " WHERE LOWER(d.nombre) LIKE LOWER(?) 
                      OR LOWER(d.nro_pasaporte) LIKE LOWER(?) 
                      OR LOWER(o.nombre_servicio) LIKE LOWER(?)";
}

$stmt_total = mysqli_prepare($conexion, $total_query);
if ($search_term) {
    $search_param = "%$search_term%";
    mysqli_stmt_bind_param($stmt_total, "sss", $search_param, $search_param, $search_param);
}
mysqli_stmt_execute($stmt_total);
$result_total = mysqli_stmt_get_result($stmt_total);
$total_row = mysqli_fetch_assoc($result_total);
$total_registros = $total_row['total'];
$total_paginas = ceil($total_registros / $registros_por_pagina);

// Consulta de datos con paginación
$query = "SELECT d.id_cliente, o.id_operaciones, c.id_contabilidad,
          c.metodo_pago, c.precio_servicio, c.pagado_a_cuenta, c.saldo_pendiente,
          c.fecha_pago_saldo, c.modalidad_recibo, c.nro_boleta_total, c.detraccion,
          c.igv, d.nombre AS cliente_nombre, d.nro_pasaporte, 
          o.nombre_servicio
          FROM datos_clientes d
          LEFT JOIN operaciones o ON d.id_cliente = o.id_cliente
          LEFT JOIN contabilidad c ON o.id_operaciones = c.id_operaciones";

if ($search_term) {
    $query .= " WHERE LOWER(d.nombre) LIKE LOWER(?) 
                OR LOWER(d.nro_pasaporte) LIKE LOWER(?) 
                OR LOWER(o.nombre_servicio) LIKE LOWER(?)";
}

$query .= " LIMIT ? OFFSET ?";

$stmt = mysqli_prepare($conexion, $query);
if ($search_term) {
    mysqli_stmt_bind_param($stmt, "sssii", $search_param, $search_param, $search_param, $registros_por_pagina, $offset);
} else {
    mysqli_stmt_bind_param($stmt, "ii", $registros_por_pagina, $offset);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<div class="table-responsive">
    <table class="table table-hover table-bordered text-center">
        <thead class="table-dark">
            <tr>
                <th>ID Contabilidad</th>
                <th>Cliente</th>
                <th>Nro. Pasaporte</th>
                <th>Nombre de Servicio</th>
                <th>Método de Pago</th>
                <th>Precio Servicio</th>
                <th>Pagado a Cuenta</th>
                <th>Saldo Pendiente</th>
                <th>Fecha Pago Saldo</th>
                <th>Modalidad Recibo</th>
                <th>Nro Boleta Total</th>
                <th>Detracción</th>
                <th>IGV</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                <tr>
                    <td><?= htmlspecialchars($row['id_contabilidad'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['cliente_nombre'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['nro_pasaporte'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['nombre_servicio'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['metodo_pago'] ?? '') ?></td>
                    <td><?= !empty($row['precio_servicio']) ? htmlspecialchars($row['precio_servicio']) : '' ?></td>
                    <td><?= !empty($row['pagado_a_cuenta']) ? htmlspecialchars($row['pagado_a_cuenta']) : '' ?></td>
                    <td><?= !empty($row['saldo_pendiente']) ? htmlspecialchars($row['saldo_pendiente']) : '' ?></td>
                    <td><?= htmlspecialchars($row['fecha_pago_saldo'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['modalidad_recibo'] ?? '') ?></td>
                    <td><?= htmlspecialchars($row['nro_boleta_total'] ?? '') ?></td>
                    <td><?= !empty($row['detraccion']) ? htmlspecialchars($row['detraccion']) : '' ?></td>
                    <td><?= !empty($row['igv']) ? htmlspecialchars($row['igv']) : '' ?></td>
                    <td>
                        <?php if (!empty($row['id_contabilidad'])) : ?>
                            <a href='editar.php?id_contabilidad=<?= $row['id_contabilidad'] ?>' class='btn btn-warning btn-sm'>Editar</a>
                            <a href='eliminar.php?id_contabilidad=<?= $row['id_contabilidad'] ?>' class='btn btn-danger btn-sm' onclick='return confirm("¿Eliminar esta operación?")'>Eliminar</a>
                        <?php else : ?>
                            <a href='agregar.php?id_cliente=<?= $row['id_cliente'] ?>' class='btn btn-success btn-sm'>Agregar</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>