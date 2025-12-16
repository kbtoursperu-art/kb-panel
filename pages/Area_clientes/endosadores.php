<?php
include('../../conexion.php');  
include('./../header.php');
include('./../sidebar.php');

// Consulta para obtener clientes tipo Endosador
$query_endosadores = "
SELECT d.id_cliente, d.nombre, d.apellido, d.genero, d.nro_pasaporte,
       e.empresa_endosadora, e.contacto, e.telefono_contacto, e.email_contacto
FROM Datos_clientes d
JOIN Clientes_Endosadores e ON d.id_cliente = e.id_cliente
WHERE d.tipo_cliente = 'Endosador'
";

$result_endosadores = mysqli_query($conexion, $query_endosadores);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Clientes Endosadores</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- DataTables -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>

    <!-- Estilos personalizados -->
    <style>
        body {
            padding: 20px;
            background-color: #f8f9fa;
        }
        h2 {
            background-color: #6f42c1;
            color: white;
            padding: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
    <br><br><br>

    <h2>Clientes Endosadores</h2>

    <div class="container">
        <a href="agregar_endosador.php" class="btn btn-success mb-3">Agregar Cliente Endosador</a>

        <table id="clientes-endosadores" class="display">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Apellido</th>
                    <th>Género</th>
                    <th>Pasaporte</th>
                    <th>Empresa</th>
                    <th>Contacto</th>
                    <th>Teléfono</th>
                    <th>Email</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result_endosadores)) : ?>
                    <tr>
                        <td><?= htmlspecialchars($row['id_cliente']) ?></td>
                        <td><?= htmlspecialchars($row['nombre']) ?></td>
                        <td><?= htmlspecialchars($row['apellido']) ?></td>
                        <td><?= htmlspecialchars($row['genero']) ?></td>
                        <td><?= htmlspecialchars($row['nro_pasaporte']) ?></td>
                        <td><?= htmlspecialchars($row['empresa_endosadora']) ?></td>
                        <td><?= htmlspecialchars($row['contacto']) ?></td>
                        <td><?= htmlspecialchars($row['telefono_contacto']) ?></td>
                        <td><?= htmlspecialchars($row['email_contacto']) ?></td>
                        <td>
                            <a href="editar_endosador.php?id_cliente=<?= $row['id_cliente'] ?>" class="btn btn-success btn-sm">Editar</a>
                            <a href="eliminar_endosador.php?id_cliente=<?= $row['id_cliente'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de eliminar este cliente Endosador?')">Eliminar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- DataTable script -->
    <script>
        $(document).ready(function () {
            $('#clientes-endosadores').DataTable({
                responsive: true,
                paging: true,
                searching: true,
                ordering: true,
                info: true,
                lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "Todos"]],
                language: {
                    "lengthMenu": "Mostrar _MENU_ registros por página",
                    "zeroRecords": "No se encontraron resultados",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    "infoEmpty": "No hay registros disponibles",
                    "infoFiltered": "(filtrado de _MAX_ registros en total)",
                    "search": "Buscar:",
                    "paginate": {
                        "first": "Primero",
                        "last": "Último",
                        "next": "Siguiente",
                        "previous": "Anterior"
                    }
                }
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
