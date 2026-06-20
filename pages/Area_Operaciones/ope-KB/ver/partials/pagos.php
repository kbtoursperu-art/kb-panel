<!-- ════ PAGOS COMPLETOS ════ -->
<div class="kb-card">
    <div class="kb-card-header">
        <div class="section-title">
            <span class="section-icon" style="background:#16a34a"><i class="bi bi-cash-stack"></i></span>
            Registro de Pagos
        </div>
        <span style="font-size:12px;color:var(--text-muted)"><?= $n_pagos ?> transacción(es)</span>
    </div>
    <?php if (empty($todos_pagos)): ?>
        <div class="kb-card-body"><div class="empty-state"><i class="bi bi-cash"></i>Sin pagos registrados</div></div>
    <?php else: ?>
    <div style="overflow-x:auto">
        <table class="kb-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Tipo</th>
                    <th>Método pago</th>
                    <th>Moneda</th>
                    <th>Monto</th>
                    <th>Tipo cambio</th>
                    <th>Monto conv.</th>
                    <th>Fecha</th>
                    <th>Observación</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($todos_pagos as $idx => $p): ?>
            <tr>
                <td style="color:var(--text-muted);font-size:12px"><?= $idx+1 ?></td>
                <td><span class="tipo-badge tipo-<?= $p['tipo'] ?>"><?= ucfirst($p['tipo']) ?></span></td>
                <td>
                    <?php
                    $icons_mp = ['Efectivo'=>'bi-cash','Transferencia'=>'bi-bank','Yape'=>'bi-phone','Plin'=>'bi-phone-fill','Tarjeta'=>'bi-credit-card'];
                    $ic = $icons_mp[$p['metodo_pago']] ?? 'bi-wallet2';
                    echo '<i class="bi '.$ic.' me-1"></i>'.htmlspecialchars($p['metodo_pago'] ?? '—');
                    ?>
                </td>
                <td><?= htmlspecialchars($p['moneda'] ?? '—') ?></td>
                <td>
                    <span class="monto-val <?= $p['moneda']==='Dólares'?'monto-dolares':'monto-soles' ?><?= $p['tipo']==='reembolso'?' monto-neg':'' ?>">
                        <?= $p['tipo']==='reembolso'?'-':'' ?>
                        <?= $p['moneda']==='Dólares'?'$ ':'S/ ' ?><?= number_format($p['monto'], 2) ?>
                    </span>
                </td>
                <td style="font-family:'DM Mono',monospace;font-size:12px;color:var(--text-muted)">
                    <?= $p['tipo_cambio'] != 1 ? number_format($p['tipo_cambio'], 3) : '—' ?>
                </td>
                <td>
                    <?php if ($p['monto_convertido'] && $p['monto_convertido'] != $p['monto']): ?>
                        <span class="monto-val monto-soles">S/ <?= number_format($p['monto_convertido'], 2) ?></span>
                    <?php else: echo '—'; endif; ?>
                </td>
                <td style="font-size:12px;color:var(--text-muted)">
                    <?= $p['fecha'] ? date('d/m/Y', strtotime($p['fecha'])) : '—' ?>
                </td>
                <td style="font-size:12px;color:var(--text-muted);max-width:180px">
                    <?= htmlspecialchars($p['observacion'] ?? '') ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="totales-box">
        <div class="total-item">
            <span class="t-label">Total en Soles</span>
            <span class="t-value monto-soles">S/ <?= number_format($total_soles, 2) ?></span>
        </div>
        <div class="total-item">
            <span class="t-label">Total en Dólares</span>
            <span class="t-value monto-dolares">$ <?= number_format($total_dolares, 2) ?></span>
        </div>
        <?php if ($op): ?>
        <div class="total-item ms-auto">
            <span class="t-label">Total operación</span>
            <span class="t-value"><?= number_format($op['total_operacion'] ?? 0, 2) ?></span>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
