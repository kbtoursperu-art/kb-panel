<?php
// HTML de fila vacía para JS (index 999 → reemplazado en JS)
$FILA_TOUR_VACIA = buildFilaTour(999, $servicios, [], $ADICIONALES_OPTS_LIST);
$FILA_PAGO_VACIA = buildFilaPago([]);
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
const DURACION      = <?= json_encode($duraciones_js) ?>;
const FILA_TOUR_TPL = <?= json_encode($FILA_TOUR_VACIA) ?>;
const FILA_PAGO_TPL = <?= json_encode($FILA_PAGO_VACIA) ?>;
let tourIdx = <?= count($tours) ?>;

function agregarFila() {

    const tbody = document.getElementById('bodyTours');

    const div = document.createElement('div');

    div.innerHTML =
        '<table><tbody>' +
        FILA_TOUR_TPL.replace(/\[999\]/g, '[' + tourIdx + ']') +
        '</tbody></table>';

    const tr = div.querySelector('tr');

    tbody.appendChild(tr);

    tourIdx++;
}
function eliminarFila(btn) {
    if (document.querySelectorAll('#bodyTours tr').length === 1) {
        alert('Debe haber al menos un tour.');
        return;
    }

    const tr = btn.closest('tr');

    try {
        $(tr).find('.adicionales-select').select2('destroy');
    } catch(e) {
        console.log(e);
    }

    tr.remove();

    reindexarFilas();
    actualizarResumen();
}

function agregarPago() {

    const tbody = document.getElementById('bodyPagos');

    const div = document.createElement('div');

    div.innerHTML =
        '<table><tbody>' +
        FILA_PAGO_TPL +
        '</tbody></table>';

    const tr = div.querySelector('tr');

    tbody.appendChild(tr);
}
function eliminarPago(btn) {

    if (document.querySelectorAll('#bodyPagos tr').length === 1) {
        alert('Debe haber al menos una fila de pago.');
        return;
    }

    btn.closest('tr').remove();

    actualizarResumen();
}
</script>