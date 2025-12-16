<?php
include('../../conexion.php');  
include('./../header.php');
include('./../sidebar.php');

$search_term = isset($_GET['search']) ? mysqli_real_escape_string($conexion, $_GET['search']) : '';

$query = "SELECT * FROM Vista_DatosClientes";
if ($search_term) {
    $query .= " WHERE nombre LIKE '%$search_term%' 
                OR apellido LIKE '%$search_term%'
                OR nro_pasaporte LIKE '%$search_term%' 
                OR grupo LIKE '%$search_term%'";
}

$result = mysqli_query($conexion, $query);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Clientes</title>

    <!-- Bootstrap SOLO para botones y textos -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="stilo.css">

    <!-- DataTables -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
</head>
<body>
<br><br><br>

<div>
    <div class="content">
        <h2 class="text-center text-white fw-bold p-2" style="background-color: #007bff;">Gestión de Clientes</h2>

        <a href="agregar.php" class="btn btn-success mb-3">Agregar Cliente</a>
        <table id="clientes-table" class="display">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Apellido</th>
                    <th>Edad</th>
                    <th>Género</th>
                    <th>Pasaporte</th>
                    <th>Foto</th>
                    <th>WhatsApp</th>
                    <th>Nacionalidad</th>
                    <th>Grupo pax</th>
                    <th>Hotel</th>
                    <th>Historial</th>
                    <th>Perfil</th>
                    <th>Correo</th>
                    <th>Acciones</th>
                </tr>

            </thead>
            <tbody>
                <?php while ($row = mysqli_fetch_assoc($result)) : ?>
                    <tr>
                        <td><?= htmlspecialchars($row['id_cliente']) ?></td>
                        <td><?= htmlspecialchars($row['nombre']) ?></td>
                        <td><?= htmlspecialchars($row['apellido']) ?></td>
                        <td><?= htmlspecialchars($row['edad']) ?></td>
                        <td><?= htmlspecialchars($row['genero']) ?></td>
                        <td><?= htmlspecialchars($row['nro_pasaporte']) ?></td>
                        <td><img src="<?= htmlspecialchars($row['foto_pasaporte']) ?>" alt="Foto" width="50" class="rounded"></td>
                        <td><?= htmlspecialchars($row['nro_whatsapp']) ?></td>
                        <td><?= htmlspecialchars($row['nacionalidad']) ?></td>
                        <td><?= htmlspecialchars($row['grupo']) ?></td>
                        <td><?= htmlspecialchars($row['hotel']) ?></td>
                        <td><a href="historial.php?id_cliente=<?= $row['id_cliente'] ?>" class="btn btn-info btn-sm">Ver Historial</a></td>
                        <td><a href="perfil.php?id_cliente=<?= $row['id_cliente'] ?>" class="btn btn-primary btn-sm">Ver Perfil</a></td>
                        <td><a href="enviar_correo.php?id_cliente=<?= $row['id_cliente'] ?>" class="btn btn-warning btn-sm">Enviar Correo</a></td>
                        <td>
                            <a href="editar.php?id_cliente=<?= $row['id_cliente'] ?>" class="btn btn-success btn-sm">Editar</a>
                            <a href="eliminar.php?id_cliente=<?= $row['id_cliente'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Seguro que deseas eliminar este cliente?')">Eliminar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <div class="excel-form-container">
    <form action="importar_excel.php" method="POST" enctype="multipart/form-data">
        <input type="file" name="archivo_excel" accept=".xlsx, .xls" required class="form-control">
        <button type="submit" class="btn btn-primary">Importar Excel</button>
        <a href="exportar_excel.php" class="btn btn-secondary">Descargar Excel</a>
    </form>
</div>
</div>

<script>
$(document).ready(function() {
    new DataTable('#clientes-table', {
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