<!-- ════ TOURS ════ -->
<div class="section-card">
    <div class="section-header">
        <div class="sh-left">
            <div class="sh-icon sh-green"><i class="fas fa-map-marked-alt"></i></div>
            <div><h5>Tours del grupo</h5><small>Modifica los servicios de esta operación</small></div>
        </div>
        <span id="lbl-n-tours" style="font-size:12px;color:var(--muted)"><?= count($tours) ?> tour(s)</span>
    </div>
    <div class="kb-table-wrap">
        <table class="kb-table" id="tablaTours">
            <thead>
                <tr>
                    <th style="min-width:200px;text-align:left">Servicio</th>
                    <th style="width:100px">Precio</th>
                    <th style="width:80px">Moneda</th>
                    <th style="width:130px">Salida</th>
                    <th style="width:130px">Retorno</th>
                    <th style="width:120px">Modalidad</th>
                    <th style="width:65px">Ingreso</th>
                    <th style="min-width:200px">Adicionales</th>
                    <th style="width:44px"></th>
                </tr>
            </thead>
            <tbody id="bodyTours">
                <?php foreach ($tours as $idx => $t): ?>
                <?= buildFilaTour($idx, $servicios, $t, $ADICIONALES_OPTS_LIST) ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="section-footer">
        <button type="button" class="kb-btn kb-btn-success kb-btn-sm" onclick="agregarFila()">
            <i class="fas fa-plus"></i> Agregar tour
        </button>
    </div>
</div>