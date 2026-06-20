<?php 
// ── Guardar operación ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    mysqli_begin_transaction($conexion);

    try {
        $tipo_precio     = $_POST['tipo_precio'] ?? 'por_tour';
        $total_operacion = floatval($_POST['total_operacion'] ?? 0);

        $total_soles = $total_dolares = 0;
        foreach ($_POST['precio_tour'] as $i => $p) {
            $precio = floatval($p);
            $moneda = $_POST['moneda_tour'][$i] ?? 'Soles';
            if ($moneda === 'Soles') $total_soles += $precio; else $total_dolares += $precio;
        }

        $precio_final  = ($tipo_precio === 'total' && $total_operacion > 0) ? $total_operacion : 0;
        $fecha_reserva = $_POST['fecha_reserva'][0] ?? date('Y-m-d');
        $observaciones = trim($_POST['observaciones'][0] ?? '');
        $encargado     = trim($_POST['Encargado'][0] ?? '');
        $estado        = 'pendiente';

        $stmtOp = mysqli_prepare($conexion, "
            INSERT INTO operaciones (id_cliente, id_grupo, fecha_reserva, observaciones, encargado, tipo_precio, total_operacion, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        mysqli_stmt_bind_param($stmtOp, "iissssds",
            $id_cliente, $id_grupo, $fecha_reserva, $observaciones, $encargado, $tipo_precio, $precio_final, $estado);
        // fix: bind correcto
        mysqli_stmt_bind_param($stmtOp, "iisssdds",
            $id_cliente, $id_grupo, $fecha_reserva, $observaciones, $encargado, $tipo_precio, $precio_final, $estado);
        mysqli_stmt_execute($stmtOp);
        $id_operaciones = mysqli_insert_id($conexion);

        // ── Detalles + adicionales ─────────────────────────────────────
        foreach ($_POST['id_servicio'] as $i => $id_serv) {
            $id_serv = (int)$id_serv;
            if ($id_serv <= 0) continue;

            $precio = floatval($_POST['precio_tour'][$i] ?? 0);
            if ($precio <= 0) continue;

            $fecha_salida  = $_POST['fecha_salida'][$i]  ?? null;
            $fecha_retorno = $_POST['fecha_retorno'][$i] ?? null;
            $modalidad     = $_POST['modalidad_retorno'][$i] ?? null;
            $moneda        = $_POST['moneda_tour'][$i] ?? 'Soles';
            $moneda        = ($moneda === 'S/' ? 'Soles' : ($moneda === '$' ? 'Dólares' : $moneda));
            $ingreso       = (($_POST['incluye_ingreso'][$i] ?? 'NO') === 'SI') ? 'SI' : 'NO';

            $stmtDet = mysqli_prepare($conexion, "
                INSERT INTO operaciones_detalle
                (id_operaciones, id_servicio, precio, fecha_salida, fecha_retorno, modalidad_retorno, incluye_ingreso, tipo_moneda)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            mysqli_stmt_bind_param($stmtDet, "iidsssss",
                $id_operaciones, $id_serv, $precio,
                $fecha_salida, $fecha_retorno, $modalidad, $ingreso, $moneda);
            mysqli_stmt_execute($stmtDet);
            $id_detalle = mysqli_insert_id($conexion);

            if (!empty($_POST['servicio_adicional'][$i])) {
                foreach ($_POST['servicio_adicional'][$i] as $k => $nombre) {
                    if ($nombre === 'Ninguna' || empty($nombre)) continue;
                    $precio_ad = floatval($_POST['precio_adicional'][$i][$k] ?? 0);
                    $stmtAd    = mysqli_prepare($conexion, "INSERT INTO adicionales_detalle (id_detalle, nombre, precio) VALUES (?, ?, ?)");
                    mysqli_stmt_bind_param($stmtAd, "isd", $id_detalle, $nombre, $precio_ad);
                    mysqli_stmt_execute($stmtAd);
                }
            }
        }

        // ── Pagos ──────────────────────────────────────────────────────
        $total_pagado_soles = $total_pagado_dolares = 0;

        if (!empty($_POST['monto_multi'])) {
            foreach ($_POST['monto_multi'] as $i => $monto) {
                $monto = floatval($monto);
                if ($monto <= 0) continue;

                $tipo_pago  = $_POST['tipo_pago'][$i]         ?? 'tour';
                $metodo     = $_POST['metodo_pago_multi'][$i] ?? 'Efectivo';
                $moneda     = $_POST['moneda_multi'][$i]       ?? 'Soles';
                $fecha      = $_POST['fecha_multi'][$i]        ?? date('Y-m-d');
                $moneda     = ($moneda === 'S/' ? 'Soles' : ($moneda === '$' ? 'Dólares' : $moneda));
                $id_det     = null;

                if ($tipo_pago === 'tour') {
                    if ($moneda === 'Soles') $total_pagado_soles += $monto;
                    else $total_pagado_dolares += $monto;
                }

                $stmtPago = mysqli_prepare($conexion, "
                    INSERT INTO pagos (id_operaciones, id_detalle, tipo, metodo_pago, moneda, monto, fecha)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                mysqli_stmt_bind_param($stmtPago, "iisssds",
                    $id_operaciones, $id_det, $tipo_pago, $metodo, $moneda, $monto, $fecha);
                mysqli_stmt_execute($stmtPago);
            }
        }

        // ── Contabilidad ───────────────────────────────────────────────
        $comision     = floatval($_POST['comision'] ?? 0);
        $saldo_soles  = $total_soles   - $total_pagado_soles;
        $saldo_dol    = $total_dolares - $total_pagado_dolares;
        $estado_cont  = ($saldo_soles <= 0 && $saldo_dol <= 0) ? 'pagado' : 'pendiente';

        $stmtCont = mysqli_prepare($conexion, "INSERT INTO contabilidad (id_operaciones, comision, estado) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmtCont, "ids", $id_operaciones, $comision, $estado_cont);
        mysqli_stmt_execute($stmtCont);

        mysqli_commit($conexion);
  header("Location: ../index.php?mensaje=agregado");
exit;

    } catch (Exception $e) {
        mysqli_rollback($conexion);
        $error_msg = "Error al guardar: " . $e->getMessage();
    }
}
?>