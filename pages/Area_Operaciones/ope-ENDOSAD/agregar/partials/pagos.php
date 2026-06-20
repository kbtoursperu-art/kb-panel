<!-- ===== PAGOS ===== -->
<div class="card mb-4 shadow-sm">
    <div class="card-header bg-info text-white fw-bold">💳 Pagos Realizados</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered align-middle text-center mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="130">Tipo de pago</th>
                        <th width="150">Método</th>
                        <th width="90">Moneda</th>
                        <th width="130">Monto</th>
                        <th width="150">Fecha</th>
                        <th width="50"></th>
                    </tr>
                </thead>
                <tbody id="bodyPagos">
                    <tr>
                        <td>
                            <!-- ✅ tour y adicional son tipos separados — no se mezclan en el saldo -->
                            <select name="tipo_pago[]" class="form-select form-select-sm tipo-pago-select">
                                <option value="tour">Tour</option>
                                <option value="adicional">Adicional</option>
                            </select>
                        </td>
                        <td>
                            <select name="metodo_pago_multi[]" class="form-select form-select-sm">
                                <option value="Efectivo">Efectivo</option>
                                <option value="We travel">We travel</option>
                                <option value="CULQI">CULQI</option>
                                <option value="Izipay">Izipay</option>
                                <option value="PAYPAL">PAYPAL</option>
                                <option value="Bcp">Bcp</option>
                                <option value="YAPE">YAPE</option>
                            </select>
                        </td>
                        <td>
                            <select name="moneda_multi[]" class="form-select form-select-sm">
                                <option value="Soles">S/</option>
                                <option value="Dólares">$</option>
                            </select>
                        </td>
                        <td>
                            <input type="number" step="0.01" name="monto_multi[]" class="form-control form-control-sm monto-pago" placeholder="0.00">
                        </td>
                        <td>
                            <input type="date" name="fecha_multi[]" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-danger" onclick="eliminarPago(this)">✕</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer d-flex align-items-center gap-3">
        <button type="button" class="btn btn-info btn-sm text-white" onclick="agregarPago()">➕ Agregar pago</button>
        <small class="text-muted">
            💡 <strong>Tour</strong>: abono al costo del tour. 
            &nbsp;|&nbsp; 
            <strong>Adicional</strong>: bolsa de dormir, bastones, etc. (se registran por separado, no descuentan el saldo del tour).
        </small>
    </div>
</div>