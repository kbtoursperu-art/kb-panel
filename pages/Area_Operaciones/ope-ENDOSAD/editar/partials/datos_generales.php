<!-- ════ DATOS GENERALES ════ -->
<div class="section-card">
    <div class="section-header">
        <div class="sh-left">
            <div class="sh-icon sh-blue"><i class="fas fa-clipboard-list"></i></div>
            <div><h5>Datos generales</h5><small>Reserva, encargado y tipo de precio</small></div>
        </div>
    </div>
    <div class="section-body">
        <div class="row g-3">
            <div class="col-md-3">
                <label class="kb-label">Fecha de reserva</label>
                <input type="date" name="fecha_reserva[]" class="kb-input" value="<?= $op['fecha_reserva'] ?>">
            </div>
            <div class="col-md-3">
                <label class="kb-label">Encargado</label>
                <div style="position:relative">
                    <i class="fas fa-user" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--sub);font-size:14px"></i>
                    <input type="text" name="Encargado[]" class="kb-input" style="padding-left:34px" placeholder="Nombre del encargado" value="<?= htmlspecialchars($op['encargado'] ?? '') ?>">
                </div>
            </div>
            <div class="col-md-2">
                <label class="kb-label">Estado</label>
                <select name="estado_op" class="kb-input kb-select">
                    <?php foreach(['pendiente','confirmado','cancelado'] as $e): ?>
                    <option value="<?=$e?>" <?=$op['estado']===$e?'selected':''?>><?=ucfirst($e)?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="kb-label">Tipo de precio</label>
                <select name="tipo_precio" id="tipo_precio" class="kb-input kb-select">
                    <option value="por_tour" <?=$op['tipo_precio']==='por_tour'?'selected':''?>>Por tour (automático)</option>
                    <option value="total"    <?=$op['tipo_precio']==='total'   ?'selected':''?>>Total fijo</option>
                </select>
            </div>
            <div class="col-md-2" id="total-fijo-wrap" style="<?=$op['tipo_precio']==='total'?'':'display:none'?>">
                <label class="kb-label">Total fijo</label>
                <div style="position:relative">
                    <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--sub);font-size:13px">S/</span>
                    <input type="number" step="0.01" name="total_operacion" id="total_operacion_input" class="kb-input" style="padding-left:32px" placeholder="0.00"
                        value="<?=$op['tipo_precio']==='total'?htmlspecialchars($op['total_operacion']):''?>">
                </div>
            </div>
            <div class="col-12">
                <label class="kb-label">Observaciones</label>
                <textarea name="observaciones[]" class="kb-input" rows="2" placeholder="Notas internas, indicaciones especiales…"><?= htmlspecialchars($op['observaciones'] ?? '') ?></textarea>
            </div>
        </div>
    </div>
</div>