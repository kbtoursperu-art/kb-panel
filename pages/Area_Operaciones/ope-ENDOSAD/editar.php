<?php
include '../../../conexion.php';
include '../../sidebar.php';

// =======================
// 🔹 OBTENER DATOS EXISTENTES
// =======================
$id_operaciones = $_GET['id'] ?? null;

if (!$id_operaciones) {
    echo "<script>alert('ID de operación no proporcionado'); window.location='index.php';</script>";
    exit;
}

$query = "
SELECT 
    o.*, 
    CONCAT(d.nombre, ' ', d.apellido) AS cliente_nombre,

    c.metodo_pago,
    c.tipo_moneda,
    c.precio_servicio,
    c.pagado_a_cuenta,
    c.saldo_pendiente,

    c.precio_servicio_adicional,
    c.pagado_adicional,
    c.saldo_adicional,
    c.tipo_moneda_adicional,

    c.metodo_pago_saldo,
    c.tipo_moneda_saldo,
    c.monto_pago_saldo,
    c.fecha_pago_saldo

FROM Operaciones o
INNER JOIN Datos_clientes d ON o.id_cliente = d.id_cliente
LEFT JOIN Contabilidad c ON o.id_operaciones = c.id_operaciones
WHERE o.id_operaciones = $id_operaciones
";

$resultado = mysqli_query($conexion, $query);
if (!$resultado || mysqli_num_rows($resultado) === 0) {
    echo "<script>alert('Operación no encontrada'); window.location='index.php';</script>";
    exit;
}
$operacion = mysqli_fetch_assoc($resultado);
// 🔹 ID DEL CLIENTE
$id_cliente = $operacion['id_cliente'];

// 🔹 OBTENER TOURS DEL CLIENTE
$tours_sql = "
    SELECT id_operaciones, nombre_servicio, fecha_salida, fecha_retorno
    FROM Operaciones
    WHERE id_cliente = $id_cliente
    ORDER BY fecha_salida DESC
";
$tours_res = mysqli_query($conexion, $tours_sql);



// Decodificar servicios adicionales múltiples
$servicios_seleccionados = explode(', ', $operacion['servicio_adicional']);

// =======================
// 🔹 GUARDAR CAMBIOS
// =======================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // ==========================
    // 1️⃣ DATOS PRINCIPALES
    // ==========================
    $nombre_servicio = $_POST['nombre_servicio'];
    $fecha_reserva = $_POST['fecha_reserva'];
    $fecha_salida = $_POST['fecha_salida'];
    $fecha_retorno = $_POST['fecha_retorno'];
    $incluye_ingreso = isset($_POST['incluye_ingreso']) ? 'Con ingreso' : 'Sin ingreso';
    $modalidad_retorno = $_POST['modalidad_retorno'];
    $servicio_adicional = implode(', ', $_POST['servicio_adicional']);
    $observaciones = $_POST['observaciones'];
    $encargado = $_POST['encargado'];

    $metodo_pago = $_POST['metodo_pago'];
    $tipo_moneda = $_POST['tipo_moneda'];
    $precio_servicio = floatval($_POST['precio_servicio']);
    $pagado_a_cuenta = floatval($_POST['pagado_a_cuenta']);
    $saldo_pendiente = $precio_servicio - $pagado_a_cuenta;

    if ($saldo_pendiente < 0) $saldo_pendiente = 0;

    // ==========================
    // 2️⃣ ACTUALIZAR OPERACIONES
    // ==========================
    mysqli_query($conexion, "
        UPDATE Operaciones SET
            nombre_servicio='$nombre_servicio',
            fecha_reserva='$fecha_reserva',
            fecha_salida='$fecha_salida',
            fecha_retorno='$fecha_retorno',
            incluye_ingreso='$incluye_ingreso',
            modalidad_retorno='$modalidad_retorno',
            servicio_adicional='$servicio_adicional',
            observaciones='$observaciones',
            Encargado='$encargado'
        WHERE id_operaciones=$id_operaciones
    ");

    // ==========================
    // 3️⃣ ACTUALIZAR CONTABILIDAD
    // ==========================
    mysqli_query($conexion, "
        UPDATE Contabilidad SET
            metodo_pago='$metodo_pago',
            tipo_moneda='$tipo_moneda',
            precio_servicio='$precio_servicio',
            pagado_a_cuenta='$pagado_a_cuenta',
            saldo_pendiente='$saldo_pendiente'
        WHERE id_operaciones=$id_operaciones
    ");

    // ==========================
    // 4️⃣ PAGO DE SALDO (SI EXISTE)
    // ==========================
    if (!empty($_POST['monto_pago_saldo'])) {

        $monto_pago = floatval($_POST['monto_pago_saldo']);
        $nuevo_saldo = $saldo_pendiente - $monto_pago;

        if ($nuevo_saldo < 0) $nuevo_saldo = 0;

        $metodo_pago_saldo = $_POST['metodo_pago_saldo'];
        $tipo_moneda_saldo = $_POST['tipo_moneda_saldo'];
        $fecha_pago_saldo  = $_POST['fecha_pago_saldo'];

        $precio_adicional = floatval($_POST['precio_servicio_adicional'] ?? 0);
$pagado_adicional = floatval($_POST['pagado_adicional'] ?? 0);
$saldo_adicional  = max(0, $precio_adicional - $pagado_adicional);
$tipo_moneda_adicional = $_POST['tipo_moneda_adicional'] ?? null;


        mysqli_query($conexion, "
            UPDATE Contabilidad SET
                monto_pago_saldo = '$monto_pago',
                metodo_pago_saldo = '$metodo_pago_saldo',
                tipo_moneda_saldo = '$tipo_moneda_saldo',
                fecha_pago_saldo = '$fecha_pago_saldo',
                saldo_pendiente = '$nuevo_saldo',
                estado = IF($nuevo_saldo = 0, 'pagado', 'pendiente')
            WHERE id_operaciones = $id_operaciones
        ");
    }

    echo "<script>alert('✅ Operación actualizada correctamente'); window.location='index.php';</script>";
    exit;
}

?>
<br>
<br>
<br>
<div class="container mt-4">
    <h3 class="text-primary mb-3">✏️ Editar Operación - Endosador</h3>
    <form method="POST">
        <div class="row mb-3">
            <div class="col-md-6">
                <label>Cliente Endosador</label>
                <input type="text" class="form-control" 
                       value="<?= htmlspecialchars($operacion['cliente_nombre']) ?>" readonly>
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
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <label>Fecha Reserva</label>
                <input type="date" name="fecha_reserva" class="form-control" 
                       value="<?= $operacion['fecha_reserva'] ?>" required>
            </div>
            <div class="col-md-4">
                <label>Fecha Salida</label>
                <input type="date" name="fecha_salida" class="form-control" 
                       value="<?= $operacion['fecha_salida'] ?>" required>
            </div>
            <div class="col-md-4">
                <label>Fecha Retorno</label>
                <input type="date" name="fecha_retorno" class="form-control" 
                       value="<?= $operacion['fecha_retorno'] ?>">
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <label>Incluye Ingreso</label>
                <div class="form-check">
                    <input type="checkbox" name="incluye_ingreso" value="Sí" 
                           class="form-check-input" id="incluye_ingreso"
                           <?= ($operacion['incluye_ingreso'] === 'Con ingreso') ? 'checked' : '' ?>
                    <label class="form-check-label" for="incluye_ingreso">Sí</label>
                </div>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Modalidad Retorno:</label>
                <select name="modalidad_retorno" class="form-control" required>
                    <option value="">-- Seleccione una opción --</option>
                    <option value="Tren" <?= $operacion['modalidad_retorno'] == 'Tren' ? 'selected' : '' ?>>Con Tren</option>
                    <option value="Carro" <?= $operacion['modalidad_retorno'] == 'Carro' ? 'selected' : '' ?>>Con Carro</option>
                    <option value="Sin retorno" <?= $operacion['modalidad_retorno'] == 'Sin retorno' ? 'selected' : '' ?>>Sin Retorno</option>
                </select>
            </div>

            <div class="col-md-6 mb-3">
                <label class="form-label">Servicio Adicional</label>
                <select name="servicio_adicional[]" class="form-control" multiple required>
                    <?php
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
                        echo "<option value='$op' $selected>$op</option>";
                    }
                    ?>
                </select>
                <small class="text-muted">Usa CTRL o CMD para seleccionar varios</small>
            </div>
        </div>

        <div class="mb-3">
            <label>Observaciones</label>
            <textarea name="observaciones" class="form-control"><?= htmlspecialchars($operacion['observaciones']) ?></textarea>
        </div>

        <div class="row mb-3">
            <div class="col-md-6">
                <label>Encargado</label>
                <input type="text" name="encargado" class="form-control" value="<?= htmlspecialchars($operacion['Encargado']) ?>">
            </div>
            <div class="col-md-6">
                <label>Método de Pago</label>
                <select name="metodo_pago" class="form-control">
                    <option value="Efectivo" <?= $operacion['metodo_pago'] == 'Efectivo' ? 'selected' : '' ?>>Efectivo</option>
                    <option value="We travel" <?= $operacion['metodo_pago'] == 'We travel' ? 'selected' : '' ?>>We travel</option>
                    <option value="Izipay" <?= $operacion['metodo_pago'] == 'Izipay' ? 'selected' : '' ?>>Izipay</option>
                    <option value="PAYPAL" <?= $operacion['metodo_pago'] == 'PAYPAL' ? 'selected' : '' ?>>PAYPAL</option>
                    <option value="Bcp" <?= $operacion['metodo_pago'] == 'Bcp' ? 'selected' : '' ?>>BCP</option>
                    <option value="CULQI" <?= $operacion['metodo_pago'] == 'CULQI' ? 'selected' : '' ?>>CULQI</option>
                </select>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-3">
                <label>Tipo de Moneda</label>
                <select name="tipo_moneda" class="form-control">
                    <option value="PEN" <?= $operacion['tipo_moneda'] == 'PEN' ? 'selected' : '' ?>>Soles (PEN)</option>
                    <option value="USD" <?= $operacion['tipo_moneda'] == 'USD' ? 'selected' : '' ?>>Dólares (USD)</option>
                </select>
            </div>
            <div class="col-md-3">
                <label>Precio Servicio</label>
                <input type="number" step="0.01" name="precio_servicio" class="form-control" value="<?= $operacion['precio_servicio'] ?>" required>
            </div>
            <div class="col-md-3">
                <label>Pagado a Cuenta</label>
                <input type="number" step="0.01" name="pagado_a_cuenta" class="form-control" value="<?= $operacion['pagado_a_cuenta'] ?>">
            </div>
            <div class="col-md-3">
                <label>Saldo Pendiente</label>
                <input type="number" step="0.01" name="saldo_pendiente" class="form-control" value="<?= $operacion['saldo_pendiente'] ?>" readonly>
            </div>
        </div>
        <hr>
<h5 class="text-secondary">💰 Servicio Adicional</h5>

<div class="row mb-3">
    <div class="col-md-3">
        <label>Precio Adicional</label>
        <input type="number" step="0.01" class="form-control"
               value="<?= $operacion['precio_servicio_adicional'] ?>" >
    </div>

    <div class="col-md-3">
        <label>Pagado Adicional</label>
        <input type="number" step="0.01" class="form-control"
               value="<?= $operacion['pagado_adicional'] ?>" >
    </div>

    <div class="col-md-3">
        <label>Saldo Adicional</label>
        <input type="number" step="0.01" class="form-control"
               value="<?= $operacion['saldo_adicional'] ?>" >
    </div>

      <div class="col-md-4">
    <label>Moneda (Ingreso)</label>
    <select name="tipo_moneda_adicional" class="form-select">
      <option value="">-- No aplica --</option>
      <option value="Soles" <?= ($operacion['tipo_moneda_adicional']=='Soles')?'selected':'' ?>>Soles</option>
      <option value="Dólares" <?= ($operacion['tipo_moneda_adicional']=='Dólares')?'selected':'' ?>>Dólares</option>
    </select>
  </div>
</div>


        <button type="submit" class="btn btn-success">💾 Actualizar</button>
        <a href="index.php" class="btn btn-secondary">↩ Volver</a>
        <hr class="my-5">
<?php if ($operacion['saldo_pendiente'] > 0): ?>
<hr>
<h5 class="text-danger">💳 Completar Pago de Saldo</h5>

<div class="row mb-3">
    <div class="col-md-3">
        <label>Método de Pago</label>
        <select name="metodo_pago_saldo" class="form-control">
            <option value="">-- Seleccione --</option>
            <option value="Efectivo">Efectivo</option>
            <option value="We travel">We travel</option>
            <option value="Izipay">Izipay</option>
            <option value="PAYPAL">PAYPAL</option>
            <option value="Bcp">BCP</option>
            <option value="CULQI">CULQI</option>
        </select>
    </div>

    <div class="col-md-3">
        <label>Moneda</label>
        <select name="tipo_moneda_saldo" class="form-control">
            <option value="Soles">Soles</option>
            <option value="Dólares">Dólares</option>
        </select>
    </div>

    <div class="col-md-3">
        <label>Monto Pagado</label>
        <input type="number" step="0.01"
               name="monto_pago_saldo"
               class="form-control">
    </div>

    <div class="col-md-3">
        <label>Fecha de Pago</label>
        <input type="date"
               name="fecha_pago_saldo"
               class="form-control"
               value="<?= date('Y-m-d') ?>">
    </div>
</div>
<?php endif; ?>


<div class="d-flex justify-content-between align-items-center mb-3">
<h4 class="text-primary">🧭 Tours del Cliente</h4>
<a href="agregar.php?id_cliente=<?= $id_cliente ?>" class="btn btn-success">➕ Agregar Tour</a>
</div>


<?php if (mysqli_num_rows($tours_res) > 0): ?>
<table class="table table-bordered table-striped">
<thead class="table-dark">
<tr>
<th>ID</th>
<th>Servicio</th>
<th>Salida</th>
<th>Retorno</th>
<th>Acciones</th>
</tr>
</thead>
<tbody>
<?php while($t = mysqli_fetch_assoc($tours_res)): ?>
<tr>
<td><?= $t['id_operaciones'] ?></td>
<td><?= htmlspecialchars($t['nombre_servicio']) ?></td>
<td><?= $t['fecha_salida'] ?></td>
<td><?= $t['fecha_retorno'] ?></td>
<td>
<a href="editar.php?id=<?= $t['id_operaciones'] ?>" class="btn btn-sm btn-warning">✏️ Editar</a>
<a href="eliminar.php?id=<?= $t['id_operaciones'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este tour?')">🗑 Eliminar</a>
</td>
</tr>
<?php endwhile; ?>
</tbody>
</table>
<?php else: ?>
<div class="alert alert-info">Este cliente aún no tiene tours registrados.</div>
<?php endif; ?>
    </form>
    
</div>

<script>
document.querySelector('[name="pagado_a_cuenta"]').addEventListener('input', calcularSaldo);
document.querySelector('[name="precio_servicio"]').addEventListener('input', calcularSaldo);

function calcularSaldo() {
    const precio = parseFloat(document.querySelector('[name="precio_servicio"]').value) || 0;
    const pagado = parseFloat(document.querySelector('[name="pagado_a_cuenta"]').value) || 0;
    document.querySelector('[name="saldo_pendiente"]').value = (precio - pagado).toFixed(2);
}
</script>
