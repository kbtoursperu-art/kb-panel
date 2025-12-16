<?php
include('conexion.php');

// Consulta SQL
$sql = "
    SELECT 
        DATE_FORMAT(fecha_reserva, '%Y-%m') AS mes,
        nombre_servicio,
        COUNT(*) AS total_reservas
    FROM Operaciones
    GROUP BY mes, nombre_servicio
    ORDER BY mes ASC, total_reservas DESC
";
$resultado = $conexion->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reservas por Tour y Mes</title>
    <style>
        table {
            border-collapse: collapse;
            width: 90%;
            margin: 20px auto;
        }
        th, td {
            border: 1px solid #999;
            padding: 8px;
            text-align: center;
        }
        th {
            background-color: #f3f3f3;
        }
        h2 {
            text-align: center;
            margin-top: 30px;
        }
    </style>
</head>
<body>
<h2>📊 Reservas por Tour y por Mes</h2>
<table>
    <thead>
        <tr>
            <th>Mes</th>
            <th>Nombre del Tour</th>
            <th>Total de Reservas</th>
        </tr>

        
    </thead>
    <tbody>
        <?php while ($fila = $resultado->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($fila['mes']); ?></td>
                <td><?php echo htmlspecialchars($fila['nombre_servicio']); ?></td>
                <td><?php echo htmlspecialchars($fila['total_reservas']); ?></td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>


</body>
</html>

<?php
$conexion->close();
?>
