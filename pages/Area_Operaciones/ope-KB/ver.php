<?php
include '../../../conexion.php';
include '../../sidebar.php';

// 🟢 Verificar ID del grupo
if (!isset($_GET['id_grupo'])) {
    echo "<script>alert('⚠️ No se especificó el grupo.'); window.location.href='index.php';</script>";
    exit;
}

$id_grupo = intval($_GET['id_grupo']);

// 🔹 Obtener info del grupo
$query_grupo = "
SELECT 
    g.id_grupo,
    g.nombre_grupo,
    COUNT(DISTINCT k.id_cliente) AS pasajeros
FROM grupos g
JOIN clientes_kb k ON k.id_grupo = g.id_grupo
WHERE g.id_grupo = $id_grupo
GROUP BY g.id_grupo
";
$res_grupo = mysqli_query($conexion, $query_grupo);
if (!$res_grupo || mysqli_num_rows($res_grupo) == 0) {
    echo "<script>alert('❌ Grupo no encontrado.'); window.location.href='index.php';</script>";
    exit;
}
$grupo = mysqli_fetch_assoc($res_grupo);

// 🔹 Obtener clientes del grupo
$query_clientes = "
SELECT 
    k.id_cliente,
    CONCAT(d.nombre,' ',d.apellido) AS cliente,
    d.nro_pasaporte,
    d.tipo_cliente,
    d.genero,
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
FROM clientes_kb k
JOIN Datos_clientes d ON d.id_cliente = k.id_cliente
LEFT JOIN Operaciones o ON o.id_cliente = k.id_cliente
LEFT JOIN Contabilidad c ON c.id_operaciones = o.id_operaciones
WHERE k.id_grupo = $id_grupo
ORDER BY d.nombre ASC
";
$res_clientes = mysqli_query($conexion, $query_clientes);
if (!$res_clientes) {
    die("Error en la consulta: " . mysqli_error($conexion));
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Detalle Grupo KB</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-4 mb-5">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">📋 Detalle Grupo: <?= htmlspecialchars($grupo['nombre_grupo']) ?> (<?= $grupo['pasajeros'] ?> pasajeros)</h4>
        </div>
        <div class="card-body">

            <?php while($cliente = mysqli_fetch_assoc($res_clientes)): ?>
            <div class="card mb-3 border-info">
                <div class="card-header bg-info text-white">
                    <strong><?= htmlspecialchars($cliente['cliente']) ?></strong> - <?= htmlspecialchars($cliente['tipo_cliente']) ?>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4"><strong>Pasaporte:</strong> <?= htmlspecialchars($cliente['nro_pasaporte']) ?></div>
                        <div class="col-md-4"><strong>Género:</strong> <?= htmlspecialchars($cliente['genero']) ?></div>
                        <div class="col-md-4"><strong>Encargado:</strong> <?= htmlspecialchars($cliente['Encargado'] ?? '—') ?></div>
                    </div>
                    <hr>
                    <h6>🧭 Servicio</h6>
                    <div class="row">
                        <div class="col-md-4"><strong>Servicio:</strong> <?= htmlspecialchars($cliente['nombre_servicio'] ?? '—') ?></div>
                        <div class="col-md-4"><strong>Salida:</strong> <?= htmlspecialchars($cliente['fecha_salida'] ?? '—') ?></div>
                        <div class="col-md-4"><strong>Retorno:</strong> <?= htmlspecialchars($cliente['fecha_retorno'] ?? '—') ?></div>
                        <div class="col-md-4"><strong>Reserva:</strong> <?= htmlspecialchars($cliente['fecha_reserva'] ?? '—') ?></div>
                        <div class="col-md-4"><strong>Modalidad:</strong> <?= htmlspecialchars($cliente['modalidad_retorno'] ?? '—') ?></div>
                        <div class="col-md-4"><strong>Incluye Ingreso:</strong> <?= htmlspecialchars($cliente['incluye_ingreso'] ?? '—') ?></div>
                        <div class="col-md-4"><strong>Servicio Adicional:</strong> <?= htmlspecialchars($cliente['servicio_adicional'] ?? '—') ?></div>
                    </div>
                    <hr>
                    <h6>💰 Pago</h6>
                    <div class="row">
                        <div class="col-md-3"><strong>Método:</strong> <?= htmlspecialchars($cliente['metodo_pago'] ?? '—') ?></div>
                        <div class="col-md-3"><strong>Moneda:</strong> <?= htmlspecialchars($cliente['tipo_moneda'] ?? '—') ?></div>
                        <div class="col-md-2"><strong>Total:</strong> <?= number_format($cliente['precio_servicio'] ?? 0,2) ?></div>
                        <div class="col-md-2"><strong>Pagado:</strong> <?= number_format($cliente['pagado_a_cuenta'] ?? 0,2) ?></div>
                        <div class="col-md-2"><strong>Saldo:</strong> <?= number_format($cliente['saldo_pendiente'] ?? 0,2) ?></div>
                    </div>
                    <hr>
                    <h6>📝 Observaciones</h6>
                    <p><?= nl2br(htmlspecialchars($cliente['observaciones'] ?? '—')) ?></p>
                </div>
            </div>
            <?php endwhile; ?>

            <div class="mt-4 text-center">
                <a href="index.php" class="btn btn-secondary">⬅️ Volver</a>
            </div>

        </div>
    </div>
</div>
</body>
</html>