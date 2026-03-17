
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include('conexion.php');
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario_ingresado = trim($_POST["usuario"]);
    $contraseña_ingresada = trim($_POST["contraseña"]);
    $area_seleccionada = trim($_POST["area"]);

    // Validar si se ha seleccionado un área
    if ($area_seleccionada === "Seleccione área") {
        echo "<script>alert('Por favor, seleccione un área.');</script>";
        exit();
    }

    // Buscar usuario en la base de datos
    $sql = "SELECT ID, Usuario, Contraseña, Area, EsAdmin FROM usuarios WHERE Usuario = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("s", $usuario_ingresado);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows == 1) {
        $row = $result->fetch_assoc();
        $id_usuario = $row["ID"];
        $nombre_usuario = $row["Usuario"];
        $contraseña_hash = $row["Contraseña"];
        $area_usuario = $row["Area"];
        $es_admin = $row["EsAdmin"];

        // Verificar contraseña
        if (password_verify($contraseña_ingresada, $contraseña_hash)) {
            // Verificar área seleccionada
            if (
    $area_usuario === $area_seleccionada || ($area_usuario === "Operaciones" && in_array($area_seleccionada, ["Contabilidad", "Planificación"]))
) {
                // Iniciar sesión
                $_SESSION["ID"] = $id_usuario;
                $_SESSION["usuarios"] = $nombre_usuario;
                $_SESSION["Area"] = $area_usuario;
                $_SESSION["EsAdmin"] = $es_admin;

                // Redirigir a la página principal
                header("Location: pages/principal.php");
                exit();
            } else {
                echo "<script>alert('El área seleccionada no coincide con la asignada al usuario.');</script>";
            }
        } else {
            echo "<script>alert('Usuario o contraseña incorrectos.');</script>";
        }
    } else {
        echo "<script>alert('Usuario o contraseña incorrectos.');</script>";
    }

    $stmt->close();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio de Sesión</title>
    
    <!-- JQUERY -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>

    <!-- BOOTSTRAP -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>

    <!-- FONT AWESOME -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.0.8/css/solid.css">
    <script src="https://use.fontawesome.com/releases/v5.0.7/js/all.js"></script>

    <!-- GOOGLE FONT -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(to right,rgb(248, 228, 52), #2575fc);
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
        }

        .main-section {
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            padding: 30px;
            width: 100%;
            max-width: 400px;
        }

        .user-img img {
            width: 100px;
            height: 100px;
            margin-bottom: 20px;
        }

        .form-control {
            border-radius: 30px;
            border: 1px solid #ddd;
            padding: 10px 20px;
        }

        .form-control:focus {
            box-shadow: 0 0 5px rgba(0, 0, 255, 0.2);
            border-color: #6a11cb;
        }

        .btn-primary {
            background: #2575fc;
            border: none;
            border-radius: 30px;
            padding: 10px 20px;
            transition: background 0.3s ease;
            width: 100%;
        }

        .btn-primary:hover {
            background: #6a11cb;
        }

        .forgot a {
            color: #2575fc;
            text-decoration: none;
        }

        .forgot a:hover {
            text-decoration: underline;
        }

        .error-modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .error-modal-content {
            background-color: #fff;
            border-radius: 10px;
            margin: 15% auto;
            padding: 20px;
            width: 90%;
            max-width: 400px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .error-close {
            color: #aaa;
            font-size: 20px;
            font-weight: bold;
            cursor: pointer;
        }

        .error-close:hover {
            color: black;
        }

        .error-modal-content p {
            color: #d9534f;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="main-section text-center">
        <div class="user-img">
            <img src="/assets/images/usuario.png" alt="Usuario">

        </div>
        <form id="loginForm" method="post">
            <div class="form-group">
                <input type="text" class="form-control" placeholder="Nombre de usuario" name="usuario" required>
            </div>
            <div class="form-group">
                <input type="password" class="form-control" placeholder="Contraseña" name="contraseña" required>
            </div>
            <div class="form-group">
              <select class="form-control" name="area" required>
                    <option selected disabled>Seleccione área</option>
                    <option value="Operaciones">Operaciones</option>
                    <option value="Almacén">Almacén</option>
                    <option value="Contabilidad">Contabilidad</option>
                    <option value="Planificación">Planificación</option>
                    <option value="Administrador">Administrador</option>
                </select>

            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> Ingresar</button>
        </form>
        <div class="forgot mt-3">
            <a href="registration.php">Registrarse</a>
        </div>
    </div>

    <!-- Error Modal -->
    <div id="errorModal" class="error-modal">
        <div class="error-modal-content">
            <span class="error-close">&times;</span>
            <p id="errorMessage"></p>
        </div>
    </div>

    <script>
        function showErrorModal(message) {
            document.getElementById("errorMessage").innerText = message;
            document.getElementById("errorModal").style.display = "block";
        }cxd

        document.querySelector(".error-close").onclick = function () {
            document.getElementById("errorModal").style.display = "none";
        };

        document.getElementById("loginForm").onsubmit = function (event) {
            const area = document.querySelector("select[name='area']").value;
            if (area === "Seleccione área") {
                event.preventDefault();
                showErrorModal("Por favor, seleccione un área.");
            }
        };
    </script>
</body>
</html>
