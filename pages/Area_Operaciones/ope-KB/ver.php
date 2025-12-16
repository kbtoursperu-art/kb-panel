<?php
include '../../../conexion.php';

// 🟢 Verificar si se pasó un ID de operación
if (!isset($_GET['id'])) {
    echo "<script>alert('⚠️ No se especificó la operación.'); window.location.href='index.php';</script>";
    exit;
}

$id_operacion = intval($_GET['id']);

// 🔍 Consulta detallada de la operación, cliente y contabilidad
$query = "
SELECT 
    o.id_operaciones,
    CONCAT(d.nombre, ' ', d.apellido) AS cliente,
    d.nro_pasaporte,
    d.genero,
    d.tipo_cliente,
    k.grupo,
    k.hotel,
    o.nombre_servicio,
    o.fecha_reserva,
    o.fecha_salida,
    o.fecha_retorno,
    o.incluye_ingreso,
    o.modalidad_retorno,
    o.servicio_adicional,
    o.observaciones,
    o.Encargado,
    c.metodo_pago,
    c.tipo_moneda,
    c.precio_servicio,
    c.pagado_a_cuenta,
    c.saldo_pendiente
FROM Operaciones o
INNER JOIN Datos_clientes d ON o.id_cliente = d.id_cliente
LEFT JOIN Clientes_KB k ON d.id_cliente = k.id_cliente
LEFT JOIN Contabilidad c ON o.id_operaciones = c.id_operaciones
WHERE o.id_operaciones = $id_operacion
";

$resultado = mysqli_query($conexion, $query);

if (!$resultado || mysqli_num_rows($resultado) == 0) {
    echo "<script>alert('❌ No se encontró la operación.'); window.location.href='index.php';</script>";
    exit;
}

$datos = mysqli_fetch_assoc($resultado);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ver Detalle de Operación KB</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../../sidebar.php'; ?>
<div class="container mt-4 mb-5">
    <div class="card shadow-lg border-0">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">📋 Detalle de Operación - Cliente KB</h4>
        </div>
        <div class="card-body">
            <div class="row g-3">

                <!-- Información del Cliente -->
                <div class="col-md-12">
                    <h5 class="text-secondary">👤 Información del Cliente</h5>
                    <hr>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Nombre del Cliente:</label>
                    <p><?= htmlspecialchars($datos['cliente']) ?></p>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Pasaporte:</label>
                    <p><?= htmlspecialchars($datos['nro_pasaporte']) ?></p>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Género:</label>
                    <p><?= htmlspecialchars($datos['genero']) ?></p>
                </div>

                <div class="col-md-3">
                    <label class="form-label fw-bold">Grupo:</label>
                    <p><?= htmlspecialchars($datos['grupo'] ?? '—') ?></p>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Hotel:</label>
                    <p><?= htmlspecialchars($datos['hotel'] ?? '—') ?></p>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Tipo Cliente:</label>
                    <p><?= htmlspecialchars($datos['tipo_cliente']) ?></p>
                </div>

                <!-- Información del Servicio -->
                <div class="col-md-12 mt-3">
                    <h5 class="text-secondary">🧭 Detalles del Servicio</h5>
                    <hr>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold">Nombre del Servicio:</label>
                    <p><?= htmlspecialchars($datos['nombre_servicio']) ?></p>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Fecha de Reserva:</label>
                    <p><?= htmlspecialchars($datos['fecha_reserva']) ?></p>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Salida:</label>
                    <p><?= htmlspecialchars($datos['fecha_salida']) ?></p>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Retorno:</label>
                    <p><?= htmlspecialchars($datos['fecha_retorno']) ?></p>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Incluye Ingreso:</label>
                    <p><?= htmlspecialchars($datos['incluye_ingreso']) ?></p>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Modalidad de Retorno:</label>
                    <p><?= htmlspecialchars($datos['modalidad_retorno']) ?></p>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Servicio Adicional:</label>
                    <p><?= htmlspecialchars($datos['servicio_adicional']) ?></p>
                </div>

                <!-- Observaciones -->
                <div class="col-md-12">
                    <label class="form-label fw-bold">Observaciones:</label>
                    <p><?= nl2br(htmlspecialchars($datos['observaciones'] ?? '—')) ?></p>
                </div>

                <!-- Información Económica -->
                <div class="col-md-12 mt-3">
                    <h5 class="text-secondary">💰 Información de Pago</h5>
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
                    <label class="form-label fw-bold">Pagado:</label>
                    <p><?= number_format($datos['pagado_a_cuenta'] ?? 0, 2) ?></p>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-bold">Saldo:</label>
                    <p><?= number_format($datos['saldo_pendiente'] ?? 0, 2) ?></p>
                </div>

                <div class="col-md-12 mt-4 text-center">
                    <a href="index.php" class="btn btn-secondary">⬅️ Volver</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
