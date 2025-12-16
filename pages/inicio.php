<?php
include '../conexion.php';
include './header.php';
include './sidebar.php';

if (!$conexion) {
    die("❌ Error de conexión: " . mysqli_connect_error());
}

// --- MÉTRICAS PRINCIPALES ---
$metrics = [
    'reservas_activas' => 0,
    'tours_programados' => 0,
    'nuevos_clientes' => 0,
    'ingresos_mes' => 0.00
];

// Reservas activas
$sql = "SELECT COUNT(*) AS total FROM Operaciones WHERE fecha_salida >= CURDATE()";
if ($result = $conexion->query($sql)) {
    $metrics['reservas_activas'] = $result->fetch_assoc()['total'];
}

// Tours programados
$sql = "SELECT COUNT(*) AS total FROM Operaciones";
if ($result = $conexion->query($sql)) {
    $metrics['tours_programados'] = $result->fetch_assoc()['total'];
}

// Nuevos clientes
$sql = "SELECT COUNT(DISTINCT id_cliente) AS total FROM Operaciones WHERE fecha_reserva >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
if ($result = $conexion->query($sql)) {
    $metrics['nuevos_clientes'] = $result->fetch_assoc()['total'];
}

// Ingresos del mes
$sql = "SELECT SUM(precio_servicio) AS total FROM Contabilidad WHERE MONTH(fecha_pago_saldo) = MONTH(CURDATE()) AND YEAR(fecha_pago_saldo) = YEAR(CURDATE())";
if ($result = $conexion->query($sql)) {
    $metrics['ingresos_mes'] = $result->fetch_assoc()['total'] ?? 0.00;
}

// Eventos próximos
$sql = "SELECT fecha_salida AS fecha, nombre_servicio AS evento FROM Operaciones WHERE fecha_salida >= CURDATE() ORDER BY fecha_salida ASC LIMIT 5";
$resultEventos = $conexion->query($sql);
$eventos = $resultEventos ? $resultEventos->fetch_all(MYSQLI_ASSOC) : [];

// Notificaciones
$sql = "SELECT observaciones AS mensaje FROM Operaciones WHERE observaciones IS NOT NULL AND observaciones != '' ORDER BY fecha_reserva DESC LIMIT 5";
$resultNotificaciones = $conexion->query($sql);
$notificaciones = $resultNotificaciones ? $resultNotificaciones->fetch_all(MYSQLI_ASSOC) : [];

// Estadísticas mensuales
$sql = "SELECT MONTH(fecha_reserva) AS mes, nombre_servicio, COUNT(*) AS cantidad FROM Operaciones GROUP BY mes, nombre_servicio ORDER BY mes, nombre_servicio";
$resultadoEstadisticas = $conexion->query($sql);

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
    <title>Dashboard - Turismo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Estilos Bootstrap y FontAwesome -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

    <!-- Estilo personalizado -->
   <style>
    body {
        background-color: #f4f6f9;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .card {
        border-radius: 15px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        transition: transform 0.2s ease-in-out;
    }

    .card:hover {
        transform: scale(1.01);
    }

    .card-title {
        font-size: 1rem;
        font-weight: 600;
    }

    .display-6 {
        font-size: 1.8rem;
    }

    .table-responsive {
        overflow-x: auto;
    }

    @media (max-width: 576px) {
        .card-title {
            font-size: 0.9rem;
        }

        .display-6 {
            font-size: 1.5rem;
        }

        .list-group-item {
            font-size: 0.9rem;
        }

        .card-body, .card-header {
            padding: 0.8rem;
        }

        .table th, .table td {
            font-size: 0.85rem;
        }

        .table thead {
            font-size: 0.9rem;
        }
    }

    .card-header h5 {
        margin: 0;
        font-size: 1rem;
    }

    .list-group-item {
        border-radius: 6px;
        margin-bottom: 0.4rem;
    }
    html, body {
    box-sizing: border-box;
    overflow-x: hidden;
}

</style>

</head>
<body>

<div class="container mt-5">

    <!-- MÉTRICAS -->
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-4 text-center mb-4">
        <div class="col">
            <div class="card text-white bg-primary p-3">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-calendar-check"></i> Reservas Activas</h5>
                    <p class="card-text display-6"><?= $metrics['reservas_activas']; ?></p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card text-white bg-success p-3">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-chart-bar"></i> Tours Programados</h5>
                    <p class="card-text display-6"><?= $metrics['tours_programados']; ?></p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card text-white bg-warning p-3">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-users"></i> Nuevos Clientes</h5>
                    <p class="card-text display-6"><?= $metrics['nuevos_clientes']; ?></p>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card text-white bg-danger p-3">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-money-bill-wave"></i> Ingresos del Mes</h5>
                    <p class="card-text display-6">$<?= number_format($metrics['ingresos_mes'], 2); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- EVENTOS Y NOTIFICACIONES -->
    <div class="row mb-4">
        <div class="col-12 col-md-6 mb-3 mb-md-0">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-info text-white text-center">
                    <h5><i class="fas fa-calendar"></i> Eventos Próximos</h5>
                </div>
                <div class="card-body p-3">
                    <ul class="list-group">
                        <?php if (count($eventos) > 0): ?>
                            <?php foreach ($eventos as $evento): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <strong><?= $evento['fecha']; ?></strong> <span><?= $evento['evento']; ?></span>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="list-group-item text-center">No hay eventos programados</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-warning text-white text-center">
                    <h5><i class="fas fa-bell"></i> Notificaciones</h5>
                </div>
                <div class="card-body p-3">
                    <ul class="list-group">
                        <?php if (count($notificaciones) > 0): ?>
                            <?php foreach ($notificaciones as $noti): ?>
                                <li class="list-group-item d-flex align-items-center">
                                    <i class="fas fa-exclamation-circle text-danger me-2"></i> <?= $noti['mensaje']; ?>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="list-group-item text-center">No hay notificaciones</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- ESTADÍSTICA MENSUAL -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white text-center">
                    <h5><i class="fas fa-chart-line"></i> Estadística de Reservas por Mes y Tour</h5>
                </div>
                <div class="card-body p-3">
                    <div class="table-responsive">
                     

                        <table class="table table-sm table-striped table-hover align-middle">
                            <thead class="table-dark text-center">
                                <tr>
                                    <th>Mes</th>
                                    <th>Servicio</th>
                                    <th>Cantidad</th>
                                </tr>
                            </thead>
                            <tbody class="text-center">
                                <?php while ($fila = $resultadoEstadisticas->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $meses[$fila['mes']]; ?></td>
                                        <td><?= $fila['nombre_servicio']; ?></td>
                                        <td><?= $fila['cantidad']; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

</body>
</html>


