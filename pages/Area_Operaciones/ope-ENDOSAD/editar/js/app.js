// ── Select2 ──────────────────────────────────────────────────────────
function initSelect2Fila(tr) {
    $(tr).find('.adicionales-select').select2({
        placeholder: 'Seleccionar…',
        width: '100%',
        dropdownParent: $('body')
    });
}
document.querySelectorAll('#bodyTours tr').forEach(tr => initSelect2Fila(tr));

// ── Auto fecha retorno ────────────────────────────────────────────────
document.addEventListener('change', function(e) {
    const el = e.target;
    const tr = el.closest('tr');
    if (!tr) return;
    if (el.classList.contains('serv-select') || el.classList.contains('fecha-salida')) {
        const servId = tr.querySelector('.serv-select').value;
        const salida = tr.querySelector('.fecha-salida').value;
        const retEl  = tr.querySelector('[name="fecha_retorno[]"]');
        if (servId && salida && DURACION[servId]) {
            const d = new Date(salida);
            d.setDate(d.getDate() + DURACION[servId] - 1);
            retEl.value = d.toISOString().split('T')[0];
        }
    }
    actualizarResumen();
});

document.addEventListener('input', function(e) {
    if (e.target.classList.contains('precio_tour') || e.target.classList.contains('monto-pago'))
        actualizarResumen();
});

// ── Tipo precio toggle ────────────────────────────────────────────────
document.getElementById('tipo_precio').addEventListener('change', function() {
    document.getElementById('total-fijo-wrap').style.display = this.value === 'total' ? '' : 'none';
});

// ── Calcular ──────────────────────────────────────────────────────────
function calcularTours() {
    let s=0, d=0;
    document.querySelectorAll('#bodyTours tr').forEach(tr => {
        const precio = parseFloat(tr.querySelector('.precio_tour')?.value) || 0;
        const moneda = tr.querySelector('[name="moneda_tour[]"]')?.value;
        moneda === 'Soles' ? (s += precio) : (d += precio);
    });
    return {s, d};
}

function calcularPagos() {
    let ts=0, td=0, as_=0, ad=0;
    document.querySelectorAll('#bodyPagos tr').forEach(tr => {
        const tipo   = tr.querySelector('[name="tipo_pago[]"]')?.value;
        const moneda = tr.querySelector('[name="moneda_multi[]"]')?.value;
        const monto  = parseFloat(tr.querySelector('.monto-pago')?.value) || 0;
        const esSoles = moneda === 'Soles';
        if (tipo === 'tour' || tipo === 'cuenta' || tipo === 'saldo') {
            esSoles ? (ts += monto) : (td += monto);
        } else if (tipo === 'adicional') {
            esSoles ? (as_ += monto) : (ad += monto);
        }
    });
    return {ts, td, as_, ad};
}

function actualizarResumen() {
    const t  = calcularTours();
    const p  = calcularPagos();
    const ss = t.s - p.ts;
    const sd = t.d - p.td;

    setText('totalToursSoles',    t.s.toFixed(2));
    setText('totalToursDolares',  t.d.toFixed(2));
    setText('pagadoToursSoles',   p.ts.toFixed(2));
    setText('pagadoToursDolares', p.td.toFixed(2));
    setText('saldoSoles',         ss.toFixed(2));
    setText('saldoDolares',       sd.toFixed(2));
    setText('pagadoAdSoles',      p.as_.toFixed(2));
    setText('pagadoAdDolares',    p.ad.toFixed(2));

    colorSaldo('saldoSoles',   'box-saldo-s', ss);
    colorSaldo('saldoDolares', 'box-saldo-d', sd);

    document.getElementById('total_operacion_input').value = t.s.toFixed(2);

    // contadores
    const nt = document.querySelectorAll('#bodyTours tr').length;
    const np = document.querySelectorAll('#bodyPagos tr').length;
    const ln = document.getElementById('lbl-n-tours'); if(ln) ln.textContent = nt+' tour(s)';
    const lp = document.getElementById('lbl-n-pagos'); if(lp) lp.textContent = np+' pago(s)';
}

function setText(id, val) { const e=document.getElementById(id); if(e) e.textContent=val; }

function colorSaldo(spanId, boxId, val) {
    const span = document.getElementById(spanId);
    const box  = document.getElementById(boxId);
    if (!span || !box) return;
    span.className = val > 0.01 ? 'rojo' : (val < -0.01 ? 'verde' : '');
    box.className  = 'resumen-box ' + (val > 0.01 ? 'saldo-rojo' : (val < -0.01 ? 'saldo-verde' : 'saldo-cero'));
}

// ── Reindexar ─────────────────────────────────────────────────────────
function reindexarFilas() {
    document.querySelectorAll('#bodyTours tr').forEach((tr, i) => {
        tr.querySelectorAll('[name^="incluye_ingreso"]').forEach(el => { el.name = `incluye_ingreso[${i}]`; });
        const ad = tr.querySelector('[name^="servicio_adicional"]');
        if (ad) ad.name = `servicio_adicional[${i}][]`;
    });
}

// ── Agregar tour ─────────────────────────────────────────────────────

// ── Completar saldo ───────────────────────────────────────────────────
document.getElementById('btnCompletarSaldo')?.addEventListener('click', () => {
    const ss = parseFloat(document.getElementById('saldoSoles')?.textContent) || 0;
    agregarPago('saldo', ss > 0 ? ss.toFixed(2) : '');
});

// ── Validación ────────────────────────────────────────────────────────
document.getElementById('formOp').addEventListener('submit', function(e) {
    const t = calcularTours();
    const p = calcularPagos();
    if (p.ts > t.s + 0.01) { alert('El pago en Soles supera el total del tour.'); e.preventDefault(); return; }
    if (p.td > t.d + 0.01) { alert('El pago en Dólares supera el total del tour.'); e.preventDefault(); }
});

// ── Init ──────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    actualizarResumen();
});