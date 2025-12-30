<?php
include('../../conexion.php');
include('../sidebar.php');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);

// =======================
// 🔹 NUEVOS CLIENTES (últimos 30 días)
// =======================
$sql = "
SELECT 
    id_cliente,
    nombre,
    apellido,
    tipo_cliente,
    nro_pasaporte,
    nacionalidad,
    fecha_registro
FROM Datos_clientes
WHERE fecha_registro >= DATE_SUB(NOW(), INTERVAL 30 DAY)
ORDER BY fecha_registro DESC
";

$resultado = mysqli_query($conexion, $sql);
?>

<div class="container-fluid mt-4">

    <div class="card shadow mb-4">
        <div class="card-header bg-warning text-dark">
            <h4 class="mb-0">👥 Nuevos Clientes (Últimos 30 días)</h4>
        </div>

        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-warning text-center">
                        <tr>
                            <th>#</th>
                            <th>Cliente</th>
                            <th>Tipo</th>
                            <th>Pasaporte</th>
                            <th>Nacionalidad</th>
                            <th>Fecha Registro</th>
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
                            <td class="text-center">
                                <?php if ($row['tipo_cliente'] == 'KB'): ?>
                                    <span class="badge bg-primary">KB</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Endosador</span>
                                <?php endif; ?>
                            </td>
                            <td><?= $row['nro_pasaporte'] ?></td>
                            <td><?= $row['nacionalidad'] ?: '-' ?></td>
                            <td class="text-center">
                                <?= date('d/m/Y H:i', strtotime($row['fecha_registro'])) ?>
                            </td>
                        </tr>
                        <?php endwhile; else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">
                                No hay nuevos clientes registrados en los últimos 30 días
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

</div>
