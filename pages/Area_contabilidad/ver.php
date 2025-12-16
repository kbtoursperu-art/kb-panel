<?php
include '../../conexion.php';
include '../sidebar.php';

// 🟢 Verificar si se pasó un ID de contabilidad
if (!isset($_GET['id'])) {
    echo "<script>alert('⚠️ No se especificó el registro contable.'); window.location.href='index.php';</script>";
    exit;
}

$id_conta = intval($_GET['id']);

// 🔍 Consulta detallada de contabilidad, operación y cliente
$query = "
SELECT 
    c.id_contabilidad,
    o.id_operaciones,
    CONCAT(d.nombre, ' ', d.apellido) AS cliente,
    d.nro_pasaporte,
    d.tipo_cliente,
    o.nombre_servicio,
    o.fecha_reserva,
    o.fecha_salida,
    o.fecha_retorno,
    o.servicio_adicional,
    o.observaciones,
    c.metodo_pago,
    c.tipo_moneda,
    c.precio_servicio,
    c.comision,
    c.pagado_a_cuenta,
    c.saldo_pendiente,
    c.fecha_pago_saldo,
    c.estado,
    c.modalidad_recibo AS tipo_comprobante_pago,
    c.nro_boleta_cuenta,
    c.nro_boleta_total,
    c.Nro_Comprobante_adicional,
    c.detraccion,
    c.NotaCredito
FROM Contabilidad c
LEFT JOIN Operaciones o ON c.id_operaciones = o.id_operaciones
LEFT JOIN Datos_clientes d ON o.id_cliente = d.id_cliente
WHERE c.id_contabilidad = $id_conta
";

$resultado = mysqli_query($conexion, $query);

if (!$resultado || mysqli_num_rows($resultado) == 0) {
    echo "<script>alert('❌ No se encontró el registro contable.'); window.location.href='index.php';</script>";
    exit;
}

$datos = mysqli_fetch_assoc($resultado);
?>

<!-- 🔹 CONTENIDO PRINCIPAL -->
<div class="container mt-4 mb-5">
    <div class="card shadow-lg border-0">
        <div class="card-header bg-success text-white">
            <h4 class="mb-0">💰 Detalle de Registro Contable</h4>
        </div>
        <div class="card-body">
            <div class="row g-3">

                <!-- Información del Cliente -->
                <div class="col-md-12">
                    <h5 class="text-secondary">👤 Información del Cliente</h5>
                    <hr>
                </div>
                <div class="col-md-5">
                    <label class="form-label fw-bold">Cliente:</label>
                    <p><?= htmlspecialchars($datos['cliente']) ?></p>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Pasaporte:</label>
                    <p><?= htmlspecialchars($datos['nro_pasaporte'] ?? '—') ?></p>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Tipo Cliente:</label>
                    <p><?= htmlspecialchars($datos['tipo_cliente'] ?? '—') ?></p>
                </div>

                <!-- Información del Servicio -->
                <div class="col-md-12 mt-3">
                    <h5 class="text-secondary">🧭 Detalles del Servicio</h5>
                    <hr>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Servicio:</label>
                    <p><?= htmlspecialchars($datos['nombre_servicio'] ?? '—') ?></p>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Reserva:</label>
                    <p><?= htmlspecialchars($datos['fecha_reserva'] ?? '—') ?></p>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Salida:</label>
                    <p><?= htmlspecialchars($datos['fecha_salida'] ?? '—') ?></p>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Retorno:</label>
                    <p><?= htmlspecialchars($datos['fecha_retorno'] ?? '—') ?></p>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Servicio Adicional:</label>
                    <p><?= htmlspecialchars($datos['servicio_adicional'] ?? '—') ?></p>
                </div>

                <!-- Información Económica -->
                <div class="col-md-12 mt-3">
                    <h5 class="text-secondary">💵 Información de Pago</h5>
                    <hr>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Método de Pago:</label>
                    <p><?= htmlspecialchars($datos['metodo_pago'] ?? '—') ?></p>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Moneda:</label>
                    <p><?= htmlspecialchars($datos['tipo_moneda'] ?? '—') ?></p>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Precio Total:</label>
                    <p><?= number_format($datos['precio_servicio'] ?? 0, 2) ?></p>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Comisión:</label>
                    <p><?= number_format($datos['comision'] ?? 0, 2) ?></p>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Detracción:</label>
                    <p><?= number_format($datos['detraccion'] ?? 0, 2) ?></p>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold">Pagado a Cuenta:</label>
                    <p><?= number_format($datos['pagado_a_cuenta'] ?? 0, 2) ?></p>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Saldo Pendiente:</label>
                    <p><?= number_format($datos['saldo_pendiente'] ?? 0, 2) ?></p>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Fecha Pago Saldo:</label>
                    <p><?= htmlspecialchars($datos['fecha_pago_saldo'] ?? '—') ?></p>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Estado:</label>
                    <?php
                    $estado = htmlspecialchars($datos['estado'] ?? '—');
                    if ($estado === 'pagado') echo "<p><span class='badge bg-success'>Pagado</span></p>";
                    elseif ($estado === 'pendiente') echo "<p><span class='badge bg-warning text-dark'>Pendiente</span></p>";
                    elseif ($estado === 'reembolsado') echo "<p><span class='badge bg-secondary'>Reembolsado</span></p>";
                    else echo "<p>{$estado}</p>";
                    ?>
                </div>

                <!-- Información del Comprobante -->
                <div class="col-md-12 mt-3">
                    <h5 class="text-secondary">📄 Detalles del Comprobante</h5>
                    <hr>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Tipo de Comprobante:</label>
                    <p><?= htmlspecialchars($datos['tipo_comprobante_pago'] ?? '—') ?></p>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">N° Comprobante Cuenta:</label>
                    <p><?= htmlspecialchars($datos['nro_boleta_cuenta'] ?? '—') ?></p>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">N° Comprobante Total:</label>
                    <p><?= htmlspecialchars($datos['nro_boleta_total'] ?? '—') ?></p>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">N° Comprobante Adicional:</label>
                    <p><?= htmlspecialchars($datos['Nro_Comprobante_adicional'] ?? '—') ?></p>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Nota de Crédito:</label>
                    <p><?= ($datos['NotaCredito'] == 1) ? 'Sí' : 'No' ?></p>
                </div>

                <!-- Observaciones -->
                <div class="col-md-12 mt-3">
                    <h5 class="text-secondary">📝 Observaciones</h5>
                    <hr>
                    <p><?= nl2br(htmlspecialchars($datos['observaciones'] ?? '—')) ?></p>
                </div>

                <!-- Botón Volver -->
                <div class="col-md-12 mt-4 text-center">
                    <a href="index.php" class="btn btn-secondary">⬅️ Volver</a>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>
