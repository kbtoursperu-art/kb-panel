<!-- ════════════════════════════════════════
     CABECERA
═════════════════════════════════════════ -->
<div class="page-header">
    <a href="javascript:history.back()" class="back-btn">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
    <div>
        <h1 class="mb-0">
            <i class="bi bi-people-fill text-primary me-2"></i>
            <?= htmlspecialchars($grupo['nombre_grupo']) ?>
        </h1>
        <p class="subtitle mb-0">
            ID Grupo: #<?= $id_grupo ?>
            &nbsp;·&nbsp; Creado: <?= date('d/m/Y', strtotime($grupo['fecha_creacion'])) ?>
            <?php if ($id_operacion): ?>
                &nbsp;·&nbsp; Operación: #<?= $id_operacion ?>
            <?php endif; ?>
        </p>
    </div>
    <div class="ms-auto d-flex align-items-center gap-2">
        <span class="estado-badge estado-<?= $grupo['estado'] ?>">
            <i class="bi bi-circle-fill" style="font-size:8px"></i>
            <?= ucfirst($grupo['estado']) ?>
        </span>
        <?php if ($op): ?>
        <span class="estado-badge estado-<?= $op['estado'] ?? 'pendiente' ?>">
            <?= ucfirst($op['estado'] ?? 'pendiente') ?>
        </span>
        <?php endif; ?>
    </div>
</div>

<div class="main-content"></div>