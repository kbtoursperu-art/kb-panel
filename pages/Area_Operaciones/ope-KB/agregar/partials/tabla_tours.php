<!-- ════════════════ TOURS ════════════════ -->
        <div class="section-card">
            <div class="section-header">
                <div class="sh-icon sh-green"><i class="fas fa-map-marked-alt"></i></div>
                <div><h5>Tours del grupo</h5><small>Agrega uno o más servicios a esta operación</small></div>
            </div>
            <div class="kb-table-wrap">
                <table class="kb-table" id="tablaTours">
                    <thead>
                        <tr>
                            <th style="min-width:200px;">Servicio</th>
                            <th style="width:100px;">Precio</th>
                            <th style="width:80px;">Moneda</th>
                            <th style="width:130px;">Salida</th>
                            <th style="width:130px;">Retorno</th>
                            <th style="width:120px;">Modalidad</th>
                            <th style="width:65px;">Ingreso</th>
                            <th style="min-width:200px;">Adicionales</th>
                            <th style="width:44px;"></th>
                        </tr>
                    </thead>
                    <tbody id="bodyTours">
                        <?php echo buildFilaTour(0, $servicios); ?>
                    </tbody>
                </table>
            </div>
            <div class="section-footer">
                <button type="button" class="kb-btn kb-btn-success kb-btn-sm" onclick="agregarFila()">
                    <i class="fas fa-plus"></i> Agregar tour
                </button>
            </div>
        </div>