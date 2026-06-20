<!DOCTYPE html>
<html lang="en">
<head>
    <title>Mi primer Login</title>

    <!-- JQUERY -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>

    <!-- FRAMEWORK BOOTSTRAP para el estilo de la página -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>

    <!-- Los iconos tipo Solid de Fontawesome -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.0.8/css/solid.css">
    <script src="https://use.fontawesome.com/releases/v5.0.7/js/all.js"></script>

    <!-- Nuestro css -->
    <link rel="stylesheet" type="text/css" href="static/css/index.css" th:href="@{/css/index.css}">
    
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
            box-shadow: 0 10px 25px rgba(255, 255, 255, 0);
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

    <div class="modal-dialog text-center">
        <div class="col-sm-8 main-section">
            <div class="modal-content">
                <!-- Icono de usuario -->
                <div class="col-12 user-img">
                    <img src="./assets/images/usuario.png" alt="Usuario"/>
                </div>

                <!-- Formulario de registro -->
                <form id="loginForm" class="col-12" action="register.php" method="post">
                    <h1>REGÍSTRATE</h1>

                    <!-- Campo para Nombre de usuario -->
                    <div class="form-group" id="user-group">
                        <input type="text" class="form-control" placeholder="Nombre de usuario" name="usuario" required />
                    </div>

                    <!-- Campo para Contraseña -->
                    <div class="form-group" id="contrasena-group">
                        <input type="password" class="form-control" placeholder="Contraseña" name="contraseña" required />
                    </div>

                    <!-- Campo para Selección de Área -->
                    <div class="form-group" id="select-group">
                        <select class="form-control" name="area" required>
                            <option value="" disabled selected>Seleccione área</option>
                            <option value="Operaciones">Operaciones</option>
                            <option value="Contabilidad">Contabilidad</option>
                            <option value="Ventas">Ventas</option>
                            <option value="Almacén">Almacén</option>
                            <option value="Administrador">Administrador</option>
                        </select>
                    </div>

                    <!-- Checkbox para Administrador -->
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" id="admin" name="admin" value="admin" class="form-check-input">
                            <label for="admin" class="form-check-label">¿Registrarse como administrador?</label>
                        </div>
                    </div>

                    <!-- Botón de Registro -->
                    <button type="submit" class="btn btn-primary"> <i class="fas fa-sign-in-alt"></i> Registra </button>
                </form>
            </div>
        </div>
    </div>

</body>

</html>
