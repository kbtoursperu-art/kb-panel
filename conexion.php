<?php
$servidor = "localhost";  // Servidor de la base de datos (normalmente "localhost")
$usuario = "u455910502_user_kb";        // Usuario de MySQL (por defecto "root")
$contrasena = "SystemKB2025";         // Contraseña de MySQL (si no tiene, dejar vacío)
$base_de_datos = "u455910502_kb_sistema"; // Nombre de la base de datos

// Conectar a la base de datos
$conexion = mysqli_connect($servidor, $usuario, $contrasena, $base_de_datos);

// Verificar si la conexión fue exitosa
if (!$conexion) {
    die("❌ Error de conexión: " . mysqli_connect_error());
}
?>