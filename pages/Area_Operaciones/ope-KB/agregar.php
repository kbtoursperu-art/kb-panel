<?php
ob_start();
include '../../../conexion.php';

if (!isset($_GET['id_cliente'])) die("Error: Falta el ID del cliente.");

$id_cliente = (int) $_GET['id_cliente'];

// ── Grupo del cliente ──────────────────────────────────────────────────
$qGrupo   = mysqli_query($conexion, "SELECT id_grupo FROM clientes_grupo WHERE id_cliente = $id_cliente LIMIT 1");
$rowGrupo = mysqli_fetch_assoc($qGrupo);
$id_grupo = $rowGrupo['id_grupo'] ?? null;

// ── Datos del cliente ──────────────────────────────────────────────────
$res     = mysqli_query($conexion, "SELECT CONCAT(nombre,' ',apellido) nombre_completo FROM datos_clientes WHERE id_cliente = $id_cliente");
$cliente = mysqli_fetch_assoc($res);
if (!$cliente) die("Cliente no encontrado.");

// ── Servicios desde BD ─────────────────────────────────────────────────
$resServ  = mysqli_query($conexion, "SELECT id_servicio, nombre, duracion_dias FROM servicios WHERE activo = 1 ORDER BY nombre");
$servicios = [];
while ($s = mysqli_fetch_assoc($resServ)) $servicios[] = $s;

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
        mysqli_stmt_bind_param($stmtOp, "iisssds s",
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
        header("Location: index.php?mensaje=agregado");
        exit;

    } catch (Exception $e) {
        mysqli_rollback($conexion);
        $error_msg = "Error al guardar: " . $e->getMessage();
    }
}

// Mapeo duraciones para JS
$duraciones_js = [];
foreach ($servicios as $s) {
    if ($s['duracion_dias']) $duraciones_js[$s['id_servicio']] = (int)$s['duracion_dias'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Operación – KB Adventures</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">

    <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
        --bg:        #f0f4fa;
        --surface:   #ffffff;
        --surface2:  #f8faff;
        --border:    #e2e8f4;
        --border2:   #c7d4ea;
        --accent:    #2563eb;
        --accent-h:  #1d4ed8;
        --accent-lt: #eff6ff;
        --text:      #0f172a;
        --muted:     #64748b;
        --sub:       #94a3b8;
        --green:     #15803d;
        --green-bg:  #f0fdf4;
        --green-bd:  #bbf7d0;
        --amber:     #d97706;
        --amber-bg:  #fffbeb;
        --amber-bd:  #fde68a;
        --info-bg:   #eff6ff;
        --info-bd:   #bfdbfe;
        --info-txt:  #1e40af;
        --danger:    #dc2626;
        --danger-bg: #fff1f2;
        --danger-bd: #fecdd3;
        --radius:    12px;
        --radius-sm: 8px;
    }

    html, body { min-height: 100vh; font-family: 'Outfit', sans-serif; background: var(--bg); color: var(--text); }

    /* ── CONTENT ── */
    .content {
        margin-left: 256px; padding: 28px 28px 64px;
        min-height: 100vh; transition: margin-left .32s cubic-bezier(.4,0,.2,1);
    }
    body.sidebar-collapsed .content { margin-left: 64px; }
    @media (max-width: 992px) { .content { margin-left: 0 !important; padding: 16px 14px 48px; } }

    /* ── HEADER ── */
    .page-header { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 24px; }
    .page-title  { font-size: 20px; font-weight: 600; color: var(--text); }
    .page-sub    { font-size: 13px; color: var(--muted); margin-top: 3px; }
    .page-back   {
        display: inline-flex; align-items: center; gap: 7px; font-size: 13px;
        color: var(--muted); text-decoration: none; background: var(--surface);
        border: 1px solid var(--border); padding: 7px 14px; border-radius: var(--radius-sm);
        transition: all .15s;
    }
    .page-back:hover { background: var(--accent-lt); color: var(--accent); border-color: var(--info-bd); }

    /* ── CLIENT CHIP ── */
    .client-chip {
        display: inline-flex; align-items: center; gap: 8px;
        background: var(--info-bg); border: 1px solid var(--info-bd);
        border-radius: 20px; padding: 6px 14px; font-size: 13px;
        color: var(--info-txt); font-weight: 500; margin-bottom: 20px;
    }
    .client-chip i { font-size: 14px; }

    /* ── SECTION CARD ── */
    .section-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; margin-bottom: 20px; }
    .section-header {
        display: flex; align-items: center; gap: 10px;
        padding: 13px 20px; border-bottom: 1px solid var(--border); background: var(--surface2);
    }
    .sh-icon { width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0; }
    .sh-blue   { background: #dbeafe; color: var(--accent); }
    .sh-green  { background: #dcfce7; color: var(--green); }
    .sh-amber  { background: #fef3c7; color: var(--amber); }
    .sh-cyan   { background: #cffafe; color: #0e7490; }
    .sh-indigo { background: #e0e7ff; color: #4338ca; }
    .section-header h5 { font-size: 14px; font-weight: 600; color: var(--text); margin: 0; }
    .section-header small { font-size: 11px; color: var(--muted); display: block; margin-top: 1px; }
    .section-body { padding: 18px 20px; }
    .section-footer { padding: 12px 20px; border-top: 1px solid var(--border); background: var(--surface2); }

    /* ── LABELS & INPUTS ── */
    .kb-label { display: block; font-size: 11px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .05em; margin-bottom: 5px; }
    .kb-input, .kb-select {
        width: 100%; background: var(--surface); border: 1.5px solid var(--border);
        border-radius: var(--radius-sm); padding: 8px 12px;
        font-family: 'Outfit', sans-serif; font-size: 13.5px; color: var(--text);
        outline: none; transition: border-color .15s, box-shadow .15s; appearance: none;
    }
    .kb-input:focus, .kb-select:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(37,99,235,.1); }
    .kb-input::placeholder { color: var(--sub); }
    .kb-input[readonly] { background: var(--surface2); color: var(--muted); cursor: default; }
    textarea.kb-input { resize: vertical; min-height: 72px; }

    /* ── TABLE STYLES ── */
    .kb-table-wrap { overflow-x: auto; }
    .kb-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .kb-table thead tr { background: #f1f5f9; }
    .kb-table th {
        padding: 9px 10px; text-align: center; font-size: 11px; font-weight: 600;
        color: var(--muted); text-transform: uppercase; letter-spacing: .05em;
        border-bottom: 1px solid var(--border); white-space: nowrap;
    }
    .kb-table th:first-child { text-align: left; padding-left: 14px; }
    .kb-table td { padding: 8px 6px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .kb-table td:first-child { padding-left: 14px; }
    .kb-table tbody tr:hover { background: #fafbff; }
    .kb-table tbody tr:last-child td { border-bottom: none; }

    .kb-table .kb-input, .kb-table .kb-select { padding: 7px 8px; font-size: 13px; }

    /* ── RESUMEN BOXES ── */
    .resumen-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px,1fr)); gap: 12px; }
    .resumen-box {
        background: var(--surface2); border: 1px solid var(--border);
        border-radius: var(--radius-sm); padding: 12px 14px;
    }
    .resumen-box .r-label { font-size: 10px; font-weight: 600; color: var(--sub); text-transform: uppercase; letter-spacing: .05em; margin-bottom: 5px; }
    .resumen-box .r-val   { font-size: 18px; font-weight: 600; color: var(--text); }
    .resumen-box .r-cur   { font-size: 12px; color: var(--muted); font-weight: 400; }
    .resumen-box.saldo-rojo  .r-val { color: var(--danger); }
    .resumen-box.saldo-verde .r-val { color: var(--green); }
    .resumen-box.saldo-cero  .r-val { color: var(--muted); }

    /* ── DIVIDER LABEL ── */
    .divider-label {
        display: flex; align-items: center; gap: 8px;
        font-size: 11px; font-weight: 600; color: var(--muted);
        text-transform: uppercase; letter-spacing: .06em;
        margin: 18px 0 12px;
    }
    .divider-label::before, .divider-label::after { content: ''; flex: 1; height: 1px; background: var(--border); }

    /* ── BTNS ── */
    .kb-btn {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 8px 16px; border-radius: var(--radius-sm); border: none;
        font-family: 'Outfit', sans-serif; font-size: 13px; font-weight: 500;
        cursor: pointer; text-decoration: none; transition: filter .15s, transform .1s;
        white-space: nowrap;
    }
    .kb-btn:active { transform: scale(.97); }
    .kb-btn:hover  { filter: brightness(1.1); }
    .kb-btn-primary { background: var(--accent); color: #fff; }
    .kb-btn-success { background: #166534; color: #dcfce7; }
    .kb-btn-amber   { background: #d97706; color: #fff; }
    .kb-btn-outline { background: var(--surface); color: var(--muted); border: 1.5px solid var(--border); }
    .kb-btn-outline:hover { background: var(--accent-lt); color: var(--accent); border-color: var(--info-bd); }
    .kb-btn-danger  { background: var(--danger-bg); color: var(--danger); border: 1px solid var(--danger-bd); }
    .kb-btn-danger:hover { background: #fee2e2; }
    .kb-btn-sm { padding: 5px 10px; font-size: 12px; }
    .kb-btn-xs { padding: 4px 8px; font-size: 11px; }

    .kb-submit {
        width: 100%; padding: 13px; background: var(--accent); color: #fff;
        border: none; border-radius: var(--radius-sm); font-family: 'Outfit', sans-serif;
        font-size: 15px; font-weight: 600; cursor: pointer;
        display: flex; align-items: center; justify-content: center; gap: 8px;
        transition: background .15s, transform .1s;
    }
    .kb-submit:hover  { background: var(--accent-h); }
    .kb-submit:active { transform: scale(.98); }

    /* ── ALERT ── */
    .kb-alert { display: flex; align-items: flex-start; gap: 10px; padding: 13px 16px; border-radius: var(--radius); font-size: 13.5px; margin-bottom: 18px; }
    .kb-alert.error { background: var(--danger-bg); border: 1px solid var(--danger-bd); color: #9f1239; }
    .kb-alert i { font-size: 17px; flex-shrink: 0; margin-top: 1px; }

    /* ── INGRESO CHECKBOX ── */
    .check-wrap { display: flex; align-items: center; justify-content: center; height: 100%; }
    .kb-checkbox { width: 18px; height: 18px; cursor: pointer; accent-color: var(--accent); }

    /* ── TIPO MONEDA TOGGLE ── */
    .moneda-toggle { display: flex; border: 1.5px solid var(--border); border-radius: var(--radius-sm); overflow: hidden; }
    .moneda-toggle input[type=radio] { display: none; }
    .moneda-toggle label {
        flex: 1; text-align: center; padding: 7px 6px; font-size: 12px; font-weight: 600;
        cursor: pointer; color: var(--muted); transition: background .15s, color .15s;
        background: var(--surface);
    }
    .moneda-toggle input[type=radio]:checked + label { background: var(--accent); color: #fff; }

    /* Select2 */
    .select2-container--default .select2-selection--multiple {
        border: 1.5px solid var(--border) !important; border-radius: var(--radius-sm) !important;
        background: var(--surface) !important; min-height: 36px !important;
        font-family: 'Outfit', sans-serif !important; font-size: 13px !important;
    }
    .select2-container--default.select2-container--focus .select2-selection--multiple {
        border-color: var(--accent) !important; box-shadow: 0 0 0 3px rgba(37,99,235,.1) !important;
    }
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background: var(--accent-lt) !important; border: 1px solid var(--info-bd) !important;
        color: var(--info-txt) !important; font-size: 11px !important; padding: 1px 6px !important;
    }
    .select2-dropdown { border: 1.5px solid var(--border2) !important; border-radius: var(--radius-sm) !important; font-family: 'Outfit', sans-serif !important; font-size: 13px !important; }
    .select2-container--default .select2-results__option--highlighted { background: var(--accent) !important; }

    /* ── HINT TIP ── */
    .kb-tip {
        background: var(--amber-bg); border: 1px solid var(--amber-bd);
        border-radius: var(--radius-sm); padding: 9px 13px;
        font-size: 12px; color: #92400e;
    }
    .kb-tip i { margin-right: 5px; }

    /* SALDO COLORS */
    #saldoSoles.rojo, #saldoDolares.rojo { color: var(--danger) !important; font-weight: 700; }
    #saldoSoles.verde, #saldoDolares.verde { color: var(--green) !important; font-weight: 700; }
    </style>
</head>
<body>

<?php include '../../sidebar.php'; ?>

<div class="kb-content">

    <!-- ── Header ─────────────────────────────────────────── -->
    <div class="page-header">
        <div>
            <div class="page-title">Agregar operación</div>
            <div class="page-sub">Registra tours, pagos y comisión</div>
        </div>
        <a href="index.php" class="page-back"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>

    <div class="client-chip">
        <i class="fas fa-user"></i>
        <?= htmlspecialchars($cliente['nombre_completo']) ?>
        <?php if ($id_grupo): ?>
            &nbsp;·&nbsp; <i class="fas fa-users" style="font-size:12px;"></i> Grupo #<?= $id_grupo ?>
        <?php endif; ?>
    </div>

    <?php if (!empty($error_msg)): ?>
    <div class="kb-alert error">
        <i class="fas fa-exclamation-circle"></i>
        <div><?= htmlspecialchars($error_msg) ?></div>
    </div>
    <?php endif; ?>

    <form method="POST" id="formOp" novalidate>

        <!-- ════════════════ DATOS GENERALES ════════════════ -->
        <div class="section-card">
            <div class="section-header">
                <div class="sh-icon sh-blue"><i class="fas fa-clipboard-list"></i></div>
                <div><h5>Datos generales</h5><small>Reserva, encargado y tipo de precio</small></div>
            </div>
            <div class="section-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="kb-label">Fecha de reserva</label>
                        <input type="date" name="fecha_reserva[]" class="kb-input" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="kb-label">Encargado</label>
                        <div style="position:relative;">
                            <i class="fas fa-user" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--sub);font-size:14px;"></i>
                            <input type="text" name="Encargado[]" class="kb-input" style="padding-left:34px;" placeholder="Nombre del encargado">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="kb-label">Tipo de precio</label>
                        <select name="tipo_precio" id="tipo_precio" class="kb-input kb-select">
                            <option value="por_tour">Por tour (automático)</option>
                            <option value="total">Total fijo</option>
                        </select>
                    </div>
                    <div class="col-md-3" id="total-fijo-wrap" style="display:none;">
                        <label class="kb-label">Total fijo</label>
                        <div style="position:relative;">
                            <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--sub);font-size:13px;">S/</span>
                            <input type="number" step="0.01" name="total_operacion" id="total_operacion_input" class="kb-input" style="padding-left:32px;" placeholder="0.00">
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="kb-label">Observaciones</label>
                        <textarea name="observaciones[]" class="kb-input" rows="2" placeholder="Notas internas, indicaciones especiales…"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- ════════════════ TOURS ════════════════ -->
        <div class="section-card">
            <div class="section-header">
                <div class="sh-icon sh-green"><i class="fas fa-map-marked-alt"></i></div>
                <div><h5>Tours del grupo</h5><small>Agrega uno o más servicios a esta operación</small></div>
            </div>
            <div class="kb-table-wrap">
                <table class="kb-table" id="tablaTours">
                    <thead>
                        <tr>
                            <th style="min-width:200px;">Servicio</th>
                            <th style="width:100px;">Precio</th>
                            <th style="width:80px;">Moneda</th>
                            <th style="width:130px;">Salida</th>
                            <th style="width:130px;">Retorno</th>
                            <th style="width:120px;">Modalidad</th>
                            <th style="width:65px;">Ingreso</th>
                            <th style="min-width:200px;">Adicionales</th>
                            <th style="width:44px;"></th>
                        </tr>
                    </thead>
                    <tbody id="bodyTours">
                        <?php echo buildFilaTour(0, $servicios); ?>
                    </tbody>
                </table>
            </div>
            <div class="section-footer">
                <button type="button" class="kb-btn kb-btn-success kb-btn-sm" onclick="agregarFila()">
                    <i class="fas fa-plus"></i> Agregar tour
                </button>
            </div>
        </div>

        <!-- ════════════════ RESUMEN ════════════════ -->
        <div class="section-card">
            <div class="section-header">
                <div class="sh-icon sh-amber"><i class="fas fa-calculator"></i></div>
                <div><h5>Resumen de operación</h5><small>Calculado automáticamente en tiempo real</small></div>
            </div>
            <div class="section-body">

                <div class="divider-label">Totales tours</div>
                <div class="resumen-grid" style="grid-template-columns: repeat(auto-fit, minmax(140px,1fr));">
                    <div class="resumen-box">
                        <div class="r-label">Total S/</div>
                        <div class="r-val"><span id="totalToursSoles">0.00</span> <span class="r-cur">soles</span></div>
                    </div>
                    <div class="resumen-box">
                        <div class="r-label">Total $</div>
                        <div class="r-val"><span id="totalToursDolares">0.00</span> <span class="r-cur">USD</span></div>
                    </div>
                    <div class="resumen-box">
                        <div class="r-label">Pagado tours S/</div>
                        <div class="r-val"><span id="pagadoToursSoles">0.00</span> <span class="r-cur">soles</span></div>
                    </div>
                    <div class="resumen-box">
                        <div class="r-label">Pagado tours $</div>
                        <div class="r-val"><span id="pagadoToursDolares">0.00</span> <span class="r-cur">USD</span></div>
                    </div>
                    <div class="resumen-box" id="box-saldo-s">
                        <div class="r-label">Saldo S/</div>
                        <div class="r-val"><span id="saldoSoles">0.00</span> <span class="r-cur">soles</span></div>
                    </div>
                    <div class="resumen-box" id="box-saldo-d">
                        <div class="r-label">Saldo $</div>
                        <div class="r-val"><span id="saldoDolares">0.00</span> <span class="r-cur">USD</span></div>
                    </div>
                </div>

                <div class="divider-label" style="margin-top:20px;">Adicionales y comisión</div>
                <div class="resumen-grid" style="grid-template-columns: repeat(auto-fit, minmax(140px,1fr));">
                    <div class="resumen-box">
                        <div class="r-label">Pagado adics. S/</div>
                        <div class="r-val"><span id="pagadoAdSoles">0.00</span> <span class="r-cur">soles</span></div>
                    </div>
                    <div class="resumen-box">
                        <div class="r-label">Pagado adics. $</div>
                        <div class="r-val"><span id="pagadoAdDolares">0.00</span> <span class="r-cur">USD</span></div>
                    </div>
                    <div class="resumen-box">
                        <div class="r-label">Comisión</div>
                        <input type="number" step="0.01" name="comision" class="kb-input" placeholder="0.00" style="margin-top:4px;font-size:16px;font-weight:600;">
                    </div>
                </div>

            </div>
        </div>

        <!-- ════════════════ PAGOS ════════════════ -->
        <div class="section-card">
            <div class="section-header">
                <div class="sh-icon sh-cyan"><i class="fas fa-credit-card"></i></div>
                <div><h5>Pagos realizados</h5><small>Registra abonos separando tours y adicionales</small></div>
            </div>
            <div class="kb-table-wrap">
                <table class="kb-table" id="tablaPagos">
                    <thead>
                        <tr>
                            <th style="width:120px;">Tipo</th>
                            <th style="width:140px;">Método</th>
                            <th style="width:80px;">Moneda</th>
                            <th style="width:120px;">Monto</th>
                            <th style="width:140px;">Fecha</th>
                            <th style="width:44px;"></th>
                        </tr>
                    </thead>
                    <tbody id="bodyPagos">
                        <?php echo buildFilaPago(); ?>
                    </tbody>
                </table>
            </div>
            <div class="section-footer d-flex align-items-center gap-3 flex-wrap">
                <button type="button" class="kb-btn kb-btn-success kb-btn-sm" onclick="agregarPago()">
                    <i class="fas fa-plus"></i> Agregar pago
                </button>
                <div class="kb-tip">
                    <i class="fas fa-info-circle"></i>
                    <strong>Tour:</strong> abona al costo del servicio y afecta el saldo.
                    &nbsp;|&nbsp;
                    <strong>Adicional:</strong> bolsa de dormir, bastones, etc. Se registra aparte, no descuenta el saldo del tour.
                </div>
            </div>
        </div>

        <!-- ════════════════ SUBMIT ════════════════ -->
        <button type="submit" class="kb-submit">
            <i class="fas fa-save"></i> Guardar operación
        </button>

    </form>
</div>

<?php
// ─── PHP helpers para renderizar filas ────────────────────────────────────
function buildFilaTour(int $idx, array $servicios): string {
    $opts = '<option value="">— Seleccionar servicio —</option>';
    foreach ($servicios as $s) {
        $opts .= '<option value="' . $s['id_servicio'] . '">' . htmlspecialchars($s['nombre']) . '</option>';
    }
    $adOpts = '<option value="Ninguna">Ninguna</option>
        <option value="Ingreso a Mollepata">Ingreso a Mollepata</option>
        <option value="Bolsa de Dormir">Bolsa de Dormir</option>
        <option value="Bastones">Bastones</option>
        <option value="Hotel">Hotel</option>
        <option value="Montaña Huayna Picchu">Montaña Huayna Picchu</option>
        <option value="Montaña Machu Picchu">Montaña Machu Picchu</option>
        <option value="Trans. Mochilas Playa-Idro">Trans. Mochilas Playa-Idro</option>
        <option value="Trans. Mochilas Hidro-Aguas">Trans. Mochilas Hidro-Aguas</option>';

    return <<<HTML
<tr>
    <td><select name="id_servicio[]" class="kb-input kb-select serv-select">$opts</select></td>
    <td><input type="number" step="0.01" name="precio_tour[]" class="kb-input precio_tour" placeholder="0.00"></td>
    <td>
        <select name="moneda_tour[]" class="kb-input kb-select moneda-tour-sel">
            <option value="Soles">S/</option>
            <option value="Dólares">$</option>
        </select>
    </td>
    <td><input type="date" name="fecha_salida[]"  class="kb-input fecha-salida"></td>
    <td><input type="date" name="fecha_retorno[]" class="kb-input"></td>
    <td>
        <select name="modalidad_retorno[]" class="kb-input kb-select">
            <option value="">—</option>
            <option value="Tren">Tren</option>
            <option value="Carro">Carro</option>
            <option value="Caminata">Caminata</option>
            <option value="Sin retorno">Sin retorno</option>
        </select>
    </td>
    <td>
        <div class="check-wrap">
            <input type="hidden"   name="incluye_ingreso[$idx]" value="NO">
            <input type="checkbox" name="incluye_ingreso[$idx]" value="SI" class="kb-checkbox" title="Incluye ingreso">
        </div>
    </td>
    <td>
        <select name="servicio_adicional[$idx][]" multiple class="adicionales-select">$adOpts</select>
    </td>
    <td>
        <button type="button" class="kb-btn kb-btn-danger kb-btn-xs" onclick="eliminarFila(this)" title="Eliminar">
            <i class="fas fa-times"></i>
        </button>
    </td>
</tr>
HTML;
}

function buildFilaPago(): string {
    $hoy = date('Y-m-d');
    return <<<HTML
<tr>
    <td>
        <select name="tipo_pago[]" class="kb-input kb-select tipo-pago-select">
            <option value="tour">Tour</option>
            <option value="adicional">Adicional</option>
        </select>
    </td>
    <td>
        <select name="metodo_pago_multi[]" class="kb-input kb-select">
            <option value="Efectivo">Efectivo</option>
            <option value="We travel">We travel</option>
            <option value="CULQI">CULQI</option>
            <option value="Izipay">Izipay</option>
            <option value="PAYPAL">PAYPAL</option>
            <option value="Bcp">BCP</option>
            <option value="YAPE">YAPE</option>
        </select>
    </td>
    <td>
        <select name="moneda_multi[]" class="kb-input kb-select">
            <option value="Soles">S/</option>
            <option value="Dólares">$</option>
        </select>
    </td>
    <td><input type="number" step="0.01" name="monto_multi[]" class="kb-input monto-pago" placeholder="0.00"></td>
    <td><input type="date"   name="fecha_multi[]" class="kb-input" value="$hoy"></td>
    <td>
        <button type="button" class="kb-btn kb-btn-danger kb-btn-xs" onclick="eliminarPago(this)" title="Eliminar">
            <i class="fas fa-times"></i>
        </button>
    </td>
</tr>
HTML;
}
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
// ── Duraciones por servicio (desde BD vía PHP) ──────────────────────────
const DURACION = <?= json_encode($duraciones_js) ?>;
const SERVICIOS_HTML = <?= json_encode(buildFilaTour(0, $servicios)) ?>;

let tourIdx = 1;

// ── Inicializar Select2 en fila ─────────────────────────────────────────
function initSelect2Fila(tr) {
    $(tr).find('.adicionales-select').select2({
        placeholder: 'Seleccionar…',
        width: '100%',
        dropdownParent: $('body')
    });
}

// ── Auto fecha retorno ──────────────────────────────────────────────────
document.addEventListener('change', function (e) {
    const el   = e.target;
    const tr   = el.closest('tr');
    if (!tr) return;

    if (el.classList.contains('serv-select') || el.classList.contains('fecha-salida')) {
        const servId = tr.querySelector('.serv-select').value;
        const salida = tr.querySelector('.fecha-salida').value;
        const retEl  = tr.querySelector('[name="fecha_retorno[]"]');
        if (servId && salida && DURACION[servId]) {
            const d = new Date(salida);
            d.setDate(d.getDate() + DURACION[servId] - 1);
            retEl.value = d.toISOString().split('T')[0];
        }
    }
    actualizarResumen();
});

document.addEventListener('input', function (e) {
    if (e.target.classList.contains('precio_tour') || e.target.classList.contains('monto-pago')) {
        actualizarResumen();
    }
});

// ── Tipo de precio toggle ───────────────────────────────────────────────
document.getElementById('tipo_precio').addEventListener('change', function () {
    document.getElementById('total-fijo-wrap').style.display = this.value === 'total' ? '' : 'none';
});

// ── Calcular totales tours ──────────────────────────────────────────────
function calcularTours() {
    let s = 0, d = 0;
    document.querySelectorAll('#bodyTours tr').forEach(tr => {
        const precio = parseFloat(tr.querySelector('.precio_tour').value) || 0;
        const moneda = tr.querySelector('[name="moneda_tour[]"]').value;
        moneda === 'Soles' ? (s += precio) : (d += precio);
    });
    return { s, d };
}

// ── Calcular pagos ──────────────────────────────────────────────────────
function calcularPagos() {
    let ts = 0, td = 0, as_ = 0, ad = 0;
    document.querySelectorAll('#bodyPagos tr').forEach(tr => {
        const tipo   = tr.querySelector('[name="tipo_pago[]"]').value;
        const moneda = tr.querySelector('[name="moneda_multi[]"]').value;
        const monto  = parseFloat(tr.querySelector('.monto-pago').value) || 0;
        const esSoles = moneda === 'Soles';
        if (tipo === 'tour') { esSoles ? (ts += monto) : (td += monto); }
        else                 { esSoles ? (as_ += monto) : (ad += monto); }
    });
    return { ts, td, as_, ad };
}

// ── Actualizar resumen visual ───────────────────────────────────────────
function actualizarResumen() {
    const t = calcularTours();
    const p = calcularPagos();
    const ss = t.s - p.ts;
    const sd = t.d - p.td;

    setText('totalToursSoles',   t.s.toFixed(2));
    setText('totalToursDolares', t.d.toFixed(2));
    setText('pagadoToursSoles',   p.ts.toFixed(2));
    setText('pagadoToursDolares', p.td.toFixed(2));
    setText('saldoSoles',   ss.toFixed(2));
    setText('saldoDolares', sd.toFixed(2));
    setText('pagadoAdSoles',   p.as_.toFixed(2));
    setText('pagadoAdDolares', p.ad.toFixed(2));

    colorSaldo('saldoSoles',   'box-saldo-s', ss);
    colorSaldo('saldoDolares', 'box-saldo-d', sd);

    document.getElementById('total_operacion_input').value = t.s.toFixed(2);
}

function setText(id, val) {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
}

function colorSaldo(spanId, boxId, val) {
    const span = document.getElementById(spanId);
    const box  = document.getElementById(boxId);
    if (!span || !box) return;
    span.className = val > 0.01 ? 'rojo' : (val < -0.01 ? 'verde' : '');
    box.className  = 'resumen-box ' + (val > 0.01 ? 'saldo-rojo' : (val < -0.01 ? 'saldo-verde' : 'saldo-cero'));
}

// ── Reindexar filas ─────────────────────────────────────────────────────
function reindexarFilas() {
    document.querySelectorAll('#bodyTours tr').forEach((tr, i) => {
        tr.querySelectorAll('[name^="incluye_ingreso"]').forEach(el => { el.name = `incluye_ingreso[${i}]`; });
        const ad = tr.querySelector('[name^="servicio_adicional"]');
        if (ad) ad.name = `servicio_adicional[${i}][]`;
    });
}

// ── Agregar / eliminar tour ─────────────────────────────────────────────
function agregarFila() {
    const body   = document.getElementById('bodyTours');
    const tmpDiv = document.createElement('tbody');
    tmpDiv.innerHTML = SERVICIOS_HTML.replace(/\[0\]/g, `[${tourIdx++}]`);
    const newTr = tmpDiv.querySelector('tr');
    body.appendChild(newTr);
    initSelect2Fila(newTr);
    reindexarFilas();
    actualizarResumen();
}

function eliminarFila(btn) {
    if (document.querySelectorAll('#bodyTours tr').length === 1) {
        alert('Debe haber al menos un tour.');
        return;
    }
    btn.closest('tr').remove();
    reindexarFilas();
    actualizarResumen();
}

// ── Agregar / eliminar pago ─────────────────────────────────────────────
function agregarPago() {
    const body   = document.getElementById('bodyPagos');
    const tmpDiv = document.createElement('tbody');
    tmpDiv.innerHTML = <?= json_encode(buildFilaPago()) ?>;
    body.appendChild(tmpDiv.querySelector('tr'));
    actualizarResumen();
}

function eliminarPago(btn) {
    if (document.querySelectorAll('#bodyPagos tr').length === 1) {
        alert('Debe haber al menos una fila de pago.');
        return;
    }
    btn.closest('tr').remove();
    actualizarResumen();
}

// ── Validación antes de enviar ──────────────────────────────────────────
document.getElementById('formOp').addEventListener('submit', function (e) {
    const t = calcularTours();
    const p = calcularPagos();
    if (p.ts > t.s + 0.01) {
        alert('El pago en Soles supera el total del tour en Soles.');
        e.preventDefault(); return;
    }
    if (p.td > t.d + 0.01) {
        alert('El pago en Dólares supera el total del tour en Dólares.');
        e.preventDefault();
    }
});

// ── Init ────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    initSelect2Fila(document.querySelector('#bodyTours tr'));
    actualizarResumen();
});
</script>
</body>
</html>