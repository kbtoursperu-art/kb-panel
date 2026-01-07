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
    c.comision
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
    $incluye_ingreso = isset($_POST['incluye_ingreso']) ? 'Sí' : 'No';
    $servicio_adicional = isset($_POST['servicio_adicional']) ? implode(', ', $_POST['servicio_adicional']) : 'Ninguna';
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
        comision = '$comision'
    WHERE id_operaciones = $id";
    mysqli_query($conexion, $updateCont);

    echo "<script>
        alert('✅ Operación actualizada correctamente');
        window.location.href = 'index.php';
    </script>";
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
                        "SALKANTAY A MACHU PICCHU 5 DÍAS (PRIVADO)",
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
                            <?= ($operacion['incluye_ingreso'] == 'Sí') ? 'checked' : '' ?>>
                        <label for="incluyeIngreso" class="form-check-label">¿Incluye Ingreso?</label>
                    </div>
                </div>

              <div class="col-md-6">
    <label>Servicio Adicional</label>
    <select name="servicio_adicional[][]" class="form-control" multiple>
        <?php
        $servicios_seleccionados = $servicios_seleccionados ?? [];
        $opciones = [
            "Ninguna",
            "Ingreso a Mollepata",
            "Desayuno en Mollepata",
            "Bolsa de Dormir",
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
                    <label>Empresa</label>
                    <input type="text" name="empresa" class="form-control" value="<?= htmlspecialchars($operacion['empresa']) ?>">
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
                        $metodos = ['Efectivo','We travel','Izipay','PAYPAL','Bcp','Otro'];
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
                        <option value="PEN" <?= ($operacion['tipo_moneda'] == 'PEN') ? 'selected' : '' ?>>Soles (PEN)</option>
                        <option value="USD" <?= ($operacion['tipo_moneda'] == 'USD') ? 'selected' : '' ?>>Dólares (USD)</option>
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

            <div class="mt-4">
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" class="btn btn-primary">💾 Guardar Cambios</button>
            </div>
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
const precio = document.getElementById('precio_servicio');
const pagado = document.getElementById('pagado_a_cuenta');
const saldo = document.getElementById('saldo_pendiente');

function calcularSaldo() {
    const p = parseFloat(precio.value) || 0;
    const a = parseFloat(pagado.value) || 0;
    saldo.value = (p - a).toFixed(2);
}

precio.addEventListener('input', calcularSaldo);
pagado.addEventListener('input', calcularSaldo);
</script>
<?php include '../../footer.php'; ?>
</body>
</html>
