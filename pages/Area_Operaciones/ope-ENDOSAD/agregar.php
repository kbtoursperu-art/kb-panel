
<?php
ob_start();
include '../../../conexion.php';
// Validar ID cliente
if (!isset($_GET['id_cliente'])) {
    die("❌ Falta el ID del cliente.");
}

$id_cliente = (int)$_GET['id_cliente'];

// Obtener nombre cliente
$sqlCliente = "SELECT CONCAT(nombre,' ',apellido) AS nombre FROM Datos_clientes WHERE id_cliente=$id_cliente";
$resCliente = mysqli_query($conexion, $sqlCliente);
$cliente = mysqli_fetch_assoc($resCliente);

// ======================= GUARDAR =======================
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    foreach ($_POST['nombre_servicio'] as $i => $v) {

        $nombre_servicio   = $_POST['nombre_servicio'][$i];
        $fecha_reserva     = $_POST['fecha_reserva'][$i];
        $fecha_salida      = $_POST['fecha_salida'][$i];
        $fecha_retorno     = $_POST['fecha_retorno'][$i];
        $modalidad_retorno = $_POST['modalidad_retorno'][$i];
        $incluye_ingreso = isset($_POST['incluye_ingreso'][$i]) ? 'Con ingreso' : 'Sin ingreso';

$servicios = $_POST['servicio_adicional'][$i] ?? [];

if (is_array($servicios)) {
    $servicio_adicional = implode(', ', $servicios);
} else {
    $servicio_adicional = $servicios;
}

        $observaciones = $_POST['observaciones'][$i];
        $encargado     = $_POST['encargado'][$i];

        $metodo_pago   = $_POST['metodo_pago'][$i];
        $tipo_moneda   = $_POST['tipo_moneda'][$i];
        $precio        = floatval($_POST['precio_servicio'][$i]);
        $pagado        = floatval($_POST['pagado_a_cuenta'][$i]);
        $saldo         = $precio - $pagado;
        // ADICIONAL
        $precio_adicional = floatval($_POST['precio_servicio_adicional'][$i] ?? 0);
        $pagado_adicional = floatval($_POST['pagado_adicional'][$i] ?? 0);
        $saldo_adicional  = $precio_adicional - $pagado_adicional;
        $tipo_moneda_adicional = $_POST['tipo_moneda_adicional'][$i] ?? null;

        // INSERT OPERACIONES
        $sqlOp = "INSERT INTO Operaciones 
        (id_cliente, nombre_servicio, fecha_reserva, fecha_salida, fecha_retorno, modalidad_retorno, incluye_ingreso, servicio_adicional, observaciones, Encargado)
        VALUES (?,?,?,?,?,?,?,?,?,?)";

        $stmtOp = mysqli_prepare($conexion, $sqlOp);
        mysqli_stmt_bind_param(
            $stmtOp,
            "isssssssss",
            $id_cliente,
            $nombre_servicio,
            $fecha_reserva,
            $fecha_salida,
            $fecha_retorno,
            $modalidad_retorno,
            $incluye_ingreso,
            $servicio_adicional,
            $observaciones,
            $encargado
        );
        mysqli_stmt_execute($stmtOp);
        $id_operaciones = mysqli_insert_id($conexion);

        // INSERT CONTABILIDAD
$sqlCont = "INSERT INTO Contabilidad
(
    id_operaciones, metodo_pago, tipo_moneda, precio_servicio, pagado_a_cuenta, saldo_pendiente, precio_servicio_adicional, tipo_moneda_adicional, pagado_adicional,
    saldo_adicional
)
VALUES (?,?,?,?,?,?,?,?,?,?)";

$stmtCont = mysqli_prepare($conexion, $sqlCont);
mysqli_stmt_bind_param(
    $stmtCont, "issddddsdd", $id_operaciones, $metodo_pago, $tipo_moneda, $precio, $pagado, $saldo, $precio_adicional,
    $tipo_moneda_adicional, $pagado_adicional, $saldo_adicional
);
        mysqli_stmt_execute($stmtCont);
 
}
header("Location: index.php?mensaje=agregado");
    exit;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Nueva Operación Endosador</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- ✅ BOOTSTRAP -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- (opcional) tu CSS -->
<link rel="stylesheet" href="../../stilo.css">
</head>

<body class="bg-light">

<!-- ✅ SIDEBAR (AHORA SÍ) -->
<?php include '../../sidebar.php'; ?>

<!-- ✅ CONTENIDO -->
<div class="container-fluid">
    <div class="container mt-4">

        <div class="card shadow">
            <div class="card-body">

                <h4 class="text-primary mb-4">
                    ➕ Nueva Operación Endosador: <?= htmlspecialchars($cliente['nombre']) ?>
                </h4>

                <form method="POST">
        <div id="contenedorTours">

            <!-- ================= TOUR ================= -->
            <div class="tour-item card shadow p-4 mb-4">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label>Servicio</label>
                        <select name="nombre_servicio[]" class="form-control" required>
                            <option value="">-- Seleccione --</option>
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
                    </div>

                    <div class="col-md-3">
                        <label>Fecha Reserva</label>
                        <input type="date" name="fecha_reserva[]" class="form-control" value="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="col-md-3">
                        <label>Fecha Salida</label>
                        <input type="date" name="fecha_salida[]" class="form-control">
                    </div>

                    <div class="col-md-3">
                        <label>Fecha Retorno</label>
                        <input type="date" name="fecha_retorno[]" class="form-control">
                    </div>

                    <div class="col-md-3">
                        <label>Modalidad Retorno</label>
                        <select name="modalidad_retorno[]" class="form-control">
                            <option value="">--</option>
                            <option value="Tren">Tren</option>
                            <option value="Carro">Carro</option>
                            <option value="Sin retorno">Sin retorno</option>
                        </select>
                    </div>

                    <div class="col-md-3 mt-4">
                        <input type="checkbox" name="incluye_ingreso[]" class="form-check-input">
                        <label class="form-check-label">Incluye Ingreso</label>
                    </div>

                    <div class="col-md-6">
                        <label>Servicio Adicional</label>
                        <select name="servicio_adicional[]" class="form-control" multiple>
                            <option value="Ninguna">Ninguna</option>
                             <option value="Ingreso a Mollepata">Ingreso a Mollepata</option>
                            <option value="Bolsa de Dormir">Bolsa de Dormir</option>
                            <option value="Trans. Mochilas Playa-Idro">Trans. Mochilas Playa-Idro</option>
                            <option value="Trans. Mochilas Hidro-Aguas">Trans. Mochilas Hidro-Aguas</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label>Observaciones</label>
                        <textarea name="observaciones[]" class="form-control"></textarea>
                    </div>

                    <div class="col-md-4">
                        <label>Encargado</label>
                        <input type="text" name="encargado[]" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label>Método Pago</label>
                        <select name="metodo_pago[]" class="form-control">
                            <option value="Efectivo">Efectivo</option>
                            <option value="We travel">We travel</option>
                             <option value="CULQI">CULQI</option>
                            <option value="Izipay">Izipay</option>
                            <option value="PAYPAL">PAYPAL</option>
                            <option value="Bcp">Bcp</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label>Moneda</label>
                        <select name="tipo_moneda[]" class="form-control" required>
                            <option value="Soles">Soles</option>
                            <option value="Dólares">Dólares</option>
                        </select>

                    </div>

                    <div class="col-md-4">
                        <label>Precio</label>
                        <input type="number" step="0.01" name="precio_servicio[]" class="form-control precio_servicio">
                    </div>

                    <div class="col-md-4">
                        <label>Pagado</label>
                        <input type="number" step="0.01" name="pagado_a_cuenta[]" class="form-control pagado_a_cuenta">
                    </div>

                    <div class="col-md-4">
                        <label>Saldo</label>
                        <input type="number" step="0.01" name="saldo_pendiente[]" class="form-control saldo_pendiente" readonly>
                    </div>
                </div>
                <hr>
<h6 class="text-secondary">💰 Servicio Adicional</h6>

<div class="row g-3">
    <div class="col-md-4">
        <label>Precio Adicional</label>
        <input type="number" step="0.01"
               name="precio_servicio_adicional[]"
               class="form-control precio_adicional">
    </div>

    <div class="col-md-4">
        <label>Pagado Adicional</label>
        <input type="number" step="0.01"
               name="pagado_adicional[]"
               class="form-control pagado_adicional">
    </div>

    <div class="col-md-4">
        <label>Saldo Adicional</label>
        <input type="number" step="0.01"
               class="form-control saldo_adicional"
               readonly>
    </div>

    <div class="col-md-4">
        <label>Moneda Adicional</label>
        <select name="tipo_moneda_adicional[]" class="form-control">
            <option value="">--</option>
            <option value="Soles">Soles</option>
            <option value="Dólares">Dólares</option>
        </select>
    </div>
</div>


                    <div class="mt-4">
                        <button type="button" class="btn btn-success" onclick="agregarTour()">
        ➕ Agregar otro tour
    </button>
        <button type="submit" class="btn btn-primary">💾 Guardar Operación</button>
        <a href="index.php" class="btn btn-secondary">↩ Volver</a>
                        <button type="button" class="btn btn-danger" onclick="eliminarTour(this)">
                            🗑 Eliminar este tour
                        </button>
                    </div>


            </div>
            <!-- ================= FIN TOUR ================= -->

        </div>
    </form>

            </div>
        </div>

    </div>
</div>

<!-- ✅ JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Calcular saldo
document.addEventListener("input", e => {
    const bloque = e.target.closest(".tour-item");
    if (!bloque) return;

    // TOUR
    const precio = parseFloat(bloque.querySelector(".precio_servicio")?.value) || 0;
    const pagado = parseFloat(bloque.querySelector(".pagado_a_cuenta")?.value) || 0;
    const saldoInput = bloque.querySelector(".saldo_pendiente");
    if (saldoInput) saldoInput.value = (precio - pagado).toFixed(2);

    // ADICIONAL
    const precioA = parseFloat(bloque.querySelector(".precio_adicional")?.value) || 0;
    const pagadoA = parseFloat(bloque.querySelector(".pagado_adicional")?.value) || 0;
    const saldoAInput = bloque.querySelector(".saldo_adicional");
    if (saldoAInput) saldoAInput.value = (precioA - pagadoA).toFixed(2);
});
// ================== EVENTOS ==================
document.addEventListener("change", function (e) {

    if (
        e.target.name === "nombre_servicio[]" ||
        e.target.name === "fecha_salida[]"
    ) {
        const bloque = e.target.closest(".tour-item");
        if (bloque) calcularFechaRetorno(bloque);
    }
});
// Agregar tour
function agregarTour() {
    const contenedor = document.getElementById("contenedorTours");
    const nuevo = document.querySelector(".tour-item").cloneNode(true);
    nuevo.querySelectorAll("input, textarea").forEach(e => e.value = "");
    nuevo.querySelectorAll("select").forEach(s => s.selectedIndex = 0);
    contenedor.appendChild(nuevo);
}

// Eliminar tour
function eliminarTour(btn) {
    const tours = document.querySelectorAll(".tour-item");
    if (tours.length === 1) {
        alert("Debe existir al menos un tour.");
        return;
    }
    btn.closest(".tour-item").remove();
}
// ================== DURACIÓN DE TOURS ==================
const DURACION_TOURS = {
    "SALKANTAY A MACHU PICCHU 5 DÍAS": 5,
    "SALKANTAY A MACHU PICCHU 4 DÍAS": 4,
    "SALKANTAY A MACHU PICCHU 3 DÍAS": 3,
    "SALKANTAY TREK 5D / 4N WITH LUXURY DOMES": 5,
    "SALKANTAY TREK 4D / 3N WITH LUXURY DOMES": 4,
    "SALKANTAY TREK 2D / 1N WITH LUXURY DOMES": 2,
    "SALKANTAY Y LAGUNA HUMANTAY 2 DÍAS": 2,
    "SALKANTAY Y CAMINO INCA 7 DÍAS (PRIVADO)": 7,
    "CAMINO INCA 4 DÍAS": 4,
    "CAMINO INCA 4 DÍAS (PRIVADO)": 4,
    "CAMINO INCA 2 DÍAS": 2,
    "MACHU PICCHU DE UN DÍA": 1,
    "MACHU PICCHU EN TREN 2 DÍAS": 2,
    "VALLE SAGRADO A MACHU PICCHU 2 DÍAS": 2,
    "CHOQUEQUIRAO 5 DÍAS (PRIVADO)": 5,
    "CHOQUEQUIRAO 4 DÍAS": 4,
    "CHOQUEQUIRAO 4 DÍAS (PRIVADO)": 4,
    "LARES A MACHU PICCHU 4 DÍAS (PRIVADO)": 4,
    "AUSANGATE Y MONTAÑA DE COLORES 4 DÍAS": 4,
    "HUCHUY QOSQO 3 DÍAS (PRIVADO)": 3,
    "INCA JUNGLE TRAIL 4 DAYS": 4,
    "LAGUNA HUMANTAY DE UN DÍA": 1,
    "MONTAÑA DE COLORES DE UN DÍA": 1,
    "PALCOYO DE UN DÍA": 1,
    "VALLE SAGRADO VIP DE UN DÍA": 1,
    "VALLE TRADICIONAL": 1,
    "7 LAGUNAS DE AUSANGATE DE UN DÍA": 1,
    "MARAS MORAY DE UN DÍA": 1,
    "Q’ESHUACHAKA Y 4 LAGUNAS DE UN DÍA": 1,
    "WAQRAPUKARA DE UN DÍA": 1,
    "CITY TOUR CUSCO MEDIO DÍA": 1,
    "CUATRIMOTOS": 1,
    "ICA – PARACAS DE UN DÍA": 1,
    "PUNO DE UN DÍA": 1,
    "MANU 4 DÍAS Y 3 NOCHES": 4
};
// ================== CALCULAR FECHA RETORNO ==================
function calcularFechaRetorno(bloque) {

    const servicio = bloque.querySelector('select[name="nombre_servicio[]"]').value;
    const salidaInput = bloque.querySelector('input[name="fecha_salida[]"]');
    const retornoInput = bloque.querySelector('input[name="fecha_retorno[]"]');

    if (!servicio || !salidaInput.value) return;

    const dias = DURACION_TOURS[servicio] ?? 1;

    const fechaSalida = new Date(salidaInput.value);
    fechaSalida.setDate(fechaSalida.getDate() + (dias - 1));

    retornoInput.value = fechaSalida.toISOString().split('T')[0];
}
</script>


</body>
</html>

