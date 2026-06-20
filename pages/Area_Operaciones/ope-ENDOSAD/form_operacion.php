<?php
include '../../../conexion.php';

if (!isset($_GET['id_cliente'])) {
    die("Falta ID cliente");
}

$id_cliente = (int) $_GET['id_cliente'];

$q = mysqli_query($conexion,"
SELECT CONCAT(nombre,' ',apellido) as nombre 
FROM datos_clientes 
WHERE id_cliente=$id_cliente
");

$cliente = mysqli_fetch_assoc($q);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Nueva Operación</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
.card { border-radius:12px; }
</style>

</head>
<body class="bg-light">

<div class="container mt-4">

<h3 class="mb-3">➕ Operación: <?= $cliente['nombre'] ?></h3>

<form method="POST" action="guardar.php?id_cliente=<?= $id_cliente ?>">

<!-- ================= DATOS GENERALES ================= -->
<div class="card p-3 mb-3">
<h5>📌 Datos Generales</h5>

<div class="row">
<div class="col-md-3">
<label>Fecha</label>
<input type="date" name="fecha_reserva" class="form-control" value="<?= date('Y-m-d') ?>">
</div>

<div class="col-md-3">
<label>Encargado</label>
<input type="text" name="Encargado" class="form-control">
</div>

<div class="col-md-6">
<label>Observaciones</label>
<input type="text" name="observaciones" class="form-control">
</div>
</div>
</div>

<!-- ================= TOURS ================= -->
<div class="card p-3 mb-3">
<h5>🧭 Tours</h5>

<table class="table" id="tablaTours">
<thead>
<tr>
<th>Servicio</th>
<th>Precio</th>
<th>Salida</th>
<th>Retorno</th>
<th>Moneda</th>
<th></th>
</tr>
</thead>

<tbody id="bodyTours">

<tr>
<td>
<select name="nombre_servicio[]" class="form-select">
<option value="">Seleccionar</option>
<option>SALKANTAY 5 DÍAS</option>
<option>CAMINO INCA 4 DÍAS</option>
<option>MACHU PICCHU 1 DÍA</option>
</select>
</td>

<td><input type="number" name="precio_tour[]" class="form-control precio"></td>
<td><input type="date" name="fecha_salida[]" class="form-control"></td>
<td><input type="date" name="fecha_retorno[]" class="form-control"></td>

<td>
<select name="tipo_moneda_tour[]" class="form-select">
<option>Soles</option>
<option>Dólares</option>
</select>
</td>

<td>
<button type="button" class="btn btn-danger" onclick="eliminarFila(this)">X</button>
</td>
</tr>

</tbody>
</table>

<button type="button" class="btn btn-success" onclick="agregarFila()">+ Tour</button>

<div class="mt-3">
<label>Total</label>
<input type="number" name="total_operacion" id="total" class="form-control" readonly>
</div>

</div>

<!-- ================= PAGOS ================= -->
<div class="card p-3 mb-3">
<h5>💳 Pagos</h5>

<table class="table" id="tablaPagos">
<thead>
<tr>
<th>Aplica a</th>
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
<select name="id_detalle_pago[]" class="form-select">
<option value="">Operación</option>
</select>
</td>

<td>
<select name="tipo_pago[]" class="form-select">
<option value="cuenta">Cuenta</option>
<option value="saldo">Saldo</option>
<option value="adicional">Adicional</option>
</select>
</td>

<td>
<select name="metodo_pago_multi[]" class="form-select">
<option>Efectivo</option>
<option>YAPE</option>
<option>Bcp</option>
<option>PAYPAL</option>
</select>
</td>

<td>
<select name="moneda_multi[]" class="form-select">
<option>Soles</option>
<option>Dólares</option>
</select>
</td>

<td><input type="number" name="monto_multi[]" class="form-control"></td>
<td><input type="date" name="fecha_multi[]" class="form-control"></td>

<td>
<button type="button" class="btn btn-danger" onclick="eliminarPago(this)">X</button>
</td>

</tr>

</tbody>
</table>

<button type="button" class="btn btn-success" onclick="agregarPago()">+ Pago</button>

</div>

<button class="btn btn-primary w-100">Guardar</button>

</form>
</div>

<script>

// ================= SUMA TOTAL =================
document.addEventListener("input", function(){
let total = 0;

document.querySelectorAll(".precio").forEach(i=>{
total += parseFloat(i.value) || 0;
});

document.getElementById("total").value = total.toFixed(2);
});

// ================= TOURS =================
function agregarFila(){
let fila = document.querySelector("#bodyTours tr");
let nueva = fila.cloneNode(true);

nueva.querySelectorAll("input").forEach(i=>i.value="");
document.getElementById("bodyTours").appendChild(nueva);

actualizarTours();
}

function eliminarFila(btn){
let filas = document.querySelectorAll("#bodyTours tr");
if(filas.length==1) return alert("Debe haber al menos 1");
btn.closest("tr").remove();
actualizarTours();
}

// ================= PAGOS =================
function agregarPago(){
let fila = document.querySelector("#bodyPagos tr");
let nueva = fila.cloneNode(true);

nueva.querySelectorAll("input").forEach(i=>i.value="");
document.getElementById("bodyPagos").appendChild(nueva);
}

function eliminarPago(btn){
let filas = document.querySelectorAll("#bodyPagos tr");
if(filas.length==1) return alert("Debe haber al menos 1");
btn.closest("tr").remove();
}

// ================= RELACION TOUR - PAGO =================
function actualizarTours(){

let tours = document.querySelectorAll("#bodyTours tr");
let selects = document.querySelectorAll("[name='id_detalle_pago[]']");

selects.forEach(s=>{
s.innerHTML = "<option value=''>Operación</option>";

tours.forEach((t,i)=>{
let op = document.createElement("option");
op.value = i;
op.textContent = "Tour " + (i+1);
s.appendChild(op);
});

});

}

</script>

</body>
</html>