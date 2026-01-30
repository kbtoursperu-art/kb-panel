<?php
include '../../../conexion.php';

// ✅ Validar ID
if (!isset($_GET['id'])) {
    die("<div class='alert alert-danger m-4'>❌ Error: Falta el ID de la operación.</div>");
}

$id = (int)$_GET['id'];

// 🔹 Obtener datos actuales
$query = "
SELECT 
    d.id_cliente,
    CONCAT(d.nombre, ' ', d.apellido) AS cliente,
    d.nro_pasaporte,
    kb.grupo,

    o.id_operaciones,
    o.nombre_servicio,
    o.fecha_reserva,
    o.fecha_salida,
    o.fecha_retorno,
    o.incluye_ingreso,
    o.modalidad_retorno,
    o.servicio_adicional,
    o.observaciones,   
    o.Encargado,
    o.empresa,

    c.metodo_pago,
    c.tipo_moneda,
    c.precio_servicio,
    c.pagado_a_cuenta,
    c.saldo_pendiente,
    c.fecha_pago_saldo,
    c.comision,

    c.precio_servicio_adicional,
    c.metodo_pago_adicional,
    c.tipo_moneda_adicional,
    c.pagado_adicional,
    c.saldo_adicional

FROM Datos_clientes d
INNER JOIN Clientes_KB kb ON d.id_cliente = kb.id_cliente
LEFT JOIN Operaciones o ON d.id_cliente = o.id_cliente
LEFT JOIN Contabilidad c ON o.id_operaciones = c.id_operaciones
WHERE o.id_operaciones = $id
LIMIT 1";


$res = mysqli_query($conexion, $query);
if (!$res || mysqli_num_rows($res) == 0) {
    die("<div class='alert alert-danger m-4'>❌ No se encontró la operación.</div>");
}
$operacion = mysqli_fetch_assoc($res);

// ✅ Guardar cambios al enviar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Datos de Operación ---
    $fecha_reserva = $_POST['fecha_reserva'];
    $nombre_servicio = $_POST['nombre_servicio'];
    $fecha_salida = $_POST['fecha_salida'];
    $fecha_retorno = $_POST['fecha_retorno'];
    $modalidad_retorno = $_POST['modalidad_retorno'];
    $incluye_ingreso = isset($_POST['incluye_ingreso']) ? 'Con ingreso' : 'Sin ingreso';
   $servicio_adicional = isset($_POST['servicio_adicional'])
    ? implode(', ', $_POST['servicio_adicional'])
    : 'Ninguna';

    $observaciones = $_POST['observaciones'];
    $Encargado = $_POST['Encargado'];
    $empresa = $_POST['empresa'];

    // --- Datos Contables ---
    $metodo_pago = $_POST['metodo_pago'];
    $tipo_moneda = $_POST['tipo_moneda'];
    $precio_servicio = floatval($_POST['precio_servicio']);
    $pagado_a_cuenta = floatval($_POST['pagado_a_cuenta']);
    $saldo_pendiente = floatval($_POST['saldo_pendiente']);
    $fecha_pago_saldo = $_POST['fecha_pago_saldo'];
    $comision = $_POST['comision'];
    $metodo_pago_saldo = $_POST['metodo_pago_saldo'] ?? null;
$tipo_moneda_saldo = $_POST['tipo_moneda_saldo'] ?? null;

    $precio_adicional = floatval($_POST['precio_servicio_adicional'] ?? 0);
$pagado_adicional = floatval($_POST['pagado_adicional'] ?? 0);
$saldo_adicional  = floatval($_POST['saldo_adicional'] ?? 0);

$metodo_pago_adicional = $_POST['metodo_pago_adicional'] ?? null;
$tipo_moneda_adicional = $_POST['tipo_moneda_adicional'] ?? null;

    // 🔹 Actualizar Operaciones
    $updateOp = "
    UPDATE Operaciones SET
        fecha_reserva = '$fecha_reserva',
        nombre_servicio = '$nombre_servicio',
        fecha_salida = '$fecha_salida',
        fecha_retorno = '$fecha_retorno',
        modalidad_retorno = '$modalidad_retorno',
        incluye_ingreso = '$incluye_ingreso',
        servicio_adicional = '$servicio_adicional',
        observaciones = '$observaciones',
        Encargado = '$Encargado',
        empresa = '$empresa'
    WHERE id_operaciones = $id";
    mysqli_query($conexion, $updateOp);

    // 🔹 Actualizar Contabilidad
    $updateCont = "
    UPDATE Contabilidad SET
    metodo_pago = '$metodo_pago',
    tipo_moneda = '$tipo_moneda',
    precio_servicio = $precio_servicio,
    pagado_a_cuenta = $pagado_a_cuenta,
    saldo_pendiente = $saldo_pendiente,
    fecha_pago_saldo = '$fecha_pago_saldo',
    comision = '$comision',
    
    precio_servicio_adicional = $precio_adicional,
    metodo_pago_adicional = '$metodo_pago_adicional',
    tipo_moneda_adicional = '$tipo_moneda_adicional',
    pagado_adicional = $pagado_adicional,
    saldo_adicional = $saldo_adicional

WHERE id_operaciones = $id";
    mysqli_query($conexion, $updateCont);

    echo "<script>
        alert('✅ Operación actualizada correctamente');
        window.location.href = 'index.php';
    </script>";
    
}
// ===============================
// 💳 PROCESAR PAGO DE SALDO
// ===============================
if (!empty($_POST['pago_saldo']) && $_POST['pago_saldo'] > 0) {

    $pago_saldo = floatval($_POST['pago_saldo']);

    $saldo_anterior  = floatval($operacion['saldo_pendiente']);
    $pagado_anterior = floatval($operacion['pagado_a_cuenta']);

    // 🔒 Evitar pagar más del saldo
    if ($pago_saldo > $saldo_anterior) {
        die("❌ El pago no puede ser mayor al saldo pendiente.");
    }

    // 🔢 Cálculos
    $nuevo_pagado = $pagado_anterior + $pago_saldo;
    $nuevo_saldo  = $saldo_anterior - $pago_saldo;

    // 📌 Estado automático
    if ($nuevo_saldo == 0) {
        $estado = 'CANCELADO';
        $fecha_pago_saldo = date('Y-m-d');
    } else {
        $estado = 'PENDIENTE';
        $fecha_pago_saldo = NULL;
    }

    // ✅ UPDATE FINAL
$sql = "
UPDATE Contabilidad SET
    pagado_a_cuenta = ?,
    saldo_pendiente = ?,
    estado = ?,
    fecha_pago_saldo = ?,
    metodo_pago_saldo = ?,
    tipo_moneda_saldo = ?,
    monto_pago_saldo = ?
WHERE id_operaciones = ?
";


  mysqli_stmt_bind_param(
    $stmt,
    "ddssssdi",
    $nuevo_pagado,
    $nuevo_saldo,
    $estado,
    $fecha_pago_saldo,
    $metodo_pago_saldo,
    $tipo_moneda_saldo,
    $pago_saldo,
    $id
);
    mysqli_stmt_execute($stmt);
    exit;
}


?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Editar Operación KB</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="stilo.css">
</head>
<body class="bg-light">
<?php include '../../sidebar.php'; ?>
<br>
<div class="container mt-5">
    <div class="card shadow p-4">
        <h3 class="text-primary mb-4">✏️ Editar Operación - <?= htmlspecialchars($operacion['cliente']) ?></h3>

        <form method="POST">
            <!-- DATOS DE OPERACIÓN -->
            <h5 class="text-secondary mb-3">📋 Datos de Operación</h5>
            <div class="row g-3">
                <div class="col-md-4">
                    <label>Fecha de Reserva</label>
                    <input type="date" name="fecha_reserva" class="form-control" value="<?= $operacion['fecha_reserva'] ?>">
                </div>
                 <div class="col-md-6 mb-3">
                <label class="form-label">Nombre Servicio:</label>
                <select name="nombre_servicio" class="form-control" required>
                    <option value="">-- Seleccione una opción --</option>
                    <?php
                    $servicios = [
                        "SALKANTAY A MACHU PICCHU 5 DÍAS",
                        "SALKANTAY A MACHU PICCHU 4 DÍAS",
                        "SALKANTAY A MACHU PICCHU 3 DÍAS",
                        "SALKANTAY Y LAGUNA HUMANTAY 2 DÍAS",
                        "SALKANTAY Y CAMINO INCA 7 DÍAS (PRIVADO)",
                        'SALKANTAY TREK 5D/4N WITH LUXURY DOMES (PRIVADO)',
                        'SALKANTAY TREK 4D / 3N WITH LUXURY DOMES (PRIVADO)',
                        'SALKANTAY & HUMANTAY LAKE 2D WITH LUXURY DOMES (PRIVADO)',
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
                        $selected = ($s == $operacion['nombre_servicio']) ? 'selected' : '';
                        echo "<option value='$s' $selected>$s</option>";
                    }
                    ?>
                </select>
            </div>
        
                <div class="col-md-4">
                    <label>Fecha de Salida</label>
                    <input type="date" name="fecha_salida" class="form-control" value="<?= $operacion['fecha_salida'] ?>">
                </div>
                <div class="col-md-4">
                    <label>Fecha de Retorno</label>
                    <input type="date" name="fecha_retorno" class="form-control" value="<?= $operacion['fecha_retorno'] ?>">
                </div>
                <div class="col-md-4">
                    <label>Modalidad Retorno</label>
                    <select name="modalidad_retorno" class="form-select">
                        <option value="">-- Seleccione --</option>
                        <option value="Tren" <?= ($operacion['modalidad_retorno'] == 'Tren') ? 'selected' : '' ?>>Con Tren</option>
                        <option value="Carro" <?= ($operacion['modalidad_retorno'] == 'Carro') ? 'selected' : '' ?>>Con Carro</option>
                        <option value="Sin retorno" <?= ($operacion['modalidad_retorno'] == 'Sin retorno') ? 'selected' : '' ?>>Sin Retorno</option>
                    </select>
                </div>
                <div class="col-md-4 mt-4">
                    <div class="form-check">
                        <input type="checkbox" name="incluye_ingreso" class="form-check-input" id="incluyeIngreso"
                            <?= ($operacion['incluye_ingreso'] == 'Con ingreso') ? 'checked' : '' ?>
>
                        <label for="incluyeIngreso" class="form-check-label">¿Incluye Ingreso?</label>
                    </div>
                </div>

              <div class="col-md-6">
    <label>Servicio Adicional</label>
    <select name="servicio_adicional[]" class="form-control" multiple>
        <?php
        $servicios_seleccionados = !empty($operacion['servicio_adicional'])
    ? explode(', ', $operacion['servicio_adicional'])
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
            $selected = in_array($op, $servicios_seleccionados) ? 'selected' : '';
            echo "<option value=\"$op\" $selected>$op</option>";
        }
        ?>
    </select>
    <small class="text-muted">Usa CTRL o CMD para seleccionar varios</small>
</div>

                <div class="col-md-6">
                    <label>Encargado</label>
                    <input type="text" name="Encargado" class="form-control" value="<?= htmlspecialchars($operacion['Encargado']) ?>">
                </div>

                <div class="col-12">
                    <label>Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="3"><?= htmlspecialchars($operacion['observaciones']) ?></textarea>
                </div>
            </div>

            <hr class="my-4">

            <!-- DATOS CONTABLES -->
            <h5 class="text-secondary mb-3">💰 Datos Contables</h5>
            <div class="row g-3">
                <div class="col-md-4">
                    <label>Método de Pago</label>
                    <select name="metodo_pago" class="form-select">
                        <?php 
                        $metodos = ['Efectivo','We travel','Izipay','PAYPAL','Bcp'];
                        foreach ($metodos as $m) {
                            $sel = ($operacion['metodo_pago'] == $m) ? 'selected' : '';
                            echo "<option value='$m' $sel>$m</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label>Moneda</label>
                    <select name="tipo_moneda" class="form-select">
                        <option value="Soles" <?= ($operacion['tipo_moneda'] == 'Soles') ? 'selected' : '' ?>>Soles (PEN)</option>
                        <option value="Dólares" <?= ($operacion['tipo_moneda'] == 'Dólares') ? 'selected' : '' ?>>Dólares (USD)</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label>Precio del Servicio</label>
                    <input type="number" step="0.01" name="precio_servicio" id="precio_servicio" class="form-control"
                        value="<?= $operacion['precio_servicio'] ?>">
                </div>
                <div class="col-md-4">
                    <label>Pagado a Cuenta</label>
                    <input type="number" step="0.01" name="pagado_a_cuenta" id="pagado_a_cuenta" class="form-control"
                        value="<?= $operacion['pagado_a_cuenta'] ?>">
                </div>
                <div class="col-md-4">
                    <label>Saldo Pendiente</label>
                    <input type="number" step="0.01" name="saldo_pendiente" id="saldo_pendiente" class="form-control"
                        value="<?= $operacion['saldo_pendiente'] ?>" readonly>
                </div>
                <div class="col-md-4">
                    <label>Fecha Pago del Saldo</label>
                    <input type="date" name="fecha_pago_saldo" class="form-control"
                        value="<?= $operacion['fecha_pago_saldo'] ?>">
                </div>
                <div class="col-md-4">
                    <label>Comisión</label>
                    <input type="text" name="comision" class="form-control" value="<?= htmlspecialchars($operacion['comision']) ?>">
                </div>
            </div>
            <hr>
<h5 class="text-secondary">🎟 Ingreso / Servicio Adicional</h5>

<div class="row g-3">

  <div class="col-md-4">
    <label>Método de Pago (Ingreso)</label>
    <select name="metodo_pago_adicional" class="form-select">
      <option value="">-- No aplica --</option>
      <?php
      $metodos = ['Efectivo','We travel','Izipay','PAYPAL','Bcp','CULQI'];
      foreach ($metodos as $m) {
        $sel = ($operacion['metodo_pago_adicional'] == $m) ? 'selected' : '';
        echo "<option value='$m' $sel>$m</option>";
      }
      ?>
    </select>
  </div>

  <div class="col-md-4">
    <label>Moneda (Ingreso)</label>
    <select name="tipo_moneda_adicional" class="form-select">
      <option value="">-- No aplica --</option>
      <option value="Soles" <?= ($operacion['tipo_moneda_adicional']=='Soles')?'selected':'' ?>>Soles</option>
      <option value="Dólares" <?= ($operacion['tipo_moneda_adicional']=='Dólares')?'selected':'' ?>>Dólares</option>
    </select>
  </div>

  <div class="col-md-4">
    <label>Precio Adicional</label>
    <input type="number" step="0.01"
           name="precio_servicio_adicional"
           class="form-control precio_adicional"
           value="<?= $operacion['precio_servicio_adicional'] ?>">
  </div>

  <div class="col-md-4">
    <label>Pagado Adicional</label>
    <input type="number" step="0.01"
           name="pagado_adicional"
           class="form-control pagado_adicional"
           value="<?= $operacion['pagado_adicional'] ?>">
  </div>

  <div class="col-md-4">
    <label>Saldo Adicional</label>
    <input type="number" step="0.01"
           name="saldo_adicional"
           class="form-control saldo_adicional"
           value="<?= $operacion['saldo_adicional'] ?>"
           readonly>
  </div>

</div>

            <div class="mt-4">
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">💾 Guardar Cambios</button>
            </div>
              <?php if ($operacion['saldo_pendiente'] > 0): ?>
<hr class="my-4">
<h5 class="text-danger mb-3">💳 Pago de Saldo Pendiente</h5>

<div class="row g-3">
    <div class="col-md-4">
        <label>Monto a Pagar</label>
        <input type="number" step="0.01" name="pago_saldo"
               class="form-control"
               max="<?= $operacion['saldo_pendiente'] ?>">
    </div>

    <div class="col-md-4">
        <label>Método de Pago (Saldo)</label>
        <select name="metodo_pago_saldo" class="form-select">
            <option value="">-- Seleccione --</option>
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
        <label>Moneda (Saldo)</label>
        <select name="tipo_moneda_saldo" class="form-select">
            <option value="">-- Seleccione --</option>
            <option value="Soles">Soles</option>
            <option value="Dólares">Dólares</option>
        </select>
    </div>
</div>
<?php endif; ?>
        </form>
                                <?php    
// ==========================
// 🔎 OBTENER TOURS DEL CLIENTE
// ==========================
$id_cliente = $operacion['id_cliente'];

$tours_query = "
    SELECT id_operaciones, nombre_servicio, fecha_salida, fecha_retorno 
    FROM Operaciones 
    WHERE id_cliente = $id_cliente
    ORDER BY fecha_salida DESC
";

$tours_res = mysqli_query($conexion, $tours_query);
?>

<hr class="my-5">

<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="text-primary">🧭 Tours del Cliente</h4>

    <!-- Botón para agregar nuevo tour -->
    <a href="agregar.php?id_cliente=<?= $id_cliente ?>" 
       class="btn btn-success">
        ➕ Agregar Tour
    </a>
</div>

<?php if (mysqli_num_rows($tours_res) > 0): ?>
<table class="table table-bordered table-striped">
    <thead class="table-dark">
        <tr>
            <th>ID</th>
            <th>Servicio</th>
            <th>Fecha Salida</th>
            <th>Fecha Retorno</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php while($t = mysqli_fetch_assoc($tours_res)): ?>
        <tr>
            <td><?= $t['id_operaciones'] ?></td>
            <td><?= $t['nombre_servicio'] ?></td>
            <td><?= $t['fecha_salida'] ?></td>
            <td><?= $t['fecha_retorno'] ?></td>
            <td>
                <a href="editar.php?id=<?= $t['id_operaciones'] ?>" class="btn btn-sm btn-warning">✏️ Editar</a>
                <a href="eliminar.php?id=<?= $t['id_operaciones'] ?>" 
                   class="btn btn-sm btn-danger"
                   onclick="return confirm('¿Eliminar este tour?')">
                    🗑️ Eliminar
                </a>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<?php else: ?>

<div class="alert alert-info">
    ⚠️ Este cliente aún no tiene tours registrados.
</div>

<?php endif; ?>

    </div>
    </div>
</div>

<script>
document.addEventListener("input", function(e) {

    // 🟦 Servicio principal
    if (
        e.target.id === "precio_servicio" ||
        e.target.id === "pagado_a_cuenta"
    ) {
        const precio = parseFloat(precio_servicio.value) || 0;
        const pagado = parseFloat(pagado_a_cuenta.value) || 0;
        saldo_pendiente.value = (precio - pagado).toFixed(2);
    }

    // 🟩 Servicio adicional
    if (
        e.target.classList.contains("precio_adicional") ||
        e.target.classList.contains("pagado_adicional")
    ) {
        const bloque = e.target.closest("form");

        const precio = parseFloat(
            bloque.querySelector(".precio_adicional").value
        ) || 0;

        const pagado = parseFloat(
            bloque.querySelector(".pagado_adicional").value
        ) || 0;

        bloque.querySelector(".saldo_adicional").value =
            (precio - pagado).toFixed(2);
    }
});
</script>
<script>
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

// ================== CALCULAR FECHA RETORNO ==================
function normalizarServicio(nombre) {
    return nombre
        .replace(/\(PRIVADO\)/gi, '')
        .replace(/\s+/g, ' ')
        .trim();
}

function calcularFechaRetorno() {
    const servicioRaw = document.querySelector('[name="nombre_servicio"]').value;
    const salida = document.querySelector('[name="fecha_salida"]').value;
    const retorno = document.querySelector('[name="fecha_retorno"]');

    if (!servicioRaw || !salida) return;

    const servicio = normalizarServicio(servicioRaw);

    if (!DURACION_TOURS[servicio]) return;

    const dias = DURACION_TOURS[servicio];
    const fecha = new Date(salida);
    fecha.setDate(fecha.getDate() + (dias - 1));

    retorno.value = fecha.toISOString().split('T')[0];
}

document.querySelector('[name="nombre_servicio"]').addEventListener('change', calcularFechaRetorno);
document.querySelector('[name="fecha_salida"]').addEventListener('change', calcularFechaRetorno);
</script>
</body>
</html>
