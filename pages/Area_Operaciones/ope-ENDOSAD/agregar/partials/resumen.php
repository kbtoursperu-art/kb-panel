<!-- ===== RESUMEN ===== -->
 <!-- ════════════════ RESUMEN ════════════════ -->
        <div class="section-card">
            <div class="section-header">
                <div class="sh-icon sh-amber"><i class="fas fa-calculator"></i></div>
                <div><h5>Resumen de operación</h5><small>Calculado automáticamente en tiempo real</small></div>
            </div>
            <div class="section-body">

                <div class="divider-label">Totales tours</div>
                <div class="resumen-grid" style="grid-template-columns: repeat(auto-fit, minmax(140px,1fr));">
                    <div class="resumen-box">
                        <div class="r-label">Total S/</div>
                        <div class="r-val"><span id="totalToursSoles">0.00</span> <span class="r-cur">soles</span></div>
                    </div>
                    <div class="resumen-box">
                        <div class="r-label">Total $</div>
                        <div class="r-val"><span id="totalToursDolares">0.00</span> <span class="r-cur">USD</span></div>
                    </div>
                    <div class="resumen-box">
                        <div class="r-label">Pagado tours S/</div>
                        <div class="r-val"><span id="pagadoToursSoles">0.00</span> <span class="r-cur">soles</span></div>
                    </div>
                    <div class="resumen-box">
                        <div class="r-label">Pagado tours $</div>
                        <div class="r-val"><span id="pagadoToursDolares">0.00</span> <span class="r-cur">USD</span></div>
                    </div>
                    <div class="resumen-box" id="box-saldo-s">
                        <div class="r-label">Saldo S/</div>
                        <div class="r-val"><span id="saldoSoles">0.00</span> <span class="r-cur">soles</span></div>
                    </div>
                    <div class="resumen-box" id="box-saldo-d">
                        <div class="r-label">Saldo $</div>
                        <div class="r-val"><span id="saldoDolares">0.00</span> <span class="r-cur">USD</span></div>
                    </div>
                </div>

                <div class="divider-label" style="margin-top:20px;">Adicionales y comisión</div>
                <div class="resumen-grid" style="grid-template-columns: repeat(auto-fit, minmax(140px,1fr));">
                    <div class="resumen-box">
                        <div class="r-label">Pagado adics. S/</div>
                        <div class="r-val"><span id="pagadoAdSoles">0.00</span> <span class="r-cur">soles</span></div>
                    </div>
                    <div class="resumen-box">
                        <div class="r-label">Pagado adics. $</div>
                        <div class="r-val"><span id="pagadoAdDolares">0.00</span> <span class="r-cur">USD</span></div>
                    </div>
                    <div class="resumen-box">
                        <div class="r-label">Comisión</div>
                        <input type="number" step="0.01" name="comision" class="kb-input" placeholder="0.00" style="margin-top:4px;font-size:16px;font-weight:600;">
                    </div>
                </div>

            </div>
        </div>
