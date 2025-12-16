<?php
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Navegación</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        
     body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
 
        .navbar {
            background: linear-gradient(90deg, rgb(15, 30, 48), rgb(14, 26, 37));
            padding: 15px 20px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
            position: fixed;
            top: 0;
            color: #f4f4f4;
            left: 0;
            width: 100%;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .navbar .btn-outline-light {
            border: none;
            font-size: 22px;
            color: white;
        }

        .navbar .btn-outline-light:hover {
            color: #ffc107;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            color: white;
        }

        .user-info h3 {
            margin: 0;
            font-size: 16px;
            font-weight: normal;
        }

        .user-img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
        }

        .logout-btn {
            color: white;
            font-size: 18px;
            transition: 0.3s;
        }

        .logout-btn:hover {
            color: red;
        }

        .sidebar {
            width: 260px;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 65px;
            background: linear-gradient(90deg, rgb(15, 30, 48), rgb(14, 26, 37));
            padding-top: 20px;
            transition: 0.3s ease-in-out;
            box-shadow: 2px 0px 10px rgba(0, 0, 0, 0.2);
            color: white;
            text-align: center;
        }

        .sidebar .user-section {
            margin-bottom: 20px;
        }

        .sidebar .user-section img {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid white;
            object-fit: cover;
        }

        .sidebar .user-section h5,
        .sidebar .user-section p {
            margin: 5px 0;
        }

        .sidebar .nav-link {
            color: white;
            font-size: 16px;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            transition: 0.3s;
        }

        .sidebar .nav-link i {
            margin-right: 10px;
            color: #ffc107;
        }

        .sidebar .nav-link:hover {
            background-color: #1a252f;
            color: #ffc107;
        }

        .sidebar.active {
            left: -260px;
        }

        .content {
            margin-left: 260px;
            margin-top: 80px;
            padding: 20px;
            transition: 0.3s ease-in-out;
            flex-grow: 1;
        }

        .footer {
            background-color: #002147;
            color: white;
            text-align: center;
            padding: 15px;
            position: fixed;
            bottom: 0;
            width: 100%;
        }

        @media (max-width: 992px) {
            .sidebar {
                left: -260px;
            }
            .sidebar.active {
                left: 0;
            }
            .content {
                margin-left: 0;
            }
        }
</style>

   
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark">
    <button class="btn btn-outline-light" id="toggle-sidebar"><i class="fas fa-bars"></i></button>
    <div class="ml-auto d-flex align-items-center">
        <h3 class="mb-0 mr-3"><?php echo $_SESSION["Area"] ?? "Invitado"; ?></h3>
        <img src="../assets/images/logo.png" class="user-img" alt="usuario">
        <a href="../index.php" class="logout-btn ml-3"><i class="fas fa-sign-out-alt"></i></a>
    </div>
</nav>
