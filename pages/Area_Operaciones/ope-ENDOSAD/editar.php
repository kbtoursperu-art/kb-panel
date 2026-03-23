<?php
ob_start();
include '../../../conexion.php';

if (!isset($_GET['id'])) {
    die("Falta ID");
}

$id_operacion = (int)$_GET['id'];


// =========================
// OBTENER OPERACION
// =========================

$qOp = mysqli_query($conexion,"
SELECT *
FROM operaciones
WHERE id_operaciones = $id_operacion
");

$op = mysqli_fetch_assoc($qOp);


// =========================
// TOURS
// =========================

$qTours = mysqli_query($conexion,"
SELECT *
FROM operaciones_detalle
WHERE id_operaciones = $id_operacion
");


// =========================
// CONTABILIDAD
// =========================

$qCont = mysqli_query($conexion,"
SELECT *
FROM contabilidad
WHERE id_operaciones = $id_operacion
");

$cont = mysqli_fetch_assoc($qCont);
if(!$cont){

$cont = [
    'precio_servicio'=>0,
    'pagado_a_cuenta'=>0,
    'saldo_pendiente'=>0,
    'metodo_pago'=>'',
    'tipo_moneda'=>'',
    'precio_servicio_adicional'=>0,
    'pagado_adicional'=>0,
    'saldo_adicional'=>0,
    'comision'=>0,
    'fecha_pago_saldo'=>null
];

}
// =========================
// ADICIONAL
// =========================

$qPagos = mysqli_query($conexion,"
SELECT *
FROM pagos_operacion
WHERE id_operaciones = $id_operacion
ORDER BY id_pago ASC
");


// =================================================
// GUARDAR EDITAR
// =================================================

if($_SERVER["REQUEST_METHOD"]=="POST"){

$id = $_POST['id_operacion'];

$fecha_reserva = $_POST['fecha_reserva'][0];
$obs = $_POST['observaciones'][0];
$encargado = $_POST['Encargado'][0];

$total = $_POST['total_operacion'][0];

$pagado = $cont['pagado_a_cuenta'] ?? 0;
$saldo  = $cont['saldo_pendiente'] ?? 0;

// =========================
// UPDATE OPERACIONES
// =========================

mysqli_query($conexion,"
UPDATE operaciones SET

fecha_reserva='$fecha_reserva',
observaciones='$obs',
Encargado='$encargado',
total_operacion='$total'

WHERE id_operaciones=$id
");


// =========================
// BORRAR DETALLE
// =========================

mysqli_query($conexion,"
DELETE FROM operaciones_detalle
WHERE id_operaciones=$id
");
// =========================
// BORRAR TODOS LOS PAGOS
// =========================

mysqli_query($conexion,"
DELETE FROM pagos_operacion
WHERE id_operaciones=$id
");

// =========================
// GUARDAR TOURS
// =========================

if (isset($_POST['nombre_servicio'])) {

foreach ($_POST['nombre_servicio'] as $i => $servicio) {

if ($servicio == '') continue;

$precio = $_POST['precio_tour'][$i] ?? 0;
$salida = $_POST['fecha_salida'][$i] ?? null;
$retorno = $_POST['fecha_retorno'][$i] ?? null;
$modalidad = $_POST['modalidad_retorno'][$i] ?? '';

$ingreso =
isset($_POST['incluye_ingreso'][$i])
? "Con ingreso"
: "Sin ingreso";


$adicional = "";

if (isset($_POST['servicio_adicional'][$i])) {

$adicional =
implode(", ", $_POST['servicio_adicional'][$i]);

}


mysqli_query($conexion,"
INSERT INTO operaciones_detalle
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
VALUES
(
$id,
'$servicio',
'$precio',
'$salida',
'$retorno',
'$modalidad',
'$ingreso',
'$adicional'
)
");

}

}
// =========================
// GUARDAR PAGOS MULTIPLES
// =========================

if (isset($_POST['monto_multi']) && is_array($_POST['monto_multi'])) {

    foreach ($_POST['monto_multi'] as $i => $monto) {

        if (trim($monto) == '') continue;

       $tipo = $_POST['tipo_pago'][$i] ?? 'cuenta';
        $metodo = $_POST['metodo_pago_multi'][$i] ?? '';
        $moneda = $_POST['moneda_multi'][$i] ?? '';
        $fecha  = $_POST['fecha_multi'][$i] ?? null;

        mysqli_query($conexion,"
        INSERT INTO pagos_operacion
        (
        id_operaciones,
        tipo_pago,
        metodo_pago,
        tipo_moneda,
        monto,
        fecha_pago
        )
        VALUES
        (
        $id,
        '$tipo',
        '$metodo',
        '$moneda',
        '$monto',
        '$fecha'
        )
        ");

    }

}
// =========================
// SUMAR ADICIONAL
// =========================

$qAd = mysqli_query($conexion,"
SELECT SUM(monto) as total_adicional
FROM pagos_operacion
WHERE id_operaciones = $id
AND tipo_pago = 'adicional'
");

$rowAd = mysqli_fetch_assoc($qAd);

$pagadoAd = $rowAd['total_adicional'] ?? 0;
// =========================
// PAGAR SALDO (SOLO CONTABILIDAD)
// =========================

if (isset($_POST['pagar_saldo']) && $_POST['pagar_saldo']!='') {

$montoSaldo = floatval($_POST['pagar_saldo']);

$saldo -= $montoSaldo;
$pagado += $montoSaldo;

$metodoSaldo = $_POST['metodo_saldo'] ?? '';
$monedaSaldo = $_POST['moneda_saldo'] ?? '';
$fechaSaldo  = $_POST['fecha_pago_saldo'] ?? null;


// ✅ guardar en pagos_operacion

mysqli_query($conexion,"
INSERT INTO pagos_operacion
(
id_operaciones,
tipo_pago,
metodo_pago,
tipo_moneda,
monto,
fecha_pago
)
VALUES
(
$id,
'saldo',
'$metodoSaldo',
'$monedaSaldo',
'$montoSaldo',
'$fechaSaldo'
)
");

}
// =========================
// UPDATE / INSERT CONTABILIDAD
// =========================

$metodo = $_POST['metodo_pago'] ?? '';
$moneda = $_POST['tipo_moneda'] ?? '';

$fechaSaldo = $_POST['fecha_pago_saldo'] ?? null;
$comision = $_POST['comision'] ?? 0;

$metodoAd = $_POST['metodo_pago_adicional'] ?? '';
$monedaAd = $_POST['tipo_moneda_adicional'] ?? '';

$precioAd = $cont['precio_servicio_adicional'] ?? 0;
$saldoAd  = $cont['saldo_adicional'] ?? 0;


// 🔹 verificar si existe contabilidad
$qExiste = mysqli_query($conexion,"
SELECT id_contabilidad
FROM contabilidad
WHERE id_operaciones=$id
");

if(mysqli_num_rows($qExiste)==0){

// 🔹 INSERT

mysqli_query($conexion,"
INSERT INTO contabilidad
(
id_operaciones,
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
)
VALUES
(
$id,
'$metodo',
'$moneda',
'$total',
'$pagado',
'$saldo',
'$metodoAd',
'$monedaAd',
'$precioAd',
'$pagadoAd',
'$saldoAd',
'$comision',
'$fechaSaldo'
)
");

}else{

// 🔹 UPDATE

mysqli_query($conexion,"
UPDATE contabilidad SET

metodo_pago='$metodo',
tipo_moneda='$moneda',

precio_servicio='$total',
pagado_a_cuenta='$pagado',
saldo_pendiente='$saldo',

metodo_pago_adicional='$metodoAd',
tipo_moneda_adicional='$monedaAd',
precio_servicio_adicional='$precioAd',
pagado_adicional='$pagadoAd',
saldo_adicional='$saldoAd',

comision='$comision',
fecha_pago_saldo='$fechaSaldo'

WHERE id_operaciones=$id
");

}
header("Location:index.php?editado=1");
exit;

}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Editar operación</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="bg-light">

<?php include '../../sidebar.php'; ?>

<div class="container mt-4">

<h3>Editar operación</h3>

<form method="POST">

<input type="hidden"
name="id_operacion"
value="<?= $id_operacion ?>">




<h5>Datos generales</h5>

<div class="row">

<div class="col-md-4">
<label>Fecha reserva</label>
<input type="date"
name="fecha_reserva[]"
class="form-control"
value="<?= $op['fecha_reserva'] ?>">
</div>

<div class="col-md-4">
<label>Encargado</label>
<input type="text"
name="Encargado[]"
class="form-control"
value="<?= $op['Encargado'] ?>">
</div>

<div class="col-md-12">
<label>Observaciones</label>
<textarea
name="observaciones[]"
class="form-control"><?= $op['observaciones'] ?></textarea>
</div>

</div>



<hr>

<h5>Tours</h5>

<table class="table table-bordered">

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

<?php while($t=mysqli_fetch_assoc($qTours)): ?>

<tr>

<td>
 <select name="nombre_servicio[]" class="form-control" required>

<option value="">-- Seleccione una opción --</option>

<?php

$servicios = [
"SALKANTAY A MACHU PICCHU 5 DÍAS",
"SALKANTAY A MACHU PICCHU 4 DÍAS",
"SALKANTAY A MACHU PICCHU 3 DÍAS",
"SALKANTAY Y LAGUNA HUMANTAY 2 DÍAS",
"SALKANTAY Y CAMINO INCA 7 DÍAS (PRIVADO)",
"SALKANTAY TREK 5D/4N WITH LUXURY DOMES (PRIVADO)",
"SALKANTAY TREK 4D / 3N WITH LUXURY DOMES (PRIVADO)",
"SALKANTAY & HUMANTAY LAKE 2D WITH LUXURY DOMES (PRIVADO)",
"CAMINO INCA 4 DÍAS",
"CAMINO INCA 4 DÍAS (PRIVADO)",
"CAMINO INCA 2 DÍAS",
"MACHU PICCHU DE UN DÍA",
"MACHU PICCHU EN TREN 2 DÍAS",
"VALLE SAGRADO A MACHU PICCHU 2 DÍAS",
"CHOQUEQUIRAO 5 DÍAS (PRIVADO)",
"CHOQUEQUIRAO 4 DÍAS",
"CHOQUEQUIRAO 4 DÍAS (PRIVADO)",
"LARES A MACHU PICCHU 4 DÍAS (PRIVADO)",
"AUSANGATE Y MONTAÑA DE COLORES 4 DÍAS",
"HUCHUY QOSQO 3 DÍAS (PRIVADO)",
"INCA JUNGLE TRAIL 4 DAYS",
"LAGUNA HUMANTAY DE UN DÍA",
"MONTAÑA DE COLORES DE UN DÍA",
"PALCOYO DE UN DÍA",
"VALLE SAGRADO VIP DE UN DÍA",
"VALLE TRADICIONAL",
"7 LAGUNAS DE AUSANGATE DE UN DÍA",
"MARAS MORAY DE UN DÍA",
"Q’ESHUACHAKA Y 4 LAGUNAS DE UN DÍA",
"WAQRAPUKARA DE UN DÍA",
"CITY TOUR CUSCO MEDIO DÍA",
"CUATRIMOTOS",
"ICA – PARACAS DE UN DÍA",
"PUNO DE UN DÍA",
"MANU 4 DÍAS Y 3 NOCHES"
];

foreach ($servicios as $s) {

$selected = ($s == $t['nombre_servicio']) ? 'selected' : '';

echo "<option value='$s' $selected>$s</option>";

}

?>

</select>

</td>


<td>

<input
name="precio_tour[]"
class="form-control precio_tour"
value="<?= $t['precio'] ?>">

</td>


<td>

<input
type="date"
name="fecha_salida[]"
class="form-control"
value="<?= $t['fecha_salida'] ?>">

</td>


<td>

<input
type="date"
name="fecha_retorno[]"
class="form-control"
value="<?= $t['fecha_retorno'] ?>">

</td>



<td>

<select
name="modalidad_retorno[]"
class="form-select">

<option value="">--</option>

<option value="Tren"
<?= $t['modalidad_retorno']=="Tren"?'selected':'' ?>>
Tren
</option>

<option value="Carro"
<?= $t['modalidad_retorno']=="Carro"?'selected':'' ?>>
Carro
</option>

<option value="Sin retorno"
<?= $t['modalidad_retorno']=="Sin retorno"?'selected':'' ?>>
Sin retorno
</option>

</select>

</td>



<td>

<input
type="checkbox"
name="incluye_ingreso[]"
<?= $t['incluye_ingreso']=="Con ingreso"?'checked':'' ?>>

</td>



<td>

<select name="servicio_adicional[][]" class="form-control" multiple>

<?php

$servicios_seleccionados =
!empty($t['servicio_adicional'])
? explode(', ', $t['servicio_adicional'])
: [];

$opciones = [
"Ninguna",
"Ingreso a Mollepata",
"Desayuno en Mollepata",
"Bolsa de Dormir",
"Bastones",
"Hotel",
"Montaña Huayna Picchu",
"Montaña Machu Picchu",
"Trans. Playa-Idro Pax",
"Trans. Mochilas Playa-Idro",
"Trans. Mochilas Hidro-Aguas"
];

foreach ($opciones as $op) {

$selected =
in_array($op,$servicios_seleccionados)
? 'selected'
: '';

echo "<option value='$op' $selected>$op</option>";

}

?>

</select>

</td>



<td>

<button
type="button"
class="btn btn-danger"
onclick="eliminarFila(this)">

X

</button>

</td>

</tr>

<?php endwhile; ?>

</tbody>

</table>


<button
type="button"
class="btn btn-success"
onclick="agregarFila()">

+ Agregar tour

</button>



<hr>

<h5>Contabilidad</h5>

<div class="row">

<div class="col-md-3">
<label>Total</label>
<input name="total_operacion[]"
class="form-control"
value="<?= $cont['precio_servicio'] ?>">
</div>

<div class="col-md-3">
<label>Método</label>
<select name="metodo_pago" class="form-select">

<?php

$metodos=[
'Efectivo',
'We travel',
'Izipay',
'PAYPAL',
'Bcp',
'CULQI',
'YAPE'
];

foreach($metodos as $m){

$sel =
($cont['metodo_pago']==$m)
? 'selected'
: '';

echo "<option value='$m' $sel>$m</option>";

}

?>

</select>
</div>

<div class="col-md-3">
<label>Moneda</label>
<input name="tipo_moneda"
class="form-control"
value="<?= $cont['tipo_moneda'] ?>">
</div>

<div class="col-md-3">
<label>Pagado</label>
<input name="pagado_a_cuenta"
class="form-control pagado_a_cuenta"
value="<?= $cont['pagado_a_cuenta'] ?>">
</div>

<div class="col-md-3">
<label>Saldo</label>
<input name="saldo_pendiente"
class="form-control saldo_pendiente"
value="<?= $cont['saldo_pendiente'] ?>">
</div>
<?php if ($cont['saldo_pendiente'] > 0): ?>

<div class="col-md-3">
<label>Pagar saldo</label>
<input
name="pagar_saldo"
class="form-control"
placeholder="Monto a pagar">
</div>

<div class="col-md-3">
<label>Método saldo</label>
<select name="metodo_saldo" class="form-select">

<option>Efectivo</option>
<option>YAPE</option>
<option>Bcp</option>
<option>PAYPAL</option>
<option>CULQI</option>
<option>Izipay</option>
<option>WeTravel</option>

</select>
</div>

<div class="col-md-3">
<label>Moneda saldo</label>
<select name="moneda_saldo" class="form-select">

<option>Soles</option>
<option>Dólares</option>

</select>
</div>
<div class="col-md-3">
<label>Fecha saldo</label>
<input type="date"
name="fecha_pago_saldo"
class="form-control"
value="<?= $cont['fecha_pago_saldo'] ?>">
</div>
<?php endif; ?>


<div class="col-md-3">
<label>Comisión</label>
<input name="comision"
class="form-control"
value="<?= $cont['comision'] ?>">
</div>

</div>

<hr>

<hr>

<h5>💳 Pagos realizados</h5>

<table class="table table-bordered">

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

<?php while($p=mysqli_fetch_assoc($qPagos)): ?>

<tr>

<td>
<select name="tipo_pago[]" class="form-select">

<option value="adicional" selected>
Adicional
</option>

</select>
</td>

<td>
<select name="metodo_pago_multi[]" class="form-select">

<?php
$metodos=[
'Efectivo','YAPE','Bcp','PAYPAL','Izipay','CULQI','We travel'
];

foreach($metodos as $m){

$sel=$p['metodo_pago']==$m?'selected':'';

echo "<option $sel>$m</option>";

}
?>

</select>
</td>

<td>
<select name="moneda_multi[]" class="form-select">

<option <?= $p['tipo_moneda']=="Soles"?'selected':'' ?>>
Soles
</option>

<option <?= $p['tipo_moneda']=="Dólares"?'selected':'' ?>>
Dólares
</option>

</select>
</td>

<td>
<input
name="monto_multi[]"
class="form-control"
value="<?= $p['monto'] ?>">
</td>

<td>
<input
type="date"
name="fecha_multi[]"
class="form-control"
value="<?= $p['fecha_pago'] ?>">
</td>

<td>
<button type="button"
class="btn btn-danger"
onclick="eliminarPago(this)">
X
</button>
</td>

</tr>

<?php endwhile; ?>

</tbody>
</table>

<button type="button"
class="btn btn-success"
onclick="agregarPago()">
+ Agregar pago
</button>
<br>

<button
class="btn btn-primary">

Guardar cambios

</button>

</form>

</div>
<script>

function agregarFila(){

let fila = document.querySelector("#bodyTours tr")

let nueva = fila.cloneNode(true)

nueva.querySelectorAll("input").forEach(i=>i.value="")

nueva.querySelectorAll("select").forEach(s=>s.selectedIndex=0)

document.getElementById("bodyTours").appendChild(nueva)

}


function eliminarPago(btn){

let fila = btn.closest("tr")

let tbody = document.getElementById("bodyPagos")


fila.remove()

}



document.addEventListener("input",function(){

let total=0

document.querySelectorAll(".precio_tour")
.forEach(i=>{

total+=parseFloat(i.value)||0

})

document.querySelector(
"[name='total_operacion[]']"
).value = total.toFixed(2)

})



document.addEventListener("input",function(){

let precio =
parseFloat(
document.querySelector(
"[name='total_operacion[]']"
).value
)||0

let pagado =
parseFloat(
document.querySelector(
".pagado_a_cuenta"
).value
)||0

document.querySelector(
".saldo_pendiente"
).value =
(precio-pagado).toFixed(2)

})
// ================== DURACIÓN DE TOURS ==================
const DURACION_TOURS = {
    "SALKANTAY A MACHU PICCHU 5 DÍAS": 5,
    "SALKANTAY A MACHU PICCHU 4 DÍAS": 4,
    "SALKANTAY A MACHU PICCHU 3 DÍAS": 3,
    "SALKANTAY TREK 5D/4N WITH LUXURY DOMES": 5,
    "SALKANTAY TREK 4D / 3N WITH LUXURY DOMES": 4,
    "SALKANTAY & HUMANTAY LAKE 2D WITH LUXURY DOMES": 2,
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
// ================== PAGOS ==================

function agregarPago(){

let tbody = document.getElementById("bodyPagos")

let fila = tbody.querySelector("tr")

if(!fila){

tbody.innerHTML += `
<tr>

<td>
<select name="tipo_pago[]" class="form-select">
<option value="adicional" selected>Adicional</option>
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
<input name="monto_multi[]" class="form-control">
</td>

<td>
<input type="date" name="fecha_multi[]" class="form-control">
</td>

<td>
<button type="button"
class="btn btn-danger"
onclick="eliminarPago(this)">
X
</button>
</td>

</tr>
`

return
}

let nueva = fila.cloneNode(true)

nueva.querySelectorAll("input").forEach(i=>i.value="")
nueva.querySelectorAll("select").forEach(s=>s.selectedIndex=0)

tbody.appendChild(nueva)

}
function eliminarPago(btn){

btn.closest("tr").remove()

}
</script>
</body>
</html>