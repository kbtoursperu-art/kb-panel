<tbody id="bodyTours">

        <tr>

            <td>
                <select name="nombre_servicio[]" class="form-select" required>
                            <option value="">-- Seleccione una opción --</option>

                            <option value="SALKANTAY A MACHU PICCHU 5 DÍAS">SALKANTAY A MACHU PICCHU 5 DÍAS</option>
                            <option value="SALKANTAY A MACHU PICCHU 4 DÍAS">SALKANTAY A MACHU PICCHU 4 DÍAS</option>
                            <option value="SALKANTAY A MACHU PICCHU 3 DÍAS">SALKANTAY A MACHU PICCHU 3 DÍAS</option>
                            <option value="SALKANTAY TREK 5D/4N WITH LUXURY DOMES (PRIVADO)">SALKANTAY TREK 5D / 4N WITH LUXURY DOMES</option>
                            <option value="SALKANTAY TREK 4D / 3N WITH LUXURY DOMES (PRIVADO)">SALKANTAY TREK 4D / 3N WITH LUXURY DOMES</option>
                            <option value="SALKANTAY & HUMANTAY LAKE 2D WITH LUXURY DOMES (PRIVADO)">SALKANTAY TREK 2D / 1N WITH LUXURY DOMES</option>
                            <option value="SALKANTAY Y LAGUNA HUMANTAY 2 DÍAS">SALKANTAY Y LAGUNA HUMANTAY 2 DÍAS</option>
                            <option value="SALKANTAY Y CAMINO INCA 7 DÍAS (PRIVADO)">SALKANTAY Y CAMINO INCA 7 DÍAS (PRIVADO)</option>
                            <option value="CAMINO INCA 4 DÍAS">CAMINO INCA 4 DÍAS</option>
                            <option value="CAMINO INCA 4 DÍAS (PRIVADO)">CAMINO INCA 4 DÍAS (PRIVADO)</option>
                            <option value="CAMINO INCA 2 DÍAS">CAMINO INCA 2 DÍAS</option>
                            <option value="MACHU PICCHU DE UN DÍA">MACHU PICCHU DE UN DÍA</option>
                            <option value="MACHU PICCHU EN TREN 2 DÍAS">MACHU PICCHU EN TREN 2 DÍAS</option>
                            <option value="VALLE SAGRADO A MACHU PICCHU 2 DÍAS">VALLE SAGRADO A MACHU PICCHU 2 DÍAS</option>
                            <option value="CHOQUEQUIRAO 5 DÍAS (PRIVADO)">CHOQUEQUIRAO 5 DÍAS (PRIVADO)</option>
                            <option value="CHOQUEQUIRAO 4 DÍAS">CHOQUEQUIRAO 4 DÍAS</option>
                            <option value="CHOQUEQUIRAO 4 DÍAS (PRIVADO)">CHOQUEQUIRAO 4 DÍAS (PRIVADO)</option>
                            <option value="LARES A MACHU PICCHU 4 DÍAS (PRIVADO)">LARES A MACHU PICCHU 4 DÍAS (PRIVADO)</option>
                            <option value="AUSANGATE Y MONTAÑA DE COLORES 4 DÍAS">AUSANGATE Y MONTAÑA DE COLORES 4 DÍAS</option>
                            <option value="HUCHUY QOSQO 3 DÍAS (PRIVADO)">HUCHUY QOSQO 3 DÍAS (PRIVADO)</option>
                            <option value="INCA JUNGLE TRAIL 4 DAYS">INCA JUNGLE TRAIL 4 DAYS</option>
                            <option value="LAGUNA HUMANTAY DE UN DIA">LAGUNA HUMANTAY DE UN DÍA</option>
                            <option value="MONTAÑA DE COLORES DE UN DIA">MONTAÑA DE COLORES DE UN DÍA</option>
                            <option value="PALCOYO DE UN DIA">PALCOYO DE UN DÍA</option>
                            <option value="VALLE SAGRADO VIP DE UN DIA">VALLE SAGRADO VIP DE UN DÍA</option>
                            <option value="VALLE TRADICIONAL">VALLE TRADICIONAL</option>
                            <option value="7 LAGUNAS DE AUSANGATE DE UN DIA">7 LAGUNAS DE AUSANGATE DE UN DÍA</option>
                            <option value="MARAS MORAY DE UN DIA">MARAS MORAY DE UN DÍA</option>
                            <option value="Q’ESHUACHAKA Y 4 LAGUNAS DE UN DÍA">Q’ESHUACHAKA Y 4 LAGUNAS DE UN DÍA</option>
                            <option value="WAQRAPUKARA DE UN DIA">WAQRAPUKARA DE UN DÍA</option>
                            <option value="CITY TOUR CUSCO MEDIO DIA">CITY TOUR CUSCO MEDIO DÍA</option>
                            <option value="CUATRIMOTOS">CUATRIMOTOS</option>
                            <option value="ICA – PARACAS DE UN DIA">ICA – PARACAS DE UN DÍA</option>
                            <option value="PUNO DE UN DÍA">PUNO DE UN DÍA</option>
                            <option value="MANU 4 DÍAS Y 3 NOCHES">MANU 4 DÍAS Y 3 NOCHES</option>
                        </select>
            </td>

            <td>
                <input type="number"
                       step="0.01"
                       name="precio_tour[]"
                       class="form-control precio_tour">
            </td>

            <td>
                <input type="date"
                       name="fecha_salida[]"
                       class="form-control">
            </td>

            <td>
                <input type="date"
                       name="fecha_retorno[]"
                       class="form-control">
            </td>
            <td>
                    <div class="col-md-4">
                        <label>Modalidad Retorno</label>
                        <select name="modalidad_retorno[]" class="form-select">
                            <option value="">-- Seleccione --</option>
                            <option value="Tren">Con Tren</option>
                            <option value="Carro">Con Carro</option>
                            <option value="Sin retorno">Sin Retorno</option>
                        </select>
                    </div>
            </td>
            <td>
                
                        <div class="form-check">
                            <input type="checkbox" name="incluye_ingreso[]" class="form-check-input">
                            <label class="form-check-label">¿Incluye Ingreso?</label>
                        </div>
                    
            </td>
                    
            <td>
               
                        <select name="servicio_adicional[0][]" class="form-select" multiple>
                            <option value="Ninguna">Ninguna</option>
                            <option value="Ingreso a Mollepata">Ingreso a Mollepata</option>
                            <option value="Bolsa de Dormir">Bolsa de Dormir</option>
                            <option value="Bastones">Bastones</option>
                            <option value="Hotel">Hotel</option>
                            <option value="Montaña Huayna Picchu">Montaña Huayna Picchu</option>
                            <option value="Montaña Machu Picchu">Montaña Machu Picchu</option>
                            <option value="Trans. Mochilas Playa-Idro">Trans. Mochilas Playa-Idro</option>
                            <option value="Trans. Mochilas Hidro-Aguas">Trans. Mochilas Hidro-Aguas</option>
                        </select>
                 
            </td>
                    

            <td>
                <button type="button"
                        class="btn btn-danger"
                        onclick="eliminarFila(this)">
                    X
                </button>
            </td>

        </tr>

    </tbody>






    <tbody id="bodyPagos">

<tr>

<td>
<select name="tipo_pago[]" class="form-select">
<option value="adicional">Adicional</option>
</select>
</td>

<td>
<select name="metodo_pago_multi[]" class="form-select">
<option>Efectivo</option>
<option>YAPE</option>
<option>Bcp</option>
<option>PAYPAL</option>
<option>Izipay</option>
<option>CULQI</option>
<option>We travel</option>
</select>
</td>

<td>
<select name="moneda_multi[]" class="form-select">
<option>Soles</option>
<option>Dólares</option>
</select>
</td>

<td>
<input type="number" step="0.01" name="monto_multi[]" class="form-control">
</td>

<td>
<input type="date" name="fecha_multi[]" class="form-control">
</td>

<td>
<button type="button" class="btn btn-danger" onclick="eliminarPago(this)">
X
</button>
</td>

</tr>

</tbody>