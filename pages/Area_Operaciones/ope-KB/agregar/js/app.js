// ── Duraciones por servicio (desde BD vía PHP) ──────────────────────────

let tourIdx = 1;

// ── Inicializar Select2 en fila ─────────────────────────────────────────
function initSelect2Fila(tr) {
    $(tr).find('.adicionales-select').select2({
        placeholder: 'Seleccionar…',
        width: '100%',
        dropdownParent: $('body')
    });
}
function calcularDias(tr) {
    const salida = tr.querySelector('.fecha-salida');
    const retorno = tr.querySelector('.fecha-retorno');

    if (!salida || !retorno) return;
    if (!salida.value || !retorno.value) return;

    const f1 = new Date(salida.value);
    const f2 = new Date(retorno.value);

    const dias = Math.round((f2 - f1) / (1000 * 60 * 60 * 24)) + 1;

    // si algún día quieres mostrarlo:
    // console.log('Días:', dias);
}
// ── Auto fecha retorno ──────────────────────────────────────────────────
document.addEventListener('change', function (e) {
    const el = e.target;
    const tr = el.closest('tr');
    if (!tr) return;

    const servSelect = tr.querySelector('.serv-select');
    const salida = tr.querySelector('.fecha-salida');
    const retEl = tr.querySelector('.fecha-retorno');

    // 🔥 AUTO RETORNO
    if (el.classList.contains('serv-select') ||
        el.classList.contains('fecha-salida')) {

        const servId = servSelect?.value;

        if (servId && salida?.value && typeof DURACION !== 'undefined' && DURACION[servId]) {

            const d = new Date(salida.value);
            d.setDate(d.getDate() + DURACION[servId] - 1);

            if (retEl) {
                retEl.value = d.toISOString().split('T')[0];
            }
        }
    }

    // 🔥 calcular días seguro
    calcularDias(tr);

    // 🔥 actualizar resumen SOLO si existe
    if (typeof actualizarResumen === 'function') {
        actualizarResumen();
    }
});
document.addEventListener('input', function (e) {
    if (e.target.classList.contains('precio_tour') || e.target.classList.contains('monto-pago')) {
        actualizarResumen();
    }
});

// ── Tipo de precio toggle ───────────────────────────────────────────────
const tipoPrecio = document.getElementById('tipo_precio');
if (tipoPrecio) {
    tipoPrecio.addEventListener('change', function () {
        const wrap = document.getElementById('total-fijo-wrap');
        if (wrap) wrap.style.display = this.value === 'total' ? '' : 'none';
    });
}

// ── Calcular totales tours ──────────────────────────────────────────────
function calcularTours() {
    let s = 0, d = 0;
    document.querySelectorAll('#bodyTours tr').forEach(tr => {
        const precio = parseFloat(tr.querySelector('.precio_tour').value) || 0;
        const moneda = tr.querySelector('[name="moneda_tour[]"]').value;
        moneda === 'Soles' ? (s += precio) : (d += precio);
    });
    return { s, d };
}

// ── Calcular pagos ─────────────────────────────────────────────────────────────
function calcularPagos() {
    let ts = 0, td = 0, as_ = 0, ad = 0;
    document.querySelectorAll('#bodyPagos tr').forEach(tr => {
        const tipo   = tr.querySelector('[name="tipo_pago[]"]').value;
        const moneda = tr.querySelector('[name="moneda_multi[]"]').value;
        const monto  = parseFloat(tr.querySelector('.monto-pago').value) || 0;
        const esSoles = moneda === 'Soles';
        if (tipo === 'tour') { esSoles ? (ts += monto) : (td += monto); }
        else                 { esSoles ? (as_ += monto) : (ad += monto); }
    });
    return { ts, td, as_, ad };
}

// ── Actualizar resumen visual 
// ───────────────────────────────────────────
function actualizarResumen() {
    const t = calcularTours();
    const p = calcularPagos();
    const ss = t.s - p.ts;
    const sd = t.d - p.td;
    setText('totalToursSoles',   t.s.toFixed(2));
    setText('totalToursDolares', t.d.toFixed(2));
    setText('pagadoToursSoles',   p.ts.toFixed(2));
    setText('pagadoToursDolares', p.td.toFixed(2));
    setText('saldoSoles',   ss.toFixed(2));
    setText('saldoDolares', sd.toFixed(2));
    setText('pagadoAdSoles',   p.as_.toFixed(2));
    setText('pagadoAdDolares', p.ad.toFixed(2));
    colorSaldo('saldoSoles',   'box-saldo-s', ss);
    colorSaldo('saldoDolares', 'box-saldo-d', sd);
    document.getElementById('total_operacion_input').value = t.s.toFixed(2);
}

function setText(id, val) {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
}

function colorSaldo(spanId, boxId, val) {
    const span = document.getElementById(spanId);
    const box  = document.getElementById(boxId);
    if (!span || !box) return;
    span.className = val > 0.01 ? 'rojo' : (val < -0.01 ? 'verde' : '');
    box.className  = 'resumen-box ' + (val > 0.01 ? 'saldo-rojo' : (val < -0.01 ? 'saldo-verde' : 'saldo-cero'));
}

// ── Reindexar filas ─────────────────────────────────────────────────────
function reindexarFilas() {
    document.querySelectorAll('#bodyTours tr').forEach((tr, i) => {
        tr.querySelectorAll('[name^="incluye_ingreso"]').forEach(el => { el.name = `incluye_ingreso[${i}]`; });
        const ad = tr.querySelector('[name^="servicio_adicional"]');
        if (ad) ad.name = `servicio_adicional[${i}][]`;
    });
}

// ── Agregar / eliminar tour ─────────────────────────────────────────────
function agregarFila() {
    const body   = document.getElementById('bodyTours');
    const tmpDiv = document.createElement('tbody');
    tmpDiv.innerHTML = SERVICIOS_HTML.replace(/\[0\]/g, `[${tourIdx++}]`);
    const newTr = tmpDiv.querySelector('tr');
    body.appendChild(newTr);
    initSelect2Fila(newTr);
    reindexarFilas();
    actualizarResumen();
}

function eliminarFila(btn) {
    if (document.querySelectorAll('#bodyTours tr').length === 1) {
        alert('Debe haber al menos un tour.');
        return;
    }
    btn.closest('tr').remove();
    reindexarFilas();
    actualizarResumen();
}

// ── Agregar / eliminar pago ───────────────────────────────────────────────────────────────
function agregarPago() {
    const body   = document.getElementById('bodyPagos');
    const tmpDiv = document.createElement('tbody');
    tmpDiv.innerHTML = PAGO_HTML;
    body.appendChild(tmpDiv.querySelector('tr'));
    actualizarResumen();
}

function eliminarPago(btn) {
    if (document.querySelectorAll('#bodyPagos tr').length === 1) {
        alert('Debe haber al menos una fila de pago.');
        return;
    }
    btn.closest('tr').remove();
    actualizarResumen();
}

// ── Validación antes de enviar ───────────────────────────────────────────────────────
document.getElementById('formOp').addEventListener('submit', function (e) {
    const t = calcularTours();
    const p = calcularPagos();
    if (p.ts > t.s + 0.01) {
        alert('El pago en Soles supera el total del tour en Soles.');
        e.preventDefault(); return;
    }
    if (p.td > t.d + 0.01) {
        alert('El pago en Dólares supera el total del tour en Dólares.');
        e.preventDefault();
    }
});

// ── Init ────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    initSelect2Fila(document.querySelector('#bodyTours tr'));
    actualizarResumen();
});