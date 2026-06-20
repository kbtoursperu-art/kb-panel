<!-- ════ TOURS / DETALLE OPERACIONES ════ -->
<div class="kb-card">
    <div class="kb-card-header">
        <div class="section-title">
            <span class="section-icon" style="background:#0891b2"><i class="bi bi-map-fill"></i></span>
            Tours y Servicios Contratados
        </div>
        <?php if ($op): ?>
        <span style="font-size:12px;color:var(--text-muted)">
            Encargado: <strong><?= htmlspecialchars($op['encargado'] ?? '—') ?></strong>
            &nbsp;·&nbsp; Reserva: <?= $op['fecha_reserva'] ? date('d/m/Y', strtotime($op['fecha_reserva'])) : '—' ?>
        </span>
        <?php endif; ?>
    </div>
    <div class="kb-card-body">

    <?php if ($n_tours === 0): ?>
        <div class="empty-state"><i class="bi bi-map"></i>Sin tours registrados</div>
    <?php else:
        // Re-ejecutar query para iterar limpiamente
        $qDet2 = mysqli_query($conexion,"
            SELECT od.*, s.nombre AS nombre_servicio, s.duracion_dias,
                   DATEDIFF(od.fecha_retorno, od.fecha_salida) AS dias_calculados
            FROM operaciones_detalle od
            LEFT JOIN servicios s ON s.id_servicio = od.id_servicio
            WHERE od.id_operaciones = $id_operacion
            ORDER BY od.fecha_salida ASC
        ");
        $num_tour = 1;
        while ($d = mysqli_fetch_assoc($qDet2)):
    ?>
    <div class="tour-card">
        <div class="tour-card-header">
            <div class="tour-name">
                <i class="bi bi-compass-fill"></i>
                Tour <?= $num_tour++ ?> — <?= htmlspecialchars($d['nombre_servicio'] ?? 'Servicio #'.$d['id_servicio']) ?>
            </div>
            <div class="tour-meta">
                <?php if ($d['fecha_salida']): ?>
                <span><i class="bi bi-calendar-event"></i> Salida: <?= date('d/m/Y', strtotime($d['fecha_salida'])) ?></span>
                <?php endif; ?>
                <?php if ($d['fecha_retorno']): ?>
                <span><i class="bi bi-calendar-check"></i> Retorno: <?= date('d/m/Y', strtotime($d['fecha_retorno'])) ?></span>
                <?php endif; ?>
                <?php $dias = $d['duracion_dias'] ?? $d['dias_calculados']; ?>
                <?php if ($dias): ?>
                <span><i class="bi bi-clock"></i> <?= $dias ?> día(s)</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="tour-card-body">
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label"><i class="bi bi-train-front"></i> Modalidad retorno</span>
                    <span class="info-value">
                        <?php
                        $icon_modal = ['Carro'=>'bi-car-front','Tren'=>'bi-train-front','Caminata'=>'bi-person-walking'];
                        $im = $icon_modal[$d['modalidad_retorno']] ?? 'bi-arrow-return-left';
                        echo '<i class="bi '.$im.' me-1"></i>'.htmlspecialchars($d['modalidad_retorno'] ?? '—');
                        ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="bi bi-ticket-perforated"></i> Incluye ingreso</span>
                    <span class="info-value">
                        <?php if ($d['incluye_ingreso'] === 'SI'): ?>
                            <span class="estado-badge estado-confirmado"><i class="bi bi-check"></i> Sí incluye</span>
                        <?php else: ?>
                            <span class="estado-badge estado-cancelado"><i class="bi bi-x"></i> No incluye</span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="bi bi-currency-exchange"></i> Moneda</span>
                    <span class="info-value">
                        <?php echo $d['tipo_moneda'] === 'Dólares'
                            ? '<span class="monto-dolares"><i class="bi bi-currency-dollar"></i> Dólares</span>'
                            : '<span class="monto-soles"><i class="bi bi-cash"></i> Soles</span>';
                        ?>
                    </span>
                </div>
                <div class="info-item">
                    <span class="info-label"><i class="bi bi-tag"></i> Precio del tour</span>
                    <span class="info-value monto-val <?= $d['tipo_moneda']==='Dólares'?'monto-dolares':'monto-soles' ?>">
                        <?= $d['tipo_moneda']==='Dólares' ? '$ ' : 'S/ ' ?><?= number_format($d['precio'] ?? 0, 2) ?>
                    </span>
                </div>
            </div>

            <!-- Adicionales de este tour -->
            <?php if (!empty($adicionales[$d['id_detalle']])): ?>
            <div class="mt-3">
                <div class="info-label mb-1"><i class="bi bi-plus-circle"></i> Adicionales</div>
                <div class="adicionales-list">
                    <?php foreach ($adicionales[$d['id_detalle']] as $ad): ?>
                    <div class="adicional-chip">
                        <i class="bi bi-star-fill"></i>
                        <?= htmlspecialchars($ad['nombre']) ?>
                        <strong>
                            S/ <?= number_format($ad['precio'], 2) ?>
                        </strong>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Servicio adicional (texto libre de la columna) -->
            <?php if (!empty($d['servicio_adicional'])): ?>
            <div class="mt-3">
                <div class="info-label mb-1"><i class="bi bi-info-circle"></i> Nota adicional</div>
                <div style="background:#fefce8;border:1px solid #fde68a;border-radius:7px;padding:8px 12px;font-size:13px">
                    <?= htmlspecialchars($d['servicio_adicional']) ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endwhile; endif; ?>

    <?php if ($op && $op['observaciones']): ?>
    <div class="mt-2" style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:8px;padding:12px 16px;font-size:13px">
        <i class="bi bi-chat-left-text text-info me-2"></i>
        <strong>Observaciones:</strong> <?= htmlspecialchars($op['observaciones']) ?>
    </div>
    <?php endif; ?>
    </div>
</div>