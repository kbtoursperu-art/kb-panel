<?php
include("../../conexion.php");

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ===============================
// OBTENER ID
// ===============================
$id_salida = intval($_GET['id_salida'] ?? 0);
if ($id_salida <= 0) {
    die("Salida inválida");
}

// ===============================
// OBTENER DATOS DE LA SALIDA
// ===============================
$salida = mysqli_fetch_assoc(mysqli_query($conexion, "
    SELECT 
        s.id_stock,
        s.cantidad,
        s.estado,
        i.tipo
    FROM almacen_salidas s
    JOIN almacen_stock st ON s.id_stock = st.id_stock
    JOIN almacen_items i ON st.id_item = i.id_item
    WHERE s.id_salida = $id_salida
"));

if (!$salida) {
    die("Salida no encontrada");
}

$total = (int)$salida['cantidad'];
$id_stock = (int)$salida['id_stock'];
$tipo = strtolower(trim($salida['tipo']));

// ===============================
// YA DEVUELTO
// ===============================
$r = mysqli_fetch_assoc(mysqli_query($conexion, "
    SELECT IFNULL(SUM(cantidad_devuelta),0) AS devuelto
    FROM almacen_devoluciones
    WHERE id_salida = $id_salida
"));

$yaDevuelto = (int)$r['devuelto'];
$pendiente = $total - $yaDevuelto;

if ($pendiente <= 0) {
    die("Esta salida ya fue devuelta completamente");
}

// ===============================
// PROCESAR FORM
// ===============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $cantidad = intval($_POST['cantidad']);
    $obs = mysqli_real_escape_string($conexion, $_POST['observacion'] ?? '');

    if ($cantidad <= 0 || $cantidad > $pendiente) {
        die("Cantidad inválida");
    }

    mysqli_begin_transaction($conexion);

    try {
        // 1️⃣ INSERT DEVOLUCIÓN
        mysqli_query($conexion, "
            INSERT INTO almacen_devoluciones
            (id_salida, cantidad_devuelta, observacion, fecha_devolucion)
            VALUES ($id_salida, $cantidad, '$obs', CURDATE())
        ");

        // 2️⃣ DEVOLVER STOCK SOLO SI ES RETORNABLE
        if ($tipo === 'retornable') {
            mysqli_query($conexion, "
                UPDATE almacen_stock
                SET cantidad_disponible = cantidad_disponible + $cantidad
                WHERE id_stock = $id_stock
            ");
        }

        // 3️⃣ NUEVO ESTADO
        $nuevoDevuelto = $yaDevuelto + $cantidad;
        $estado = ($nuevoDevuelto >= $total) ? 'Devuelto' : 'Parcial';

        mysqli_query($conexion, "
            UPDATE almacen_salidas
            SET estado = '$estado'
            WHERE id_salida = $id_salida
        ");

        // 4️⃣ MOVIMIENTO
        mysqli_query($conexion, "
            INSERT INTO almacen_movimientos
            (id_stock, tipo, cantidad, referencia)
            VALUES ($id_stock, 'Devolucion', $cantidad, 'Devolución guía')
        ");

        mysqli_commit($conexion);

        echo "<script>
            alert('✅ Devolución registrada correctamente');
            window.location='pendientes.php';
        </script>";
        exit;

    } catch (Exception $e) {
        mysqli_rollback($conexion);
        die("Error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Registrar devolución</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
<div class="container mt-5">
    <div class="card">
        <div class="card-header bg-warning">
            <h5>↩️ Registrar devolución</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label>Cantidad a devolver</label>
                    <input type="number" name="cantidad" class="form-control"
                           min="1" max="<?= $pendiente ?>" required>
                    <div class="form-text">Pendiente: <?= $pendiente ?></div>
                </div>

                <div class="mb-3">
                    <label>Observación</label>
                    <input type="text" name="observacion" class="form-control">
                </div>

                <button class="btn btn-success">Registrar devolución</button>
                <a href="pendientes.php" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
    </div>
</div>
</body>
</html>
