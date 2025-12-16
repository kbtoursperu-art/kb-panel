<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Navegación</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        /* === ESTILOS GENERALES === */
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f6f8;
            margin: 0;
            padding: 0;
            display: flex;
            min-height: 100vh;
            flex-direction: column;
        }

        /* === NAVBAR SUPERIOR === */
        .navbar {
            background: linear-gradient(90deg, #0f1e30, #1a2c40);
            padding: 12px 20px;
            box-shadow: 0px 4px 10px rgba(0,0,0,0.25);
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1001;
            display: flex;
            justify-content: space-between;
            align-items: center;
            
        }

        .navbar .btn-outline-light {
            border: none;
            color: #fff;
            font-size: 22px;
        }

        .navbar .btn-outline-light:hover {
            color: #1abc9c;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            color: #fff;
        }

        .user-info h3 {
            font-size: 16px;
            font-weight: 500;
            margin: 0;
        }

        .user-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #fff;
        }

        .logout-btn {
            color: #fff;
            font-size: 18px;
            transition: 0.3s;
        }

        .logout-btn:hover {
            color: red;
        }

        /* === SIDEBAR === */
        .sidebar {
            width: 260px;
            background: linear-gradient(180deg, #1a2c40, #111d2c);
            color: #ecf0f1;
            padding: 20px 15px;
            position: fixed;
            top: 60px;
            left: 0;
            height: calc(100vh - 65px);
            transition: all 0.3s ease-in-out;
            box-shadow: 3px 0 10px rgba(0,0,0,0.25);
            overflow-y: auto;
            z-index: 1000;
             transition: transform 0.3s ease-in-out !important;


        }

        .sidebar.hidden {
            transform: translateX(-100%);
        }

        .user-section {
            text-align: center;
            margin-bottom: 20px;
        }

        .user-section img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid #1abc9c;
            object-fit: cover;
            margin-bottom: 10px;
        }

        .user-section h5 {
            margin: 0;
            color: #fff;
            font-weight: 600;
        }

        .user-section p {
            font-size: 13px;
            color: #b0bec5;
            margin: 0;
        }

        /* === LINKS === */
        .nav-link {
            color: #ecf0f1;
            display: flex;
            align-items: center;
            font-size: 15px;
            padding: 10px 14px;
            border-radius: 8px;
            transition: all 0.25s ease;
            margin-bottom: 5px;
            text-decoration: none;
        }

        .nav-link i {
            margin-right: 10px;
            font-size: 16px;
            width: 20px;
            text-align: center;
        }

        .nav-link:hover {
            background-color: #1abc9c;
            color: #fff;
            padding-left: 18px;
            box-shadow: 0 2px 6px rgba(26,188,156,0.4);
        }

        /* === SUBMENÚ === */
        .submenu {
            display: none;
            padding-left: 15px;
            margin-top: 5px;
        }

        .submenu .nav-link {
            font-size: 14px;
            color: #b0bec5;
            background: transparent;
        }

        .submenu .nav-link:hover {
            background-color: rgba(26,188,156,0.2);
            color: #fff;
        }

        .toggle-icon {
            margin-left: auto;
            transition: transform 0.3s ease;
            font-size: 12px;
        }

        .rotate {
            transform: rotate(90deg);
        }

        /* === CONTENIDO === */
        .content {
            margin-left: 260px;
            margin-top: 80px;
            padding: 20px;
            flex-grow: 1;
            transition: all 0.3s ease-in-out;
        }

        /* === FOOTER === */
        .footer {
            background-color: #0f1e30;
            color: white;
            text-align: center;
            padding: 15px;
            width: 100%;
            position: fixed;
            bottom: 0;
            left: 0;
            font-size: 14px;
        }

       /* === Sidebar preparado para animarse === */
#sidebar {
    transition: transform 0.3s ease-in-out;
}

/* === Sidebar oculto en móviles === */
@media (max-width: 992px) {
    #sidebar {
        transform: translateX(-260px);
    }

    #sidebar.active {
        transform: translateX(0);
    }

    .content {
        margin-left: 0 !important;
    }
}

        /* SCROLLBAR PERSONALIZADO */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }
        .sidebar::-webkit-scrollbar-thumb {
            background-color: #1abc9c;
            border-radius: 10px;
        }
/* === Sidebar oculto en modo escritorio cuando haga clic === */
#sidebar.active {
    transform: translateX(-260px);  /* se oculta */
}

@media (max-width: 992px) {
    #sidebar {
        transform: translateX(-260px); /* oculto por defecto */
    }
    #sidebar.active {
        transform: translateX(0); /* visible */
    }
}

.sidebar-collapsed .sidebar {
    transform: translateX(-250px);
}

.sidebar {
    transition: all .3s ease;
}

.sidebar-collapsed .content {
    margin-left: 0 !important;
}

    </style>
</head>
<body>
<!-- 🟦 NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <button class="btn btn-outline-light" id="toggle-sidebar"><i class="fas fa-bars"></i></button>
    <div class="user-info ml-auto">
        <h3><?php echo $_SESSION["Area"] ?? "Invitado"; ?></h3>
        <img src="../assets/images/logo.png" class="user-img" alt="usuario">
        <a href="../index.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i></a>
    </div>
</nav>

<!-- 🟩 SIDEBAR -->
<div class="sidebar" id="sidebar">
    <div class="user-section">
        <img src="../assets/images/logo.png" alt="Logo usuario">
        <h5><?php echo $_SESSION["Usuario"] ?? 'Usuario'; ?></h5>
        <p><?php echo $_SESSION["Area"] ?? 'Área'; ?></p>
    </div>

    <nav class="nav flex-column">
        <a class="nav-link" href="/pages/principal.php"><i class="fas fa-home"></i> Inicio</a>

        <?php
        $area = $_SESSION["Area"] ?? '';
        $esAdmin = $_SESSION["EsAdmin"] ?? 0;

        if ($esAdmin == 1 || $area === "Operaciones" || $area === "Clientes") {
            echo '
            <a class="nav-link" onclick="toggleSubmenu(\'operacionesSub\')">
                <i class="fas fa-tasks"></i> Operaciones
                <span class="toggle-icon" id="icon-operaciones">&#9654;</span>
            </a>
            <div class="submenu" id="operacionesSub">
                <a class="nav-link" href="/pages/clientes_kb/index.php"><i class="fas fa-user"></i> Clientes KB</a>
                <a class="nav-link" href="/pages/cliente_endosador/index.php"><i class="fas fa-user-friends"></i> Clientes Endosador</a>
                <a class="nav-link" href="/pages/Area_Operaciones/ope-KB/index.php"><i class="fas fa-map"></i> Operaciones KB</a>
                <a class="nav-link" href="/pages/Area_Operaciones/ope-ENDOSAD/index.php"><i class="fas fa-route"></i> Operaciones Endosador</a>
            </div>';
        }

        if ($esAdmin == 1) {
            echo '
                <a class="nav-link" href="/pages/Area_planificacion/index.php"><i class="fas fa-cogs"></i> Planificación</a>
                <a class="nav-link" href="/pages/Area_Venta/index.php"><i class="fas fa-shopping-cart"></i> Ventas</a>
                <a class="nav-link" href="/pages/Area_contabilidad/index.php"><i class="fas fa-calculator"></i> Contabilidad</a>
                <a class="nav-link" href="/pages/almacen/stock_general.php"><i class="fas fa-box"></i> Almacén</a>
                <a class="nav-link" href="/pages/admin/resumen.php"><i class="fas fa-chart-bar"></i> Resumen</a>
            ';
        } else {
            switch ($area) {
                case "Planificación":
                    echo '<a class="nav-link" href="/pages/Area_planificacion/index.php"><i class="fas fa-cogs"></i> Planificación</a>'; break;
                case "Ventas":
                    echo '<a class="nav-link" href="/pages/Area_Venta/index.php"><i class="fas fa-shopping-cart"></i> Ventas</a>'; break;
                case "Contabilidad":
                    echo '<a class="nav-link" href="/pages/Area_contabilidad/index.php"><i class="fas fa-calculator"></i> Contabilidad</a>'; break;
                case "Almacén":
                case "Almacen":
                    echo '<a class="nav-link" href="/pages/almacen/stock_general.php"><i class="fas fa-box"></i> Almacén</a>'; break;
                case "Resumen":
                    echo '<a class="nav-link" href="/pages/admin/resumen.php"><i class="fas fa-chart-bar"></i> Resumen</a>'; break;
            }
        }
        ?>
    </nav>
</div>

<!-- 🟥 FOOTER -->
<footer class="footer">
    &copy; <?php echo date('Y'); ?> KB Adventures Perú — Todos los derechos reservados
</footer>

<!-- 🟦 SCRIPT -->
<script>
const toggleBtn = document.getElementById("toggle-sidebar");
const sidebar = document.getElementById("sidebar");

toggleBtn.addEventListener("click", () => {
    sidebar.classList.toggle("active");
});

// Cerrar sidebar al hacer clic fuera (móvil)
document.addEventListener("click", (e) => {
    if (window.innerWidth <= 992) {
        if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
            sidebar.classList.remove("active");
        }
    }
});

// Submenús
function toggleSubmenu(id) {
    const submenu = document.getElementById(id);
    const icon = document.getElementById('icon-operaciones');
    const isOpen = submenu.style.display === 'block';
    document.querySelectorAll('.submenu').forEach(s => s.style.display = 'none');
    document.querySelectorAll('.toggle-icon').forEach(i => i.classList.remove('rotate'));
    if (!isOpen) {
        submenu.style.display = 'block';
        icon.classList.add('rotate');
    }
}
</script>

</body>
</html>
