<!-- ── Header ─────────────────────────────────────────── -->
    <div class="page-header">
        <div>
            <div class="page-title">Agregar operación</div>
            <div class="page-sub">Registra tours, pagos y comisión</div>
        </div>
        <a href="index.php" class="page-back"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>

    <div class="client-chip">
        <i class="fas fa-user"></i>
        <?= htmlspecialchars($cliente['nombre_completo']) ?>
        <?php if ($id_grupo): ?>
            &nbsp;·&nbsp; <i class="fas fa-users" style="font-size:12px;"></i> Grupo #<?= $id_grupo ?>
        <?php endif; ?>
    </div>

    <?php if (!empty($error_msg)): ?>
    <div class="kb-alert error">
        <i class="fas fa-exclamation-circle"></i>
        <div><?= htmlspecialchars($error_msg) ?></div>
    </div>
    <?php endif; ?>