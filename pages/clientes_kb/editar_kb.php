<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: ../index.php");
    exit();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
include('../../conexion.php');

/* ═══════════════════════════════════════════════════════════
   VALIDACIONES INICIALES
═══════════════════════════════════════════════════════════ */
if (!isset($_GET['id_grupo'])) die("Falta el grupo");

$id_grupo   = intval($_GET['id_grupo']);
$id_cliente = isset($_GET['id_cliente']) ? intval($_GET['id_cliente']) : null;
$cliente    = null;
$mensaje    = '';
$tipo_msg   = '';

if ($id_cliente) {
    $stmt = mysqli_prepare($conexion, "
        SELECT d.id_cliente, d.nombre, d.apellido, d.genero,
               d.nro_pasaporte, d.nacionalidad, d.fecha_nacimiento,
               d.telefono, d.comida, d.hotel, d.foto_pasaporte,
               k.tipo_cliente
        FROM datos_clientes d
        JOIN clientes_grupo k ON d.id_cliente = k.id_cliente
        WHERE d.id_cliente = ?
    ");
    mysqli_stmt_bind_param($stmt, "i", $id_cliente);
    mysqli_stmt_execute($stmt);
    $cliente = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if (!$cliente) die("Cliente no encontrado");
}

/* ═══════════════════════════════════════════════════════════
   OBTENER DATOS DEL GRUPO
═══════════════════════════════════════════════════════════ */
$stmt = mysqli_prepare($conexion, "
    SELECT g.id_grupo, g.nombre_grupo, g.cantidad,
           (SELECT COUNT(*) FROM clientes_grupo WHERE id_grupo = g.id_grupo) AS ocupados
    FROM grupos g WHERE g.id_grupo = ?
");
mysqli_stmt_bind_param($stmt, 'i', $id_grupo);
mysqli_stmt_execute($stmt);
$grupo = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
if (!$grupo) die("Grupo no encontrado");

/* ═══════════════════════════════════════════════════════════
   ACTUALIZAR CAPACIDAD DEL GRUPO
═══════════════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'editar_grupo') {
    $nuevaCantidad = intval($_POST['cantidad']);
    if ($nuevaCantidad < $grupo['ocupados']) {
        $mensaje  = "No puedes establecer una capacidad menor a los pasajeros ya registrados (" . $grupo['ocupados'] . ").";
        $tipo_msg = "error";
    } else {
        $stmt = mysqli_prepare($conexion, "UPDATE grupos SET cantidad = ? WHERE id_grupo = ?");
        mysqli_stmt_bind_param($stmt, "ii", $nuevaCantidad, $id_grupo);
        mysqli_stmt_execute($stmt);
        header("Location: editar_kb.php?id_grupo=$id_grupo");
        exit;
    }
}

/* ═══════════════════════════════════════════════════════════
   ACTUALIZAR CLIENTE
═══════════════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'editar_cliente') {

    $nombre           = trim($_POST['nombre']);
    $apellido         = trim($_POST['apellido']);
    $fecha_nacimiento = $_POST['fecha_nacimiento'] ?: null;
    $genero           = $_POST['genero'];
    $nro_pasaporte    = trim($_POST['nro_pasaporte']);
    $nacionalidad     = trim($_POST['nacionalidad']);
    $Comida           = trim($_POST['Comida']);
    $hotel            = trim($_POST['hotel']);
    $telefono         = trim($_POST['telefono']);
    $id_grupo_post    = intval($_POST['id_grupo']);

    // Foto pasaporte
    if (!empty($_FILES['foto_pasaporte']['name'])) {
        if (!empty($_POST['foto_actual']) && file_exists('../../' . $_POST['foto_actual'])) {
            unlink('../../' . $_POST['foto_actual']);
        }
        $carpeta = '../../assets/images/fotos_pasaportes/';
        if (!is_dir($carpeta)) mkdir($carpeta, 0777, true);
        $nombreArchivo = time() . '_' . basename($_FILES['foto_pasaporte']['name']);
        move_uploaded_file($_FILES['foto_pasaporte']['tmp_name'], $carpeta . $nombreArchivo);
        $foto_path = 'assets/images/fotos_pasaportes/' . $nombreArchivo;
    } else {
        $foto_path = $_POST['foto_actual'];
    }

    $stmt1 = mysqli_prepare($conexion, "
        UPDATE datos_clientes
        SET nombre=?, apellido=?, genero=?, nro_pasaporte=?, nacionalidad=?, comida=?
        WHERE id_cliente=?
    ");
    mysqli_stmt_bind_param($stmt1, "ssssssi",
        $nombre, $apellido, $genero, $nro_pasaporte, $nacionalidad, $Comida, $id_cliente);
    mysqli_stmt_execute($stmt1);

    $stmt2 = mysqli_prepare($conexion, "
        UPDATE datos_clientes
        SET fecha_nacimiento=?, telefono=?, hotel=?, foto_pasaporte=?
        WHERE id_cliente=?
    ");
    mysqli_stmt_bind_param($stmt2, "ssssi",
        $fecha_nacimiento, $telefono, $hotel, $foto_path, $id_cliente);
    mysqli_stmt_execute($stmt2);

    header("Location: index.php");
    exit;
}

/* ═══════════════════════════════════════════════════════════
   AGREGAR CLIENTE AL GRUPO (desde modal)
═══════════════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'agregar_cliente') {
    $id_grupo_post = intval($_POST['id_grupo']);

    $cap  = mysqli_fetch_assoc(mysqli_query($conexion,
        "SELECT cantidad FROM grupos WHERE id_grupo=$id_grupo_post"))['cantidad'];
    $ocup = mysqli_fetch_assoc(mysqli_query($conexion,
        "SELECT COUNT(*) total FROM clientes_grupo WHERE id_grupo=$id_grupo_post"))['total'];

    if ($ocup >= $cap) {
        $mensaje  = "El grupo está lleno. No se puede agregar más clientes.";
        $tipo_msg = "error";
    } else {
        $nombre       = trim($_POST['nombre']);
        $apellido     = trim($_POST['apellido']);
        $genero       = $_POST['genero'];
        $pasaporte    = trim($_POST['nro_pasaporte']);
        $nacionalidad = trim($_POST['nacionalidad']);
        $Comida       = trim($_POST['Comida']);
        $hotel        = trim($_POST['hotel']);
        $telefono     = trim($_POST['telefono']);
        $fecha        = $_POST['fecha_nacimiento'] ?: null;

        $stmt = mysqli_prepare($conexion, "
            INSERT INTO datos_clientes
            (nombre, apellido, genero, nro_pasaporte, nacionalidad, comida, hotel, telefono, fecha_nacimiento)
            VALUES (?,?,?,?,?,?,?,?,?)
        ");
        mysqli_stmt_bind_param($stmt, "sssssssss",
            $nombre, $apellido, $genero, $pasaporte, $nacionalidad, $Comida, $hotel, $telefono, $fecha);
        mysqli_stmt_execute($stmt);

        $id_nuevo = mysqli_insert_id($conexion);
        $tipo     = 'KB';
        $stmt2    = mysqli_prepare($conexion, "
            INSERT INTO clientes_grupo (id_cliente, id_grupo, tipo_cliente) VALUES (?,?,?)
        ");
        mysqli_stmt_bind_param($stmt2, "iis", $id_nuevo, $id_grupo_post, $tipo);
        mysqli_stmt_execute($stmt2);

        header("Location: editar_kb.php?id_cliente=$id_nuevo&id_grupo=$id_grupo");
        exit;
    }
}

/* ═══════════════════════════════════════════════════════════
   CLIENTES DEL GRUPO
═══════════════════════════════════════════════════════════ */
$stmt = mysqli_prepare($conexion, "
    SELECT d.id_cliente, d.nombre, d.apellido, d.genero,
           d.nro_pasaporte, d.nacionalidad, d.fecha_nacimiento,
           d.telefono, d.hotel, d.comida, d.foto_pasaporte
    FROM datos_clientes d
    JOIN clientes_grupo k ON d.id_cliente = k.id_cliente
    WHERE k.id_grupo = ?
    ORDER BY d.apellido, d.nombre
");
mysqli_stmt_bind_param($stmt, 'i', $id_grupo);
mysqli_stmt_execute($stmt);
$clientesGrupo = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Cliente KB – KB Adventures</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">

    <style>
    /* ─── BASE ─────────────────────────────────────────────── */
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
        --success-c: #15803d;
        --success-bg:#f0fdf4;
        --success-bd:#bbf7d0;
        --info-bg:   #eff6ff;
        --info-bd:   #bfdbfe;
        --info-txt:  #1e40af;
        --danger-bg: #fff1f2;
        --danger-bd: #fecdd3;
        --danger-txt:#9f1239;
        --warn-bg:   #fffbeb;
        --warn-bd:   #fde68a;
        --warn-txt:  #92400e;
        --orange-bg: #fff7ed;
        --orange-bd: #fed7aa;
        --orange-txt:#9a3412;
        --radius:    12px;
        --radius-sm: 8px;
    }

    html, body { min-height: 100vh; font-family: 'Outfit', sans-serif; background: var(--bg); color: var(--text); }

    /* ── CONTENT WRAPPER ── */
    .content {
        margin-left: 256px;
        padding: 32px 32px 64px;
        min-height: 100vh;
        transition: margin-left .32s cubic-bezier(.4,0,.2,1);
    }
    body.sidebar-collapsed .content { margin-left: 64px; }
    @media (max-width: 992px) { .content { margin-left: 0 !important; padding: 20px 16px 48px; } }

    /* ── PAGE HEADER ── */
    .page-header {
        display: flex; align-items: flex-start;
        justify-content: space-between; flex-wrap: wrap; gap: 12px;
        margin-bottom: 24px;
    }
    .page-title { font-size: 22px; font-weight: 600; color: var(--text); }
    .page-sub   { font-size: 13px; color: var(--muted); margin-top: 3px; }
    .page-back  {
        display: inline-flex; align-items: center; gap: 7px;
        font-size: 13px; color: var(--muted); text-decoration: none;
        background: var(--surface); border: 1px solid var(--border);
        padding: 7px 14px; border-radius: var(--radius-sm);
        transition: all .15s;
    }
    .page-back:hover { background: var(--accent-lt); color: var(--accent); border-color: var(--info-bd); }

    /* ── FORM CARD ── */
    .form-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        overflow: hidden;
        margin-bottom: 20px;
    }
    .form-card-header {
        display: flex; align-items: center; justify-content: space-between;
        gap: 10px; padding: 14px 20px;
        border-bottom: 1px solid var(--border);
        background: var(--surface2);
    }
    .form-card-header-left { display: flex; align-items: center; gap: 10px; }
    .section-icon {
        width: 32px; height: 32px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-size: 15px; flex-shrink: 0;
    }
    .icon-blue   { background: #dbeafe; color: var(--accent); }
    .icon-indigo { background: #e0e7ff; color: #4338ca; }
    .icon-teal   { background: #ccfbf1; color: #0f766e; }
    .icon-amber  { background: #fef3c7; color: #d97706; }
    .icon-slate  { background: #f1f5f9; color: #475569; }
    .icon-green  { background: #dcfce7; color: #15803d; }
    .form-card-header h5     { font-size: 14px; font-weight: 600; color: var(--text); margin: 0; line-height: 1; }
    .form-card-header small  { font-size: 11px; color: var(--muted); margin-top: 2px; display: block; }
    .form-card-body { padding: 20px 22px; }

    /* ── GRUPO BANNER ── */
    .group-banner {
        display: flex; align-items: center; gap: 14px;
        background: var(--info-bg); border: 1px solid var(--info-bd);
        border-radius: var(--radius); padding: 14px 18px; margin-bottom: 20px;
    }
    .group-banner-icon {
        width: 40px; height: 40px; border-radius: 10px;
        background: var(--accent); color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: 18px; flex-shrink: 0;
    }
    .group-banner-name { font-size: 16px; font-weight: 600; color: var(--info-txt); }
    .group-banner-sub  { font-size: 12px; color: #3b82f6; margin-top: 2px; }
    .group-progress-bar {
        flex: 1; height: 6px; background: #bfdbfe;
        border-radius: 3px; overflow: hidden; min-width: 80px;
    }
    .group-progress-fill { height: 100%; background: var(--accent); border-radius: 3px; }
    .occ-badge {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;
        white-space: nowrap;
    }
    .occ-ok   { background: #dbeafe; color: #1e40af; }
    .occ-warn { background: #fef3c7; color: #92400e; }
    .occ-full { background: #fee2e2; color: #991b1b; }

    /* ── FORM CONTROLS ── */
    .kb-label {
        display: block; font-size: 12px; font-weight: 600; color: var(--muted);
        text-transform: uppercase; letter-spacing: .05em; margin-bottom: 6px;
    }
    .kb-label .req { color: #ef4444; margin-left: 2px; }

    .kb-input, .kb-select {
        width: 100%; background: var(--surface);
        border: 1.5px solid var(--border); border-radius: var(--radius-sm);
        padding: 10px 14px; font-family: 'Outfit', sans-serif;
        font-size: 14px; color: var(--text); outline: none;
        transition: border-color .15s, box-shadow .15s; appearance: none;
    }
    .kb-input:hover, .kb-select:hover  { border-color: var(--border2); }
    .kb-input:focus, .kb-select:focus  {
        border-color: var(--accent);
        box-shadow: 0 0 0 3px rgba(37,99,235,.1);
    }
    .kb-input::placeholder { color: var(--sub); }
    .kb-input:disabled     { background: #f8fafc; color: var(--muted); cursor: not-allowed; }

    .input-with-icon { position: relative; }
    .input-with-icon .kb-input { padding-left: 38px; }
    .input-with-icon .input-icon {
        position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
        font-size: 15px; color: var(--sub); pointer-events: none;
    }

    /* file input */
    .kb-file-label {
        display: flex; align-items: center; gap: 10px;
        border: 1.5px dashed var(--border2); border-radius: var(--radius-sm);
        padding: 12px 16px; cursor: pointer;
        transition: background .15s, border-color .15s; background: var(--surface2);
    }
    .kb-file-label:hover { background: var(--accent-lt); border-color: #93c5fd; }
    .kb-file-label span  { font-size: 13px; color: var(--muted); }
    .kb-file-label i     { font-size: 18px; color: var(--accent); }
    input[type="file"]   { display: none; }
    .file-name-display   { font-size: 12px; color: var(--accent); margin-top: 4px; min-height: 16px; }

    /* foto actual */
    .foto-actual-wrap {
        display: flex; align-items: center; gap: 12px;
        background: var(--surface2); border: 1px solid var(--border);
        border-radius: var(--radius-sm); padding: 10px 14px; margin-bottom: 8px;
    }
    .foto-actual-wrap img { width: 48px; height: 48px; object-fit: cover; border-radius: 6px; }
    .foto-actual-wrap span { font-size: 12px; color: var(--muted); }

    /* Select2 overrides */
    .select2-container--default .select2-selection--single {
        height: 44px !important; border: 1.5px solid var(--border) !important;
        border-radius: var(--radius-sm) !important; background: var(--surface) !important;
        padding: 0 14px !important; display: flex !important; align-items: center !important;
        font-family: 'Outfit', sans-serif !important; font-size: 14px !important; color: var(--text) !important;
    }
    .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: var(--accent) !important; box-shadow: 0 0 0 3px rgba(37,99,235,.1) !important; outline: none !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered { color: var(--text) !important; line-height: 44px !important; padding: 0 !important; }
    .select2-container--default .select2-selection--single .select2-selection__placeholder { color: var(--sub) !important; }
    .select2-container--default .select2-selection--single .select2-selection__arrow { height: 44px !important; right: 10px !important; }
    .select2-dropdown { border: 1.5px solid var(--border2) !important; border-radius: var(--radius-sm) !important; font-family: 'Outfit', sans-serif !important; font-size: 14px !important; box-shadow: 0 8px 24px rgba(0,0,0,.08) !important; }
    .select2-container--default .select2-search--dropdown .select2-search__field { border: 1px solid var(--border2) !important; border-radius: 6px !important; padding: 7px 10px !important; font-family: 'Outfit', sans-serif !important; font-size: 13px !important; outline: none !important; }
    .select2-container--default .select2-results__option--highlighted { background: var(--accent) !important; }

    /* ── BUTTONS ── */
    .kb-btn {
        display: inline-flex; align-items: center; gap: 8px;
        padding: 9px 18px; border-radius: var(--radius-sm); border: none;
        font-family: 'Outfit', sans-serif; font-size: 13.5px; font-weight: 500;
        cursor: pointer; text-decoration: none; transition: filter .15s, transform .1s;
        white-space: nowrap;
    }
    .kb-btn:active { transform: scale(.97); }
    .kb-btn:hover  { filter: brightness(1.1); }
    .kb-btn-primary { background: var(--accent); color: #fff; }
    .kb-btn-success { background: #166534; color: #86efac; }
    .kb-btn-amber   { background: #d97706; color: #fff; }
    .kb-btn-outline { background: var(--surface); color: var(--muted); border: 1.5px solid var(--border); }
    .kb-btn-outline:hover { background: var(--accent-lt); color: var(--accent); border-color: var(--info-bd); }
    .kb-btn-sm { padding: 6px 12px; font-size: 12px; }

    .kb-submit {
        width: 100%; padding: 13px; background: var(--accent); color: #fff;
        border: none; border-radius: var(--radius-sm);
        font-family: 'Outfit', sans-serif; font-size: 15px; font-weight: 600;
        cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 9px;
        transition: background .15s, transform .1s; letter-spacing: .01em;
    }
    .kb-submit:hover  { background: var(--accent-h); }
    .kb-submit:active { transform: scale(.98); }

    /* ── ALERTS ── */
    .kb-alert {
        display: flex; align-items: flex-start; gap: 12px;
        padding: 14px 18px; border-radius: var(--radius);
        font-size: 13.5px; margin-bottom: 20px;
    }
    .kb-alert i { font-size: 18px; flex-shrink: 0; margin-top: 1px; }
    .kb-alert.success { background: var(--success-bg); border: 1px solid var(--success-bd); color: #14532d; }
    .kb-alert.info    { background: var(--info-bg);    border: 1px solid var(--info-bd);    color: var(--info-txt); }
    .kb-alert.error   { background: var(--danger-bg);  border: 1px solid var(--danger-bd);  color: var(--danger-txt); }

    /* ── TABLA CLIENTES DEL GRUPO ── */
    .group-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .group-table thead tr { background: #f1f5f9; }
    .group-table th {
        padding: 10px 14px; text-align: left;
        font-size: 11px; font-weight: 600; color: var(--muted);
        text-transform: uppercase; letter-spacing: .06em;
        border-bottom: 1px solid var(--border); white-space: nowrap;
    }
    .group-table td {
        padding: 11px 14px; border-bottom: 1px solid #f1f5f9;
        color: var(--text); vertical-align: middle;
    }
    .group-table tbody tr:hover  { background: #f8faff; }
    .group-table tbody tr.active-row td { background: #eff6ff; }
    .group-table tbody tr:last-child td { border-bottom: none; }
    .table-avatar {
        width: 30px; height: 30px; border-radius: 50%;
        background: linear-gradient(135deg,#3b82f6,#1d4ed8);
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 11px; font-weight: 600; color: #fff; flex-shrink: 0;
        text-transform: uppercase;
    }
    .client-name-cell { display: flex; align-items: center; gap: 9px; }
    .badge-m { background: #dbeafe; color: #1e40af; padding: 2px 8px; border-radius: 5px; font-size: 11px; font-weight: 600; }
    .badge-f { background: #fce7f3; color: #9d174d; padding: 2px 8px; border-radius: 5px; font-size: 11px; font-weight: 600; }
    .passport-mono { font-family: monospace; font-size: 12px; color: var(--muted); letter-spacing: .4px; }
    .empty-state { text-align: center; padding: 32px 16px; color: var(--sub); }
    .empty-state i { font-size: 28px; margin-bottom: 8px; display: block; }

    /* ── MODAL ── */
    .modal-content { border: 1px solid var(--border) !important; border-radius: var(--radius) !important; background: var(--surface) !important; }
    .modal-header  { background: var(--surface2) !important; border-bottom: 1px solid var(--border) !important; padding: 16px 20px !important; }
    .modal-title   { font-size: 15px !important; font-weight: 600 !important; color: var(--text) !important; font-family: 'Outfit', sans-serif !important; }
    .modal-body    { padding: 20px !important; }
    .modal-footer  { border-top: 1px solid var(--border) !important; padding: 14px 20px !important; }
    .modal-header .btn-close { opacity: .5; }

    /* format hint */
    .info-tip {
        background: var(--info-bg); border: 1px solid var(--info-bd);
        border-radius: var(--radius-sm); padding: 10px 14px;
        font-size: 12px; color: var(--info-txt); margin-top: 16px;
    }
    .info-tip i { margin-right: 5px; }
    </style>
</head>
<body>

<?php include('../sidebar.php'); ?>
<div class="kb-content">

    <!-- ── Header ─────────────────────────────────────────── -->
    <div class="page-header">
        <div>
            <div class="page-title">
                <?= $id_cliente ? 'Editar cliente' : 'Gestionar grupo' ?>
            </div>
            <div class="page-sub">
                <?= $id_cliente
                    ? htmlspecialchars(($cliente['nombre'] ?? '') . ' ' . ($cliente['apellido'] ?? ''))
                    : 'Grupo ' . htmlspecialchars($grupo['nombre_grupo']) ?>
            </div>
        </div>
        <a href="index.php" class="page-back">
            <i class="fas fa-arrow-left"></i> Volver a la lista
        </a>
    </div>

    <?php if ($mensaje): ?>
    <div class="kb-alert <?= $tipo_msg ?>">
        <i class="fas <?= $tipo_msg === 'success' ? 'fa-check-circle' : ($tipo_msg === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle') ?>"></i>
        <div><?= $mensaje ?></div>
    </div>
    <?php endif; ?>

    <!-- ── Banner del grupo ─────────────────────────────── -->
    <?php
    $pct = $grupo['cantidad'] > 0 ? round(($grupo['ocupados'] / $grupo['cantidad']) * 100) : 0;
    $occ_class = $pct >= 100 ? 'occ-full' : ($pct >= 80 ? 'occ-warn' : 'occ-ok');
    ?>
    <div class="group-banner">
        <div class="group-banner-icon"><i class="fas fa-users"></i></div>
        <div style="flex:1;">
            <div class="group-banner-name"><?= htmlspecialchars($grupo['nombre_grupo']) ?></div>
            <div class="group-banner-sub">
                <?= $grupo['ocupados'] ?> de <?= $grupo['cantidad'] ?> pasajeros registrados
            </div>
            <div class="group-progress-bar" style="margin-top:8px;">
                <div class="group-progress-fill" style="width:<?= min($pct,100) ?>%;"></div>
            </div>
        </div>
        <span class="occ-badge <?= $occ_class ?>">
            <i class="fas fa-circle" style="font-size:7px;"></i>
            <?= $pct ?>% ocupado
        </span>
    </div>

    <!-- ── Capacidad del grupo ──────────────────────────── -->
    <div class="form-card">
        <div class="form-card-header">
            <div class="form-card-header-left">
                <div class="section-icon icon-amber"><i class="fas fa-sliders-h"></i></div>
                <div>
                    <h5>Configuración del grupo</h5>
                    <small>Ajusta la capacidad y agrega nuevos pasajeros</small>
                </div>
            </div>
        </div>
        <div class="form-card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="kb-label">Nombre del grupo</label>
                    <div class="input-with-icon">
                        <i class="fas fa-layer-group input-icon"></i>
                        <input type="text" class="kb-input"
                               value="<?= htmlspecialchars($grupo['nombre_grupo']) ?>" disabled>
                    </div>
                </div>
                <div class="col-md-4">
                    <form method="POST" id="form-grupo">
                        <input type="hidden" name="accion" value="editar_grupo">
                        <label class="kb-label">Capacidad del grupo</label>
                        <div class="input-with-icon">
                            <i class="fas fa-users input-icon"></i>
                            <input type="number" name="cantidad" class="kb-input"
                                   min="<?= $grupo['ocupados'] ?>"
                                   value="<?= $grupo['cantidad'] ?>" required>
                        </div>
                    </form>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" form="form-grupo" class="kb-btn kb-btn-amber" style="flex:1;">
                        <i class="fas fa-sync-alt"></i> Actualizar capacidad
                    </button>
                    <button class="kb-btn kb-btn-success" data-bs-toggle="modal" data-bs-target="#modalAgregarCliente">
                        <i class="fas fa-user-plus"></i> Agregar cliente
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Editar cliente ───────────────────────────────── -->
    <?php if ($id_cliente && $cliente): ?>
    <div class="form-card">
        <div class="form-card-header">
            <div class="form-card-header-left">
                <div class="section-icon icon-blue"><i class="fas fa-user-edit"></i></div>
                <div>
                    <h5>Editar datos del cliente</h5>
                    <small>ID #<?= $id_cliente ?> · <?= htmlspecialchars($cliente['nombre'] . ' ' . $cliente['apellido']) ?></small>
                </div>
            </div>
        </div>
        <div class="form-card-body">
            <form method="POST" enctype="multipart/form-data" novalidate>
                <input type="hidden" name="accion" value="editar_cliente">
                <input type="hidden" name="foto_actual" value="<?= htmlspecialchars($cliente['foto_pasaporte'] ?? '') ?>">
                <input type="hidden" name="id_grupo" value="<?= $grupo['id_grupo'] ?>">

                <!-- Datos personales -->
                <p style="font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:14px;">
                    <i class="fas fa-user" style="margin-right:6px;color:var(--accent);"></i>Datos personales
                </p>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="kb-label">Nombre <span class="req">*</span></label>
                        <div class="input-with-icon">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" name="nombre" class="kb-input" required
                                   value="<?= htmlspecialchars($cliente['nombre'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="kb-label">Apellido <span class="req">*</span></label>
                        <div class="input-with-icon">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" name="apellido" class="kb-input" required
                                   value="<?= htmlspecialchars($cliente['apellido'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="kb-label">Género</label>
                        <select name="genero" class="kb-input kb-select">
                            <option value="M" <?= ($cliente['genero'] ?? '') === 'M' ? 'selected' : '' ?>>Masculino</option>
                            <option value="F" <?= ($cliente['genero'] ?? '') === 'F' ? 'selected' : '' ?>>Femenino</option>
                            <option value="Otro" <?= ($cliente['genero'] ?? '') === 'Otro' ? 'selected' : '' ?>>Otro</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="kb-label">Fecha de nacimiento</label>
                        <div class="input-with-icon">
                            <i class="fas fa-calendar input-icon"></i>
                            <input type="date" name="fecha_nacimiento" class="kb-input"
                                   value="<?= htmlspecialchars($cliente['fecha_nacimiento'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="kb-label">Teléfono / WhatsApp</label>
                        <div class="input-with-icon">
                            <i class="fab fa-whatsapp input-icon"></i>
                            <input type="text" name="telefono" class="kb-input"
                                   placeholder="+51 987 654 321"
                                   value="<?= htmlspecialchars($cliente['telefono'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <!-- Documentos -->
                <p style="font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:14px;">
                    <i class="fas fa-passport" style="margin-right:6px;color:#4338ca;"></i>Documentos
                </p>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="kb-label">Número de pasaporte</label>
                        <div class="input-with-icon">
                            <i class="fas fa-id-card input-icon"></i>
                            <input type="text" name="nro_pasaporte" class="kb-input"
                                   style="font-family:monospace;letter-spacing:.05em;"
                                   value="<?= htmlspecialchars($cliente['nro_pasaporte'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="kb-label">Foto del pasaporte</label>
                        <?php if (!empty($cliente['foto_pasaporte'])): ?>
                        <div class="foto-actual-wrap">
                            <img src="../../<?= htmlspecialchars($cliente['foto_pasaporte']) ?>"
                                 alt="Foto actual" onerror="this.style.display='none'">
                            <span>Foto actual guardada</span>
                        </div>
                        <?php endif; ?>
                        <label class="kb-file-label" for="foto_edit_input">
                            <i class="fas fa-camera"></i>
                            <span id="foto-edit-label-text">
                                <?= empty($cliente['foto_pasaporte']) ? 'Subir foto (JPG, PNG)' : 'Reemplazar foto' ?>
                            </span>
                        </label>
                        <input type="file" id="foto_edit_input" name="foto_pasaporte" accept=".jpg,.jpeg,.png">
                        <div class="file-name-display" id="foto-edit-name"></div>
                    </div>
                </div>

                <!-- Info adicional -->
                <p style="font-size:11px;font-weight:600;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:14px;">
                    <i class="fas fa-hotel" style="margin-right:6px;color:#0f766e;"></i>Información adicional
                </p>
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="kb-label">Nacionalidad</label>
                        <select name="nacionalidad" id="nacionalidad" class="kb-select"></select>
                    </div>
                    <div class="col-md-4">
                        <label class="kb-label">Restricción / comida</label>
                        <div class="input-with-icon">
                            <i class="fas fa-utensils input-icon"></i>
                            <input type="text" name="Comida" class="kb-input"
                                   placeholder="Vegetariano, Sin gluten…"
                                   value="<?= htmlspecialchars($cliente['comida'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="kb-label">Hotel</label>
                        <div class="input-with-icon">
                            <i class="fas fa-building input-icon"></i>
                            <input type="text" name="hotel" class="kb-input"
                                   value="<?= htmlspecialchars($cliente['hotel'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 justify-content-end">
                    <a href="index.php" class="kb-btn kb-btn-outline">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                    <button type="submit" class="kb-btn kb-btn-primary" style="min-width:180px;">
                        <i class="fas fa-save"></i> Guardar cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- ── Clientes del grupo ───────────────────────────── -->
    <div class="form-card">
        <div class="form-card-header">
            <div class="form-card-header-left">
                <div class="section-icon icon-slate"><i class="fas fa-list-ul"></i></div>
                <div>
                    <h5>Pasajeros del grupo</h5>
                    <small><?= $grupo['ocupados'] ?> de <?= $grupo['cantidad'] ?> registrados</small>
                </div>
            </div>
        </div>
        <div style="overflow-x:auto;">
            <table class="group-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Pasajero</th>
                        <th>Género</th>
                        <th>Pasaporte</th>
                        <th>Nacionalidad</th>
                        <th>WhatsApp</th>
                        <th>Comida</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $i = 1;
                $hayClientes = false;
                while ($c = mysqli_fetch_assoc($clientesGrupo)):
                    $hayClientes = true;
                    $iniciales   = strtoupper(substr($c['nombre'],0,1) . substr($c['apellido'],0,1));
                    $esActual    = ($id_cliente == $c['id_cliente']);
                ?>
                <tr class="<?= $esActual ? 'active-row' : '' ?>">
                    <td style="color:var(--sub);font-size:12px;"><?= $i++ ?></td>
                    <td>
                        <div class="client-name-cell">
                            <div class="table-avatar"><?= $iniciales ?></div>
                            <span style="font-weight:500;">
                                <?= htmlspecialchars($c['nombre'] . ' ' . $c['apellido']) ?>
                                <?php if ($esActual): ?>
                                    <span style="font-size:10px;background:#dbeafe;color:#1e40af;padding:2px 6px;border-radius:4px;margin-left:4px;font-weight:600;">editando</span>
                                <?php endif; ?>
                            </span>
                        </div>
                    </td>
                    <td>
                        <?php if ($c['genero'] === 'M'): ?>
                            <span class="badge-m">M</span>
                        <?php elseif ($c['genero'] === 'F'): ?>
                            <span class="badge-f">F</span>
                        <?php else: ?>
                            <span style="color:var(--sub);font-size:12px;">—</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="passport-mono"><?= htmlspecialchars($c['nro_pasaporte'] ?? '—') ?></span></td>
                    <td style="color:var(--muted);font-size:13px;"><?= htmlspecialchars($c['nacionalidad'] ?? '—') ?></td>
                    <td style="color:var(--muted);font-size:13px;"><?= htmlspecialchars($c['telefono'] ?? '—') ?></td>
                    <td style="color:var(--muted);font-size:13px;">
                        <?= !empty($c['comida']) ? htmlspecialchars($c['comida']) : '<span style="color:var(--sub)">—</span>' ?>
                    </td>
                    <td>
                        <a href="editar_kb.php?id_grupo=<?= $id_grupo ?>&id_cliente=<?= $c['id_cliente'] ?>"
                           class="kb-btn kb-btn-primary kb-btn-sm">
                            <i class="fas fa-pen"></i> Editar
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if (!$hayClientes): ?>
                <tr>
                    <td colspan="8">
                        <div class="empty-state">
                            <i class="fas fa-users-slash"></i>
                            No hay pasajeros registrados en este grupo aún.
                        </div>
                    </td>
                </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- /content -->

<!-- ═══════════════════════════════════════════════════════════
     MODAL AGREGAR CLIENTE
═══════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalAgregarCliente" tabindex="-1"
     aria-labelledby="modalAgregarLabel" aria-modal="true" role="dialog">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <form method="POST" enctype="multipart/form-data" class="modal-content">
      <input type="hidden" name="accion"   value="agregar_cliente">
      <input type="hidden" name="id_grupo" value="<?= $id_grupo ?>">

      <div class="modal-header">
        <h5 class="modal-title" id="modalAgregarLabel">
            <i class="fas fa-user-plus me-2" style="color:var(--accent);"></i>
            Agregar cliente al grupo
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">

        <div class="row g-3">
            <div class="col-md-6">
                <label class="kb-label">Nombre <span class="req">*</span></label>
                <input type="text" name="nombre" class="kb-input" placeholder="Nombre" required>
            </div>
            <div class="col-md-6">
                <label class="kb-label">Apellido <span class="req">*</span></label>
                <input type="text" name="apellido" class="kb-input" placeholder="Apellido" required>
            </div>
            <div class="col-md-4">
                <label class="kb-label">Género</label>
                <select name="genero" class="kb-input kb-select" required>
                    <option value="">— Seleccionar —</option>
                    <option value="M">Masculino</option>
                    <option value="F">Femenino</option>
                    <option value="Otro">Otro</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="kb-label">Nro. Pasaporte <span class="req">*</span></label>
                <input type="text" name="nro_pasaporte" class="kb-input"
                       placeholder="PE123456" required
                       style="font-family:monospace;letter-spacing:.04em;">
            </div>
            <div class="col-md-4">
                <label class="kb-label">Fecha de nacimiento</label>
                <input type="date" name="fecha_nacimiento" class="kb-input">
            </div>
            <div class="col-md-4">
                <label class="kb-label">Teléfono / WhatsApp</label>
                <input type="text" name="telefono" class="kb-input" placeholder="+51 987 654">
            </div>
            <div class="col-md-4">
                <label class="kb-label">Nacionalidad</label>
                <select name="nacionalidad" id="nacionalidad_modal" class="kb-select">
                    <option value="">Seleccionar país</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="kb-label">Restricción comida</label>
                <input type="text" name="Comida" class="kb-input" placeholder="Vegetariano…">
            </div>
            <div class="col-md-6">
                <label class="kb-label">Hotel</label>
                <input type="text" name="hotel" class="kb-input" placeholder="Nombre del hotel">
            </div>
            <div class="col-md-6">
                <label class="kb-label">Foto pasaporte</label>
                <label class="kb-file-label" for="foto_modal_input">
                    <i class="fas fa-camera"></i>
                    <span id="modal-file-text">JPG o PNG del pasaporte</span>
                </label>
                <input type="file" id="foto_modal_input" name="foto_pasaporte" accept=".jpg,.jpeg,.png">
                <div class="file-name-display" id="modal-file-name"></div>
            </div>
        </div>

        <div class="info-tip">
            <i class="fas fa-info-circle"></i>
            El cliente se asignará automáticamente al grupo <strong><?= htmlspecialchars($grupo['nombre_grupo']) ?></strong>.
            Capacidad disponible: <strong><?= $grupo['cantidad'] - $grupo['ocupados'] ?></strong> lugar(es).
        </div>

      </div>

      <div class="modal-footer" style="gap:8px;">
        <button type="button" class="kb-btn kb-btn-outline" data-bs-dismiss="modal">
            <i class="fas fa-times"></i> Cancelar
        </button>
        <button type="submit" class="kb-btn kb-btn-success">
            <i class="fas fa-user-plus"></i> Guardar cliente
        </button>
      </div>
    </form>
  </div>

</div>
<!-- ── Scripts ─────────────────────────────────────────── -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
const PAIS_ACTUAL = <?= json_encode($cliente['nacionalidad'] ?? null) ?>;

// ── Select2 formulario principal ─────────────────────────────────────────
$.ajax({
    url: '../../assets/json/paises.json',
    dataType: 'json',
    success: function (data) {
        const $sel = $('#nacionalidad');
        $sel.empty();
        $sel.append('<option value="">Seleccionar país</option>');

        if (PAIS_ACTUAL) {
            $sel.append($('<option>', { value: PAIS_ACTUAL, text: PAIS_ACTUAL, selected: true }));
        }
        data.forEach(function (pais) {
            if (pais !== PAIS_ACTUAL) {
                $sel.append($('<option>', { value: pais, text: pais }));
            }
        });
        $sel.select2({ placeholder: 'Seleccionar país', width: '100%' });
    }
});

// ── Select2 en modal (carga diferida) ────────────────────────────────────
$('#modalAgregarCliente').on('shown.bs.modal', function () {
    const $sel = $('#nacionalidad_modal');
    if ($sel.children().length > 1) return;

    $.getJSON('../../assets/json/paises.json', function (data) {
        data.forEach(function (pais) {
            $sel.append($('<option>', { value: pais, text: pais }));
        });
        $sel.select2({
            dropdownParent: $('#modalAgregarCliente'),
            width: '100%',
            placeholder: 'Seleccionar país'
        });
    });
});

// ── Nombre de archivo foto (editar) ─────────────────────────────────────
document.getElementById('foto_edit_input').addEventListener('change', function () {
    const name = this.files[0]?.name || '';
    if (name) {
        document.getElementById('foto-edit-label-text').textContent = name;
        document.getElementById('foto-edit-name').textContent = '✓ Lista para subir';
    }
});

// ── Nombre de archivo foto (modal) ───────────────────────────────────────
document.getElementById('foto_modal_input').addEventListener('change', function () {
    const name = this.files[0]?.name || '';
    if (name) {
        document.getElementById('modal-file-text').textContent = name;
        document.getElementById('modal-file-name').textContent = '✓ Lista para subir';
    }
});
</script>

</body>
</html>