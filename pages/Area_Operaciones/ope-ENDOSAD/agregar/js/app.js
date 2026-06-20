// =============================================
// DURACIÓN POR SERVICIO (días)
// =============================================
const DURACION_TOURS = {
    1:5, 2:4, 3:3, 4:5, 5:4, 6:2, 7:2, 8:7, 9:4, 10:4,
    11:2,12:1,13:2,14:2,15:5,16:4,17:4,18:4,19:4,
    20:3,21:4,22:1,23:1,24:1,25:1,26:1,27:1,28:1,
    29:1,30:1,31:1,32:1,33:1,34:1,35:4
};

const form = document.getElementById("formTours");


// =============================================
// AUTO FECHA RETORNO + recalcular siempre
// =============================================
document.addEventListener("change", function(e){
    const name = e.target.name;

    // Auto-fecha retorno al elegir servicio o fecha de salida
    if (name === "id_servicio[]" || name === "fecha_salida[]") {
        const fila   = e.target.closest("tr");
        const serv   = fila.querySelector("[name='id_servicio[]']").value;
        const salida = fila.querySelector("[name='fecha_salida[]']").value;
        const retornoInput = fila.querySelector("[name='fecha_retorno[]']");

        if (serv && salida && DURACION_TOURS[serv]) {
            const d = new Date(salida);
            d.setDate(d.getDate() + (DURACION_TOURS[serv] - 1));
            retornoInput.value = d.toISOString().split("T")[0];
        }
    }

    // ✅ Siempre recalcular — incluyendo cuando cambia tipo_pago, moneda, etc.
    actualizarResumen();
});

// =============================================
// EVENTO INPUT — montos y precios
// =============================================
document.addEventListener("input", function(e) {
    if (
        e.target.classList.contains("precio_tour") ||
        e.target.classList.contains("monto-pago")
    ) {
        actualizarResumen();
    }
});


// =============================================
// CALCULAR TOTALES TOURS (por moneda)
// =============================================
function calcularTotalTours() {
    let soles = 0, dolares = 0;
    document.querySelectorAll("#bodyTours tr").forEach(fila => {
        const precio = parseFloat(fila.querySelector(".precio_tour").value) || 0;
        const moneda = fila.querySelector("[name='moneda_tour[]']").value;
        if (moneda === "S/" || moneda === "Soles") {
            soles += precio;
        } else {
            dolares += precio;
        }
    });
    return { soles, dolares };
}


// =============================================
// CALCULAR PAGOS — separado por tipo
// =============================================
function calcularPagos() {
    let tourSoles = 0, tourDolares = 0;
    let adSoles   = 0, adDolares   = 0;

    document.querySelectorAll("#bodyPagos tr").forEach(fila => {
        const tipo   = fila.querySelector("[name='tipo_pago[]']").value;
        const moneda = fila.querySelector("[name='moneda_multi[]']").value;
        const monto  = parseFloat(fila.querySelector("[name='monto_multi[]']").value) || 0;
        const esSoles = (moneda === "S/" || moneda === "Soles");

        if (tipo === "tour") {
            esSoles ? (tourSoles   += monto) : (tourDolares   += monto);
        } else {
            // adicional — se registra por separado, NO resta del saldo de tours
            esSoles ? (adSoles     += monto) : (adDolares     += monto);
        }
    });

    return { tourSoles, tourDolares, adSoles, adDolares };
}


// =============================================
// ACTUALIZAR RESUMEN EN PANTALLA
// =============================================
function actualizarResumen() {
    const total  = calcularTotalTours();
    const pagado = calcularPagos();

    const saldoS = total.soles   - pagado.tourSoles;
    const saldoD = total.dolares - pagado.tourDolares;

    // Totales tours
    document.getElementById("totalToursSoles").value   = total.soles.toFixed(2);
    document.getElementById("totalToursDolares").value = total.dolares.toFixed(2);

    // Pagado tours
    document.getElementById("pagadoToursSoles").value   = pagado.tourSoles.toFixed(2);
    document.getElementById("pagadoToursDolares").value = pagado.tourDolares.toFixed(2);

    // Saldo tours con color
    const saldoSEl = document.getElementById("saldoSoles");
    const saldoDEl = document.getElementById("saldoDolares");

    saldoSEl.value = saldoS.toFixed(2);
    saldoDEl.value = saldoD.toFixed(2);
    saldoSEl.classList.toggle("saldo-rojo",  saldoS > 0);
    saldoSEl.classList.toggle("saldo-verde", saldoS <= 0);
    saldoDEl.classList.toggle("saldo-rojo",  saldoD > 0);
    saldoDEl.classList.toggle("saldo-verde", saldoD <= 0);

    // Adicionales (solo visual)
    document.getElementById("pagadoAdSoles").value   = pagado.adSoles.toFixed(2);
    document.getElementById("pagadoAdDolares").value = pagado.adDolares.toFixed(2);

    // Campo oculto para PHP
    document.getElementById("total_operacion_input").value = total.soles.toFixed(2);
}


// =============================================
// REINDEXAR FILAS (incluye_ingreso + adicionales)
// =============================================
function reindexarFilas() {
    document.querySelectorAll("#bodyTours tr").forEach((fila, index) => {
        // Reindexar los dos inputs de incluye_ingreso (hidden + checkbox)
        fila.querySelectorAll("[name^='incluye_ingreso']").forEach(el => {
            el.name = `incluye_ingreso[${index}]`;
        });
        // Reindexar adicionales
        const adicional = fila.querySelector("[name^='servicio_adicional']");
        if (adicional) adicional.name = `servicio_adicional[${index}][]`;
    });
}


// =============================================
// AGREGAR FILA TOUR
// =============================================
function agregarFila() {
    const body  = document.getElementById("bodyTours");
    const base  = body.querySelector("tr");
    const nueva = base.cloneNode(true);

    nueva.querySelectorAll("input").forEach(i => {
        if (i.type === "checkbox") i.checked = false;
        else i.value = "";
    });
    nueva.querySelectorAll("select").forEach(s => s.selectedIndex = 0);

    body.appendChild(nueva);
    reindexarFilas();
    actualizarResumen();
}


// =============================================
// ELIMINAR FILA TOUR
// =============================================
function eliminarFila(btn) {
    if (document.querySelectorAll("#bodyTours tr").length === 1) {
        alert("Debe haber al menos 1 tour.");
        return;
    }
    btn.closest("tr").remove();
    reindexarFilas();
    actualizarResumen();
}


// =============================================
// AGREGAR FILA PAGO
// =============================================
function agregarPago() {
    const body  = document.getElementById("bodyPagos");
    const base  = body.querySelector("tr");
    const nueva = base.cloneNode(true);

    nueva.querySelectorAll("input").forEach(i => {
        if (i.type !== "date") i.value = "";
    });
    nueva.querySelectorAll("select").forEach(s => s.selectedIndex = 0);

    body.appendChild(nueva);
    actualizarResumen();
}


// =============================================
// ELIMINAR FILA PAGO
// =============================================
function eliminarPago(btn) {
    if (document.querySelectorAll("#bodyPagos tr").length === 1) {
        alert("Debe haber al menos 1 fila de pago.");
        return;
    }
    btn.closest("tr").remove();
    actualizarResumen();
}


// =============================================
// EVENTOS DE INPUT
// =============================================
document.addEventListener("input", function(e) {
    if (
        e.target.classList.contains("precio_tour") ||
        e.target.classList.contains("monto-pago")
    ) {
        actualizarResumen();
    }
});


// =============================================
// INICIO
// =============================================
document.addEventListener("DOMContentLoaded", actualizarResumen);


// =============================================
// VALIDACIÓN AL ENVIAR
// =============================================
form.addEventListener("submit", function(e) {
    const total  = calcularTotalTours();
    const pagado = calcularPagos();

    if (pagado.tourSoles > total.soles + 0.01) {
        alert("❌ El pago en Soles supera el total del tour en Soles.");
        e.preventDefault();
        return;
    }
    if (pagado.tourDolares > total.dolares + 0.01) {
        alert("❌ El pago en Dólares supera el total del tour en Dólares.");
        e.preventDefault();
    }
});
