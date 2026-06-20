<?php
ob_start();
include '../../../conexion.php';

// =============================
// VALIDAR CLIENTE
// =============================
if (!isset($_GET['id_cliente'])) {
    die("❌ Error: Falta el ID del cliente.");
}

$id_cliente = (int) $_GET['id_cliente'];

// =============================
// OBTENER GRUPO
// =============================
$qGrupo = mysqli_query($conexion,"
    SELECT id_grupo 
    FROM clientes_grupo  
    WHERE id_cliente = $id_cliente
    LIMIT 1
");
$rowGrupo = mysqli_fetch_assoc($qGrupo);
$id_grupo = $rowGrupo['id_grupo'] ?? null;

// =============================
// CLIENTE
// =============================
$res = mysqli_query($conexion,"
    SELECT CONCAT(nombre,' ',apellido) nombre_completo 
    FROM datos_clientes 
    WHERE id_cliente = $id_cliente
");
$cliente = mysqli_fetch_assoc($res);


// =============================
// GUARDAR
// =============================
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    mysqli_begin_transaction($conexion);

    try {

        // =============================
        // TOTALES POR MONEDA (solo tours)
        // =============================
        $tipo_precio     = $_POST['tipo_precio'] ?? 'por_tour';
        $total_operacion = floatval($_POST['total_operacion'] ?? 0);

        $total_soles   = 0;
        $total_dolares = 0;

        foreach ($_POST['precio_tour'] as $i => $p) {
            $precio = floatval($p);
            $moneda = $_POST['moneda_tour'][$i] ?? 'Soles';
            if ($moneda == 'Soles' || $moneda == 'S/') {
                $total_soles += $precio;
            } else {
                $total_dolares += $precio;
            }
        }

        $precio_final = ($tipo_precio == 'total' && $total_operacion > 0)
            ? $total_operacion
            : 0;

        // =============================
        // INSERT OPERACION
        // =============================
        $fecha_reserva = $_POST['fecha_reserva'][0] ?? date('Y-m-d');
        $observaciones = $_POST['observaciones'][0] ?? '';
        $encargado     = $_POST['Encargado'][0] ?? '';
        $estado        = 'pendiente';

        $stmtOp = mysqli_prepare($conexion,"
            INSERT INTO operaciones 
            (id_cliente, id_grupo, fecha_reserva, observaciones, encargado, tipo_precio, total_operacion, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        mysqli_stmt_bind_param($stmtOp, "iisssdds",
            $id_cliente, $id_grupo, $fecha_reserva, $observaciones,
            $encargado, $tipo_precio, $precio_final, $estado
        );
        mysqli_stmt_execute($stmtOp);
        $id_operaciones = mysqli_insert_id($conexion);

        // =============================
        // INSERT DETALLE + ADICIONALES
        // =============================
        $detalles_ids = [];

        foreach ($_POST['id_servicio'] as $i => $id_servicio) {

            $id_servicio = (int)$id_servicio;
            if ($id_servicio <= 0) continue;

            $precio = floatval($_POST['precio_tour'][$i] ?? 0);
            if ($precio <= 0) continue;

            $fecha_salida  = $_POST['fecha_salida'][$i]  ?? null;
            $fecha_retorno = $_POST['fecha_retorno'][$i] ?? null;
            $modalidad     = $_POST['modalidad_retorno'][$i] ?? null;

            $moneda = $_POST['moneda_tour'][$i] ?? 'Soles';
            $moneda = ($moneda == 'S/' ? 'Soles' : ($moneda == '$' ? 'Dólares' : $moneda));

            $ingreso = ($_POST['incluye_ingreso'][$i] ?? 'NO') === 'SI' ? 'SI' : 'NO';

            $stmtDet = mysqli_prepare($conexion,"
                INSERT INTO operaciones_detalle
                (id_operaciones, id_servicio, precio, fecha_salida, fecha_retorno, modalidad_retorno, incluye_ingreso, tipo_moneda)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            mysqli_stmt_bind_param($stmtDet, "iidsssss",
                $id_operaciones, $id_servicio, $precio,
                $fecha_salida, $fecha_retorno, $modalidad, $ingreso, $moneda
            );
            mysqli_stmt_execute($stmtDet);

            $id_detalle         = mysqli_insert_id($conexion);
            $detalles_ids[$i]   = $id_detalle;

            // ADICIONALES del tour
            if (!empty($_POST['servicio_adicional'][$i])) {
                foreach ($_POST['servicio_adicional'][$i] as $k => $nombre) {
                    if ($nombre == "Ninguna") continue;

                    // Precio individual del adicional
                    $precio_adicional = floatval($_POST['precio_adicional'][$i][$k] ?? 0);

                    $stmtAd = mysqli_prepare($conexion,"
                        INSERT INTO adicionales_detalle (id_detalle, nombre, precio)
                        VALUES (?, ?, ?)
                    ");
                    mysqli_stmt_bind_param($stmtAd, "isd", $id_detalle, $nombre, $precio_adicional);
                    mysqli_stmt_execute($stmtAd);
                }
            }
        }

        // =============================
        // PAGOS — separar tours y adicionales
        // =============================
        $total_pagado_soles   = 0;
        $total_pagado_dolares = 0;

        if (!empty($_POST['monto_multi'])) {

            foreach ($_POST['monto_multi'] as $i => $monto) {

                $monto = floatval($monto);
                if ($monto <= 0) continue;

                $tipo_pago  = $_POST['tipo_pago'][$i]         ?? 'tour';
                $metodo     = $_POST['metodo_pago_multi'][$i] ?? 'Efectivo';
                $moneda     = $_POST['moneda_multi'][$i]       ?? 'Soles';
                $fecha      = $_POST['fecha_multi'][$i]        ?? date('Y-m-d');
                $id_detalle = $_POST['id_detalle_pago'][$i]   ?? null;

                $moneda = ($moneda == 'S/' ? 'Soles' : ($moneda == '$' ? 'Dólares' : $moneda));

                // ✅ Solo sumar al saldo de tours si el tipo es 'tour'
                if ($tipo_pago === 'tour') {
                    if ($moneda === 'Soles') {
                        $total_pagado_soles   += $monto;
                    } else {
                        $total_pagado_dolares += $monto;
                    }
                }
                // Los pagos de 'adicional' se registran pero NO afectan el saldo de tours

                $stmtPago = mysqli_prepare($conexion,"
                    INSERT INTO pagos
                    (id_operaciones, id_detalle, tipo, metodo_pago, moneda, monto, fecha)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                mysqli_stmt_bind_param($stmtPago, "iisssds",
                    $id_operaciones, $id_detalle, $tipo_pago,
                    $metodo, $moneda, $monto, $fecha
                );
                mysqli_stmt_execute($stmtPago);
            }
        }

        // =============================
        // CONTABILIDAD
        // =============================
        $comision = floatval($_POST['comision'] ?? 0);

        $saldo_soles   = $total_soles   - $total_pagado_soles;
        $saldo_dolares = $total_dolares - $total_pagado_dolares;

        $estado_contable = ($saldo_soles <= 0 && $saldo_dolares <= 0)
            ? 'pagado'
            : 'pendiente';

        $stmtCont = mysqli_prepare($conexion,"
            INSERT INTO contabilidad (id_operaciones, comision, estado)
            VALUES (?, ?, ?)
        ");
        mysqli_stmt_bind_param($stmtCont, "ids", $id_operaciones, $comision, $estado_contable);
        mysqli_stmt_execute($stmtCont);

        mysqli_commit($conexion);
        header("Location: index.php?mensaje=agregado");
        exit;

    } catch (Exception $e) {
        mysqli_rollback($conexion);
        die("❌ Error al guardar: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agregar Operación</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="stilo.css">
    <style>
        .badge-moneda { font-size: .75rem; }
        .table th { white-space: nowrap; }
        #resumen-box .form-control[readonly] { background: #f8f9fa; font-weight: 600; }
        .saldo-rojo { color: #dc3545 !important; }
        .saldo-verde { color: #198754 !important; }
    </style>
</head>
<body class="bg-light">
<?php include '../../sidebar.php'; ?>

<div class="container-fluid mt-4 px-4">

<h3 class="text-primary mb-4">
    ➕ Agregar Operación — <?= htmlspecialchars($cliente['nombre_completo']) ?>
</h3>

<form method="POST" id="formTours">

<!-- ===== DATOS GENERALES ===== -->
<div class="card mb-4 shadow-sm">
    <div class="card-header bg-primary text-white fw-bold">📋 Datos Generales</div>
    <div class="card-body">
        <div class="row g-3">

            <div class="col-md-3">
                <label class="form-label">Fecha de Reserva</label>
                <input type="date" name="fecha_reserva[]" class="form-control" value="<?= date('Y-m-d') ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label">Encargado</label>
                <input type="text" name="Encargado[]" class="form-control" placeholder="Nombre del encargado">
            </div>

            <div class="col-md-3">
                <label class="form-label">Tipo de precio</label>
                <select name="tipo_precio" class="form-select">
                    <option value="por_tour">Por tour (automático)</option>
                    <option value="total">Total fijo</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Total fijo (si aplica)</label>
                <!-- Este campo lo actualiza JS, solo se usa si tipo_precio=total -->
                <input type="number" step="0.01" name="total_operacion" id="total_operacion_input" class="form-control" placeholder="0.00">
            </div>

            <div class="col-md-12">
                <label class="form-label">Observaciones</label>
                <textarea name="observaciones[]" class="form-control" rows="2" placeholder="Notas internas..."></textarea>
            </div>

        </div>
    </div>
</div>


<!-- ===== TOURS ===== -->
<div class="card mb-4 shadow-sm">
    <div class="card-header bg-success text-white fw-bold">🗺️ Tours del Grupo</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle text-center mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="min-width:220px">Servicio</th>
                        <th width="110">Precio</th>
                        <th width="90">Moneda</th>
                        <th width="140">Salida</th>
                        <th width="140">Retorno</th>
                        <th width="130">Modalidad</th>
                        <th width="80">Ingreso</th>
                        <th style="min-width:180px">Adicionales</th>
                        <th width="60"></th>
                    </tr>
                </thead>
                <tbody id="bodyTours">
                    <!-- fila base -->
                    <tr>
                        <td>
                            <select name="id_servicio[]" class="form-select form-select-sm">
                                <option value="">-- Seleccione --</option>
                                <option value="1">SALKANTAY A MACHU PICCHU 5 DÍAS</option>
                                <option value="2">SALKANTAY A MACHU PICCHU 4 DÍAS</option>
                                <option value="3">SALKANTAY A MACHU PICCHU 3 DÍAS</option>
                                <option value="4">SALKANTAY TREK 5D / 4N WITH LUXURY DOMES</option>
                                <option value="5">SALKANTAY TREK 4D / 3N WITH LUXURY DOMES</option>
                                <option value="6">SALKANTAY TREK 2D / 1N WITH LUXURY DOMES</option>
                                <option value="7">SALKANTAY Y LAGUNA HUMANTAY 2 DÍAS</option>
                                <option value="8">SALKANTAY Y CAMINO INCA 7 DÍAS (PRIVADO)</option>
                                <option value="9">CAMINO INCA 4 DÍAS</option>
                                <option value="10">CAMINO INCA 4 DÍAS (PRIVADO)</option>
                                <option value="11">CAMINO INCA 2 DÍAS</option>
                                <option value="12">MACHU PICCHU DE UN DÍA</option>
                                <option value="13">MACHU PICCHU EN TREN 2 DÍAS</option>
                                <option value="14">VALLE SAGRADO A MACHU PICCHU 2 DÍAS</option>
                                <option value="15">CHOQUEQUIRAO 5 DÍAS (PRIVADO)</option>
                                <option value="16">CHOQUEQUIRAO 4 DÍAS</option>
                                <option value="17">CHOQUEQUIRAO 4 DÍAS (PRIVADO)</option>
                                <option value="18">LARES A MACHU PICCHU 4 DÍAS (PRIVADO)</option>
                                <option value="19">AUSANGATE Y MONTAÑA DE COLORES 4 DÍAS</option>
                                <option value="20">HUCHUY QOSQO 3 DÍAS (PRIVADO)</option>
                                <option value="21">INCA JUNGLE TRAIL 4 DAYS</option>
                                <option value="22">LAGUNA HUMANTAY DE UN DÍA</option>
                                <option value="23">MONTAÑA DE COLORES DE UN DÍA</option>
                                <option value="24">PALCOYO DE UN DÍA</option>
                                <option value="25">VALLE SAGRADO VIP DE UN DÍA</option>
                                <option value="26">VALLE TRADICIONAL</option>
                                <option value="27">7 LAGUNAS DE AUSANGATE DE UN DÍA</option>
                                <option value="28">MARAS MORAY DE UN DÍA</option>
                                <option value="29">Q'ESHUACHAKA Y 4 LAGUNAS DE UN DÍA</option>
                                <option value="30">WAQRAPUKARA DE UN DÍA</option>
                                <option value="31">CITY TOUR CUSCO MEDIO DÍA</option>
                                <option value="32">CUATRIMOTOS</option>
                                <option value="33">ICA – PARACAS DE UN DÍA</option>
                                <option value="34">PUNO DE UN DÍA</option>
                                <option value="35">MANU 4 DÍAS Y 3 NOCHES</option>
                            </select>
                        </td>
                        <td>
                            <input type="number" step="0.01" name="precio_tour[]" class="form-control form-control-sm precio_tour" placeholder="0.00">
                        </td>
                        <td>
                            <select name="moneda_tour[]" class="form-select form-select-sm">
                                <option value="Soles">S/</option>
                                <option value="Dólares">$</option>
                            </select>
                        </td>
                        <td>
                            <input type="date" name="fecha_salida[]" class="form-control form-control-sm">
                        </td>
                        <td>
                            <input type="date" name="fecha_retorno[]" class="form-control form-control-sm">
                        </td>
                        <td>
                            <select name="modalidad_retorno[]" class="form-select form-select-sm">
                                <option value="">--</option>
                                <option>Tren</option>
                                <option>Carro</option>
                                <option>Sin retorno</option>
                            </select>
                        </td>
                        <td>
                            <!-- hidden garantiza que si el checkbox no está marcado igual llega 'NO' -->
                            <input type="hidden"   name="incluye_ingreso[0]" value="NO">
                            <input type="checkbox" name="incluye_ingreso[0]" value="SI" class="form-check-input" title="Incluye ingreso">
                        </td>
                        <td>
                            <select name="servicio_adicional[0][]" multiple class="form-select form-select-sm adicionales-select">
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
                            <button type="button" class="btn btn-sm btn-danger" onclick="eliminarFila(this)">✕</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        <button type="button" class="btn btn-success btn-sm" onclick="agregarFila()">➕ Agregar tour</button>
    </div>
</div>

<!-- ===== RESUMEN ===== -->
<div class="card mb-4 shadow-sm" id="resumen-box">
    <div class="card-header bg-warning fw-bold">💰 Resumen de Operación</div>
    <div class="card-body">
        <div class="row g-3">

            <div class="col-md-3">
                <label class="form-label">Total tours S/</label>
                <input type="text" id="totalToursSoles" class="form-control" readonly placeholder="0.00">
            </div>
            <div class="col-md-3">
                <label class="form-label">Total tours $</label>
                <input type="text" id="totalToursDolares" class="form-control" readonly placeholder="0.00">
            </div>

            <div class="col-md-3">
                <label class="form-label">Pagado tours S/</label>
                <input type="text" id="pagadoToursSoles" class="form-control" readonly placeholder="0.00">
            </div>
            <div class="col-md-3">
                <label class="form-label">Pagado tours $</label>
                <input type="text" id="pagadoToursDolares" class="form-control" readonly placeholder="0.00">
            </div>

            <div class="col-md-3">
                <label class="form-label">Saldo tours S/</label>
                <input type="text" id="saldoSoles" class="form-control fw-bold" readonly placeholder="0.00">
            </div>
            <div class="col-md-3">
                <label class="form-label">Saldo tours $</label>
                <input type="text" id="saldoDolares" class="form-control fw-bold" readonly placeholder="0.00">
            </div>

            <div class="col-md-3">
                <label class="form-label">Pagado adicionales S/</label>
                <input type="text" id="pagadoAdSoles" class="form-control text-muted" readonly placeholder="0.00">
            </div>
            <div class="col-md-3">
                <label class="form-label">Pagado adicionales $</label>
                <input type="text" id="pagadoAdDolares" class="form-control text-muted" readonly placeholder="0.00">
            </div>

            <div class="col-md-3">
                <label class="form-label">Comisión</label>
                <input type="number" step="0.01" name="comision" class="form-control" placeholder="0.00">
            </div>

        </div>
    </div>
</div>


<!-- ===== PAGOS ===== -->
<div class="card mb-4 shadow-sm">
    <div class="card-header bg-info text-white fw-bold">💳 Pagos Realizados</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered align-middle text-center mb-0">
                <thead class="table-light">
                    <tr>
                        <th width="130">Tipo de pago</th>
                        <th width="150">Método</th>
                        <th width="90">Moneda</th>
                        <th width="130">Monto</th>
                        <th width="150">Fecha</th>
                        <th width="50"></th>
                    </tr>
                </thead>
                <tbody id="bodyPagos">
                    <tr>
                        <td>
                            <!-- ✅ tour y adicional son tipos separados — no se mezclan en el saldo -->
                            <select name="tipo_pago[]" class="form-select form-select-sm tipo-pago-select">
                                <option value="tour">Tour</option>
                                <option value="adicional">Adicional</option>
                            </select>
                        </td>
                        <td>
                            <select name="metodo_pago_multi[]" class="form-select form-select-sm">
                                <option value="Efectivo">Efectivo</option>
                                <option value="We travel">We travel</option>
                                <option value="CULQI">CULQI</option>
                                <option value="Izipay">Izipay</option>
                                <option value="PAYPAL">PAYPAL</option>
                                <option value="Bcp">Bcp</option>
                                <option value="YAPE">YAPE</option>
                            </select>
                        </td>
                        <td>
                            <select name="moneda_multi[]" class="form-select form-select-sm">
                                <option value="Soles">S/</option>
                                <option value="Dólares">$</option>
                            </select>
                        </td>
                        <td>
                            <input type="number" step="0.01" name="monto_multi[]" class="form-control form-control-sm monto-pago" placeholder="0.00">
                        </td>
                        <td>
                            <input type="date" name="fecha_multi[]" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>">
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-danger" onclick="eliminarPago(this)">✕</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer d-flex align-items-center gap-3">
        <button type="button" class="btn btn-info btn-sm text-white" onclick="agregarPago()">➕ Agregar pago</button>
        <small class="text-muted">
            💡 <strong>Tour</strong>: abono al costo del tour. 
            &nbsp;|&nbsp; 
            <strong>Adicional</strong>: bolsa de dormir, bastones, etc. (se registran por separado, no descuentan el saldo del tour).
        </small>
    </div>
</div>


<!-- ===== BOTÓN GUARDAR ===== -->
<div class="text-end mb-5">
    <button type="submit" class="btn btn-primary btn-lg px-5">
        💾 Guardar Operación
    </button>
</div>

</form>
</div><!-- /container -->


<script>
// =============================================
// DURACIÓN POR SERVICIO (días)
// =============================================
const DURACION_TOURS = {
    1:5, 2:4, 3:3, 4:5, 5:4, 6:2, 7:2, 8:7, 9:4, 10:4,
    11:2,12:1,13:2,14:2,15:5,16:4,17:4,18:4,19:4,
    20:3,21:4,22:1,23:1,24:1,25:1,26:1,27:1,28:1,
    29:1,30:1,31:1,32:1,33:1,34:1,35:4
};

const form = document.getElementById("formTours");


// =============================================
// AUTO FECHA RETORNO + recalcular siempre
// =============================================
document.addEventListener("change", function(e){
    const name = e.target.name;

    // Auto-fecha retorno al elegir servicio o fecha de salida
    if (name === "id_servicio[]" || name === "fecha_salida[]") {
        const fila   = e.target.closest("tr");
        const serv   = fila.querySelector("[name='id_servicio[]']").value;
        const salida = fila.querySelector("[name='fecha_salida[]']").value;
        const retornoInput = fila.querySelector("[name='fecha_retorno[]']");

        if (serv && salida && DURACION_TOURS[serv]) {
            const d = new Date(salida);
            d.setDate(d.getDate() + (DURACION_TOURS[serv] - 1));
            retornoInput.value = d.toISOString().split("T")[0];
        }
    }

    // ✅ Siempre recalcular — incluyendo cuando cambia tipo_pago, moneda, etc.
    actualizarResumen();
});

// =============================================
// EVENTO INPUT — montos y precios
// =============================================
document.addEventListener("input", function(e) {
    if (
        e.target.classList.contains("precio_tour") ||
        e.target.classList.contains("monto-pago")
    ) {
        actualizarResumen();
    }
});


// =============================================
// CALCULAR TOTALES TOURS (por moneda)
// =============================================
function calcularTotalTours() {
    let soles = 0, dolares = 0;
    document.querySelectorAll("#bodyTours tr").forEach(fila => {
        const precio = parseFloat(fila.querySelector(".precio_tour").value) || 0;
        const moneda = fila.querySelector("[name='moneda_tour[]']").value;
        if (moneda === "S/" || moneda === "Soles") {
            soles += precio;
        } else {
            dolares += precio;
        }
    });
    return { soles, dolares };
}


// =============================================
// CALCULAR PAGOS — separado por tipo
// =============================================
function calcularPagos() {
    let tourSoles = 0, tourDolares = 0;
    let adSoles   = 0, adDolares   = 0;

    document.querySelectorAll("#bodyPagos tr").forEach(fila => {
        const tipo   = fila.querySelector("[name='tipo_pago[]']").value;
        const moneda = fila.querySelector("[name='moneda_multi[]']").value;
        const monto  = parseFloat(fila.querySelector("[name='monto_multi[]']").value) || 0;
        const esSoles = (moneda === "S/" || moneda === "Soles");

        if (tipo === "tour") {
            esSoles ? (tourSoles   += monto) : (tourDolares   += monto);
        } else {
            // adicional — se registra por separado, NO resta del saldo de tours
            esSoles ? (adSoles     += monto) : (adDolares     += monto);
        }
    });

    return { tourSoles, tourDolares, adSoles, adDolares };
}


// =============================================
// ACTUALIZAR RESUMEN EN PANTALLA
// =============================================
function actualizarResumen() {
    const total  = calcularTotalTours();
    const pagado = calcularPagos();

    const saldoS = total.soles   - pagado.tourSoles;
    const saldoD = total.dolares - pagado.tourDolares;

    // Totales tours
    document.getElementById("totalToursSoles").value   = total.soles.toFixed(2);
    document.getElementById("totalToursDolares").value = total.dolares.toFixed(2);

    // Pagado tours
    document.getElementById("pagadoToursSoles").value   = pagado.tourSoles.toFixed(2);
    document.getElementById("pagadoToursDolares").value = pagado.tourDolares.toFixed(2);

    // Saldo tours con color
    const saldoSEl = document.getElementById("saldoSoles");
    const saldoDEl = document.getElementById("saldoDolares");

    saldoSEl.value = saldoS.toFixed(2);
    saldoDEl.value = saldoD.toFixed(2);
    saldoSEl.classList.toggle("saldo-rojo",  saldoS > 0);
    saldoSEl.classList.toggle("saldo-verde", saldoS <= 0);
    saldoDEl.classList.toggle("saldo-rojo",  saldoD > 0);
    saldoDEl.classList.toggle("saldo-verde", saldoD <= 0);

    // Adicionales (solo visual)
    document.getElementById("pagadoAdSoles").value   = pagado.adSoles.toFixed(2);
    document.getElementById("pagadoAdDolares").value = pagado.adDolares.toFixed(2);

    // Campo oculto para PHP
    document.getElementById("total_operacion_input").value = total.soles.toFixed(2);
}


// =============================================
// REINDEXAR FILAS (incluye_ingreso + adicionales)
// =============================================
function reindexarFilas() {
    document.querySelectorAll("#bodyTours tr").forEach((fila, index) => {
        // Reindexar los dos inputs de incluye_ingreso (hidden + checkbox)
        fila.querySelectorAll("[name^='incluye_ingreso']").forEach(el => {
            el.name = `incluye_ingreso[${index}]`;
        });
        // Reindexar adicionales
        const adicional = fila.querySelector("[name^='servicio_adicional']");
        if (adicional) adicional.name = `servicio_adicional[${index}][]`;
    });
}


// =============================================
// AGREGAR FILA TOUR
// =============================================
function agregarFila() {
    const body  = document.getElementById("bodyTours");
    const base  = body.querySelector("tr");
    const nueva = base.cloneNode(true);

    nueva.querySelectorAll("input").forEach(i => {
        if (i.type === "checkbox") i.checked = false;
        else i.value = "";
    });
    nueva.querySelectorAll("select").forEach(s => s.selectedIndex = 0);

    body.appendChild(nueva);
    reindexarFilas();
    actualizarResumen();
}


// =============================================
// ELIMINAR FILA TOUR
// =============================================
function eliminarFila(btn) {
    if (document.querySelectorAll("#bodyTours tr").length === 1) {
        alert("Debe haber al menos 1 tour.");
        return;
    }
    btn.closest("tr").remove();
    reindexarFilas();
    actualizarResumen();
}


// =============================================
// AGREGAR FILA PAGO
// =============================================
function agregarPago() {
    const body  = document.getElementById("bodyPagos");
    const base  = body.querySelector("tr");
    const nueva = base.cloneNode(true);

    nueva.querySelectorAll("input").forEach(i => {
        if (i.type !== "date") i.value = "";
    });
    nueva.querySelectorAll("select").forEach(s => s.selectedIndex = 0);

    body.appendChild(nueva);
    actualizarResumen();
}


// =============================================
// ELIMINAR FILA PAGO
// =============================================
function eliminarPago(btn) {
    if (document.querySelectorAll("#bodyPagos tr").length === 1) {
        alert("Debe haber al menos 1 fila de pago.");
        return;
    }
    btn.closest("tr").remove();
    actualizarResumen();
}


// =============================================
// EVENTOS DE INPUT
// =============================================
document.addEventListener("input", function(e) {
    if (
        e.target.classList.contains("precio_tour") ||
        e.target.classList.contains("monto-pago")
    ) {
        actualizarResumen();
    }
});


// =============================================
// INICIO
// =============================================
document.addEventListener("DOMContentLoaded", actualizarResumen);


// =============================================
// VALIDACIÓN AL ENVIAR
// =============================================
form.addEventListener("submit", function(e) {
    const total  = calcularTotalTours();
    const pagado = calcularPagos();

    if (pagado.tourSoles > total.soles + 0.01) {
        alert("❌ El pago en Soles supera el total del tour en Soles.");
        e.preventDefault();
        return;
    }
    if (pagado.tourDolares > total.dolares + 0.01) {
        alert("❌ El pago en Dólares supera el total del tour en Dólares.");
        e.preventDefault();
    }
});
</script>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>
</body>
</html>