<!-- ════ STATS ════ -->
<?php
$n_tours = mysqli_num_rows(mysqli_query($conexion,"SELECT id_detalle FROM operaciones_detalle WHERE id_operaciones = $id_operacion"));
$n_pagos = count($todos_pagos);
?>
<div class="stats-row">
    <div class="stat-card">
        <div class="stat-label"><i class="bi bi-people me-1"></i>Clientes</div>
        <div class="stat-value text-primary"><?= $grupo['total_clientes'] ?? 0 ?></div>
        <div class="stat-sub">en el grupo</div>
    </div>
    <div class="stat-card">
        <div class="stat-label"><i class="bi bi-map me-1"></i>Tours</div>
        <div class="stat-value text-info"><?= $n_tours ?></div>
        <div class="stat-sub">servicios contratados</div>
    </div>
    <div class="stat-card">
        <div class="stat-label"><i class="bi bi-cash me-1"></i>Soles pagados</div>
        <div class="stat-value text-success">S/ <?= number_format($total_soles, 2) ?></div>
        <div class="stat-sub">total registrado</div>
    </div>
    <div class="stat-card">
        <div class="stat-label"><i class="bi bi-currency-dollar me-1"></i>Dólares pagados</div>
        <div class="stat-value text-success">$ <?= number_format($total_dolares, 2) ?></div>
        <div class="stat-sub">total registrado</div>
    </div>
    <?php if ($op): ?>
    <div class="stat-card">
        <div class="stat-label"><i class="bi bi-receipt me-1"></i>Total operación</div>
        <div class="stat-value"><?= number_format($op['total_operacion'] ?? 0, 2) ?></div>
        <div class="stat-sub"><?= $op['tipo_precio'] ?? '-' ?></div>
    </div>
    <?php endif; ?>
    <?php if ($grupo['hotel']): ?>
    <div class="stat-card">
        <div class="stat-label"><i class="bi bi-building me-1"></i>Hotel grupo</div>
        <div class="stat-value" style="font-size:15px"><?= htmlspecialchars($grupo['hotel']) ?></div>
        <div class="stat-sub">alojamiento</div>
    </div>
    <?php endif; ?>
</div>