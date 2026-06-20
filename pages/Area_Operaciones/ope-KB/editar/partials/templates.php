<!-- ════ TEMPLATES ════ -->
<template id="tplTour">
<div class="tour-card">
    <div class="tour-card-header">
        <div style="display:flex;align-items:center;gap:8px">
            <div class="tour-num">N</div>
            <span style="font-weight:600;font-size:13px">Nuevo tour</span>
        </div>
        <button type="button" class="btn-del btnDelTour"><i class="bi bi-trash3"></i></button>
    </div>
    <div class="fg3" style="margin-bottom:10px">
        <div class="field-group">
            <label class="field-label">Servicio<span class="req">*</span></label>
            <select name="id_servicio[]" class="field-input serv-select">
                <?= $opts_servicios ?>
            </select>
        </div>
        <div class="field-group">
            <label class="field-label">Precio<span class="req">*</span></label>
            <input type="number" step="0.01" min="0" name="precio_tour[]" class="field-input precio_tour" placeholder="0.00">
        </div>
        <div class="field-group">
            <label class="field-label">Moneda</label>
            <select name="moneda_tour[]" class="field-input moneda_tour">
                <option value="Soles">S/ Soles</option>
                <option value="Dólares">$ Dólares</option>
            </select>
        </div>
    </div>
    <div class="fg4" style="margin-bottom:10px">
        <div class="field-group">
            <label class="field-label">Salida</label>
            <input type="date" name="fecha_salida[]" class="field-input fecha-salida">
        </div>
        <div class="field-group">
            <label class="field-label">Retorno</label>
            <input type="date" name="fecha_retorno[]" class="field-input fecha-retorno">
        </div>
        <div class="field-group">
            <label class="field-label">Modalidad</label>
            <select name="modalidad_retorno[]" class="field-input">
                <option>Carro</option><option>Tren</option><option>Caminata</option>
            </select>
        </div>
        <div class="field-group">
            <label class="field-label">Incluye ingreso</label>
            <div style="display:flex;align-items:center;gap:7px;height:38px">
                <input type="hidden" name="incluye_ingreso[]" value="NO">
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13px;font-weight:500">
                    <input type="checkbox" name="incluye_ingreso[]" value="SI" style="width:16px;height:16px;accent-color:var(--brand)">
                    Sí incluye
                </label>
            </div>
        </div>
    </div>
    <div class="field-group" style="margin-bottom:10px">
        <label class="field-label">Nota / adicional texto libre</label>
        <input type="text" name="servicio_adicional_txt[]" class="field-input" placeholder="Ej: Boleto Machu Picchu…">
    </div>
    <div class="adicionales-block">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
            <span style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted)">
                <i class="bi bi-plus-square me-1"></i>Adicionales
            </span>
            <button type="button" class="btn-add-sm btnAddAdicional"><i class="bi bi-plus"></i> Añadir</button>
        </div>
        <div class="adicionales-container">
            <div class="adicional-row">
                <select name="adicional_nombre[__IDX__][]" class="field-input">
                    <option>Ninguna</option><option>Ingreso a Mollepata</option><option>Bolsa de Dormir</option>
                    <option>Bastones</option><option>Hotel</option><option>Montaña Huayna Picchu</option>
                    <option>Montaña Machu Picchu</option><option>Trans. Mochilas Playa-Idro</option>
                    <option>Trans. Mochilas Hidro-Aguas</option><option>Otro</option>
                </select>
                <input type="number" step="0.01" min="0" name="adicional_precio[__IDX__][]" class="field-input adic-precio" placeholder="S/ 0.00" value="0">
                <button type="button" class="btn-del btnDelAdicional"><i class="bi bi-x"></i></button>
            </div>
        </div>
    </div>
</div>
</template>

<template id="tplPago">
<div class="pago-card">
    <div class="pago-card-header">
        <div style="display:flex;align-items:center;gap:8px">
            <span class="tc tc-tour">Tour</span>
            <span style="font-size:12px;color:var(--text-muted)">Nuevo pago</span>
        </div>
        <button type="button" class="btn-del btnDelPago"><i class="bi bi-trash3"></i></button>
    </div>
    <div class="fg4" style="margin-bottom:8px">
        <div class="field-group">
            <label class="field-label">Tipo pago</label>
            <select name="tipo_pago[]" class="field-input tipo_pago_sel">
                <option value="tour">Tour</option>
                <option value="adicional">Adicional</option>
                <option value="cuenta">A Cuenta</option>
                <option value="saldo">Saldo</option>
                <option value="reembolso">Reembolso</option>
            </select>
        </div>
        <div class="field-group">
            <label class="field-label">Monto<span class="req">*</span></label>
            <input type="number" step="0.01" min="0" name="monto_pago[]" class="field-input monto_pago" placeholder="0.00">
        </div>
        <div class="field-group">
            <label class="field-label">Método de pago</label>
            <select name="metodo_pago[]" class="metodo-select">
                <option>Efectivo</option><option>We travel</option><option>CULQI</option>
                <option>Izipay</option><option>PAYPAL</option><option>BCP</option>
                <option>YAPE</option><option>Transferencia</option><option>Otro</option>
            </select>
        </div>
        <div class="field-group">
            <label class="field-label">Moneda</label>
            <select name="moneda_pago[]" class="field-input moneda_pago_sel">
                <option value="Soles">S/ Soles</option>
                <option value="Dólares">$ Dólares</option>
            </select>
        </div>
    </div>
    <div class="fg3">
        <div class="field-group">
            <label class="field-label">Fecha pago</label>
            <input type="date" name="fecha_pago[]" class="field-input">
        </div>
        <div class="field-group">
            <label class="field-label">Tipo de cambio</label>
            <input type="number" step="0.001" min="1" name="tipo_cambio[]" class="field-input" placeholder="1.000" value="1">
        </div>
        <div class="field-group">
            <label class="field-label">Observación</label>
            <input type="text" name="obs_pago[]" class="field-input" placeholder="Referencia…">
        </div>
    </div>
</div>
</template>