<?php 
include '../../conexion.php'; 
// 🟢 Consulta principal: muestra todas las operaciones con su planificación (si existe)
$query = "
SELECT 
    o.id_operaciones, 
    o.nombre_servicio, 
    o.fecha_salida, 
    o.fecha_retorno, 
    p.id_planificacion, 
    p.nombre_guia, 
    p.nombre_cocinero, 
    p.nombre_asistente 
FROM Operaciones o 
LEFT JOIN Planificacion p ON o.id_operaciones = p.id_operaciones
ORDER BY o.id_operaciones DESC
";
$resultado = mysqli_query($conexion, $query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📋 Lista de Planificación</title>

    <!-- Estilos -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link rel="stylesheet" href="stilo.css">
</head>
<body>
<?php include '../sidebar.php'; ?>
<!-- Botón de menú responsive -->
<button class="toggle-btn" onclick="toggleSidebar()">☰</button>

<!-- Contenido principal -->
<div class="contenido-planificacion">
    <div class="container-fluid mt-4">
        <h2 class="titulo-seccion">📋 Lista de Planificación</h2>

        <div class="card p-3">
            <div class="table-responsive">
                <table id="tablaPlanificacion" class="table table-striped table-bordered nowrap">
                    <thead class="table-primary text-center">
                        <tr>
                            <th>ID Planificación</th>
                            <th>Servicio</th>
                            <th>Fecha Salida</th>
                            <th>Fecha Retorno</th>
                            <th>Guía</th>
                            <th>Cocinero</th>
                            <th>Asistente</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($resultado && mysqli_num_rows($resultado) > 0):
                            while ($fila = mysqli_fetch_assoc($resultado)): ?>
                                <tr>
                                    <td class="text-center"><?= $fila['id_planificacion'] ?? '—' ?></td>
                                    <td><?= htmlspecialchars($fila['nombre_servicio'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($fila['fecha_salida'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($fila['fecha_retorno'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($fila['nombre_guia'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($fila['nombre_cocinero'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($fila['nombre_asistente'] ?? '-') ?></td>
                                    <td class="text-center">
                                        <?php if (empty($fila['id_planificacion'])): ?>
                                            <a href="agregar.php?id_operaciones=<?= $fila['id_operaciones'] ?>" class="btn btn-success btn-sm">➕ Agregar</a>
                                        <?php else: ?>
                                            <a href="editar.php?id=<?= $fila['id_planificacion'] ?>" class="btn btn-warning btn-sm">✏️ Editar</a>
                                            <a href="eliminar.php?id=<?= $fila['id_planificacion'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar esta planificación?')">🗑 Eliminar</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile;
                        else: ?>
                            <tr><td colspan="8" class="text-center text-muted">No hay registros disponibles</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    $('#tablaPlanificacion').DataTable({
        language: { url: "//cdn.datatables.net/plug-ins/1.13.5/i18n/es-ES.json" },
        pageLength: 10,
        order: [[0, 'desc']]
    });
});

// Sidebar toggle en pantallas pequeñas
function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('active');
}
</script>

</body>
</html>
