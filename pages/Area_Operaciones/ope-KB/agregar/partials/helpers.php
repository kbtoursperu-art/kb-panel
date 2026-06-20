<?php
// ─── PHP helpers para renderizar filas ────────────────────────────────────
function buildFilaTour(int $idx, array $servicios): string {
    $opts = '<option value="">— Seleccionar servicio —</option>';
    foreach ($servicios as $s) {
        $opts .= '<option value="' . $s['id_servicio'] . '">' . htmlspecialchars($s['nombre']) . '</option>';
    }
    $adOpts = '<option value="Ninguna">Ninguna</option>
        <option value="Ingreso a Mollepata">Ingreso a Mollepata</option>
        <option value="Bolsa de Dormir">Bolsa de Dormir</option>
        <option value="Bastones">Bastones</option>
        <option value="Hotel">Hotel</option>
        <option value="Montaña Huayna Picchu">Montaña Huayna Picchu</option>
        <option value="Montaña Machu Picchu">Montaña Machu Picchu</option>
        <option value="Trans. Mochilas Playa-Idro">Trans. Mochilas Playa-Idro</option>
        <option value="Trans. Mochilas Hidro-Aguas">Trans. Mochilas Hidro-Aguas</option>';

    return <<<HTML
<tr>
    <td><select name="id_servicio[]" class="kb-input kb-select serv-select">$opts</select></td>
    <td><input type="number" step="0.01" name="precio_tour[]" class="kb-input precio_tour" placeholder="0.00"></td>
    <td>
        <select name="moneda_tour[]" class="kb-input kb-select moneda-tour-sel">
            <option value="Soles">S/</option>
            <option value="Dólares">$</option>
        </select>
    </td>
    <td><input type="date" name="fecha_salida[]"  class="kb-input fecha-salida"></td>
    <td><input type="date" name="fecha_retorno[]" class="kb-input fecha-retorno"></td>
    <td>
        <select name="modalidad_retorno[]" class="kb-input kb-select">
            <option value="">—</option>
            <option value="Tren">Tren</option>
            <option value="Carro">Carro</option>
            <option value="Caminata">Caminata</option>
            <option value="Sin retorno">Sin retorno</option>
        </select>
    </td>
    <td>
        <div class="check-wrap">
            <input type="hidden"   name="incluye_ingreso[$idx]" value="NO">
            <input type="checkbox" name="incluye_ingreso[$idx]" value="SI" class="kb-checkbox" title="Incluye ingreso">
        </div>
    </td>
    <td>
        <select name="servicio_adicional[$idx][]" multiple class="adicionales-select">$adOpts</select>
    </td>
    <td>
        <button type="button" class="kb-btn kb-btn-danger kb-btn-xs" onclick="eliminarFila(this)" title="Eliminar">
            <i class="fas fa-times"></i>
        </button>
    </td>
</tr>
HTML;
}

function buildFilaPago(): string {
    $hoy = date('Y-m-d');
    return <<<HTML
<tr>
    <td>
        <select name="tipo_pago[]" class="kb-input kb-select tipo-pago-select">
            <option value="tour">Tour</option>
            <option value="adicional">Adicional</option>
        </select>
    </td>
    <td>
        <select name="metodo_pago_multi[]" class="kb-input kb-select">
            <option value="Efectivo">Efectivo</option>
            <option value="We travel">We travel</option>
            <option value="CULQI">CULQI</option>
            <option value="Izipay">Izipay</option>
            <option value="PAYPAL">PAYPAL</option>
            <option value="Bcp">BCP</option>
            <option value="YAPE">YAPE</option>
        </select>
    </td>
    <td>
        <select name="moneda_multi[]" class="kb-input kb-select">
            <option value="Soles">S/</option>
            <option value="Dólares">$</option>
        </select>
    </td>
    <td><input type="number" step="0.01" name="monto_multi[]" class="kb-input monto-pago" placeholder="0.00"></td>
    <td><input type="date"   name="fecha_multi[]" class="kb-input" value="$hoy"></td>
    <td>
        <button type="button" class="kb-btn kb-btn-danger kb-btn-xs" onclick="eliminarPago(this)" title="Eliminar">
            <i class="fas fa-times"></i>
        </button>
    </td>
</tr>
HTML;
}
?>
