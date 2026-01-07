<?php
ob_start();
include '../../../conexion.php';

// Validar ID del cliente
if (!isset($_GET['id_cliente'])) {
    die("❌ Error: Falta el ID del cliente.");
}

$id_cliente = (int) $_GET['id_cliente'];

// Obtener nombre del cliente
$query = "SELECT CONCAT(nombre, ' ', apellido) AS nombre_completo FROM Datos_clientes WHERE id_cliente = $id_cliente";
$res = mysqli_query($conexion, $query);
$cliente = mysqli_fetch_assoc($res);

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Recorremos todas las operaciones enviadas
    foreach ($_POST['fecha_reserva'] as $i => $v) {

        $fecha_reserva = $_POST['fecha_reserva'][$i];
        $nombre_servicio = $_POST['nombre_servicio'][$i];
        $fecha_salida = $_POST['fecha_salida'][$i];
        $fecha_retorno = $_POST['fecha_retorno'][$i];
        $modalidad_retorno = $_POST['modalidad_retorno'][$i];
        $incluye_ingreso = isset($_POST['incluye_ingreso'][$i]) ? 'Con ingreso' : 'Sin ingreso';
        $servicios = $_POST['servicio_adicional'] ?? [];

if (!is_array($servicios)) {
    $servicios = [$servicios]; // convertir string en array
}

$servicio_adicional = implode(", ", $servicios);

        $observaciones = $_POST['observaciones'][$i];
        $encargado = $_POST['Encargado'][$i];
        $empresa = $_POST['empresa'][$i];

        $metodo_pago = $_POST['metodo_pago'][$i];
        $tipo_moneda = $_POST['tipo_moneda'][$i];
        $precio_servicio = floatval($_POST['precio_servicio'][$i]);
        $pagado_a_cuenta = floatval($_POST['pagado_a_cuenta'][$i]);
        $saldo_pendiente = floatval($_POST['saldo_pendiente'][$i]);
        $fecha_pago_saldo = $_POST['fecha_pago_saldo'][$i];
        $comision = $_POST['comision'][$i];

        // INSERTAR OPERACIÓN
        $queryOp = "INSERT INTO Operaciones (
            id_cliente, fecha_reserva, nombre_servicio, fecha_salida, fecha_retorno,
            modalidad_retorno, incluye_ingreso, servicio_adicional,
            observaciones, Encargado, empresa
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmtOp = mysqli_prepare($conexion, $queryOp);
        mysqli_stmt_bind_param(
            $stmtOp,
            "issssssssss",
            $id_cliente,
            $fecha_reserva,
            $nombre_servicio,
            $fecha_salida,
            $fecha_retorno,
            $modalidad_retorno,
            $incluye_ingreso,
            $servicio_adicional,
            $observaciones,
            $encargado,
            $empresa
        );

        mysqli_stmt_execute($stmtOp);
        $id_operaciones = mysqli_insert_id($conexion);

        // INSERTAR CONTABILIDAD
        $queryCont = "INSERT INTO Contabilidad (
            id_operaciones, metodo_pago, tipo_moneda, precio_servicio,
            pagado_a_cuenta, saldo_pendiente, fecha_pago_saldo, comision
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmtCont = mysqli_prepare($conexion, $queryCont);
        mysqli_stmt_bind_param(
            $stmtCont,
            "issdddss",
            $id_operaciones,
            $metodo_pago,
            $tipo_moneda,
            $precio_servicio,
            $pagado_a_cuenta,
            $saldo_pendiente,
            $fecha_pago_saldo,
            $comision
        );

        mysqli_stmt_execute($stmtCont);
    }

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

    <form method="POST" id="formTours">

        <div id="contenedorTours">

            <!-- ================= TOUR ITEM ================= -->
            <div class="tour-item card shadow p-4 mb-4">

                <!-- DATOS DE OPERACIÓN -->
                <h5 class="text-secondary mb-3">📋 Datos de Operación</h5>
                <div class="row g-3">

                    <div class="col-md-4">
                        <label>Fecha de Reserva</label>
                        <input type="date" name="fecha_reserva[]" class="form-control" required value="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="col-md-4">
                        <label>Servicio</label>
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
                    </div>

                    <div class="col-md-4">
                        <label>Fecha de Salida</label>
                        <input type="date" name="fecha_salida[]" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label>Fecha de Retorno</label>
                        <input type="date" name="fecha_retorno[]" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label>Modalidad Retorno</label>
                        <select name="modalidad_retorno[]" class="form-select">
                            <option value="">-- Seleccione --</option>
                            <option value="Tren">Con Tren</option>
                            <option value="Carro">Con Carro</option>
                            <option value="Sin retorno">Sin Retorno</option>
                        </select>
                    </div>

                    <div class="col-md-4 mt-4">
                        <div class="form-check">
                            <input type="checkbox" name="incluye_ingreso[]" class="form-check-input">
                            <label class="form-check-label">¿Incluye Ingreso?</label>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label>Servicio Adicional</label>
                        <select name="servicio_adicional[]" class="form-select" multiple>
                            <option value="Ninguna">Ninguna</option>
                            <option value="Ingreso a Mollepata">Ingreso a Mollepata</option>
                            <option value="Bolsa de Dormir">Bolsa de Dormir</option>
                            <option value="Trans. Mochilas Playa-Idro">Trans. Mochilas Playa-Idro</option>
                            <option value="Trans. Mochilas Hidro-Aguas">Trans. Mochilas Hidro-Aguas</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label>Empresa</label>
                        <input type="text" name="empresa[]" class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label>Encargado</label>
                        <input type="text" name="Encargado[]" class="form-control">
                    </div>

                    <div class="col-12">
                        <label>Observaciones</label>
                        <textarea name="observaciones[]" class="form-control" rows="3"></textarea>
                    </div>

                </div>

                <hr class="my-4">

                <!-- DATOS CONTABLES -->
                <h5 class="text-secondary mb-3">💰 Datos Contables</h5>

                <div class="row g-3">

                    <div class="col-md-4">
                        <label>Método de Pago</label>
                        <select name="metodo_pago[]" class="form-select">
                            <option value="Efectivo">Efectivo</option>
                            <option value="We travel">We travel</option>
                             <option value="CULQI">CULQI</option>
                            <option value="Izipay">Izipay</option>
                            <option value="PAYPAL">PAYPAL</option>
                            <option value="Bcp">Bcp</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label>Moneda</label>
                        <select name="tipo_moneda[]" class="form-select">
                            <option value="Soles">Soles (PEN)</option>
                            <option value="Dólares">Dólares (USD)</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label>Precio del Servicio</label>
                        <input type="number" step="0.01" name="precio_servicio[]" class="form-control precio_servicio">
                    </div>

                    <div class="col-md-4">
                        <label>Pagado a Cuenta</label>
                        <input type="number" step="0.01" name="pagado_a_cuenta[]" class="form-control pagado_a_cuenta">
                    </div>

                    <div class="col-md-4">
                        <label>Saldo Pendiente</label>
                        <input type="number" step="0.01" name="saldo_pendiente[]" class="form-control saldo_pendiente" readonly>
                    </div>

                    <div class="col-md-4">
                        <label>Fecha Pago del Saldo</label>
                        <input type="date" name="fecha_pago_saldo[]" class="form-control">
                    </div>

                    <div class="col-md-4">
                        <label>Comisión</label>
                        <input type="text" name="comision[]" class="form-control">
                    </div>
<div class="mt-4 d-flex gap-2 flex-wrap">
    <button type="button" class="btn btn-success" onclick="agregarTour()">+ Agregar otro tour</button>

    <button type="button" class="btn btn-danger" onclick="eliminarTour(this)">
        🗑 Eliminar este tour
    </button>

    <a href="index.php" class="btn btn-secondary">Cancelar</a>
    <button type="submit" class="btn btn-primary">Guardar Operación</button>
</div>

                </div>

            </div>
            <!-- ================= FIN TOUR ITEM ================= -->

        </div>

       

    </form>

</div>


<!-- ================= JAVASCRIPT FINAL ================= -->
<script>
// 🟦 Calcular saldo por cada bloque independiente
document.addEventListener("input", function(e) {

    if (
        e.target.classList.contains("precio_servicio") ||
        e.target.classList.contains("pagado_a_cuenta")
    ) {
        let bloque = e.target.closest(".tour-item");

        let precio = parseFloat(bloque.querySelector(".precio_servicio").value) || 0;
        let pagado = parseFloat(bloque.querySelector(".pagado_a_cuenta").value) || 0;

        bloque.querySelector(".saldo_pendiente").value = (precio - pagado).toFixed(2);
    }

});


// 🟩 Agregar otro tour
function agregarTour() {

    const contenedor = document.getElementById("contenedorTours");
    const primer = document.querySelector(".tour-item");

    let nuevo = primer.cloneNode(true);

    // limpiar inputs
    nuevo.querySelectorAll("input").forEach(i => {

        if (i.type === "checkbox") {
            i.checked = false;
        } else {
            i.value = "";
        }
    });

    // limpiar selects
    nuevo.querySelectorAll("select").forEach(s => {
    
        if (s.multiple) {
            [...s.options].forEach(o => o.selected = false);
        } else {
            s.selectedIndex = 0;
        }
    });

    // limpiar textarea
    nuevo.querySelectorAll("textarea").forEach(t => t.value = "");

    contenedor.appendChild(nuevo);
}
function eliminarTour(btn) {

    const contenedor = document.getElementById("contenedorTours");
    const tours = contenedor.querySelectorAll(".tour-item");

    // No permitir borrar el último tour
    if (tours.length === 1) {
        alert("⚠️ Debe existir al menos un tour.");
        return;
    }

    // Eliminar solo el bloque donde se presionó el botón
    btn.closest(".tour-item").remove();
}
</script>
<?php include '../../footer.php'; ?>
</body>
</html>
