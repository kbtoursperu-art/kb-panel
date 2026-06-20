<?php
// =====================
// CONFIGURACIÓN DE CONEXIÓN
// =====================

// 🔹 Cambia esto según donde estés trabajando:
// "local" para XAMPP
// "produccion" para Hostinger
$entorno = "local"; // <-- Cambiar a "produccion" al subir al hosting

if ($entorno === "local") {
    // 🔹 ENTORNO LOCAL (XAMPP)
    $servidor = "127.0.0.1";
    $usuario = "root";
    $contrasena = "";
    $base_de_datos = "bd_kb"; // nombre de tu base local
} else {
    // 🔹 ENTORNO PRODUCCIÓN (Hostinger)
    $servidor = "localhost"; // normalmente Hostinger también usa localhost
    $usuario = "u455910502_user_kb"; // tu usuario de Hostinger
    $contrasena = "SystemKB2025"; // tu contraseña de Hostinger
    $base_de_datos = "u455910502_kb_sistemkb"; // tu base en Hostinger
}

// =====================
// CONEXIÓN A LA BASE DE DATOS
// =====================
$conexion = mysqli_connect($servidor, $usuario, $contrasena, $base_de_datos);

// Verificar conexión
if (!$conexion) {
    die("❌ Error de conexión a la base de datos: " . mysqli_connect_error());
}

// Establecer codificación UTF-8
mysqli_set_charset($conexion, "utf8");

// ✅ Listo, ahora $conexion está disponible para tus páginas
?>
