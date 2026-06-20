<?php
 
// ══════════════════════════════════════════════════
// GUARDAR (POST)
// ══════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    mysqli_begin_transaction($conexion);
    try {
        $id = (int)($_POST['id_operacion'] ?? 0);
        if ($id <= 0) throw new Exception("ID inválido.");

        $tipo_precio     = $_POST['tipo_precio']      ?? 'por_tour';
        $total_operacion = floatval($_POST['total_operacion'] ?? 0);
        $fecha_reserva   = $_POST['fecha_reserva'][0] ?? date('Y-m-d');
        $observaciones   = mysqli_real_escape_string($conexion, trim($_POST['observaciones'][0] ?? ''));
        $encargado       = mysqli_real_escape_string($conexion, trim($_POST['Encargado'][0] ?? ''));
        $estado_op       = mysqli_real_escape_string($conexion, $_POST['estado_op'] ?? $op['estado']);
        $comision        = floatval($_POST['comision'] ?? 0);

        // Totales tours
        $total_soles = $total_dolares = 0;
        foreach ($_POST['precio_tour'] as $i => $p) {
            $precio = floatval($p);
            $moneda = $_POST['moneda_tour'][$i] ?? 'Soles';
            if ($moneda === 'Soles') $total_soles += $precio; else $total_dolares += $precio;
        }
        $precio_final = ($tipo_precio === 'total' && $total_operacion > 0) ? $total_operacion : $total_soles;

        // UPDATE operaciones
        $stmtOp = mysqli_prepare($conexion,"
            UPDATE operaciones SET
                fecha_reserva='$fecha_reserva', observaciones='$observaciones',
                encargado='$encargado', tipo_precio='$tipo_precio',
                total_operacion=?, estado='$estado_op'
            WHERE id_operaciones=$id
        ");
        mysqli_stmt_bind_param($stmtOp,'d',$precio_final);
        mysqli_stmt_execute($stmtOp);

        // Borrar adicionales → detalle
        $qOld = mysqli_query($conexion,"SELECT id_detalle FROM operaciones_detalle WHERE id_operaciones=$id");
        while ($od = mysqli_fetch_assoc($qOld))
            mysqli_query($conexion,"DELETE FROM adicionales_detalle WHERE id_detalle=".$od['id_detalle']);
        mysqli_query($conexion,"DELETE FROM operaciones_detalle WHERE id_operaciones=$id");

        // INSERT detalle + adicionales
        foreach ($_POST['id_servicio'] as $i => $id_serv) {
            $id_serv = (int)$id_serv; if ($id_serv <= 0) continue;
            $precio  = floatval($_POST['precio_tour'][$i] ?? 0); if ($precio <= 0) continue;
            $fecha_salida  = $_POST['fecha_salida'][$i]       ?? null;
            $fecha_retorno = $_POST['fecha_retorno'][$i]      ?? null;
            $modalidad     = $_POST['modalidad_retorno'][$i]  ?? null;
            $moneda        = $_POST['moneda_tour'][$i]        ?? 'Soles';
            $moneda        = ($moneda==='S/'?'Soles':($moneda==='$'?'Dólares':$moneda));
            $ingreso       = (($_POST['incluye_ingreso'][$i] ?? 'NO') === 'SI') ? 'SI' : 'NO';

            $stmtDet = mysqli_prepare($conexion,"
                INSERT INTO operaciones_detalle
                (id_operaciones,id_servicio,precio,fecha_salida,fecha_retorno,modalidad_retorno,incluye_ingreso,tipo_moneda)
                VALUES (?,?,?,?,?,?,?,?)
            ");
            mysqli_stmt_bind_param($stmtDet,'iidsssss',
                $id,$id_serv,$precio,$fecha_salida,$fecha_retorno,$modalidad,$ingreso,$moneda);
            mysqli_stmt_execute($stmtDet);
            $id_detalle = mysqli_insert_id($conexion);

            if (!empty($_POST['servicio_adicional'][$i])) {
                foreach ($_POST['servicio_adicional'][$i] as $k => $nombre) {
                    if ($nombre === 'Ninguna' || empty($nombre)) continue;
                    $precio_ad = floatval($_POST['precio_adicional'][$i][$k] ?? 0);
                    $stmtAd = mysqli_prepare($conexion,"INSERT INTO adicionales_detalle (id_detalle,nombre,precio) VALUES (?,?,?)");
                    mysqli_stmt_bind_param($stmtAd,'isd',$id_detalle,$nombre,$precio_ad);
                    mysqli_stmt_execute($stmtAd);
                }
            }
        }

        // Pagos
        mysqli_query($conexion,"DELETE FROM pagos WHERE id_operaciones=$id");
        $total_pagado_soles = $total_pagado_dolares = 0;
        if (!empty($_POST['monto_multi'])) {
            foreach ($_POST['monto_multi'] as $i => $monto) {
                $monto = floatval($monto); if ($monto <= 0) continue;
                $tipo_pago = $_POST['tipo_pago'][$i]         ?? 'tour';
                $metodo    = $_POST['metodo_pago_multi'][$i] ?? 'Efectivo';
                $moneda    = $_POST['moneda_multi'][$i]      ?? 'Soles';
                $fecha     = $_POST['fecha_multi'][$i]       ?? date('Y-m-d');
                $moneda    = ($moneda==='S/'?'Soles':($moneda==='$'?'Dólares':$moneda));
                $id_det    = null;
                $tipos_v   = ['tour','adicional','cuenta','saldo','reembolso'];
                if (!in_array($tipo_pago,$tipos_v)) $tipo_pago='tour';

                if ($tipo_pago==='tour') {
                    if ($moneda==='Soles') $total_pagado_soles += $monto;
                    else $total_pagado_dolares += $monto;
                }
                $stmtP = mysqli_prepare($conexion,"
                    INSERT INTO pagos (id_operaciones,id_detalle,tipo,metodo_pago,moneda,monto,fecha)
                    VALUES (?,?,?,?,?,?,?)
                ");
                mysqli_stmt_bind_param($stmtP,'iisssds',
                    $id,$id_det,$tipo_pago,$metodo,$moneda,$monto,$fecha);
                mysqli_stmt_execute($stmtP);
            }
        }
        
        // Contabilidad
        $saldo_s  = $total_soles    - $total_pagado_soles;
        $saldo_d  = $total_dolares  - $total_pagado_dolares;
        $est_c    = ($saldo_s<=0 && $saldo_d<=0) ? 'pagado' : 'pendiente';
        $existe   = mysqli_fetch_assoc(mysqli_query($conexion,"SELECT id_contabilidad FROM contabilidad WHERE id_operaciones=$id"));
        if ($existe) {
            mysqli_query($conexion,"UPDATE contabilidad SET comision=$comision,estado='$est_c' WHERE id_operaciones=$id");
        } else {
            $stmtC = mysqli_prepare($conexion,"INSERT INTO contabilidad (id_operaciones,comision,estado) VALUES (?,?,?)");
            mysqli_stmt_bind_param($stmtC,'ids',$id,$comision,$est_c);
            mysqli_stmt_execute($stmtC);
        }

        mysqli_commit($conexion);
        header("Location: index.php?id={$id}&editado=1");
        exit;

    } catch (Exception $e) {
        mysqli_rollback($conexion);
        $error_msg = "Error al guardar: " . $e->getMessage();
    }
}
?>