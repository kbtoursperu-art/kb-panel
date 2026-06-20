<?php
include '../../../conexion.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);

if (!isset($_GET['id_cliente'])) {
    die("Falta ID cliente");
}

$id_cliente = (int) $_GET['id_cliente'];

/* =========================
   OBTENER GRUPO
========================= */
$qGrupo = mysqli_query($conexion,"
SELECT id_grupo 
FROM clientes_endosadores
WHERE id_cliente = $id_cliente
");

$rowGrupo = mysqli_fetch_assoc($qGrupo);
$id_grupo = $rowGrupo['id_grupo'] ?? null;

/* =========================
   INICIO TRANSACCION
========================= */
mysqli_begin_transaction($conexion);

try {

    /* =========================
       1. OPERACION
    ========================= */
    $fecha_reserva = $_POST['fecha_reserva'];
    $encargado = $_POST['Encargado'];
    $observaciones = $_POST['observaciones'];

    // calcular total
    $total = 0;
    foreach ($_POST['precio_tour'] as $p) {
        $total += floatval($p);
    }

    // empresa
    $empresa = "KB";
    $qEnd = mysqli_query($conexion,"SELECT 1 FROM clientes_endosadores WHERE id_cliente=$id_cliente");
    if (mysqli_num_rows($qEnd) > 0) {
        $empresa = "ENDOSADOR";
    }

    $stmt = mysqli_prepare($conexion,"
        INSERT INTO operaciones
        (id_cliente, id_grupo, empresa, fecha_reserva, observaciones, Encargado, total_operacion)
        VALUES (?,?,?,?,?,?,?)
    ");

    mysqli_stmt_bind_param($stmt,"iissssd",
        $id_cliente,
        $id_grupo,
        $empresa,
        $fecha_reserva,
        $observaciones,
        $encargado,
        $total
    );

    mysqli_stmt_execute($stmt);
    $id_operacion = mysqli_insert_id($conexion);

    /* =========================
       2. DETALLE TOURS
    ========================= */
    $detalles_ids = [];

    foreach ($_POST['nombre_servicio'] as $i => $servicio) {

        if (!$servicio) continue;

        $precio = floatval($_POST['precio_tour'][$i]);
        $salida = $_POST['fecha_salida'][$i];
        $retorno = $_POST['fecha_retorno'][$i];
        $modalidad = $_POST['modalidad_retorno'][$i];
        $moneda = $_POST['tipo_moneda_tour'][$i];

        $ingreso = isset($_POST['incluye_ingreso'][$i]) ? 'Con ingreso' : 'Sin ingreso';

        $servicios = $_POST['servicio_adicional'][$i] ?? [];
        $servicio_txt = is_array($servicios) ? implode(", ", $servicios) : '';

        $stmtDet = mysqli_prepare($conexion,"
            INSERT INTO operaciones_detalle
            (id_operaciones, nombre_servicio, precio, fecha_salida, fecha_retorno, modalidad_retorno, incluye_ingreso, servicio_adicional, tipo_moneda)
            VALUES (?,?,?,?,?,?,?,?,?)
        ");

        mysqli_stmt_bind_param($stmtDet,"isdssssss",
            $id_operacion,
            $servicio,
            $precio,
            $salida,
            $retorno,
            $modalidad,
            $ingreso,
            $servicio_txt,
            $moneda
        );

        mysqli_stmt_execute($stmtDet);
        $detalles_ids[$i] = mysqli_insert_id($conexion);
    }

    /* =========================
       3. PAGOS (UNIFICADO)
    ========================= */
    if (!empty($_POST['monto_multi'])) {

        foreach ($_POST['monto_multi'] as $i => $monto) {

            if ($monto == "") continue;

            $tipo = $_POST['tipo_pago'][$i];
            $metodo = $_POST['metodo_pago_multi'][$i];
            $moneda = $_POST['moneda_multi'][$i];
            $fecha = $_POST['fecha_multi'][$i];

            // asignar a detalle si corresponde
            $id_detalle = null;
            if (isset($_POST['id_detalle_pago'][$i])) {
                $index = $_POST['id_detalle_pago'][$i];
                $id_detalle = $detalles_ids[$index] ?? null;
            }

            $stmtPago = mysqli_prepare($conexion,"
                INSERT INTO pagos
                (id_operaciones, id_detalle, tipo, metodo_pago, moneda, monto, fecha)
                VALUES (?,?,?,?,?,?,?)
            ");

            mysqli_stmt_bind_param($stmtPago,"iisssds",
                $id_operacion,
                $id_detalle,
                $tipo,
                $metodo,
                $moneda,
                $monto,
                $fecha
            );

            mysqli_stmt_execute($stmtPago);
        }
    }

    /* =========================
       4. CONTABILIDAD
    ========================= */
    $pagado = floatval($_POST['pagado_a_cuenta']);
    $saldo = $total - $pagado;

    $stmtCont = mysqli_prepare($conexion,"
        INSERT INTO contabilidad
        (id_operaciones, id_grupo, metodo_pago, tipo_moneda, precio_servicio, pagado_a_cuenta, saldo_pendiente)
        VALUES (?,?,?,?,?,?,?)
    ");

    mysqli_stmt_bind_param($stmtCont,"iissddd",
        $id_operacion,
        $id_grupo,
        $_POST['metodo_pago'],
        $_POST['tipo_moneda'],
        $total,
        $pagado,
        $saldo
    );

    mysqli_stmt_execute($stmtCont);

    /* =========================
       COMMIT
    ========================= */
    mysqli_commit($conexion);

    header("Location: index.php?ok=1");
    exit;

} catch (Exception $e) {

    mysqli_rollback($conexion);

    echo "Error: " . $e->getMessage();
}