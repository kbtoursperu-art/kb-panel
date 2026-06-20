<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: ../index.php");
    exit();
}
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

/* ── Obtener cliente con datos empresa ───────────────────── */
if ($id_cliente) {
    $stmt = mysqli_prepare($conexion, "
        SELECT d.*,
               cg.id_grupo        AS cg_id_grupo,
               cg.tipo_cliente,
               cg.empresa_endosadora,
               cg.contacto,
               cg.telefono_contacto,
               cg.email_contacto
        FROM datos_clientes d
        LEFT JOIN clientes_grupo cg
               ON d.id_cliente = cg.id_cliente AND cg.tipo_cliente = 'ENDOSADOR'
        WHERE d.id_cliente = ?
    ");
    mysqli_stmt_bind_param($stmt, "i", $id_cliente);
    mysqli_stmt_execute($stmt);
    $cliente = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    if (!$cliente) die("El cliente no existe en datos_clientes");
}

/* ── Obtener datos del grupo ────────────────────────────── */
$stmt = mysqli_prepare($conexion, "
    SELECT g.id_grupo, g.nombre_grupo, g.cantidad,
           (SELECT COUNT(*) FROM clientes_grupo
            WHERE id_grupo = g.id_grupo AND tipo_cliente = 'ENDOSADOR') AS ocupados
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
        $mensaje  = "No puedes establecer una capacidad menor a los pasajeros registrados (" . $grupo['ocupados'] . ").";
        $tipo_msg = "error";
    } else {
        $stmt = mysqli_prepare($conexion, "UPDATE grupos SET cantidad = ? WHERE id_grupo = ?");
        mysqli_stmt_bind_param($stmt, "ii", $nuevaCantidad, $id_grupo);
        mysqli_stmt_execute($stmt);
        header("Location: editar_endosador.php?id_grupo=$id_grupo");
        exit;
    }
}

/* ═══════════════════════════════════════════════════════════
   EDITAR CLIENTE
═══════════════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'editar_cliente') {

    $nombre        = trim($_POST['nombre']         ?? '');
    $apellido      = trim($_POST['apellido']        ?? '');
    $genero        = $_POST['genero']               ?? '';
    $nro_pasaporte = trim($_POST['nro_pasaporte']   ?? '');
    $telefono      = trim($_POST['telefono']        ?? '');
    $empresa       = trim($_POST['empresa']         ?? '');
    $contacto      = trim($_POST['contacto']        ?? '');
    $tel_cont      = trim($_POST['telefono_contacto'] ?? '');
    $email         = trim($_POST['email_contacto']  ?? '');
    $id_grupo_post = intval($_POST['id_grupo']);

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
        $foto_path = $_POST['foto_actual'] ?? '';
    }

    // UPDATE datos_clientes
    $stmt = mysqli_prepare($conexion, "
        UPDATE datos_clientes
        SET nombre=?, apellido=?, genero=?, nro_pasaporte=?, telefono=?, foto_pasaporte=?
        WHERE id_cliente=?
    ");
    mysqli_stmt_bind_param($stmt, "ssssssi",
        $nombre, $apellido, $genero, $nro_pasaporte, $telefono, $foto_path, $id_cliente);
    mysqli_stmt_execute($stmt);

    // Guardar grupo anterior para actualizar contadores
    $qOld = $conexion->prepare("SELECT id_grupo FROM clientes_grupo WHERE id_cliente=? AND tipo_cliente='ENDOSADOR'");
    $qOld->bind_param("i", $id_cliente);
    $qOld->execute();
    $rOld     = $qOld->get_result();
    $oldGrupo = $rOld->num_rows > 0 ? $rOld->fetch_assoc()['id_grupo'] : null;

    // ¿existe relación?
    $check = $conexion->prepare("SELECT id FROM clientes_grupo WHERE id_cliente=? AND tipo_cliente='ENDOSADOR'");
    $check->bind_param("i", $id_cliente);
    $check->execute();

    if ($check->get_result()->num_rows > 0) {
        $u = $conexion->prepare("
            UPDATE clientes_grupo
            SET id_grupo=?, empresa_endosadora=?, contacto=?, telefono_contacto=?, email_contacto=?
            WHERE id_cliente=? AND tipo_cliente='ENDOSADOR'
        ");
        $u->bind_param("issssi", $id_grupo_post, $empresa, $contacto, $tel_cont, $email, $id_cliente);
        $u->execute();
    } else {
        $ins = mysqli_prepare($conexion, "
            INSERT INTO clientes_grupo (id_cliente, id_grupo, tipo_cliente, empresa_endosadora, contacto, telefono_contacto, email_contacto)
            VALUES (?, ?, 'ENDOSADOR', ?, ?, ?, ?)
        ");
        mysqli_stmt_bind_param($ins, "iissss", $id_cliente, $id_grupo_post, $empresa, $contacto, $tel_cont, $email);
        mysqli_stmt_execute($ins);
    }

    header("Location: editar_endosador.php?id_grupo=$id_grupo&id_cliente=$id_cliente&saved=1");
    exit;
}

/* ═══════════════════════════════════════════════════════════
   AGREGAR CLIENTE AL GRUPO
═══════════════════════════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'agregar_cliente') {
    $id_grupo_post = intval($_POST['id_grupo']);

    $stmtCap = $conexion->prepare("SELECT cantidad FROM grupos WHERE id_grupo=?");
    $stmtCap->bind_param("i", $id_grupo_post);
    $stmtCap->execute();
    $cap = $stmtCap->get_result()->fetch_assoc()['cantidad'] ?? 0;

    $stmtOcup = $conexion->prepare("SELECT COUNT(*) total FROM clientes_grupo WHERE id_grupo=? AND tipo_cliente='ENDOSADOR'");
    $stmtOcup->bind_param("i", $id_grupo_post);
    $stmtOcup->execute();
    $ocup = $stmtOcup->get_result()->fetch_assoc()['total'] ?? 0;

    if ($ocup >= $cap) {
        $mensaje  = "El grupo está lleno. No se pueden agregar más clientes.";
        $tipo_msg = "error";
    } else {
        $nombre   = trim($_POST['nombre']         ?? '');
        $apellido = trim($_POST['apellido']        ?? '');
        $genero   = $_POST['genero']               ?? '';
        $pasap    = trim($_POST['nro_pasaporte']   ?? '');
        $telefono = trim($_POST['telefono']        ?? '');
        $empresa  = trim($_POST['empresa']         ?? '');
        $contacto = trim($_POST['contacto']        ?? '');
        $tel_cont = trim($_POST['telefono_contacto'] ?? '');
        $email    = trim($_POST['email_contacto']  ?? '');

        $stmtIns = mysqli_prepare($conexion, "
            INSERT INTO datos_clientes (nombre, apellido, genero, nro_pasaporte, telefono)
            VALUES (?,?,?,?,?)
        ");
        mysqli_stmt_bind_param($stmtIns, "sssss", $nombre, $apellido, $genero, $pasap, $telefono);
        mysqli_stmt_execute($stmtIns);
        $id_nuevo = mysqli_insert_id($conexion);

        $stmtRel = mysqli_prepare($conexion, "
            INSERT INTO clientes_grupo (id_cliente, id_grupo, tipo_cliente, empresa_endosadora, contacto, telefono_contacto, email_contacto)
            VALUES (?, ?, 'ENDOSADOR', ?, ?, ?, ?)
        ");
        mysqli_stmt_bind_param($stmtRel, "iissss", $id_nuevo, $id_grupo_post, $empresa, $contacto, $tel_cont, $email);
        mysqli_stmt_execute($stmtRel);

        header("Location: editar_endosador.php?id_grupo=$id_grupo_post&id_cliente=$id_nuevo");
        exit;
    }
}

/* ── Clientes del grupo ─────────────────────────────────── */
$stmtCG = mysqli_prepare($conexion, "
    SELECT d.id_cliente, d.nombre, d.apellido, d.genero, d.nro_pasaporte,
           d.telefono, cg.empresa_endosadora
    FROM datos_clientes d
    JOIN clientes_grupo cg ON d.id_cliente = cg.id_cliente
    WHERE cg.id_grupo = ? AND cg.tipo_cliente = 'ENDOSADOR'
    ORDER BY d.apellido, d.nombre
");
mysqli_stmt_bind_param($stmtCG, 'i', $id_grupo);
mysqli_stmt_execute($stmtCG);
$clientesGrupo = mysqli_stmt_get_result($stmtCG);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Endosador – KB Adventures</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">

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
        --violet-bg: #f5f3ff;
        --violet-bd: #ddd6fe;
        --violet:    #7c3aed;
        --danger:    #dc2626;
        --danger-bg: #fff1f2;
        --danger-bd: #fecdd3;
        --radius:    12px;
        --radius-sm: 8px;
    }

    html, body { min-height: 100vh; font-family: 'Outfit', sans-serif; background: var(--bg); color: var(--text); }

    /* ── CONTENT ── */
    .content {
        margin-left: 256px; padding: 32px 32px 64px;
        min-height: 100vh; transition: margin-left .32s cubic-bezier(.4,0,.2,1);
    }
    body.sidebar-collapsed .content { margin-left: 64px; }
    @media (max-width: 992px) { .content { margin-left: 0 !important; padding: 20px 16px 48px; } }

    /* ── PAGE HEADER ── */
    .page-header { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 24px; }
    .page-title  { font-size: 22px; font-weight: 600; color: var(--text); }
    .page-sub    { font-size: 13px; color: var(--muted); margin-top: 3px; }
    .page-back   { display: inline-flex; align-items: center; gap: 7px; font-size: 13px; color: var(--muted); text-decoration: none; background: var(--surface); border: 1px solid var(--border); padding: 7px 14px; border-radius: var(--radius-sm); transition: all .15s; }
    .page-back:hover { background: var(--accent-lt); color: var(--accent); border-color: var(--info-bd); }

    /* ── GRUPO BANNER ── */
    .group-banner { display: flex; align-items: center; gap: 14px; background: var(--violet-bg); border: 1px solid var(--violet-bd); border-radius: var(--radius); padding: 14px 18px; margin-bottom: 20px; }
    .group-banner-icon { width: 40px; height: 40px; border-radius: 10px; background: var(--violet); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
    .group-banner-name { font-size: 16px; font-weight: 600; color: var(--violet); }
    .group-banner-sub  { font-size: 12px; color: #7c3aed; margin-top: 2px; }
    .group-progress-bar { flex: 1; height: 6px; background: var(--violet-bd); border-radius: 3px; overflow: hidden; min-width: 80px; }
    .group-progress-fill { height: 100%; background: var(--violet); border-radius: 3px; }
    .occ-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; white-space: nowrap; }
    .occ-ok   { background: var(--info-bg); color: var(--info-txt); }
    .occ-warn { background: var(--amber-bg); color: var(--amber); }
    .occ-full { background: var(--danger-bg); color: var(--danger); }

    /* ── FORM CARD ── */
    .form-card { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; margin-bottom: 20px; }
    .form-card-header { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 14px 20px; border-bottom: 1px solid var(--border); background: var(--surface2); }
    .fch-left { display: flex; align-items: center; gap: 10px; }
    .section-icon { width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 15px; flex-shrink: 0; }
    .icon-blue   { background: #dbeafe; color: var(--accent); }
    .icon-indigo { background: #e0e7ff; color: #4338ca; }
    .icon-teal   { background: #ccfbf1; color: #0f766e; }
    .icon-amber  { background: #fef3c7; color: var(--amber); }
    .icon-violet { background: var(--violet-bg); color: var(--violet); }
    .icon-slate  { background: #f1f5f9; color: #475569; }
    .form-card-header h5    { font-size: 14px; font-weight: 600; color: var(--text); margin: 0; }
    .form-card-header small { font-size: 11px; color: var(--muted); display: block; margin-top: 2px; }
    .form-card-body { padding: 20px 22px; }

    /* ── FORM CONTROLS ── */
    .kb-label { display: block; font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .05em; margin-bottom: 6px; }
    .kb-label .req { color: #ef4444; margin-left: 2px; }
    .kb-input, .kb-select { width: 100%; background: var(--surface); border: 1.5px solid var(--border); border-radius: var(--radius-sm); padding: 10px 14px; font-family: 'Outfit', sans-serif; font-size: 14px; color: var(--text); outline: none; transition: border-color .15s, box-shadow .15s; appearance: none; }
    .kb-input:hover, .kb-select:hover { border-color: var(--border2); }
    .kb-input:focus, .kb-select:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(37,99,235,.1); }
    .kb-input::placeholder { color: var(--sub); }
    .kb-input:disabled { background: var(--surface2); color: var(--muted); cursor: not-allowed; }

    .input-icon-wrap { position: relative; }
    .input-icon-wrap .kb-input { padding-left: 38px; }
    .input-icon-wrap .field-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); font-size: 15px; color: var(--sub); pointer-events: none; }

    /* file */
    .kb-file-label { display: flex; align-items: center; gap: 10px; border: 1.5px dashed var(--border2); border-radius: var(--radius-sm); padding: 12px 16px; cursor: pointer; transition: background .15s, border-color .15s; background: var(--surface2); }
    .kb-file-label:hover { background: var(--accent-lt); border-color: #93c5fd; }
    .kb-file-label i { font-size: 18px; color: var(--accent); }
    .kb-file-label span { font-size: 13px; color: var(--muted); }
    input[type="file"] { display: none; }
    .file-name-display { font-size: 12px; color: var(--accent); margin-top: 4px; min-height: 16px; }

    /* foto actual */
    .foto-actual-wrap { display: flex; align-items: center; gap: 12px; background: var(--surface2); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 10px 14px; margin-bottom: 8px; }
    .foto-actual-wrap img { width: 48px; height: 48px; object-fit: cover; border-radius: 6px; }
    .foto-actual-wrap span { font-size: 12px; color: var(--muted); }

    /* section sub-label */
    .sub-section-label { font-size: 11px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; margin-bottom: 14px; display: flex; align-items: center; gap: 6px; }
    .sub-section-label i { font-size: 12px; }
    .divider { height: 1px; background: var(--border); margin: 20px 0; }

    /* ── BTNS ── */
    .kb-btn { display: inline-flex; align-items: center; gap: 8px; padding: 9px 18px; border-radius: var(--radius-sm); border: none; font-family: 'Outfit', sans-serif; font-size: 13.5px; font-weight: 500; cursor: pointer; text-decoration: none; transition: filter .15s, transform .1s; white-space: nowrap; }
    .kb-btn:active { transform: scale(.97); }
    .kb-btn:hover  { filter: brightness(1.1); }
    .kb-btn-primary { background: var(--accent);  color: #fff; }
    .kb-btn-success { background: #166534; color: #dcfce7; }
    .kb-btn-amber   { background: var(--amber); color: #fff; }
    .kb-btn-outline { background: var(--surface); color: var(--muted); border: 1.5px solid var(--border); }
    .kb-btn-outline:hover { background: var(--accent-lt); color: var(--accent); border-color: var(--info-bd); }
    .kb-btn-sm { padding: 6px 12px; font-size: 12px; }

    /* ── ALERTS ── */
    .kb-alert { display: flex; align-items: flex-start; gap: 12px; padding: 14px 18px; border-radius: var(--radius); font-size: 13.5px; margin-bottom: 20px; }
    .kb-alert i { font-size: 18px; flex-shrink: 0; margin-top: 1px; }
    .kb-alert.success { background: var(--green-bg); border: 1px solid var(--green-bd); color: #14532d; }
    .kb-alert.error   { background: var(--danger-bg); border: 1px solid var(--danger-bd); color: #9f1239; }

    /* ── TABLE GRUPO ── */
    .group-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .group-table thead tr { background: #f1f5f9; }
    .group-table th { padding: 10px 14px; text-align: left; font-size: 11px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; border-bottom: 1px solid var(--border); white-space: nowrap; }
    .group-table td { padding: 11px 14px; border-bottom: 1px solid #f1f5f9; color: var(--text); vertical-align: middle; }
    .group-table tbody tr:hover { background: #f8faff; }
    .group-table tbody tr.active-row td { background: var(--violet-bg); }
    .group-table tbody tr:last-child td { border-bottom: none; }
    .table-avatar { width: 30px; height: 30px; border-radius: 50%; background: linear-gradient(135deg,#7c3aed,#4f46e5); display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 600; color: #fff; text-transform: uppercase; flex-shrink: 0; }
    .client-cell { display: flex; align-items: center; gap: 9px; }
    .badge-m     { background: #dbeafe; color: #1e40af; padding: 2px 8px; border-radius: 5px; font-size: 11px; font-weight: 600; }
    .badge-f     { background: #fce7f3; color: #9d174d; padding: 2px 8px; border-radius: 5px; font-size: 11px; font-weight: 600; }
    .badge-emp   { background: var(--violet-bg); color: var(--violet); padding: 3px 9px; border-radius: 20px; font-size: 11px; font-weight: 600; }
    .passport-mono { font-family: monospace; font-size: 12px; color: var(--sub); letter-spacing: .4px; }
    .empty-state   { text-align: center; padding: 32px 16px; color: var(--sub); }
    .empty-state i { font-size: 28px; margin-bottom: 8px; display: block; }
    .act-btn       { width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 14px; border: none; transition: background .15s; text-decoration: none; }
    .act-edit      { background: rgba(245,158,11,.15); color: #b45309; }
    .act-edit:hover { background: rgba(245,158,11,.3); }

    /* ── MODAL ── */
    .modal-content { border: 1px solid var(--border) !important; border-radius: var(--radius) !important; background: var(--surface) !important; }
    .modal-header  { background: var(--surface2) !important; border-bottom: 1px solid var(--border) !important; padding: 16px 20px !important; }
    .modal-title   { font-size: 15px !important; font-weight: 600 !important; color: var(--text) !important; font-family: 'Outfit', sans-serif !important; }
    .modal-body    { padding: 20px !important; }
    .modal-footer  { border-top: 1px solid var(--border) !important; padding: 14px 20px !important; }
    .modal-body .kb-label { margin-bottom: 5px; }
    .modal-body .kb-input, .modal-body .kb-select { font-size: 13.5px; padding: 9px 12px; }

    /* info tip */
    .info-tip { background: var(--info-bg); border: 1px solid var(--info-bd); border-radius: var(--radius-sm); padding: 9px 13px; font-size: 12px; color: var(--info-txt); }
    .info-tip i { margin-right: 5px; }

    /* ── TOAST ── */
    .kb-toast { position: fixed; top: 72px; right: 24px; z-index: 9999; padding: 12px 18px; border-radius: 10px; font-family: 'Outfit', sans-serif; font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 9px; animation: slideIn .3s ease; }
    .kb-toast.success { background: var(--green-bg); border: 1px solid var(--green-bd); color: #14532d; }
    @keyframes slideIn { from { opacity:0; transform: translateX(40px); } to { opacity:1; transform: translateX(0); } }
    </style>
</head>
<body>

<?php include('../sidebar.php'); ?>

<?php if (isset($_GET['saved'])): ?>
<div class="kb-toast success" id="kb-toast">
    <i class="fas fa-check-circle"></i> Cambios guardados correctamente.
</div>
<script>setTimeout(() => document.getElementById('kb-toast')?.remove(), 4000);</script>
<?php endif; ?>

<div class="content">

    <!-- ── Page Header ────────────────────────────────────── -->
    <div class="page-header">
        <div>
            <div class="page-title">
                <?= $id_cliente ? 'Editar endosador' : 'Gestionar grupo' ?>
            </div>
            <div class="page-sub">
                <?= $id_cliente
                    ? htmlspecialchars(($cliente['nombre'] ?? '') . ' ' . ($cliente['apellido'] ?? ''))
                    : 'Grupo ' . htmlspecialchars($grupo['nombre_grupo']) ?>
            </div>
        </div>
        <a href="index.php" class="page-back"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>

    <?php if ($mensaje): ?>
    <div class="kb-alert <?= $tipo_msg ?>">
        <i class="fas <?= $tipo_msg === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle' ?>"></i>
        <div><?= $mensaje ?></div>
    </div>
    <?php endif; ?>

    <!-- ── Banner del grupo ───────────────────────────────── -->
    <?php
    $pct       = $grupo['cantidad'] > 0 ? round(($grupo['ocupados'] / $grupo['cantidad']) * 100) : 0;
    $occ_class = $pct >= 100 ? 'occ-full' : ($pct >= 80 ? 'occ-warn' : 'occ-ok');
    ?>
    <div class="group-banner">
        <div class="group-banner-icon"><i class="fas fa-building"></i></div>
        <div style="flex:1;">
            <div class="group-banner-name"><?= htmlspecialchars($grupo['nombre_grupo']) ?></div>
            <div class="group-banner-sub">
                <?= $grupo['ocupados'] ?> de <?= $grupo['cantidad'] ?> endosadores registrados
            </div>
            <div class="group-progress-bar" style="margin-top:8px;">
                <div class="group-progress-fill" style="width:<?= min($pct,100) ?>%;"></div>
            </div>
        </div>
        <span class="occ-badge <?= $occ_class ?>">
            <i class="fas fa-circle" style="font-size:7px;"></i> <?= $pct ?>% ocupado
        </span>
    </div>

    <!-- ── Configuración del grupo ───────────────────────── -->
    <div class="form-card">
        <div class="form-card-header">
            <div class="fch-left">
                <div class="section-icon icon-amber"><i class="fas fa-sliders-h"></i></div>
                <div><h5>Configuración del grupo</h5><small>Ajusta la capacidad y agrega clientes</small></div>
            </div>
        </div>
        <div class="form-card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="kb-label">Nombre del grupo</label>
                    <div class="input-icon-wrap">
                        <i class="fas fa-layer-group field-icon"></i>
                        <input type="text" class="kb-input" value="<?= htmlspecialchars($grupo['nombre_grupo']) ?>" disabled>
                    </div>
                </div>
                <div class="col-md-4">
                    <form method="POST" id="form-grupo">
                        <input type="hidden" name="accion" value="editar_grupo">
                        <label class="kb-label">Capacidad del grupo</label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-users field-icon"></i>
                            <input type="number" name="cantidad" class="kb-input"
                                   min="<?= $grupo['ocupados'] ?>"
                                   value="<?= $grupo['cantidad'] ?>" required>
                        </div>
                    </form>
                </div>
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" form="form-grupo" class="kb-btn kb-btn-amber" style="flex:1;">
                        <i class="fas fa-sync-alt"></i> Actualizar
                    </button>
                    <button class="kb-btn kb-btn-success" data-bs-toggle="modal" data-bs-target="#modalAgregarCliente">
                        <i class="fas fa-user-plus"></i> Agregar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Editar cliente ─────────────────────────────────── -->
    <?php if ($id_cliente && $cliente): ?>
    <div class="form-card">
        <div class="form-card-header">
            <div class="fch-left">
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
                <input type="hidden" name="id_grupo" value="<?= $cliente['cg_id_grupo'] ?? $grupo['id_grupo'] ?>">

                <!-- Datos personales -->
                <div class="sub-section-label">
                    <i class="fas fa-user" style="color:var(--accent);"></i> Datos personales
                </div>
                <div class="row g-3 mb-1">
                    <div class="col-md-6">
                        <label class="kb-label">Nombre <span class="req">*</span></label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-user field-icon"></i>
                            <input type="text" name="nombre" class="kb-input" required
                                   value="<?= htmlspecialchars($cliente['nombre'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="kb-label">Apellido <span class="req">*</span></label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-user field-icon"></i>
                            <input type="text" name="apellido" class="kb-input" required
                                   value="<?= htmlspecialchars($cliente['apellido'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="kb-label">Género</label>
                        <select name="genero" class="kb-input kb-select">
                            <option value="M"    <?= ($cliente['genero'] ?? '') === 'M'    ? 'selected' : '' ?>>Masculino</option>
                            <option value="F"    <?= ($cliente['genero'] ?? '') === 'F'    ? 'selected' : '' ?>>Femenino</option>
                            <option value="Otro" <?= ($cliente['genero'] ?? '') === 'Otro' ? 'selected' : '' ?>>Otro</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="kb-label">Nro. Pasaporte</label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-id-card field-icon"></i>
                            <input type="text" name="nro_pasaporte" class="kb-input"
                                   style="font-family:monospace;letter-spacing:.04em;"
                                   value="<?= htmlspecialchars($cliente['nro_pasaporte'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="kb-label">Teléfono</label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-phone field-icon"></i>
                            <input type="text" name="telefono" class="kb-input"
                                   placeholder="+51 987 654 321"
                                   value="<?= htmlspecialchars($cliente['telefono'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="kb-label">Foto pasaporte</label>
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

                <div class="divider"></div>

                <!-- Datos empresa / endosador -->
                <div class="sub-section-label">
                    <i class="fas fa-building" style="color:var(--violet);"></i> Datos empresa / endosador
                </div>
                <div class="row g-3 mb-1">
                    <div class="col-md-6">
                        <label class="kb-label">Empresa endosadora</label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-building field-icon"></i>
                            <input type="text" name="empresa" class="kb-input"
                                   placeholder="Nombre de la empresa"
                                   value="<?= htmlspecialchars($cliente['empresa_endosadora'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="kb-label">Contacto</label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-user-tie field-icon"></i>
                            <input type="text" name="contacto" class="kb-input"
                                   placeholder="Nombre del contacto"
                                   value="<?= htmlspecialchars($cliente['contacto'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="kb-label">Teléfono contacto</label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-phone field-icon"></i>
                            <input type="text" name="telefono_contacto" class="kb-input"
                                   placeholder="+51 987 000"
                                   value="<?= htmlspecialchars($cliente['telefono_contacto'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="kb-label">Email contacto</label>
                        <div class="input-icon-wrap">
                            <i class="fas fa-envelope field-icon"></i>
                            <input type="email" name="email_contacto" class="kb-input"
                                   placeholder="contacto@empresa.com"
                                   value="<?= htmlspecialchars($cliente['email_contacto'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <div class="divider"></div>

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

    <!-- ── Clientes del grupo ─────────────────────────────── -->
    <div class="form-card">
        <div class="form-card-header">
            <div class="fch-left">
                <div class="section-icon icon-slate"><i class="fas fa-list-ul"></i></div>
                <div>
                    <h5>Endosadores del grupo</h5>
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
                        <th>Empresa</th>
                        <th>Teléfono</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $i          = 1;
                $hayClientes = false;
                while ($c = mysqli_fetch_assoc($clientesGrupo)):
                    $hayClientes = true;
                    $iniciales   = strtoupper(substr($c['nombre'],0,1) . substr($c['apellido'],0,1));
                    $esActual    = ($id_cliente == $c['id_cliente']);
                ?>
                <tr class="<?= $esActual ? 'active-row' : '' ?>">
                    <td style="color:var(--sub);font-size:12px;"><?= $i++ ?></td>
                    <td>
                        <div class="client-cell">
                            <div class="table-avatar"><?= $iniciales ?></div>
                            <span style="font-weight:500;">
                                <?= htmlspecialchars($c['nombre'] . ' ' . $c['apellido']) ?>
                                <?php if ($esActual): ?>
                                    <span style="font-size:10px;background:var(--violet-bg);color:var(--violet);padding:2px 6px;border-radius:4px;margin-left:4px;font-weight:600;">editando</span>
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
                            <span style="color:var(--sub);">—</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="passport-mono"><?= htmlspecialchars($c['nro_pasaporte'] ?? '—') ?></span></td>
                    <td>
                        <?php if (!empty($c['empresa_endosadora'])): ?>
                            <span class="badge-emp"><?= htmlspecialchars($c['empresa_endosadora']) ?></span>
                        <?php else: ?>
                            <span style="color:var(--sub);">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12px;color:var(--muted);"><?= htmlspecialchars($c['telefono'] ?? '—') ?></td>
                    <td>
                        <a href="editar_endosador.php?id_grupo=<?= $id_grupo ?>&id_cliente=<?= $c['id_cliente'] ?>"
                           class="act-btn act-edit" title="Editar">
                            <i class="fas fa-pen"></i>
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if (!$hayClientes): ?>
                <tr>
                    <td colspan="7">
                        <div class="empty-state">
                            <i class="fas fa-users-slash"></i>
                            No hay endosadores en este grupo.
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
<div class="modal fade" id="modalAgregarCliente" tabindex="-1" aria-modal="true" role="dialog">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <form method="POST" enctype="multipart/form-data" class="modal-content">
      <input type="hidden" name="accion"   value="agregar_cliente">
      <input type="hidden" name="id_grupo" value="<?= $id_grupo ?>">

      <div class="modal-header">
        <h5 class="modal-title">
            <i class="fas fa-user-plus me-2" style="color:var(--violet);"></i>
            Agregar cliente al grupo
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">

        <!-- Datos personales -->
        <div class="sub-section-label" style="margin-bottom:12px;">
            <i class="fas fa-user" style="color:var(--accent);"></i> Datos personales
        </div>
        <div class="row g-3 mb-3">
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
                <select name="genero" class="kb-input kb-select">
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
                <label class="kb-label">Teléfono</label>
                <input type="text" name="telefono" class="kb-input" placeholder="+51 987 654">
            </div>
        </div>

        <div class="divider"></div>

        <!-- Datos empresa -->
        <div class="sub-section-label" style="margin-bottom:12px;">
            <i class="fas fa-building" style="color:var(--violet);"></i> Datos empresa / endosador
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="kb-label">Empresa endosadora</label>
                <input type="text" name="empresa" class="kb-input" placeholder="Nombre de la empresa">
            </div>
            <div class="col-md-6">
                <label class="kb-label">Contacto</label>
                <input type="text" name="contacto" class="kb-input" placeholder="Nombre del contacto">
            </div>
            <div class="col-md-6">
                <label class="kb-label">Teléfono contacto</label>
                <input type="text" name="telefono_contacto" class="kb-input" placeholder="+51 987 000">
            </div>
            <div class="col-md-6">
                <label class="kb-label">Email contacto</label>
                <input type="email" name="email_contacto" class="kb-input" placeholder="contacto@empresa.com">
            </div>
        </div>

        <div class="info-tip" style="margin-top:16px;">
            <i class="fas fa-info-circle"></i>
            Se asignará al grupo <strong><?= htmlspecialchars($grupo['nombre_grupo']) ?></strong>.
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
<script>
// Nombre de archivo foto
document.getElementById('foto_edit_input')?.addEventListener('change', function () {
    const name = this.files[0]?.name || '';
    if (name) {
        document.getElementById('foto-edit-label-text').textContent = name;
        document.getElementById('foto-edit-name').textContent = '✓ Lista para subir';
    }
});
</script>
</body>
</html>