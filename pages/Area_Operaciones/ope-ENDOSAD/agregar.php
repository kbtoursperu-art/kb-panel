<?php
ob_start();
include '../../../conexion.php';

if (!isset($_GET['id_cliente'])) {
    die("❌ Falta el ID del cliente.");
}

$id_cliente = (int)$_GET['id_cliente'];

$sqlCliente = "SELECT CONCAT(nombre,' ',apellido) AS nombre 
FROM datos_clientes 
WHERE id_cliente=$id_cliente";

$resCliente = mysqli_query($conexion, $sqlCliente);
$cliente = mysqli_fetch_assoc($resCliente);


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // =========================
    // CONTABILIDAD TOTAL
    // =========================

$precio_total = floatval($_POST['precio_total'] ?? 0);
$pagado_total = floatval($_POST['pagado_total'] ?? 0);

$saldo_total = $precio_total - $pagado_total;

if ($saldo_total < 0) {
    $saldo_total = 0;
}


// =====================
// ADICIONAL TOTAL
// =====================

$precio_adicional = floatval($_POST['precio_servicio_adicional'][0] ?? 0);
$pagado_adicional = floatval($_POST['pagado_ingreso'][0] ?? 0);

$saldo_adicional = $precio_adicional - $pagado_adicional;

if ($saldo_adicional < 0) {
    $saldo_adicional = 0;
}

    if ($saldo_total < 0) {
        $saldo_total = 0;
    }

    $metodo_pago = $_POST['metodo_pago'] ?? '';
    $tipo_moneda = $_POST['tipo_moneda'] ?? '';

    // 🔴 SIEMPRE PENDIENTE
    $estado = 'pendiente';


    // =========================
    // INSERTAR TOURS
    // =========================

    foreach ($_POST['nombre_servicio'] as $i => $v) {

        if (empty($_POST['nombre_servicio'][$i])) continue;

        $nombre_servicio   = $_POST['nombre_servicio'][$i];
        $fecha_reserva     = $_POST['fecha_reserva'][$i];
        $fecha_salida      = $_POST['fecha_salida'][$i];
        $fecha_retorno     = $_POST['fecha_retorno'][$i];
        $modalidad_retorno = $_POST['modalidad_retorno'][$i];

        $incluye_ingreso = isset($_POST['incluye_ingreso'][$i])
            ? 'Con ingreso'
            : 'Sin ingreso';

        $servicios = $_POST['servicio_adicional'][$i] ?? [];
        $servicio_adicional = is_array($servicios)
            ? implode(', ', $servicios)
            : $servicios;

        $observaciones = $_POST['observaciones'][$i];
        $encargado     = $_POST['encargado'][$i];


        $sqlOp = "INSERT INTO Operaciones
        (
            id_cliente,
            nombre_servicio,
            fecha_reserva,
            fecha_salida,
            fecha_retorno,
            modalidad_retorno,
            incluye_ingreso,
            servicio_adicional,
            observaciones,
            Encargado
        )
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

        // guardar solo el primer id de operación
if (!isset($id_operaciones)) {
    $id_operaciones = mysqli_insert_id($conexion);
}
    }


    // =========================
    // INSERTAR CONTABILIDAD (1 sola vez)
    // =========================

    $sqlCont = "INSERT INTO Contabilidad
(
    id_operaciones,
    metodo_pago,
    tipo_moneda,
    precio_servicio,
    pagado_a_cuenta,
    saldo_pendiente,

    precio_servicio_adicional,
    pagado_adicional,
    saldo_adicional,

    estado
)
VALUES (?,?,?,?,?,?,?,?,?,?)";


    $stmt = mysqli_prepare($conexion, $sqlCont);

 mysqli_stmt_bind_param(
    $stmt,
    "issdddddds",
    $id_operaciones,
    $metodo_pago,
    $tipo_moneda,

    $precio_total,
    $pagado_total,
    $saldo_total,

    $precio_adicional,
    $pagado_adicional,
    $saldo_adicional,

    $estado
);

    mysqli_stmt_execute($stmt);


    header("Location: index.php");
    exit;
}
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
</head>

<body>
<!-- ✅ SIDEBAR (AHORA SÍ) -->
<?php include '../../sidebar.php'; ?>

<!-- ✅ CONTENIDO -->
<div class="container mt-4">
    <div class="container-fluid">

        <div class="card shadow">
            <div class="card-body">

                <h4 class="text-primary mb-4">
                    ➕ Nueva Operación Endosador: <?= htmlspecialchars($cliente['nombre']) ?>
                </h4>

                <form method="POST">

            <!-- ================= TOUR ================= -->
 <!-- ================= TOURS ================= -->

<div id="contenedorTours">

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
<input type="date" name="fecha_reserva[]" class="form-control">
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
<label>Modalidad retorno</label>
<select name="modalidad_retorno[]" class="form-control">
<option>Tren</option>
<option>Carro</option>
<option>Sin retorno</option>
</select>
</div>

<div class="col-md-3 mt-4">
<input type="checkbox" name="incluye_ingreso[]">
Incluye ingreso
</div>

<div class="col-md-6">
<label>Servicio adicional</label>
                        <select name="servicio_adicional[]" class="form-select" multiple>
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
</div>

<div class="col-md-6">
<label>Observaciones</label>
<textarea name="observaciones[]" class="form-control"></textarea>
</div>

<div class="col-md-4">
<label>Encargado</label>
<input type="text" name="encargado[]" class="form-control">
</div>

</div>

<button type="button" onclick="eliminarTour(this)" class="btn btn-danger mt-3">
Eliminar tour
</button>

</div>

</div>



<hr>

<h5>💰 CONTABILIDAD TOTAL</h5>

<div class="row">

<div class="col-md-4">
<label>Precio total tours</label>
<input type="number" step="0.01" name="precio_total" class="form-control">
</div>

<div class="col-md-4">
<label>Pagado tours</label>
<input type="number" step="0.01" name="pagado_total" class="form-control">
</div>

<div class="col-md-4">
<label>Saldo tours</label>
<input type="number" step="0.01" name="saldo_total" class="form-control" readonly>
</div>

<div class="col-md-4">
<label>Método pago</label>
                        <select name="metodo_pago" class="form-select">
                            <option value="">-- No aplica --</option>
                            <option value="Efectivo">Efectivo</option>
                            <option value="We travel">We travel</option>
                             <option value="CULQI">CULQI</option>
                            <option value="Izipay">Izipay</option>
                            <option value="PAYPAL">PAYPAL</option>
                            <option value="Bcp">Bcp</option>
                            <option value="YAPE">YAPE</option>
                        </select>
</div>

<div class="col-md-4">
<label>Moneda</label>
<select name="tipo_moneda" class="form-control">
<option value="">-- No aplica --</option>
<option>Soles</option>
<option>Dólares</option>
</select>
</div>

</div>


<hr>

<h5>💰 SERVICIO ADICIONAL TOTAL</h5>

<div class="row">

<div class="col-md-4">
    <label>Método de Pago (Ingreso)</label>
    <select name="metodo_pago_ingreso[]" class="form-select">
      <option value="">-- No aplica --</option>
      <option value="Efectivo">Efectivo</option>
      <option value="We travel">We travel</option>
      <option value="Izipay">Izipay</option>
      <option value="PAYPAL">PAYPAL</option>
      <option value="Bcp">Bcp</option>
      <option value="CULQI">CULQI</option>
      <option value="YAPE">YAPE</option>
    </select>
  </div>

  <div class="col-md-4">
    <label>Moneda (Ingreso)</label>
    <select name="tipo_moneda_ingreso[]" class="form-select">
      <option value="">-- No aplica --</option>
      <option value="Soles">Soles</option>
      <option value="Dólares">Dólares</option>
    </select>
  </div>

  <div class="col-md-4">
    <label>Precio Ingreso Adi...</label>
    <input type="number" step="0.01" name="precio_servicio_adicional[]" class="form-control precio_adicional">
  </div>

  <div class="col-md-4">
    <label>Pagado Ingreso Adi...</label>
    <input type="number" step="0.01" name="pagado_ingreso[]" class="form-control pagado_adicional">
  </div>

  <div class="col-md-4">
    <label>Saldo Ingreso</label>
    <input type="number" step="0.01" name="saldo_ingreso[]" class="form-control saldo_adicional" readonly>
  </div>

</div>


<div class="mt-4">

<button type="button" onclick="agregarTour()" class="btn btn-success">
Agregar tour
</button>

<button type="submit" class="btn btn-primary">
Guardar
</button>

<a href="index.php" class="btn btn-secondary">
Volver
</a>

</div>
    </form>


    </div>
</div>

<!-- ✅ JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>

// ================== CALCULAR SALDOS ==================
document.addEventListener("input", function (e) {

    // ================= TOUR =================

    const bloque = e.target.closest(".tour-item");

    if (bloque) {

        const precioInput = bloque.querySelector(".precio_servicio");
        const pagadoInput = bloque.querySelector(".pagado_a_cuenta");
        const saldoInput = bloque.querySelector(".saldo_pendiente");

        const precio = precioInput ? parseFloat(precioInput.value) || 0 : 0;
        const pagado = pagadoInput ? parseFloat(pagadoInput.value) || 0 : 0;

        if (saldoInput) {
            saldoInput.value = (precio - pagado).toFixed(2);
        }


        // ---------- ADICIONAL TOUR ----------

        const precioAInput = bloque.querySelector(".precio_adicional");
        const pagadoAInput = bloque.querySelector(".pagado_adicional");
        const saldoAInput = bloque.querySelector(".saldo_adicional");

        const precioA = precioAInput ? parseFloat(precioAInput.value) || 0 : 0;
        const pagadoA = pagadoAInput ? parseFloat(pagadoAInput.value) || 0 : 0;

        if (saldoAInput) {
            saldoAInput.value = (precioA - pagadoA).toFixed(2);
        }

    }


    // ================= TOTAL GENERAL =================

    const precioTotal = document.querySelector('input[name="precio_total"]');
    const pagadoTotal = document.querySelector('input[name="pagado_total"]');
    const saldoTotal = document.querySelector('input[name="saldo_total"]');

    if (precioTotal && pagadoTotal && saldoTotal) {

        const p = parseFloat(precioTotal.value) || 0;
        const pg = parseFloat(pagadoTotal.value) || 0;

        let s = p - pg;

        if (s < 0) s = 0;

        saldoTotal.value = s.toFixed(2);
    }


    // ================= ADICIONAL TOTAL =================

    const precioATotal = document.querySelector('input[name="precio_servicio_adicional[]"]');
    const pagadoATotal = document.querySelector('input[name="pagado_ingreso[]"]');
    const saldoATotal = document.querySelector('input[name="saldo_ingreso[]"]');

    if (precioATotal && pagadoATotal && saldoATotal) {

        const pA = parseFloat(precioATotal.value) || 0;
        const pgA = parseFloat(pagadoATotal.value) || 0;

        let sA = pA - pgA;

        if (sA < 0) sA = 0;

        saldoATotal.value = sA.toFixed(2);
    }

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


// ================== AGREGAR TOUR ==================
function agregarTour() {

    const contenedor = document.getElementById("contenedorTours");
    const original = document.querySelector(".tour-item");

    const nuevo = original.cloneNode(true);

    // limpiar inputs
    nuevo.querySelectorAll("input").forEach(e => {

        if (e.type === "checkbox") {
            e.checked = false;
        } else {
            e.value = "";
        }

    });

    // limpiar textarea
    nuevo.querySelectorAll("textarea").forEach(e => e.value = "");

    // limpiar selects
    nuevo.querySelectorAll("select").forEach(s => s.selectedIndex = 0);

    // limpiar saldos
    nuevo.querySelectorAll(".saldo_pendiente").forEach(e => e.value = "");
    nuevo.querySelectorAll(".saldo_adicional").forEach(e => e.value = "");

    contenedor.appendChild(nuevo);

}


// ================== ELIMINAR TOUR ==================
function eliminarTour(btn) {

    const tours = document.querySelectorAll(".tour-item");

    if (tours.length === 1) {
        alert("Debe existir al menos un tour.");
        return;
    }

    btn.closest(".tour-item").remove();

}


// ================== DURACION ==================
const DURACION_TOURS = {

"SALKANTAY A MACHU PICCHU 5 DÍAS": 5,
"SALKANTAY A MACHU PICCHU 4 DÍAS": 4,
"SALKANTAY A MACHU PICCHU 3 DÍAS": 3,

"SALKANTAY TREK 5D/4N WITH LUXURY DOMES (PRIVADO)": 5,
"SALKANTAY TREK 4D / 3N WITH LUXURY DOMES (PRIVADO)": 4,
"SALKANTAY & HUMANTAY LAKE 2D WITH LUXURY DOMES (PRIVADO)": 2,

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

"LAGUNA HUMANTAY DE UN DIA": 1,
"MONTAÑA DE COLORES DE UN DIA": 1,
"PALCOYO DE UN DIA": 1,

"VALLE SAGRADO VIP DE UN DIA": 1,
"VALLE TRADICIONAL": 1,
"7 LAGUNAS DE AUSANGATE DE UN DIA": 1,
"MARAS MORAY DE UN DIA": 1,

"Q’ESHUACHAKA Y 4 LAGUNAS DE UN DÍA": 1,
"WAQRAPUKARA DE UN DIA": 1,

"CITY TOUR CUSCO MEDIO DIA": 1,
"CUATRIMOTOS": 1,
"ICA – PARACAS DE UN DIA": 1,
"PUNO DE UN DÍA": 1,

"MANU 4 DÍAS Y 3 NOCHES": 4

};


// ================== CALCULAR RETORNO ==================
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