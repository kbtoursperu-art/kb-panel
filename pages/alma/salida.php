<?php include '../../conexion.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Salida a Guías</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-4">
<h4>📤 Entrega de productos a guías</h4>

<div class="card p-4 shadow-sm">
<form action="acciones/salida_action.php" method="POST">

    <!-- 🔹 Selección de guía desde Planificacion -->
    <div class="mb-3">
        <label class="form-label">Guía</label>
        <select name="nombre_guia" class="form-select" required>
            <option value="">Seleccione guía</option>
            <?php
            $guias = mysqli_query($conexion, "
                SELECT DISTINCT nombre_guia 
                FROM Planificacion 
                WHERE nombre_guia IS NOT NULL AND nombre_guia != ''
                ORDER BY nombre_guia
            ");
            while($g = mysqli_fetch_assoc($guias)):
            ?>
            <option value="<?= htmlspecialchars($g['nombre_guia']) ?>">
                <?= htmlspecialchars($g['nombre_guia']) ?>
            </option>
            <?php endwhile; ?>
        </select>
    </div>

    <!-- 🔹 Selección de producto disponible -->
    <div class="mb-3">
        <label class="form-label">Producto</label>
        <select name="id_stock" id="producto" class="form-select" required>
            <option value="">Seleccione producto</option>
            <?php
            $stock = mysqli_query($conexion, "
                SELECT st.id_stock, i.nombre, st.talla, st.cantidad_disponible, i.tipo
                FROM almacen_stock st
                JOIN almacen_items i ON st.id_item = i.id_item
                WHERE st.cantidad_disponible > 0
                ORDER BY i.nombre, st.talla
            ");
            while($s = mysqli_fetch_assoc($stock)):
            ?>
            <option value="<?= $s['id_stock'] ?>" data-tipo="<?= $s['tipo'] ?>">
                <?= htmlspecialchars($s['nombre']) ?> <?= htmlspecialchars($s['talla']) ?> (Disp: <?= $s['cantidad_disponible'] ?>)
            </option>
            <?php endwhile; ?>
        </select>
    </div>

    <!-- 🔹 Cantidad y fechas -->
    <div class="mb-3">
        <label class="form-label">Cantidad</label>
        <input type="number" name="cantidad" class="form-control" min="1" required>
    </div>

    <div class="mb-3">
        <label class="form-label">Fecha de salida</label>
        <input type="date" name="fecha_salida" class="form-control" required>
    </div>

    <!-- 🔹 Garantía solo habilitada si el producto es tipo 'Garantia' -->
    <div class="mb-3">
        <label class="form-label">Garantía (si aplica)</label>
        <input type="number" step="0.01" name="garantia" id="garantia" class="form-control" placeholder="S/. 0.00" readonly>
    </div>

    <div class="mb-3">
        <label class="form-label">Observación</label>
        <textarea name="observacion" class="form-control" rows="2" placeholder="Opcional"></textarea>
    </div>

    <!-- Botón -->
    <div class="d-grid">
        <button class="btn btn-danger">Registrar salida</button>
    </div>

</form>
</div>

</div>

<!-- 🔹 Script para habilitar/deshabilitar garantía según tipo de producto -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script>
$('#producto').on('change', function() {
    var tipo = $(this).find(':selected').data('tipo');
    if(tipo === 'Garantia'){
        $('#garantia').prop('readonly', false);
    } else {
        $('#garantia').prop('readonly', true).val('');
    }
});
</script>

</body>
</html>
