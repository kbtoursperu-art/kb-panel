<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

include('conexion.php'); // Incluye la conexión

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = trim($_POST["usuario"]);
    $contraseña = trim($_POST["contraseña"]);
    $area = trim($_POST["area"]);
    $es_admin = isset($_POST["admin"]) ? 1 : 0;

    // Validar que los campos no estén vacíos
    if (empty($usuario) || empty($contraseña) || empty($area)) {
        die("Error: Todos los campos son obligatorios.");
    }

    // Hashear la contraseña de manera segura
    $contraseña_hashed = password_hash($contraseña, PASSWORD_DEFAULT);

    // Verificar si el usuario ya existe en la base de datos
    $checkUserQuery = "SELECT Usuario FROM usuarios WHERE Usuario = ?";
    $stmt = $conexion->prepare($checkUserQuery);
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        die("Error: El usuario ya está registrado.");
    }
    $stmt->close();

    // Insertar el nuevo usuario en la base de datos usando prepared statements
    $insertUserQuery = "INSERT INTO usuarios (Usuario, Contraseña, Area, EsAdmin) VALUES (?, ?, ?, ?)";
    $stmt = $conexion->prepare($insertUserQuery);
    $stmt->bind_param("sssi", $usuario, $contraseña_hashed, $area, $es_admin);

    if ($stmt->execute()) {
        $mensaje = "Usuario registrado con éxito.";
    } else {
        $mensaje = "Error al registrar usuario: " . $conexion->error;
    }

    $stmt->close();
    $conexion->close();
} else {
    header("Location: registro.php"); // Redirigir si se intenta acceder directamente
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro Exitoso</title>
    
    <!-- Estilos CSS mejorados -->
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7fc;
            margin: 0;

            
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            background-color: #fff;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 450px;
            width: 100%;
        }

        h2 {
            font-size: 30px;
            color: #3A4D98;
            margin-bottom: 20px;
        }

        p {
            font-size: 16px;
            color: #555;
            margin-bottom: 20px;
        }

        .btn {
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 30px;
            padding: 12px 25px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        .btn:hover {
            background-color: #0056b3;
            transform: scale(1.05);
        }

        .btn:active {
            transform: scale(1);
        }

        @media (max-width: 576px) {
            .container {
                padding: 30px;
            }

            h2 {
                font-size: 24px;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>¡Registro Exitoso!</h2>
        <p><?= isset($mensaje) ? htmlspecialchars($mensaje) : "Su cuenta ha sido registrada correctamente." ?></p>
        <p>Ahora puede iniciar sesión.</p>
        <a href="login.php" class="btn">Regresar al Login</a>
    </div>
</body>
</html>
