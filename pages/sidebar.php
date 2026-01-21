<?php
session_start();

// 🔐 Validación de sesión
if (!isset($_SESSION["Usuario"])) {
    header("Location: ../index.php");
    exit();
}

// Sanitización segura
function limpiar($v) { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
$usuario = limpiar($_SESSION["Usuario"] ?? "Usuario");
$area    = limpiar($_SESSION["Area"] ?? "Área");
$esAdmin = $_SESSION["EsAdmin"] ?? 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Navegación</title>

    <!-- Bootstrap / Iconos -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        :root {
            --navbar-height: 60px;
            --sidebar-width: 260px;
            --color-primario: #1a2c40;
            --color-secundario: #0f1e30;
        }

        body {
            font-family: "Segoe UI", sans-serif;
            margin: 0;
            padding: 0;
            background: #f4f6f8;
        }

        /* 🔵 NAVBAR */
        .navbar {
            background: linear-gradient(90deg, var(--color-secundario), var(--color-primario));
            height: var(--navbar-height);
            display: flex;
            justify-content: space-between;
            padding: 0 20px;
            position: fixed;
            top: 0; left: 0;
            width: 100%;
            z-index: 1000;
            color: white;
        }

        .navbar .btn-outline-light {
            border: none;
            font-size: 22px;
        }

        /* 🟩 SIDEBAR */
        #sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--color-primario), #111d2c);
            height: 100vh;
            position: fixed;
            top: var(--navbar-height);
            left: 0;
            padding: 20px 15px;
            overflow-y: auto;
            transition: transform .3s ease-in-out;
            color: white;
        }

        /* Móvil oculto */
        @media (max-width: 992px) {
            #sidebar { transform: translateX(-260px); }
            #sidebar.active { transform: translateX(0); }
        }

        /* Escritorio */
        @media (min-width: 993px) {
            #sidebar.hidden { transform: translateX(-260px); }
        }

        /* 🟧 Usuario */
        .user-section {
            text-align: center;
            margin-bottom: 25px;
        }
        .user-section img {
            width: 80px; height: 80px;
            border-radius: 50%;
            border: 3px solid #1abc9c;
            margin-bottom: 10px;
        }

        /* Links */
        .nav-link {
            color: #ecf0f1;
            display: flex;
            align-items: center;
            padding: 10px 14px;
            border-radius: 8px;
            margin-bottom: 5px;
            transition: .25s;
        }
        .nav-link:hover {
            background: #1abc9c;
            color: #fff;
            padding-left: 18px;
        }
        .nav-link i {
            width: 22px; margin-right: 10px;
        }

        /* Submenús */
        .submenu {
            display: none;
            margin-left: 15px;
        }

        .toggle-icon {
            margin-left: auto;
            transition: .3s;
        }
        .rotate {
            transform: rotate(90deg);
        }

        /* Contenido */
        .content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            margin-top: calc(var(--navbar-height) + 10px);
            transition: .3s;
        }

        @media (max-width: 992px) {
            .content { margin-left: 0; }
        }

        /* Footer */
        .footer {
            background: var(--color-secundario);
            color: white;
            text-align: center;
            padding: 12px;
            position: fixed;
            bottom: 0;
            width: 100%;
        }

        /* Scroll */
        #sidebar::-webkit-scrollbar { width: 6px; }
        #sidebar::-webkit-scrollbar-thumb {
            background: #1abc9c;
            border-radius: 10px;
        }

        /* OCULTAR SIDEBAR EN ESCRITORIO */
#sidebar.hidden {
    transform: translateX(-260px);
}

/* AJUSTAR CONTENIDO CUANDO SE OCULTA */
.content.full {
    margin-left: 0;
}

    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <button class="btn btn-outline-light" id="toggle-sidebar"><i class="fas fa-bars"></i></button>

    <div class="user-info d-flex align-items-center gap-3">
        <h5 class="m-0"><?= $area ?></h5>
        <img src="../assets/images/logo.png" class="user-img" width="40" height="40">
        <a href="../index.php" class="logout-btn text-white"><i class="fas fa-sign-out-alt"></i></a>
    </div>
</nav>

<!-- SIDEBAR -->
<div id="sidebar">
    <div class="user-section">
        <img src="../assets/images/logo.png">
        <h5><?= $usuario ?></h5>
        <p><?= $area ?></p>
    </div>

    <nav class="nav flex-column">

        <a href="/pages/principal.php" class="nav-link"><i class="fas fa-home"></i> Inicio</a>

        <!-- 🟦 Operaciones (Admin | Operaciones | Clientes) -->
        <?php if ($esAdmin == 1 || $area == "Operaciones" || $area == "Clientes"): ?>
            <a class="nav-link toggle-menu" data-target="subOperaciones">
                <i class="fas fa-tasks"></i> Operaciones
                <span class="toggle-icon">&#9654;</span>
            </a>
            <div class="submenu" id="subOperaciones">
                <a class="nav-link" href="/pages/clientes_kb/index.php">Clientes KB</a>
                <a class="nav-link" href="/pages/cliente_endosador/index.php">Clientes Endosador</a>
                <a class="nav-link" href="/pages/Area_Operaciones/ope-KB/index.php">Operaciones KB</a>
                <a class="nav-link" href="/pages/Area_Operaciones/ope-ENDOSAD/index.php">Operaciones Endosador</a>
            </div>
        <?php endif; ?>

        <!-- ÁREAS DINÁMICAS -->
        <?php if ($esAdmin == 1 || $area == "Planificación"): ?>
            <a class="nav-link" href="/pages/Area_planificacion/index.php"><i class="fas fa-cogs"></i> Planificación</a>
        <?php endif; ?>
        
        <?php if ($esAdmin == 1 || $area == "Contabilidad"): ?>
            <a class="nav-link" href="/pages/Area_contabilidad/index.php"><i class="fas fa-calculator"></i> Contabilidad</a>
        <?php endif; ?>

        <?php if ($esAdmin == 1 || $area == "Almacén"): ?>
            <a class="nav-link" href="/pages/almacen/stock_general.php"><i class="fas fa-box"></i> Almacén</a>
        <?php endif; ?>

        <?php if ($esAdmin == 1): ?>
            <a class="nav-link" href="/pages/admin/resumen.php"><i class="fas fa-chart-bar"></i> Resumen</a>
        <?php endif; ?>

    </nav>
</div>

<!-- FOOTER -->
<footer class="footer">
    &copy; <?= date("Y") ?> KB Adventures Perú — Todos los derechos reservados
</footer>

<!-- SCRIPTS -->
<script>
const sidebar = document.getElementById("sidebar");
const toggleBtn = document.getElementById("toggle-sidebar");

// Mostrar/Ocultar Sidebar
toggleBtn.addEventListener("click", () => {
    sidebar.classList.toggle("active");
});

// Cerrar en móviles al hacer clic afuera
document.addEventListener("click", (e) => {
    if (window.innerWidth <= 992) {
        if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
            sidebar.classList.remove("active");
        }
    }
});

// Submenús dinámicos
document.querySelectorAll(".toggle-menu").forEach(btn => {
    btn.addEventListener("click", () => {
        const submenu = document.getElementById(btn.dataset.target);
        const icon = btn.querySelector(".toggle-icon");
        
        document.querySelectorAll(".submenu").forEach(s => s.style.display = "none");
        document.querySelectorAll(".toggle-icon").forEach(i => i.classList.remove("rotate"));

        if (submenu.style.display !== "block") {
            submenu.style.display = "block";
            icon.classList.add("rotate");
        }
    });
});
</script>

</body>
</html>
