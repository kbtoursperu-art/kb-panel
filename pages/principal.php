<?php
include '../conexion.php';
include 'sidebar.php';

// ========================
// 📊 CONSULTAS GENERALES
// ========================

// Total de clientes KB y Endosadores
$totalKB = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) AS total FROM Datos_clientes WHERE tipo_cliente = 'KB'"))['total'];
$totalEndos = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) AS total FROM Datos_clientes WHERE tipo_cliente = 'Endosador'"))['total'];

// Operaciones registradas
$totalOperaciones = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) AS total FROM Operaciones"))['total'];

// Tours que salen hoy
$hoy = date('Y-m-d');
$toursHoy = mysqli_query($conexion, "
    SELECT o.nombre_servicio, d.nombre, d.apellido, o.observaciones, o.empresa, o.Encargado 
    FROM Operaciones o
    JOIN Datos_clientes d ON o.id_cliente = d.id_cliente
    WHERE o.fecha_salida = '$hoy'
");

// Tours próximos (en 3 días)
$toursProximos = mysqli_query($conexion, "
    SELECT o.nombre_servicio, o.fecha_salida, d.nombre, d.apellido, o.Encargado 
    FROM Operaciones o
    JOIN Datos_clientes d ON o.id_cliente = d.id_cliente
    WHERE o.fecha_salida BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
");

// Observaciones recientes
$observaciones = mysqli_query($conexion, "
    SELECT o.nombre_servicio, o.observaciones, o.fecha_reserva, o.Encargado, d.nombre, d.apellido
    FROM Operaciones o
    JOIN Datos_clientes d ON o.id_cliente = d.id_cliente
    WHERE o.observaciones IS NOT NULL AND o.observaciones <> ''
    ORDER BY o.fecha_reserva DESC
    LIMIT 5
");

// Artículos en uso (almacén)
// Artículos en uso (almacén) - basado en salidas reales
$articulosUso = mysqli_query($conexion, "
    SELECT SUM(s.cantidad) AS total
    FROM almacen_salidas s
");


// Mensajes de sistema
$fecha = date('d/m/Y');
?>
<div class="content p-4">
    <div class="container-fluid">

        <h2 class="mb-4 text-primary">📋 Resumen General del Sistema — <?php echo $fecha; ?></h2>

        <!-- ======== TARJETAS PRINCIPALES ======== -->
        <div class="row text-center mb-4">
            <div class="col-md-3">
                <div class="card shadow-sm border-left-primary">
                    <div class="card-body">
                        <h5>👥 Clientes KB</h5>
                        <h3 class="text-primary"><?php echo $totalKB; ?></h3>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm border-left-success">
                    <div class="card-body">
                        <h5>🏢 Endosadores</h5>
                        <h3 class="text-success"><?php echo $totalEndos; ?></h3>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm border-left-warning">
                    <div class="card-body">
                        <h5>🗓️ Operaciones Totales</h5>
                        <h3 class="text-warning"><?php echo $totalOperaciones; ?></h3>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card shadow-sm border-left-info">
                    <div class="card-body">
                        <h5>📦 Artículos en Uso</h5>
                        <?php 
                       $filaUso = mysqli_fetch_assoc($articulosUso);
                        $totalUso = $filaUso['total'] ?? 0;
                        echo "<h3 class='text-info'>$totalUso</h3>";

                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======== TOURS DEL DÍA ======== -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-primary text-white">🚍 Tours que salen HOY</div>
            <div class="card-body">
                <?php if (mysqli_num_rows($toursHoy) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>Servicio</th>
                                    <th>Cliente</th>
                                    <th>Encargado</th>
                                    <th>Empresa</th>
                                    <th>Observaciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($t = mysqli_fetch_assoc($toursHoy)): ?>
                                    <tr>
                                        <td><?php echo $t['nombre_servicio']; ?></td>
                                        <td><?php echo $t['nombre'] . ' ' . $t['apellido']; ?></td>
                                        <td><?php echo $t['Encargado']; ?></td>
                                        <td><?php echo $t['empresa']; ?></td>
                                        <td><?php echo $t['observaciones'] ?: '—'; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-success">✅ No hay tours saliendo hoy.</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ======== TOURS PRÓXIMOS ======== -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-warning text-dark">⏳ Tours próximos (3 días)</div>
            <div class="card-body">
                <?php if (mysqli_num_rows($toursProximos) > 0): ?>
                    <ul class="list-group">
                        <?php while($tp = mysqli_fetch_assoc($toursProximos)): ?>
                            <li class="list-group-item">
                                <strong><?php echo $tp['nombre_servicio']; ?></strong> — 
                                <?php echo $tp['fecha_salida']; ?> | Cliente: 
                                <?php echo $tp['nombre'].' '.$tp['apellido']; ?> | Encargado: 
                                <?php echo $tp['Encargado']; ?>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <div class="alert alert-info">No hay tours programados en los próximos 3 días.</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ======== OBSERVACIONES RECIENTES ======== -->
        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-dark text-white">📝 Observaciones recientes</div>
            <div class="card-body">
                <?php if (mysqli_num_rows($observaciones) > 0): ?>
                    <ul class="list-group">
                        <?php while($obs = mysqli_fetch_assoc($observaciones)): ?>
                            <li class="list-group-item">
                                <strong><?php echo $obs['nombre_servicio']; ?></strong> — 
                                <em><?php echo $obs['observaciones']; ?></em> 
                                <br><small>Por: <?php echo $obs['Encargado']; ?> (<?php echo $obs['nombre'].' '.$obs['apellido']; ?>)</small>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <div class="alert alert-light">No hay observaciones registradas.</div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>
