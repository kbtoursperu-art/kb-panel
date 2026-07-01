<?php
// Detectar entorno
if ($_SERVER['HTTP_HOST'] === 'localhost') {
    // 🔹 ENTORNO LOCAL (XAMPP)
    $servidor = "localhost";
    $usuario = "root";
    $contrasena = "";
    $base_de_datos = "bd_kb";
} else {
    // 🔹 ENTORNO HOSTINGER (PRODUCCIÓN)
    $servidor = "localhost";
    $usuario = "u455910502_user_kbpanel";
    $contrasena = "Kbsistema2026"; // ❗ poner SOLO en Hostinger
    $base_de_datos = "u455910502_kb_panel";
}

// Conectar a la base de datos
$conexion = mysqli_connect($servidor, $usuario, $contrasena, $base_de_datos);

// Verificar conexión
if (!$conexion) {
    die("❌ Error de conexión: " . mysqli_connect_error());
}
?>