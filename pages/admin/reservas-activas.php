<?php
include('../../conexion.php');
include('../sidebar.php');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {

    $sql = "
    SELECT 
        o.id_operaciones,
        d.nombre,
        d.apellido,
        o.nombre_servicio,
        o.fecha_salida,
        o.fecha_retorno
    FROM Operaciones o
    INNER JOIN Datos_clientes d ON o.id_cliente = d.id_cliente
    WHERE o.fecha_salida IS NOT NULL
      AND o.fecha_salida >= CURDATE()
    ORDER BY o.fecha_salida ASC
    ";

    $resultado = mysqli_query($conexion, $sql);

} catch (mysqli_sql_exception $e) {
    die("
        <div style='padding:20px;background:#fee;border:1px solid red'>
            <b>Error SQL:</b><br>
            {$e->getMessage()}
        </div>
    ");
}
?>

<div class="container-fluid mt-4">

    <div class="card shadow mb-4">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">📌 Reservas Activas</h4>
        </div>

        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-primary text-center">
                        <tr>
                            <th>#</th>
                            <th>Cliente</th>
                            <th>Servicio / Tour</th>
                            <th>Fecha Salida</th>
                            <th>Fecha Retorno</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $i = 1;
                        if (mysqli_num_rows($resultado) > 0):
                            while ($row = mysqli_fetch_assoc($resultado)):
                        ?>
                        <tr>
                            <td class="text-center"><?= $i++ ?></td>
                            <td><?= $row['nombre'].' '.$row['apellido'] ?></td>
                            <td><?= $row['nombre_servicio'] ?></td>
                            <td class="text-center"><?= date('d/m/Y', strtotime($row['fecha_salida'])) ?></td>
                            <td class="text-center"><?= date('d/m/Y', strtotime($row['fecha_retorno'])) ?></td>
                            <td class="text-center">
                                <span class="badge bg-success">Activa</span>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">
                                No hay reservas activas
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

</div>
