<?php
session_start();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Navegación</title>

    <!-- Bootstrap y Font Awesome -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        .navbar {
            background: linear-gradient(90deg, rgb(15, 30, 48), rgb(14, 26, 37));
            padding: 15px 20px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .navbar .btn-outline-light {
            border: none;
            font-size: 22px;
            color: white;
        }

        .navbar .btn-outline-light:hover {
            color: #ffc107;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            color: white;
        }

        .user-info h3 {
            margin: 0;
            font-size: 16px;
            font-weight: normal;
        }

        .user-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
        }

        .logout-btn {
            color: white;
            font-size: 18px;
            transition: 0.3s;
        }

        .logout-btn:hover {
            color: red;
        }

        .sidebar {
            width: 260px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 65px;
            background: linear-gradient(90deg, rgb(15, 30, 48), rgb(14, 26, 37));
            padding-top: 20px;
            transition: 0.3s ease-in-out;
            box-shadow: 2px 0px 10px rgba(0, 0, 0, 0.2);
            color: white;
            text-align: center;
        }

        .sidebar .user-section {
            margin-bottom: 20px;
        }

        .sidebar .user-section img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid white;
            object-fit: cover;
        }

        .sidebar .user-section h5,
        .sidebar .user-section p {
            margin: 5px 0;
        }

        .sidebar .nav-link {
            color: white;
            font-size: 16px;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            transition: 0.3s;
        }

        .sidebar .nav-link i {
            margin-right: 10px;
            color: #ffc107;
        }

        .sidebar .nav-link:hover {
            background-color: #1a252f;
            color: #ffc107;
        }

        .sidebar.active {
            left: -260px;
        }

        .content {
            margin-left: 260px;
            margin-top: 80px;
            padding: 20px;
            transition: 0.3s ease-in-out;
            flex-grow: 1;
        }

        .footer {
            background-color: #002147;
            color: white;
            text-align: center;
            padding: 15px;
            position: fixed;
            bottom: 0;
            width: 100%;
        }

        @media (max-width: 992px) {
            .sidebar {
                left: -260px;
            }
            .sidebar.active {
                left: 0;
            }
            .content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <button class="btn btn-outline-light" id="toggle-sidebar">
        <i class="fas fa-bars"></i>
    </button>

    <div class="ml-auto d-flex align-items-center">
        <h3 class="mb-0 mr-3"><?php echo isset($_SESSION["Area"]) ? $_SESSION["Area"] : "Invitado"; ?></h3>
        <img src="../assets/images/logo.png" class="user-img" alt="usuario">
        <a href="../index.php" class="logout-btn ml-3">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>
</nav>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="user-section">
        <img src="../assets/images/logo.png" alt="Foto usuario">
        <h5><?php echo $_SESSION["Usuario"]; ?></h5>
        <p><?php echo $_SESSION["Area"]; ?></p>
    </div>

    <nav class="nav flex-column">
        <a class="nav-link" href="/pages/principal.php"><i class="fas fa-home"></i> Inicio</a>
        <?php
        $area = $_SESSION["Area"] ?? '';
$esAdmin = $_SESSION["EsAdmin"] ?? 0;

if ($esAdmin == 1) {
    // Mostrar todos los accesos
    echo '<a class="nav-link" href="/pages/Area_clientes/index.php"><i class="fas fa-user"></i> Clientes</a>';
    echo '<a class="nav-link" href="/pages/Area_Operaciones/index.php"><i class="fas fa-users"></i> Operaciones</a>';
    echo '<a class="nav-link" href="/pages/Area_planificacion/index.php"><i class="fas fa-cogs"></i> Planificación</a>';
    echo '<a class="nav-link" href="/pages/Area_Venta/index.php"><i class="fas fa-shopping-cart"></i> Ventas</a>';
    echo '<a class="nav-link" href="/pages/Area_contabilidad/index.php"><i class="fas fa-calculator"></i> Contabilidad</a>';
    echo '<a class="nav-link" href="/pages/almacen/index.php"><i class="fas fa-box"></i> Almacén</a>';
} else {
    // Mostrar solo según el área asignada
    switch ($area) {
        case "Clientes":
            echo '<a class="nav-link" href="/pages/Area_clientes/index.php"><i class="fas fa-user"></i> Clientes</a>';
            break;
        case "Operaciones":
            echo '<a class="nav-link" href="/pages/Area_Operaciones/index.php"><i class="fas fa-users"></i> Operaciones</a>';
            break;
        case "Planificación":
            echo '<a class="nav-link" href="/pages/Area_planificacion/index.php"><i class="fas fa-cogs"></i> Planificación</a>';
            break;
        case "Ventas":
            echo '<a class="nav-link" href="/pages/Area_Venta/index.php"><i class="fas fa-shopping-cart"></i> Ventas</a>';
            break;
        case "Contabilidad":
            echo '<a class="nav-link" href="/pages/Area_contabilidad/index.php"><i class="fas fa-calculator"></i> Contabilidad</a>';
            break;
        case "Almacén":
        case "Almacen":
            echo '<a class="nav-link" href="/pages/almacen/index.php"><i class="fas fa-box"></i> Almacén</a>';
            break;
    }
}

        ?>
    </nav>
</div>

<!-- Contenido principal -->
<!-- Contenido principal -->
<div class="content">
    <div class="container">
        <h2>Bienvenido al Panel de Gestión</h2>
        <p>Selecciona una sección del menú lateral para comenzar.</p>

        <!-- Tarjetas de acceso rápido -->
        <div class="row mt-4">
            <?php if ($esAdmin == 1 || $area == "Clientes") : ?>
                <div class="col-md-4 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-user fa-2x text-primary mb-2"></i>
                            <h5 class="card-title">Clientes</h5>
                            <a href="/pages/Area_clientes/index.php" class="btn btn-outline-primary btn-sm mt-2">Ir</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($esAdmin == 1 || $area == "Operaciones") : ?>
                <div class="col-md-4 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-users fa-2x text-success mb-2"></i>
                            <h5 class="card-title">Operaciones</h5>
                            <a href="/pages/Area_Operaciones/index.php" class="btn btn-outline-success btn-sm mt-2">Ir</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($esAdmin == 1 || $area == "Planificación") : ?>
                <div class="col-md-4 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-cogs fa-2x text-warning mb-2"></i>
                            <h5 class="card-title">Planificación</h5>
                            <a href="/pages/Area_planificacion/index.php" class="btn btn-outline-warning btn-sm mt-2">Ir</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($esAdmin == 1 || $area == "Ventas") : ?>
                <div class="col-md-4 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-shopping-cart fa-2x text-danger mb-2"></i>
                            <h5 class="card-title">Ventas</h5>
                            <a href="/pages/Area_Venta/index.php" class="btn btn-outline-danger btn-sm mt-2">Ir</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($esAdmin == 1 || $area == "Contabilidad") : ?>
                <div class="col-md-4 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-calculator fa-2x text-info mb-2"></i>
                            <h5 class="card-title">Contabilidad</h5>
                            <a href="/pages/Area_contabilidad/index.php" class="btn btn-outline-info btn-sm mt-2">Ir</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($esAdmin == 1 || $area == "Almacén" || $area == "Almacen") : ?>
                <div class="col-md-4 mb-3">
                    <div class="card shadow-sm">
                        <div class="card-body text-center">
                            <i class="fas fa-box fa-2x text-secondary mb-2"></i>
                            <h5 class="card-title">Almacén</h5>
                            <a href="/pages/almacen/index.php" class="btn btn-outline-secondary btn-sm mt-2">Ir</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Resúmenes de alertas por área -->
        <div class="row mt-5">
            <?php if ($esAdmin == 1 || $area == "Clientes"): ?>
                <div class="col-12 mb-3">
                    <div class="alert alert-primary shadow-sm">
                        <?php
                        $resClientes = mysqli_query($conexion, "SELECT COUNT(*) as total FROM Datos_clientes");
                        $datoClientes = mysqli_fetch_assoc($resClientes);
                        echo "<strong>Total de clientes registrados:</strong> " . $datoClientes['total'];
                        ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($esAdmin == 1 || $area == "Operaciones"): ?>
                <div class="col-12 mb-3">
                    <div class="alert alert-warning shadow-sm">
                        <?php
                        $resObservaciones = mysqli_query($conexion, "SELECT COUNT(*) as total FROM Operaciones WHERE observaciones IS NOT NULL AND TRIM(observaciones) != ''");
                        $resReservas = mysqli_query($conexion, "SELECT COUNT(*) as total FROM Operaciones WHERE fecha_salida = CURDATE() + INTERVAL 1 DAY");
                        $obs = mysqli_fetch_assoc($resObservaciones);
                        $res = mysqli_fetch_assoc($resReservas);

                        echo "<strong>Operaciones con observaciones:</strong> {$obs['total']} | ";
                        echo "<strong>Operaciones con salida mañana:</strong> {$res['total']}";
                        ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($esAdmin == 1 || $area == "Almacén" || $area == "Almacen"): ?>
                <div class="col-12 mb-3">
                    <div class="alert alert-info shadow-sm">
                        <?php
                        $resPendientes = mysqli_query($conexion, "SELECT COUNT(*) as total FROM almacen WHERE incluye_sleeping = 1 AND estado_deposito_sleeping = 'pendiente'");
                        $resSalidas = mysqli_query($conexion, "SELECT COUNT(*) as total FROM almacen WHERE fecha_alquiler_bastones = CURDATE()");
                        $pen = mysqli_fetch_assoc($resPendientes);
                        $sal = mysqli_fetch_assoc($resSalidas);

                        echo "<strong>Depósitos de sleeping pendientes:</strong> {$pen['total']} | ";
                        echo "<strong>Alquileres de bastones hoy:</strong> {$sal['total']}";
                        ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($esAdmin == 1 || $area == "Contabilidad"): ?>
                <div class="col-12 mb-3">
                    <div class="alert alert-danger shadow-sm">
                        <?php
                        $facturasVencidas = mysqli_query($conexion, "SELECT COUNT(*) as total FROM Contabilidad WHERE estado = 'pendiente' AND fecha_pago_saldo < CURDATE()");
                        $deudas = mysqli_query($conexion, "SELECT SUM(saldo_pendiente) as total FROM Contabilidad WHERE estado = 'pendiente'");
                        $fac = mysqli_fetch_assoc($facturasVencidas);
                        $deu = mysqli_fetch_assoc($deudas);

                        echo "<strong>Pagos vencidos:</strong> {$fac['total']} | ";
                        echo "<strong>Deuda total pendiente:</strong> S/ " . number_format($deu['total'], 2);
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>


<!-- Footer -->
<footer class="footer">
    &copy; <?php echo date("Y"); ?> Mi Empresa. Todos los derechos reservados.
</footer>

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>
<script>
    $(document).ready(function () {
        $("#toggle-sidebar").click(function () {
            $("#sidebar").toggleClass("active");
        });
    });
</script>

</body>
</html>
