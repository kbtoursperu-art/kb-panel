<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
const DURACION = <?= json_encode($duraciones_js) ?>;
const SERVICIOS_HTML = <?= json_encode(buildFilaTour(0, $servicios)) ?>;
const PAGO_HTML = <?= json_encode(buildFilaPago()) ?>;
</script>

<script src="js/app.js"></script>