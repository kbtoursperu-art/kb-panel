<?php
session_start();

// 🔐 Validación de sesión
if (!isset($_SESSION["Usuario"])) {
    header("Location: ../index.php");
    exit();
}

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
    <title>Panel KB Adventures</title>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<link rel="stylesheet" href="/assets/css/layout.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <button class="btn btn-outline-light" id="toggle-sidebar">
        <i class="fas fa-bars"></i>
    </button>

    <div class="user-info d-flex align-items-center gap-3">
        <h5 class="m-0"><?= $area ?></h5>
        <img src="../assets/images/logo.png" class="user-img" width="40">
        <a href="../index.php" class="text-white">
            <i class="fas fa-sign-out-alt"></i>
        </a>
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

        <a href="/pages/principal.php" class="nav-link">
            <i class="fas fa-home"></i> Inicio
        </a>

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
                <a class="nav-link" href="/pages/alma/dashboard_almacen.php"> <i class="fas fa-box"></i> Almacén</a>
            </div>
        <?php endif; ?>

        <?php if ($esAdmin == 1 || $area == "Planificación"): ?>
            <a class="nav-link" href="/pages/Area_planificacion/index.php">
                <i class="fas fa-cogs"></i> Planificación
            </a>
        <?php endif; ?>

        <?php if ($esAdmin == 1 || $area == "Contabilidad"): ?>
            <a class="nav-link" href="/pages/Area_contabilidad/index.php">
                <i class="fas fa-calculator"></i> Contabilidad
            </a>
        <?php endif; ?>

        <?php if ($esAdmin == 1): ?>
            <a class="nav-link" href="/pages/admin/resumen.php">
                <i class="fas fa-chart-bar"></i> Resumen
            </a>
        <?php endif; ?>

    </nav>
</div>




<!-- =====================
     SCRIPTS
===================== -->
<script>
const sidebar = document.getElementById("sidebar");
const toggleBtn = document.getElementById("toggle-sidebar");
const content = document.querySelector(".content");

toggleBtn.addEventListener("click", (e) => {
    e.stopPropagation();

    if (window.innerWidth <= 992) {
        sidebar.classList.toggle("active");
    } else {
        sidebar.classList.toggle("hidden");
        content.classList.toggle("full");
    }
});

document.querySelectorAll(".toggle-menu").forEach(btn => {
    btn.addEventListener("click", () => {
        const submenu = document.getElementById(btn.dataset.target);
        const icon = btn.querySelector(".toggle-icon");

        submenu.style.display =
            submenu.style.display === "block" ? "none" : "block";

        icon.classList.toggle("rotate");
    });
});
</script>

</body>
</html>
