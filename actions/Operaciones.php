<?php
// Aquí deberías tener tu código de conexión a la base de datos

// Verificamos si se han enviado datos del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recibimos los datos del formulario
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $email = $_POST['email'];

    // Validación de los datos, puedes hacer más validaciones aquí según tus necesidades

    // Preparamos la consulta SQL para insertar los datos
    $sql = "INSERT INTO usuarios (nombre, apellido, email) VALUES ('$nombre', '$apellido', '$email')";

    // Ejecutamos la consulta
    if (mysqli_query($conexion, $sql)) {
        echo "Los datos se han insertado correctamente.";
    } else {
        echo "Error al insertar datos: " . mysqli_error($conexion);
    }

    // Cerramos la conexión a la base de datos
    mysqli_close($conexion);
    exit(); // Finalizamos el script para evitar la renderización del formulario de nuevo
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Formulario de Usuario</title>
    <style>
        /* Estilos CSS para el modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body>

<h2>Formulario de Usuario</h2>

<!-- Botón para abrir el modal -->
<button id="openModalBtn">Abrir Modal</button>

<!-- Modal -->
<div id="myModal" class="modal">
    <div class="modal-content">
        <!-- Botón para cerrar el modal -->
        <span class="close">&times;</span>

        <!-- Formulario para agregar usuario -->
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        
            <label for="nombre">Nombre:</label>
            <input type="text" id="nombre" name="nombre" required><br><br>

            <label for="apellido">Apellido:</label>
            <input type="text" id="apellido" name="apellido" required><br><br>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required><br><br>

            <input type="submit" value="Agregar">

        </form>

    </div>
</div>

<!-- Script JavaScript para abrir/cerrar el modal -->
<script>
    // Obtén el botón para abrir el modal
    var modalBtn = document.getElementById("openModalBtn");

    // Obtén el modal
    var modal = document.getElementById("myModal");

    // Obtén el botón para cerrar el modal
    var closeBtn = document.getElementsByClassName("close")[0];

    // Cuando el usuario haga clic en el botón, abre el modal
    modalBtn.onclick = function() {
        modal.style.display = "block";
    }

    // Cuando el usuario haga clic en <span> (x), cierra el modal
    closeBtn.onclick = function() {
        modal.style.display = "none";
    }

    // Cuando el usuario haga clic fuera del modal, ciérralo
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
</script>

</body>
</html>
