<?php
include '../../../conexion.php';
include '../../sidebar.php';

// 🟢 Verificar ID de operación
if (!isset($_GET['id'])) {
    echo "<script>alert('⚠️ No se especificó la operación.'); window.location.href='index.php';</script>";
    exit;
}

$id_operacion = intval($_GET['id']);

// 🔍 Consulta de operación, cliente y contabilidad
$query = "
SELECT 
    o.id_operaciones,
    CONCAT(d.nombre, ' ', d.apellido) AS cliente,
    d.nro_pasaporte,
    d.genero,
    d.tipo_cliente,
    e.empresa_endosadora AS empresa,
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
FROM operaciones o
INNER JOIN datos_clientes d ON o.id_cliente = d.id_cliente
LEFT JOIN clientes_endosadores e ON d.id_cliente = e.id_cliente
LEFT JOIN contabilidad c ON o.id_operaciones = c.id_operaciones
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
    <title>Detalle de Operación Endosador</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4 mb-5">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">📋 Detalle de Operación - Cliente Endosador</h4>
        </div>
        <div class="card-body">
            
            <!-- Información del Cliente -->
            <h5 class="text-secondary">👤 Información del Cliente</h5>
            <hr>
            <div class="row">
                <div class="col-md-6">
                    <strong>Nombre:</strong> <?= htmlspecialchars($datos['cliente']) ?>
                </div>
                <div class="col-md-3">
                    <strong>Pasaporte:</strong> <?= htmlspecialchars($datos['nro_pasaporte']) ?>
                </div>
                <div class="col-md-3">
                    <strong>Género:</strong> <?= htmlspecialchars($datos['genero']) ?>
                </div>
                <div class="col-md-6">
                    <strong>Empresa Endosadora:</strong> <?= htmlspecialchars($datos['empresa'] ?? '—') ?>
                </div>
                <div class="col-md-3">
                    <strong>Tipo Cliente:</strong> <?= htmlspecialchars($datos['tipo_cliente']) ?>
                </div>
                <div class="col-md-3">
                    <strong>Encargado:</strong> <?= htmlspecialchars($datos['Encargado']) ?>
                </div>
            </div>

            <!-- Información del Servicio -->
            <h5 class="text-secondary mt-4">🧭 Detalles del Servicio</h5>
            <hr>
            <div class="row">
                <div class="col-md-6">
                    <strong>Nombre del Servicio:</strong> <?= htmlspecialchars($datos['nombre_servicio']) ?>
                </div>
                <div class="col-md-3">
                    <strong>Fecha Reserva:</strong> <?= htmlspecialchars($datos['fecha_reserva']) ?>
                </div>
                <div class="col-md-3">
                    <strong>Salida:</strong> <?= htmlspecialchars($datos['fecha_salida']) ?>
                </div>
                <div class="col-md-3">
                    <strong>Retorno:</strong> <?= htmlspecialchars($datos['fecha_retorno']) ?>
                </div>
                <div class="col-md-3">
                    <strong>Incluye Ingreso:</strong> <?= htmlspecialchars($datos['incluye_ingreso']) ?>
                </div>
                <div class="col-md-3">
                    <strong>Modalidad Retorno:</strong> <?= htmlspecialchars($datos['modalidad_retorno']) ?>
                </div>
                <div class="col-md-3">
                    <strong>Servicio Adicional:</strong> <?= htmlspecialchars($datos['servicio_adicional']) ?>
                </div>
            </div>

            <!-- Observaciones -->
            <h5 class="text-secondary mt-4">📝 Observaciones</h5>
            <hr>
            <p><?= nl2br(htmlspecialchars($datos['observaciones'] ?? '—')) ?></p>

            <!-- Información Económica -->
            <h5 class="text-secondary mt-4">💰 Información de Pago</h5>
            <hr>
            <div class="row">
                <div class="col-md-3"><strong>Método de Pago:</strong> <?= htmlspecialchars($datos['metodo_pago'] ?? '—') ?></div>
                <div class="col-md-3"><strong>Moneda:</strong> <?= htmlspecialchars($datos['tipo_moneda'] ?? '—') ?></div>
                <div class="col-md-2"><strong>Precio Total:</strong> <?= number_format($datos['precio_servicio'] ?? 0, 2) ?></div>
                <div class="col-md-2"><strong>Pagado:</strong> <?= number_format($datos['pagado_a_cuenta'] ?? 0, 2) ?></div>
                <div class="col-md-2"><strong>Saldo:</strong> <?= number_format($datos['saldo_pendiente'] ?? 0, 2) ?></div>
            </div>

            <div class="mt-4 text-center">
                <a href="index.php" class="btn btn-secondary">⬅️ Volver</a>
            </div>

        </div>
    </div>
</div>
</body>
</html>