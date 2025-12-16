<?php
include('../../conexion.php');
include './../sidebar.php';

if (!$conexion) {
    die("❌ Error de conexión: " . mysqli_connect_error());
}

// --- MÉTRICAS PRINCIPALES ---
$metrics = [
    'reservas_activas' => 0,
    'tours_programados' => 0,
    'nuevos_clientes' => 0,
    'ingresos_mes' => 0.00,
    'gastos_mes' => 0.00,
    'balance_mes' => 0.00
];

// 🟢 Reservas activas
$sql = "SELECT COUNT(*) AS total FROM Operaciones WHERE fecha_salida >= CURDATE()";
$result = mysqli_query($conexion, $sql);
if ($result) $metrics['reservas_activas'] = mysqli_fetch_assoc($result)['total'];

// 🟢 Tours programados
$sql = "SELECT COUNT(*) AS total FROM Operaciones";
$result = mysqli_query($conexion, $sql);
if ($result) $metrics['tours_programados'] = mysqli_fetch_assoc($result)['total'];

// 🟢 Nuevos clientes (últimos 30 días)
$sql = "SELECT COUNT(*) AS total FROM Datos_clientes WHERE fecha_registro >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
$result = mysqli_query($conexion, $sql);
if ($result) $metrics['nuevos_clientes'] = mysqli_fetch_assoc($result)['total'];

// 🟢 Ingresos del mes
$sql = "SELECT SUM(precio_servicio) AS total 
        FROM Contabilidad 
        WHERE estado = 'pagado' 
        AND MONTH(fecha_pago_saldo) = MONTH(CURDATE()) 
        AND YEAR(fecha_pago_saldo) = YEAR(CURDATE())";
$result = mysqli_query($conexion, $sql);
if ($result) $metrics['ingresos_mes'] = mysqli_fetch_assoc($result)['total'] ?? 0.00;

// 🟢 Gastos (no existen en BD actualmente)
$metrics['gastos_mes'] = 0.00;

// 🟢 Balance
$metrics['balance_mes'] = $metrics['ingresos_mes'] - $metrics['gastos_mes'];

// 🟢 Próximos tours
$sql = "SELECT nombre_servicio, fecha_salida 
        FROM Operaciones 
        WHERE fecha_salida >= CURDATE() 
        ORDER BY fecha_salida ASC 
        LIMIT 5";
$eventos = mysqli_fetch_all(mysqli_query($conexion, $sql), MYSQLI_ASSOC);

// 🟢 Notificaciones (observaciones recientes)
$sql = "SELECT observaciones AS mensaje, fecha_reserva 
        FROM Operaciones 
        WHERE observaciones IS NOT NULL AND observaciones != '' 
        ORDER BY fecha_reserva DESC LIMIT 5";
$notificaciones = mysqli_fetch_all(mysqli_query($conexion, $sql), MYSQLI_ASSOC);

// 🟢 Estadísticas
$sql = "SELECT 
            MONTH(o.fecha_reserva) AS mes, 
            o.nombre_servicio, 
            COUNT(*) AS cantidad,
            ROUND(AVG(c.precio_servicio), 2) AS precio_promedio
        FROM Operaciones o
        LEFT JOIN Contabilidad c ON o.id_operaciones = c.id_operaciones
        GROUP BY mes, o.nombre_servicio
        ORDER BY mes ASC";
$resultadoEstadisticas = mysqli_query($conexion, $sql);

$meses = [
    1 => "Enero", 2 => "Febrero", 3 => "Marzo", 4 => "Abril",
    5 => "Mayo", 6 => "Junio", 7 => "Julio", 8 => "Agosto",
    9 => "Septiembre", 10 => "Octubre", 11 => "Noviembre", 12 => "Diciembre"
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard General - KB Adventures</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="resumen.css">
</head>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const toggleBtn = document.getElementById("toggle-sidebar");
    const body = document.body;

    if (toggleBtn) {
        toggleBtn.addEventListener("click", function () {
            body.classList.toggle("sidebar-collapsed");
        });
    }
});
</script>

<body>
<div class="content p-4">
    <div class="container-fluid">

        <h2 class="mb-4 text-center text-primary fw-bold">
            📊 Panel de Control - KB Adventures
        </h2>

        <!-- MÉTRICAS PRINCIPALES -->
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-4 mb-4">
            <div class="col">
                <div class="card metric-card bg-primary text-white">
                    <a href="../operaciones-kb/index.php" class="text-white text-decoration-none">
                        <div class="card-body text-center">
                            <i class="fas fa-calendar-check fa-2x mb-2"></i>
                            <h5>Reservas Activas</h5>
                            <h3><?= $metrics['reservas_activas']; ?></h3>
                        </div>
                    </a>
                </div>
            </div>
            <div class="col">
                <div class="card metric-card bg-success text-white text-center">
                    <div class="card-body">
                        <i class="fas fa-mountain fa-2x mb-2"></i>
                        <h5>Tours Programados</h5>
                        <h3><?= $metrics['tours_programados']; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card metric-card bg-warning text-white text-center">
                    <div class="card-body">
                        <i class="fas fa-users fa-2x mb-2"></i>
                        <h5>Nuevos Clientes</h5>
                        <h3><?= $metrics['nuevos_clientes']; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- FINANZAS -->
        <div class="row row-cols-1 row-cols-sm-3 g-4 text-center mb-4">
            <div class="col">
                <div class="card metric-card bg-info text-white">
                    <div class="card-body">
                        <i class="fas fa-hand-holding-usd fa-2x mb-2"></i>
                        <h5>Ingresos del Mes</h5>
                        <h3>S/. <?= number_format($metrics['ingresos_mes'], 2); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card metric-card bg-danger text-white">
                    <div class="card-body">
                        <i class="fas fa-file-invoice-dollar fa-2x mb-2"></i>
                        <h5>Gastos del Mes</h5>
                        <h3>S/. <?= number_format($metrics['gastos_mes'], 2); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card metric-card bg-secondary text-white">
                    <div class="card-body">
                        <i class="fas fa-balance-scale fa-2x mb-2"></i>
                        <h5>Balance General</h5>
                        <h3>S/. <?= number_format($metrics['balance_mes'], 2); ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- EVENTOS Y NOTIFICACIONES -->
        <div class="row mb-4">
            <div class="col-md-6 mb-3 mb-md-0">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-info text-white text-center fw-bold">
                        <i class="fas fa-calendar"></i> Próximos Tours
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            <?php if (count($eventos) > 0): ?>
                                <?php foreach ($eventos as $ev): ?>
                                    <li class="list-group-item">
                                        <strong><?= date("d/m/Y", strtotime($ev['fecha_salida'])); ?></strong> - <?= $ev['nombre_servicio']; ?>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="list-group-item text-center">No hay tours próximos</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-warning text-white text-center fw-bold">
                        <i class="fas fa-bell"></i> Notificaciones Recientes
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            <?php if (count($notificaciones) > 0): ?>
                                <?php foreach ($notificaciones as $n): ?>
                                    <li class="list-group-item">
                                        <strong><?= date("d/m/Y", strtotime($n['fecha_reserva'])); ?>:</strong>
                                        <?= htmlspecialchars($n['mensaje']); ?>
                                    </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="list-group-item text-center">No hay observaciones recientes</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- ESTADÍSTICAS -->
        <div class="card shadow-sm mb-5">
            <div class="card-header bg-dark text-white text-center fw-bold">
                <i class="fas fa-chart-line"></i> Estadísticas por Mes y Tour
            </div>
            <div class="card-body p-3">
                <div class="table-responsive">
                    <table class="table table-striped table-hover text-center align-middle">
                        <thead class="table-dark">
                            <tr>
                                <th>Mes</th>
                                <th>Servicio</th>
                                <th>Cantidad</th>
                                <th>Precio Promedio</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($fila = mysqli_fetch_assoc($resultadoEstadisticas)): ?>
                                <tr>
                                    <td><?= $meses[$fila['mes']] ?? '-'; ?></td>
                                    <td><?= htmlspecialchars($fila['nombre_servicio']); ?></td>
                                    <td><?= $fila['cantidad']; ?></td>
                                    <td>S/. <?= number_format($fila['precio_promedio'], 2); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
</body>
</html>
