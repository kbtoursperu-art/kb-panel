<!-- ════ CLIENTES ════ -->
<div class="kb-card">
    <div class="kb-card-header">
        <div class="section-title">
            <span class="section-icon" style="background:#1a56db"><i class="bi bi-people-fill"></i></span>
            Clientes del Grupo
        </div>
        <span class="text-muted" style="font-size:12px"><?= $grupo['total_clientes'] ?? 0 ?> persona(s)</span>
    </div>
    <div class="kb-card-body p-0">
        <table class="kb-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Cliente</th>
                    <th>DNI / Pasaporte</th>
                    <th>Nacionalidad</th>
                    <th>Tipo</th>
                    <th>Hotel</th>
                    <th>Comida</th>
                    <th>Pagador</th>
                </tr>
            </thead>
            <tbody>
            <?php $i = 1; while ($c = mysqli_fetch_assoc($qClientes)): ?>
            <?php $colors = ['#1a56db','#0891b2','#16a34a','#d97706','#7c3aed','#db2777']; $col = $colors[($i-1)%count($colors)]; ?>
            <tr>
                <td style="color:var(--text-muted);font-size:12px"><?= $i++ ?></td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="client-avatar" style="background:<?= $col ?>">
                            <?= strtoupper(substr($c['nombre'],0,1).substr($c['apellido'],0,1)) ?>
                        </div>
                        <div>
                            <div class="client-name"><?= htmlspecialchars($c['nombre'].' '.$c['apellido']) ?></div>
                            <div class="client-sub">
                                <?php if ($c['email']): ?><i class="bi bi-envelope"></i> <?= htmlspecialchars($c['email']) ?><?php endif; ?>
                                <?php if ($c['telefono']): ?> &nbsp;<i class="bi bi-telephone"></i> <?= htmlspecialchars($c['telefono']) ?><?php endif; ?>
                            </div>
                        </div>
                    </div>
                </td>

                 <td>
    <span style="font-family:'DM Mono',monospace;font-size:12px">
        <?= htmlspecialchars($c['nro_pasaporte'] ?? '—') ?>
    </span>
</td>
                <td><?= htmlspecialchars($c['nacionalidad'] ?? '—') ?></td>
                <td>
                    <?php if ($c['tipo_cliente'] === 'ENDOSADOR'): ?>
                        <span class="tipo-badge tipo-adicional">Endosador</span>
                        <?php if ($c['empresa_endosadora']): ?>
                            <div style="font-size:11px;color:var(--text-muted)"><?= htmlspecialchars($c['empresa_endosadora']) ?></div>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="tipo-badge tipo-tour">KB</span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($c['hotel'] ?? '—') ?></td>
                <td>
                    <?php if ($c['comida']): ?>
                        <span class="adicional-chip"><i class="bi bi-egg-fried"></i> <?= htmlspecialchars($c['comida']) ?></span>
                    <?php else: echo '—'; endif; ?>
                </td>
                <td>
                    <?php echo $c['es_pagador']
                        ? '<span class="estado-badge estado-confirmado"><i class="bi bi-check-circle-fill"></i> Sí</span>'
                        : '<span style="color:var(--text-muted);font-size:12px">No</span>';
                    ?>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>