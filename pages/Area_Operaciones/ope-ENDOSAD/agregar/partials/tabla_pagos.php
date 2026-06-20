<!-- ════════════════ PAGOS ════════════════ -->
        <div class="section-card">
            <div class="section-header">
                <div class="sh-icon sh-cyan"><i class="fas fa-credit-card"></i></div>
                <div><h5>Pagos realizados</h5><small>Registra abonos separando tours y adicionales</small></div>
            </div>
            <div class="kb-table-wrap">
                <table class="kb-table" id="tablaPagos">
                    <thead>
                        <tr>
                            <th style="width:120px;">Tipo</th>
                            <th style="width:140px;">Método</th>
                            <th style="width:80px;">Moneda</th>
                            <th style="width:120px;">Monto</th>
                            <th style="width:140px;">Fecha</th>
                            <th style="width:44px;"></th>
                        </tr>
                    </thead>
                    <tbody id="bodyPagos">
                        <?php echo buildFilaPago(); ?>
                    </tbody>
                </table>
            </div>
            <div class="section-footer d-flex align-items-center gap-3 flex-wrap">
                <button type="button" class="kb-btn kb-btn-success kb-btn-sm" onclick="agregarPago()">
                    <i class="fas fa-plus"></i> Agregar pago
                </button>
                <div class="kb-tip">
                    <i class="fas fa-info-circle"></i>
                    <strong>Tour:</strong> abona al costo del servicio y afecta el saldo.
                    &nbsp;|&nbsp;
                    <strong>Adicional:</strong> bolsa de dormir, bastones, etc. Se registra aparte, no descuenta el saldo del tour.
                </div>
            </div>
        </div>