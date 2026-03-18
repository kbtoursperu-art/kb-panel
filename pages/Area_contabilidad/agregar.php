<?php
include('../../conexion.php');

$id_cliente = isset($_GET['id_cliente']) ? (int)$_GET['id_cliente'] : 0;

if ($id_cliente == 0) {
    die("Cliente no válido.");
}

// Obtener datos del cliente y la operación asociada
$query = "

SELECT 

d.id_cliente,
CONCAT(d.nombre,' ',d.apellido) AS cliente_nombre,
d.nro_pasaporte,

o.id_operaciones,
o.nombre_servicio,

g.nombre_grupo,

c.metodo_pago,
c.tipo_moneda

FROM datos_clientes d

LEFT JOIN operaciones o 
    ON d.id_cliente = o.id_cliente

LEFT JOIN clientes_kb kb 
    ON d.id_cliente = kb.id_cliente

LEFT JOIN grupos g 
    ON kb.id_grupo = g.id_grupo

LEFT JOIN contabilidad c 
    ON o.id_operaciones = c.id_operaciones

WHERE d.id_cliente = ?

LIMIT 1

";
$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "i", $id_cliente);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$cliente = mysqli_fetch_assoc($result);

if (!$cliente) {
    die("Cliente no encontrado.");
}

// Procesar formulario al enviarlo
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_operaciones = $_POST['id_operaciones'];
    $metodo_pago = $_POST['metodo_pago'];
    $modalidad_pago = $_POST['modalidad_pago'];
    $comision = $_POST['comision'];
    $precio_servicio = $_POST['precio_servicio'];
    $pagado_a_cuenta = $_POST['pagado_a_cuenta'];
    $saldo_pendiente = $_POST['saldo_pendiente'];
    $fecha_pago_saldo = $_POST['fecha_pago_saldo'];
    $estado = $_POST['estado'];
    $modalidad_recibo = $_POST['modalidad_recibo'];
    $nro_boleta_cuenta = $_POST['nro_boleta_cuenta'];
    $nro_boleta_total = $_POST['nro_boleta_total'];
    $detraccion = $_POST['detraccion'];
    $igv = $_POST['igv'];

    $insert_query = "INSERT INTO contabilidad (id_operaciones, metodo_pago, modalidad_pago, comision, precio_servicio, pagado_a_cuenta, saldo_pendiente, fecha_pago_saldo, estado, modalidad_recibo, nro_boleta_cuenta, nro_boleta_total, detraccion, igv) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,?,?,?)";
    $stmt_insert = mysqli_prepare($conexion, $insert_query);
    mysqli_stmt_bind_param($stmt_insert, "isssssissssiii", $id_operaciones, $metodo_pago, $modalidad_pago, $comision, $precio_servicio, $pagado_a_cuenta, $saldo_pendiente, $fecha_pago_saldo, $estado, $modalidad_recibo, $nro_boleta_cuenta, $nro_boleta_total, $detraccion, $igv);
    
    if (mysqli_stmt_execute($stmt_insert)) {
        header("Location: index.php?success=1");
        exit();
    } else {
        echo "Error al guardar los datos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Contabilidad</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script>
        function calcularSaldo() {
            let precio = parseFloat(document.getElementById("precio_servicio").value) || 0;
            let pagado = parseFloat(document.getElementById("pagado_a_cuenta").value) || 0;
            let saldo = precio - pagado;
            document.getElementById("saldo_pendiente").value = saldo.toFixed(2);
        }
    </script>
</head>
<body>
    <div class="container mt-4">
        <h2>Agregar Contabilidad</h2>
        <form method="POST">
            <input type="hidden" name="id_operaciones" value="<?= htmlspecialchars($cliente['id_operaciones']) ?>">

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Cliente:</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($cliente['cliente_nombre']) ?>" disabled>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Nro. Pasaporte:</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($cliente['nro_pasaporte']) ?>" disabled>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Servicio:</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($cliente['nombre_servicio']) ?>" disabled>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Método de Pago:</label>
                    <select class="form-control" name="metodo_pago" id="metodo_pago_select">
                        <option value="Efectivo">Efectivo</option>
                        <option value="We travel">We travel</option>
                        <option value="Izipay">Izipay</option>
                        <option value="PAYPAL">PAYPAL</option>
                        <option value="Bcp">Bcp</option>
                        <option value="otro">Otro (Especificar)</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Modalidad de pago:</label>
                    <select class="form-control" name="modalidad_pago" id="modalidad_pago_select">
                        <option value="Dolares">Dolares</option>
                        <option value="Soles">Soles</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Comision:</label>
                    <input type="number" step="0.01" name="comision" id="comision" class="form-control" required >
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Precio Servicio:</label>
                    <input type="number" step="0.01" name="precio_servicio" id="precio_servicio" class="form-control" required oninput="calcularSaldo()">
            

                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Pagado a Cuenta:</label>
                    <input type="number" step="0.01" name="pagado_a_cuenta" id="pagado_a_cuenta" class="form-control" oninput="calcularSaldo()">
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Saldo Pendiente:</label>
                    <input type="number" step="0.01" name="saldo_pendiente" id="saldo_pendiente" class="form-control" readonly>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Fecha Pago Saldo:</label>
                    <input type="date" name="fecha_pago_saldo" class="form-control">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Modalidad Recibo:</label>
                    <select class="form-control" name="modalidad_recibo" id="modalidad_recibo_select">
                        <option value="">__</option>
                        <option value="Factura">Factura</option>
                        <option value="Boleta">Boleta</option>
                        <option value="otro">Otro (Especificar)</option>
                    </select>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Nro Boleta Total:</label>
                    <input type="text" name="nro_boleta_total" class="form-control">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Detracción:</label>
                    <input type="number" step="0.01" name="detraccion" class="form-control">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">IGV:</label>
                    <input type="number" step="0.01" name="igv" class="form-control">
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-primary">Guardar</button>
                <a href="listado.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</body>
</html>
