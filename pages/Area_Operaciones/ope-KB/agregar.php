<?php
ob_start();
include '../../../conexion.php';

// Validar ID del cliente
if (!isset($_GET['id_cliente'])) {
    die("❌ Error: Falta el ID del cliente.");
}

$id_cliente = (int) $_GET['id_cliente'];
// 🔹 Obtener grupo del cliente
$qGrupo = mysqli_query($conexion,"
SELECT id_grupo 
FROM clientes_kb 
WHERE id_cliente = $id_cliente
");

$rowGrupo = mysqli_fetch_assoc($qGrupo);
$id_grupo = $rowGrupo['id_grupo'] ?? 0;
// Obtener nombre del cliente
$query = "SELECT CONCAT(nombre, ' ', apellido) AS nombre_completo FROM datos_clientes WHERE id_cliente = $id_cliente";
$res = mysqli_query($conexion, $query);
$cliente = mysqli_fetch_assoc($res);

if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['ya_guardado'])) {

    $_POST['ya_guardado'] = 1;

$tipo_precio = $_POST['tipo_precio'] ?? 'por_tour';
$total_operacion = floatval($_POST['total_operacion'] ?? 0);
// calcular total por tours
$total_tours = 0;

if (!empty($_POST['precio_tour'])) {
    foreach ($_POST['precio_tour'] as $p) {
        $total_tours += floatval($p);
    }
}

// decidir precio final
if ($tipo_precio == 'total' && $total_operacion > 0) {
    $precio_final = $total_operacion;
} else {
    $precio_final = $total_tours;
}

    // =============================
    // INSERT OPERACION PRINCIPAL
    // =============================

    $fecha_reserva = $_POST['fecha_reserva'][0];
    $modalidad_retorno = $_POST['modalidad_retorno'][0];
    $incluye_ingreso = isset($_POST['incluye_ingreso'][0]) ? 'Con ingreso' : 'Sin ingreso';

    $observaciones = $_POST['observaciones'][0];
    $encargado = $_POST['Encargado'][0];

    // servicios adicionales
 $servicios = $_POST['servicio_adicional'] ?? [];

if (!is_array($servicios)) {
    $servicios = [$servicios];
}

$servicio_adicional = implode(", ", $servicios);

$queryOp = "INSERT INTO operaciones (
    id_cliente,
    id_grupo,
    fecha_reserva,
    observaciones,
    Encargado,
    total_operacion
) VALUES (?,?,?,?,?,?)";

    $stmtOp = mysqli_prepare($conexion, $queryOp);

    mysqli_stmt_bind_param(
    $stmtOp,
    "iisssd",
    $id_cliente,
    $id_grupo,
    $fecha_reserva,
    $observaciones,
    $encargado,
    $precio_final
);

    mysqli_stmt_execute($stmtOp);

    $id_operaciones = mysqli_insert_id($conexion);


    // =============================
    // INSERT DETALLE (TOURS)
    // =============================

foreach ($_POST['nombre_servicio'] as $i => $servicio) {

    if (empty($servicio)) continue;

    $precio = floatval($_POST['precio_tour'][$i] ?? 0);

    $fecha_salida = $_POST['fecha_salida'][$i] ?? null;
    $fecha_retorno = $_POST['fecha_retorno'][$i] ?? null;

    $modalidad = $_POST['modalidad_retorno'][$i] ?? null;

    $ingreso =
        isset($_POST['incluye_ingreso'][$i])
        ? 'Con ingreso'
        : 'Sin ingreso';

    $servicio_adicional =
        $_POST['servicio_adicional'][$i] ?? null;

    $sqlDet = "INSERT INTO operaciones_detalle
    (
        id_operaciones,
        nombre_servicio,
        precio,
        fecha_salida,
        fecha_retorno,
        modalidad_retorno,
        incluye_ingreso,
        servicio_adicional
    )
    VALUES (?,?,?,?,?,?,?,?)";

    $stmtDet = mysqli_prepare($conexion, $sqlDet);

    mysqli_stmt_bind_param(
        $stmtDet,
        "isdsssss",
        $id_operaciones,
        $servicio,
        $precio,
        $fecha_salida,
        $fecha_retorno,
        $modalidad,
        $ingreso,
        $servicio_adicional
    );

    mysqli_stmt_execute($stmtDet);
}


    // =============================
    // CONTABILIDAD (SOLO 1 VEZ)
    // =============================

$metodo_pago = $_POST['metodo_pago'];
$tipo_moneda = $_POST['tipo_moneda'];
$precio_servicio = $precio_final;
$pagado_a_cuenta = floatval($_POST['pagado_a_cuenta']);
$saldo_pendiente = floatval($_POST['saldo_pendiente']);
$fecha_pago_saldo = $_POST['fecha_pago_saldo'];
$comision = floatval($_POST['comision']);

$metodo_adicional = $_POST['metodo_pago_adicional'][0] ?? null;
$moneda_adicional = $_POST['tipo_moneda_adicional'][0] ?? null;

$precio_adicional = floatval($_POST['precio_servicio_adicional'][0] ?? 0);
$pagado_adicional = floatval($_POST['pagado_ingreso'][0] ?? 0);
$saldo_adicional = floatval($_POST['saldo_ingreso'][0] ?? 0);


    $queryCont = "INSERT INTO contabilidad (
        id_operaciones,
        id_grupo,

        metodo_pago,
        tipo_moneda,
        precio_servicio,
        pagado_a_cuenta,
        saldo_pendiente,

        metodo_pago_adicional,
        tipo_moneda_adicional,
        precio_servicio_adicional,
        pagado_adicional,
        saldo_adicional,

        comision,
        fecha_pago_saldo

    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

    $stmtCont = mysqli_prepare($conexion, $queryCont);

    mysqli_stmt_bind_param(
        $stmtCont,
        "iissdddssdddss",

        $id_operaciones,
        $id_grupo,

        $metodo_pago,
        $tipo_moneda,
        $precio_servicio,
        $pagado_a_cuenta,
        $saldo_pendiente,

        $metodo_adicional,
        $moneda_adicional,
        $precio_adicional,
        $pagado_adicional,
        $saldo_adicional,

        $comision,
        $fecha_pago_saldo
    );

    mysqli_stmt_execute($stmtCont);
    // =============================
// GUARDAR PAGOS MULTIPLES
// =============================

if (!empty($_POST['monto_multi'])) {

    foreach ($_POST['monto_multi'] as $i => $monto) {

        if ($monto == "") continue;

        $tipo_pago = $_POST['tipo_pago'][$i];
        $metodo = $_POST['metodo_pago_multi'][$i];
        $moneda = $_POST['moneda_multi'][$i];
        $fecha = $_POST['fecha_multi'][$i];

        $sqlPago = "INSERT INTO pagos_operacion
        (
            id_operaciones,
            tipo_pago,
            metodo_pago,
            tipo_moneda,
            monto,
            fecha_pago
        )
        VALUES (?,?,?,?,?,?)";

        $stmtPago = mysqli_prepare($conexion, $sqlPago);

        mysqli_stmt_bind_param(
            $stmtPago,
            "isssds",
            $id_operaciones,
            $tipo_pago,
            $metodo,
            $moneda,
            $monto,
            $fecha
        );

        mysqli_stmt_execute($stmtPago);

    }

}

    $_POST['ya_guardado'] = 1;
    header("Location: index.php?mensaje=agregado");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Operación KB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="stilo.css">
</head>
<body class="bg-light">
<?php include '../../sidebar.php'; ?>
<div class="container mt-5">

    <h3 class="text-primary mb-4">➕ Agregar Operación para: <?= htmlspecialchars($cliente['nombre_completo']) ?></h3>

    <h3>Agregar Operación para: <?= htmlspecialchars($cliente['nombre_completo']) ?></h3>

<form method="POST" id="formTours">
    <h5>Datos generales</h5>

<div class="row">

<div class="col-md-4">
<label>Fecha de Reserva</label>
<input type="date"
       name="fecha_reserva[]"
       class="form-control"
       value="<?= date('Y-m-d') ?>">
</div>
<div class="col-md-6">
<label>Encargado</label>
<input type="text"
       name="Encargado[]"
       class="form-control">
</div>

<div class="col-12">
<label>Observaciones</label>
<textarea name="observaciones[]"
          class="form-control"></textarea>
</div>

</div>

<hr>
    <h5 class="mt-4">Tours del grupo</h5>

<table class="table table-bordered table-sm align-middle" id="tablaTours">
    <thead>
        <tr>
            <th>Servicio</th>
            <th>Precio</th>
            <th>Salida</th>
            <th>Retorno</th>
            <th>Modalidad</th>
            <th>Ingreso</th>
            <th>Adicional</th>
            <th></th>
        </tr>
    </thead>

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
</table>
<button type="button"
        class="btn btn-success"
        onclick="agregarFila()">

    + Agregar tour

</button>
<hr>

<h5>💰 Contabilidad del grupo</h5>

<div class="row">

<div class="col-md-3">
<label>Tipo precio</label>
<input type="hidden" name="tipo_precio" value="por_tour">
</div>

<div class="col-md-3">
<label>Total operación</label>
<input type="number" step="0.01" name="total_operacion" class="form-control" readonly>
</div>

<div class="col-md-3">
<label>Método pago</label>
<select name="metodo_pago" class="form-select">
<option value="Efectivo">Efectivo</option>
<option value="We travel">We travel</option>
<option value="CULQI">CULQI</option>
<option value="Izipay">Izipay</option>
<option value="PAYPAL">PAYPAL</option>
<option value="Bcp">Bcp</option>
<option value="YAPE">YAPE</option>
</select>
</div>

<div class="col-md-3">
<label>Moneda</label>
<select name="tipo_moneda" class="form-control">
<option>Soles</option>
<option>Dólares</option>
</select>
</div>

<div class="col-md-3">
<label>Pagado</label>
<input type="number" step="0.01" name="pagado_a_cuenta" class="form-control pagado_a_cuenta">
</div>

<div class="col-md-3">
<label>Saldo</label>
<input type="number" step="0.01" name="saldo_pendiente" class="form-control saldo_pendiente">
</div>

<div class="col-md-3">
<label>Fecha saldo</label>
<input type="date" name="fecha_pago_saldo" class="form-control">
</div>

<div class="col-md-3">
<label>Comisión</label>
<input type="number" step="0.01" name="comision" class="form-control">
</div>

</div>
<hr>

<h5>🎟 Servicio adicional</h5>
<hr>

<h5>💳 Pagos realizados</h5>

<table class="table table-bordered" id="tablaPagos">

<thead>
<tr>
<th>Tipo</th>
<th>Método</th>
<th>Moneda</th>
<th>Monto</th>
<th>Fecha</th>
<th></th>
</tr>
</thead>

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
</table>

<button type="button" class="btn btn-success" onclick="agregarPago()">
+ Agregar pago
</button>

<br>

<button type="submit" class="btn btn-primary">
Guardar operación
</button>
</form>

</div>


<!-- ================= JAVASCRIPT FINAL ================= -->
<script>
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
document.addEventListener("change", function(e) {

    // Cuando cambia el servicio o la fecha de salida
    if (
        e.target.name === "nombre_servicio[]" ||
        e.target.name === "fecha_salida[]"
    ) {

        const bloque = e.target.closest("tr");

        const servicio = bloque.querySelector("[name='nombre_servicio[]']").value;
        const salidaInput = bloque.querySelector("[name='fecha_salida[]']");
        const retornoInput = bloque.querySelector("[name='fecha_retorno[]']");

        if (!servicio || !salidaInput.value) return;

        const dias = DURACION_TOURS[servicio];

        if (!dias) return;

        const fechaSalida = new Date(salidaInput.value);
        fechaSalida.setDate(fechaSalida.getDate() + (dias - 1));

        retornoInput.value = fechaSalida.toISOString().split("T")[0];
        
    }
});
// =============================
// CONTROL PRECIO TOTAL / POR TOUR
// =============================

document.addEventListener("change", function(e){

    if(e.target.name == "tipo_precio[]"){

        const tipo = e.target.value

       const precioServicio = document.querySelector("[name='total_operacion']")
        const totalOperacion = document.querySelector("[name='total_operacion']")

        if(tipo == "total"){

            precioServicio.value = ""
            precioServicio.disabled = true

            totalOperacion.disabled = false

        }else{

            precioServicio.disabled = false

            totalOperacion.value = ""
            totalOperacion.disabled = true

        }

    }

})


// =============================
// SUMAR PRECIOS AUTOMATICO
// =============================

document.addEventListener("input", function(){

    let total = 0;

    document.querySelectorAll(".precio_tour").forEach(i => {
        total += parseFloat(i.value) || 0;
    });

    const totalInput =
        document.querySelector("[name='total_operacion']");

    if(totalInput){
        totalInput.value = total.toFixed(2);
    }

});


// =============================
// CALCULAR SALDO
// =============================

document.addEventListener("input", function(){

    const precio = parseFloat(document.querySelector("[name='total_operacion']").value) || 0
    const pagado = parseFloat(document.querySelector(".pagado_a_cuenta").value) || 0

    document.querySelector(".saldo_pendiente").value = (precio - pagado).toFixed(2)

})


// =============================
// SALDO ADICIONAL
// =============================

document.addEventListener("input", function(){

const precio =
parseFloat(
document.querySelector("[name='precio_servicio_adicional[]']").value
) || 0;

const pagado =
parseFloat(
document.querySelector("[name='pagado_ingreso[]']").value
) || 0;

document.querySelector("[name='saldo_ingreso[]']").value =
(precio - pagado).toFixed(2);

})
function agregarFila(){

    let fila = document.querySelector("#bodyTours tr")

    let nueva = fila.cloneNode(true)

    nueva.querySelectorAll("input").forEach(i => i.value="")

    nueva.querySelectorAll("select").forEach(s => s.selectedIndex=0)

    document.getElementById("bodyTours").appendChild(nueva)

}


function eliminarFila(btn){

    let filas = document.querySelectorAll("#bodyTours tr")

    if(filas.length == 1){
        alert("Debe haber al menos 1 tour")
        return
    }

    btn.closest("tr").remove()

}
function agregarPago(){

let fila = document.querySelector("#bodyPagos tr")

let nueva = fila.cloneNode(true)

nueva.querySelectorAll("input").forEach(i => i.value="")

nueva.querySelectorAll("select").forEach(s => s.selectedIndex=0)

document.getElementById("bodyPagos").appendChild(nueva)

}

function eliminarPago(btn){

let filas = document.querySelectorAll("#bodyPagos tr")

if(filas.length == 1){
alert("Debe haber al menos 1 pago")
return
}

btn.closest("tr").remove()

}
</script>

</body>
</html>
