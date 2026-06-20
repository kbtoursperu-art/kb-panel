<!-- ════ CONTABILIDAD ════ -->
<?php if ($op): ?>
<div class="kb-card">
    <div class="kb-card-header">
        <div class="section-title">
            <span class="section-icon" style="background:#374151"><i class="bi bi-file-earmark-text-fill"></i></span>
            Contabilidad
        </div>
        <?php if (!empty($conta['estado'])): ?>
        <span class="estado-badge estado-<?= $conta['estado'] ?>"><?= ucfirst($conta['estado']) ?></span>
        <?php endif; ?>
    </div>
    <div class="kb-card-body">
        <div class="info-grid">
            <div class="info-item">
                <span class="info-label"><i class="bi bi-receipt"></i> Boleta a cuenta</span>
                <span class="info-value" style="font-family:'DM Mono',monospace">
                    <?= htmlspecialchars($conta['nro_boleta_cuenta'] ?? '—') ?>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label"><i class="bi bi-receipt-cutoff"></i> Boleta total</span>
                <span class="info-value" style="font-family:'DM Mono',monospace">
                    <?= htmlspecialchars($conta['nro_boleta_total'] ?? '—') ?>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label"><i class="bi bi-percent"></i> Detracción</span>
                <span class="info-value">
                    <?= $conta['detraccion'] ? 'S/ '.number_format($conta['detraccion'],2) : '—' ?>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label"><i class="bi bi-calculator"></i> IGV</span>
                <span class="info-value">
                    <?= $conta['igv'] ? 'S/ '.number_format($conta['igv'],2) : '—' ?>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label"><i class="bi bi-graph-up"></i> Comisión</span>
                <span class="info-value">
                    <?= $conta['comision'] ? 'S/ '.number_format($conta['comision'],2) : '—' ?>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label"><i class="bi bi-file-text"></i> Modalidad recibo</span>
                <span class="info-value"><?= htmlspecialchars($conta['modalidad_recibo'] ?? '—') ?></span>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
