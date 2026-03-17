<?php
include('../../conexion.php');

/* =========================================================
   CONSULTA PRINCIPAL
   Trae:
   - Todos los grupos
   - Primer cliente (KB o END)
   - Cantidad pasajeros
   - Datos planificación
========================================================= */

$query = "
SELECT 
    g.id_grupo,
    g.nombre_grupo,

    /* ===== PRIMER CLIENTE ===== */
    (
        SELECT CONCAT(d.nombre,' ',d.apellido)
        FROM datos_clientes d

        LEFT JOIN clientes_kb kb 
               ON kb.id_cliente = d.id_cliente

        LEFT JOIN clientes_endosadores e 
               ON e.id_cliente = d.id_cliente

        WHERE 
            kb.id_grupo = g.id_grupo
            OR e.id_grupo = g.id_grupo

        LIMIT 1
    ) AS primer_cliente,

    /* ===== PASAJEROS ===== */
    (
        COUNT(DISTINCT kb.id_cliente) +
        COUNT(DISTINCT e.id_cliente)
    ) AS pasajeros,

    /* ===== PLANIFICACION ===== */
    p.id_planificacion,
    p.nombre_guia,
    p.nombre_cocinero,
    p.nombre_asistente,
    p.grupo_operativo

FROM grupos g

LEFT JOIN planificacion p 
       ON p.id_grupo = g.id_grupo

LEFT JOIN clientes_kb kb 
       ON kb.id_grupo = g.id_grupo

LEFT JOIN clientes_endosadores e 
       ON e.id_grupo = g.id_grupo

GROUP BY 
    g.id_grupo,
    g.nombre_grupo,
    p.id_planificacion,
    p.nombre_guia,
    p.nombre_cocinero,
    p.nombre_asistente,
    p.grupo_operativo

ORDER BY g.id_grupo DESC
";

$resultado = mysqli_query($conexion, $query);

if (!$resultado) {
    die("Error SQL: " . mysqli_error($conexion));
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>📋 Planificación por Grupos</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
</head>

<body>
<?php include './../sidebar.php'; ?>
<div class="content p-4">
<div class="container-fluid">

<h2 class="mb-4">📋 Planificación por Grupos</h2>

<div class="card shadow">
<div class="card-body">

<div class="table-responsive">

<table id="tablaPlanificacion" class="table table-bordered table-striped">

<thead class="table-dark text-center">
<tr>
<th>ID</th>
<th>Grupo</th>
<th>Primer Cliente</th>
<th>Pax</th>
<th>Guía</th>
<th>Cocinero</th>
<th>Asistente</th>
<th>Grupo Operativo</th>
<th>Acciones</th>
</tr>
</thead>

<tbody>

<?php if (mysqli_num_rows($resultado) > 0): ?>

<?php while ($fila = mysqli_fetch_assoc($resultado)): ?>

<tr>

<td class="text-center">
<?= $fila['id_grupo'] ?>
</td>

<td class="fw-bold text-primary">
<?= htmlspecialchars($fila['nombre_grupo']) ?>
</td>

<td>
<?= htmlspecialchars($fila['primer_cliente'] ?? '—') ?>
</td>

<td class="text-center">
<?= $fila['pasajeros'] ?? 0 ?>
</td>

<td><?= htmlspecialchars($fila['nombre_guia'] ?? '—') ?></td>
<td><?= htmlspecialchars($fila['nombre_cocinero'] ?? '—') ?></td>
<td><?= htmlspecialchars($fila['nombre_asistente'] ?? '—') ?></td>
<td><?= htmlspecialchars($fila['grupo_operativo'] ?? '—') ?></td>

<td class="text-center">

<?php if (empty($fila['id_planificacion'])): ?>

<a href="agregar.php?id_grupo=<?= $fila['id_grupo'] ?>" 
   class="btn btn-success btn-sm">
   ➕ Planificar
</a>

<?php else: ?>

<a href="editar.php?id=<?= $fila['id_planificacion'] ?>"
class="btn btn-warning btn-sm">
✏️ Editar
</a>

<a href="eliminar.php?id=<?= $fila['id_planificacion'] ?>"
class="btn btn-danger btn-sm"
onclick="return confirm('¿Eliminar esta planificación?')">
🗑 Eliminar
</a>

<?php endif; ?>

</td>

</tr>

<?php endwhile; ?>

<?php else: ?>

<tr>
<td colspan="9" class="text-center text-muted">
No hay grupos registrados
</td>
</tr>

<?php endif; ?>

</tbody>
</table>

</div>
</div>
</div>

</div>
</div>

<!-- ================= DATATABLE ================= -->

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function () {
$('#tablaPlanificacion').DataTable({
language: {
url: "//cdn.datatables.net/plug-ins/1.13.5/i18n/es-ES.json"
},
pageLength: 10,
order: [[0, 'desc']]
});
});
</script>

</body>
</html>
