<?php
include('../../conexion.php');

header('Content-Type: application/json');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 0); // 🔥 importante para no romper JSON

$data = [];

/* 🔹 Reservas activas */
$res = mysqli_query($conexion, "SELECT COUNT(*) total FROM operaciones_detalle WHERE fecha_salida >= CURDATE()");
$data['reservas_activas'] = (int) mysqli_fetch_assoc($res)['total'];

/* 🔹 Tours */
$res = mysqli_query($conexion, "SELECT COUNT(*) total FROM operaciones_detalle");
$data['tours'] = (int) mysqli_fetch_assoc($res)['total'];

/* 🔹 Clientes */
$res = mysqli_query($conexion, "SELECT COUNT(*) total FROM datos_clientes WHERE fecha_registro >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$data['clientes'] = (int) mysqli_fetch_assoc($res)['total'];

/* 🔹 Ingresos */
$res = mysqli_query($conexion,"
SELECT SUM(
COALESCE(c.pagado_a_cuenta,0)+
COALESCE(c.pagado_adicional,0)+
COALESCE(c.monto_pago_saldo,0)
) total
FROM contabilidad c
INNER JOIN operaciones o ON c.id_operaciones=o.id_operaciones
WHERE DATE(o.fecha_reserva)=CURDATE()
");
$data['ingresos'] = (float) (mysqli_fetch_assoc($res)['total'] ?? 0);

/* 🔹 Saldos */
$res = mysqli_query($conexion,"SELECT SUM(IFNULL(saldo_pendiente,0)) total FROM contabilidad WHERE estado='pendiente'");
$data['saldos'] = (float) (mysqli_fetch_assoc($res)['total'] ?? 0);

/* 🔹 Gráfico */
$labels = [];
$values = [];

$q = mysqli_query($conexion,"
SELECT DATE(o.fecha_reserva) fecha,
SUM(
COALESCE(c.pagado_a_cuenta,0)+
COALESCE(c.pagado_adicional,0)+
COALESCE(c.monto_pago_saldo,0)
) total
FROM contabilidad c
INNER JOIN operaciones o ON c.id_operaciones=o.id_operaciones
WHERE o.fecha_reserva >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
GROUP BY fecha
ORDER BY fecha
");

while($row = mysqli_fetch_assoc($q)){
    $labels[] = date('d/m', strtotime($row['fecha']));
    $values[] = (float)$row['total'];
}

$data['labels'] = $labels;
$data['values'] = $values;

echo json_encode($data);