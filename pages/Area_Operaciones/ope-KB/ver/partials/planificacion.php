<!-- ════ PLANIFICACION ════ -->
<?php if ($plan): ?>
<div class="kb-card">
    <div class="kb-card-header">
        <div class="section-title">
            <span class="section-icon" style="background:#7c3aed"><i class="bi bi-clipboard2-check-fill"></i></span>
            Equipo Operativo
        </div>
    </div>
    <div class="kb-card-body">
        <div class="plan-grid">
            <?php if ($plan['nombre_guia']): ?>
            <div class="plan-item">
                <i class="bi bi-person-badge"></i>
                <div>
                    <div class="plan-role">Guía</div>
                    <div class="plan-name"><?= htmlspecialchars($plan['nombre_guia']) ?></div>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($plan['nombre_cocinero']): ?>
            <div class="plan-item">
                <i class="bi bi-cup-hot"></i>
                <div>
                    <div class="plan-role">Cocinero</div>
                    <div class="plan-name"><?= htmlspecialchars($plan['nombre_cocinero']) ?></div>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($plan['nombre_asistente']): ?>
            <div class="plan-item">
                <i class="bi bi-person-check"></i>
                <div>
                    <div class="plan-role">Asistente</div>
                    <div class="plan-name"><?= htmlspecialchars($plan['nombre_asistente']) ?></div>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($plan['grupo_operativo']): ?>
            <div class="plan-item">
                <i class="bi bi-people"></i>
                <div>
                    <div class="plan-role">Grupo operativo</div>
                    <div class="plan-name"><?= htmlspecialchars($plan['grupo_operativo']) ?></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>