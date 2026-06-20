<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: ../index.php");
    exit();
}

include('../../conexion.php');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
error_reporting(E_ALL);
ini_set('display_errors', 1);

$mensaje = "";
$tipo_msg = "";

// ── Buscar grupo activo con cupo disponible ──────────────────────────────
$sqlGrupo = "
    SELECT
        g.*,
        COUNT(cg.id_cliente) AS registrados
    FROM grupos g
    LEFT JOIN clientes_grupo cg
        ON g.id_grupo = cg.id_grupo
        AND cg.tipo_cliente = 'KB'
    WHERE g.estado = 'abierto'
      AND g.nombre_grupo LIKE 'C-KB-%'
    GROUP BY g.id_grupo
    HAVING COUNT(cg.id_cliente) < g.cantidad
    ORDER BY g.id_grupo DESC
    LIMIT 1
";
$resultGrupo = mysqli_query($conexion, $sqlGrupo);

if (!$resultGrupo) {
    die("Error SQL: " . mysqli_error($conexion));
}

$grupo = mysqli_fetch_assoc($resultGrupo);

echo "<pre>";
var_dump($grupo);
echo "</pre>";

$grupo_lleno = $grupo ? ($grupo['registrados'] >= $grupo['cantidad']) : false;

// ── Procesar formulario ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    mysqli_begin_transaction($conexion);

    try {
        $nombre           = trim($_POST['nombre']);
        $apellido         = trim($_POST['apellido']);
        $genero           = $_POST['genero'];
        $nro_pasaporte    = trim($_POST['nro_pasaporte']);
        $nacionalidad     = trim($_POST['nacionalidad']);
        $comida           = trim($_POST['Comida']);
        $fecha_nacimiento = $_POST['fecha_nacimiento'] ?: null;
        $telefono         = trim($_POST['telefono']);
        $hotel            = trim($_POST['hotel']);

        $genero = match($genero) {
            'Masculino' => 'M',
            'Femenino'  => 'F',
            default     => 'Otro'
        };

        // ── Crear o usar grupo ───────────────────────────────────────────
        if (!$grupo || $grupo_lleno) {
            if (empty($_POST['cantidad_grupo']) || intval($_POST['cantidad_grupo']) <= 0) {
                throw new Exception("Debes ingresar la cantidad de pasajeros del grupo.");
            }
            $cantidad_clientes = intval($_POST['cantidad_grupo']);

            $stmt = mysqli_prepare($conexion, "
                INSERT INTO grupos (nombre_grupo, hotel, cantidad, estado)
                VALUES ('TEMP', ?, ?, 'abierto')
            ");
            mysqli_stmt_bind_param($stmt, "si", $hotel, $cantidad_clientes);
            mysqli_stmt_execute($stmt);

            $id_grupo     = mysqli_insert_id($conexion);
            $codigo_grupo = 'C-KB-' . str_pad($id_grupo, 4, '0', STR_PAD_LEFT);
            mysqli_query($conexion, "UPDATE grupos SET nombre_grupo='$codigo_grupo' WHERE id_grupo=$id_grupo");
            $registrados = 0;

        } else {
            $id_grupo = $grupo['id_grupo'];
            $codigo_grupo = $grupo['nombre_grupo'];
            $cantidad_clientes = $grupo['cantidad'];

            $res = mysqli_query($conexion, "
                SELECT COUNT(*) AS total FROM clientes_grupo
                WHERE id_grupo = $id_grupo AND tipo_cliente = 'KB' FOR UPDATE
            ");
            $registrados = mysqli_fetch_assoc($res)['total'];

            if ($registrados >= $cantidad_clientes) {
                if (empty($_POST['cantidad_grupo']) || intval($_POST['cantidad_grupo']) <= 0) {
                    throw new Exception("El grupo está lleno. Ingresa la cantidad del nuevo grupo.");
                }
                $cantidad_clientes = intval($_POST['cantidad_grupo']);
                $stmt = mysqli_prepare($conexion, "
                    INSERT INTO grupos (nombre_grupo, hotel, cantidad, estado) VALUES ('TEMP', ?, ?, 'abierto')
                ");
                mysqli_stmt_bind_param($stmt, "si", $hotel, $cantidad_clientes);
                mysqli_stmt_execute($stmt);
                $id_grupo = mysqli_insert_id($conexion);
                $codigo_grupo = 'C-KB-' . str_pad($id_grupo, 4, '0', STR_PAD_LEFT);
                mysqli_query($conexion, "UPDATE grupos SET nombre_grupo='$codigo_grupo' WHERE id_grupo=$id_grupo");
                $registrados = 0;
            }
        }

        // ── Foto pasaporte ───────────────────────────────────────────────
        $foto_pasaporte = null;
        if (!empty($_FILES['foto_pasaporte']['name'])) {
            $permitidos = ['image/jpeg', 'image/png', 'image/jpg'];
            if (!in_array($_FILES['foto_pasaporte']['type'], $permitidos)) {
                throw new Exception("Solo se permiten imágenes JPG o PNG.");
            }
            $ruta = '../../assets/images/fotos_pasaportes/';
            if (!is_dir($ruta)) mkdir($ruta, 0777, true);
            $foto_pasaporte = time() . '_' . basename($_FILES['foto_pasaporte']['name']);
            if (!move_uploaded_file($_FILES['foto_pasaporte']['tmp_name'], $ruta . $foto_pasaporte)) {
                throw new Exception("Error al subir la imagen del pasaporte.");
            }
        }

        // ── Insertar cliente ─────────────────────────────────────────────
        $stmt = mysqli_prepare($conexion, "
            INSERT INTO datos_clientes
            (nombre, apellido, genero, nro_pasaporte, nacionalidad, comida, fecha_nacimiento, hotel, telefono, foto_pasaporte)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        mysqli_stmt_bind_param($stmt, "ssssssssss",
            $nombre, $apellido, $genero, $nro_pasaporte,
            $nacionalidad, $comida, $fecha_nacimiento,
            $hotel, $telefono, $foto_pasaporte
        );
        mysqli_stmt_execute($stmt);
        $id_cliente = mysqli_insert_id($conexion);

        // ── Relacionar con grupo ─────────────────────────────────────────
        $stmt = mysqli_prepare($conexion, "
            INSERT INTO clientes_grupo (id_cliente, id_grupo, tipo_cliente) VALUES (?, ?, 'KB')
        ");
        mysqli_stmt_bind_param($stmt, "ii", $id_cliente, $id_grupo);
        mysqli_stmt_execute($stmt);

        $registrados++;

        if ($registrados >= $cantidad_clientes) {
            echo "CERRANDO GRUPO $id_grupo";
            mysqli_query($conexion, "UPDATE grupos SET estado='cerrado' WHERE id_grupo=$id_grupo");
            $mensaje  = "Grupo <strong>$codigo_grupo</strong> completo. Todos los pasajeros registrados.";
            $tipo_msg = "success";
        } else {
            $faltan   = $cantidad_clientes - $registrados;
            $mensaje  = "Cliente agregado al grupo <strong>$codigo_grupo</strong>. Faltan <strong>$faltan</strong> pasajero(s).";
            $tipo_msg = "info";
        }

        mysqli_commit($conexion);
        
    header("Location: agregar_kb.php?ok=1");
exit;
    } catch (Exception $e) {
        mysqli_rollback($conexion);
        $mensaje  = $e->getMessage();
        $tipo_msg = "error";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Cliente KB – KB Adventures</title>

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
        --success:   #166534;
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
        --radius:    12px;
        --radius-sm: 8px;
    }

    html, body {
        min-height: 100vh;
        font-family: 'Outfit', sans-serif;
        background: var(--bg);
        color: var(--text);
    }

    /* ─── CONTENT WRAPPER ──────────────────────────────────── */
    .content {
        margin-left: 256px;
        padding: 32px 32px 64px;
        min-height: 100vh;
        transition: margin-left .32s cubic-bezier(.4,0,.2,1);
    }
    body.sidebar-collapsed .content { margin-left: 64px; }
    @media (max-width: 992px) { .content { margin-left: 0 !important; padding: 20px 16px 48px; } }

    /* ─── PAGE HEADER ──────────────────────────────────────── */
    .page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 28px;
    }
    .page-back {
        display: inline-flex; align-items: center; gap: 7px;
        font-size: 13px; color: var(--muted); text-decoration: none;
        background: var(--surface); border: 1px solid var(--border);
        padding: 7px 14px; border-radius: var(--radius-sm);
        transition: all .15s;
    }
    .page-back:hover { background: var(--accent-lt); color: var(--accent); border-color: var(--info-bd); }
    .page-title { font-size: 22px; font-weight: 600; color: var(--text); }
    .page-sub   { font-size: 13px; color: var(--muted); margin-top: 3px; }

    /* ─── GRUPO STATUS BANNER ───────────────────────────────── */
    .group-banner {
        display: flex; align-items: center; gap: 14px;
        background: var(--info-bg);
        border: 1px solid var(--info-bd);
        border-radius: var(--radius);
        padding: 14px 18px;
        margin-bottom: 20px;
    }
    .group-banner-icon {
        width: 40px; height: 40px; border-radius: 10px;
        background: var(--accent); color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: 18px; flex-shrink: 0;
    }
    .group-banner-name  { font-size: 15px; font-weight: 600; color: var(--info-txt); }
    .group-banner-sub   { font-size: 12px; color: #3b82f6; margin-top: 2px; }
    .group-progress-bar {
        flex: 1; height: 6px; background: #bfdbfe;
        border-radius: 3px; overflow: hidden; min-width: 80px;
    }
    .group-progress-fill { height: 100%; background: var(--accent); border-radius: 3px; transition: width .4s; }

    .new-group-box {
        background: var(--warn-bg);
        border: 1px solid var(--warn-bd);
        border-radius: var(--radius);
        padding: 14px 18px;
        margin-bottom: 20px;
        display: flex; align-items: flex-start; gap: 12px;
    }
    .new-group-box i { color: #d97706; font-size: 18px; margin-top: 2px; }
    .new-group-box p { font-size: 13px; color: var(--warn-txt); margin: 0 0 4px; font-weight: 500; }
    .new-group-box small { font-size: 12px; color: #b45309; }

    /* ─── FORM CARD ─────────────────────────────────────────── */
    .form-card {
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        overflow: hidden;
        margin-bottom: 20px;
    }
    .form-card-header {
        display: flex; align-items: center; gap: 10px;
        padding: 14px 20px;
        border-bottom: 1px solid var(--border);
        background: var(--surface2);
    }
    .form-card-header .section-icon {
        width: 32px; height: 32px;
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-size: 15px; flex-shrink: 0;
    }
    .icon-blue   { background: #dbeafe; color: var(--accent); }
    .icon-indigo { background: #e0e7ff; color: #4338ca; }
    .icon-teal   { background: #ccfbf1; color: #0f766e; }
    .icon-amber  { background: #fef3c7; color: #d97706; }
    .form-card-header h5 {
        font-size: 14px; font-weight: 600; color: var(--text); margin: 0; line-height: 1;
    }
    .form-card-header small { font-size: 11px; color: var(--muted); margin-top: 2px; display: block; }
    .form-card-body { padding: 20px 22px; }

    /* ─── FORM CONTROLS ─────────────────────────────────────── */
    .kb-label {
        display: block;
        font-size: 12px; font-weight: 600; color: var(--muted);
        text-transform: uppercase; letter-spacing: .05em;
        margin-bottom: 6px;
    }
    .kb-label .req { color: #ef4444; margin-left: 2px; }

    .kb-input, .kb-select {
        width: 100%;
        background: var(--surface);
        border: 1.5px solid var(--border);
        border-radius: var(--radius-sm);
        padding: 10px 14px;
        font-family: 'Outfit', sans-serif;
        font-size: 14px;
        color: var(--text);
        outline: none;
        transition: border-color .15s, box-shadow .15s;
        appearance: none;
    }
    .kb-input:hover, .kb-select:hover { border-color: var(--border2); }
    .kb-input:focus, .kb-select:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 3px rgba(37,99,235,.1);
    }
    .kb-input::placeholder { color: var(--sub); }

    .input-with-icon { position: relative; }
    .input-with-icon .kb-input { padding-left: 38px; }
    .input-with-icon .input-icon {
        position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
        font-size: 15px; color: var(--sub); pointer-events: none;
    }

    /* file input */
    .kb-file-label {
        display: flex; align-items: center; gap: 10px;
        border: 1.5px dashed var(--border2);
        border-radius: var(--radius-sm);
        padding: 12px 16px;
        cursor: pointer;
        transition: background .15s, border-color .15s;
        background: var(--surface2);
    }
    .kb-file-label:hover { background: var(--accent-lt); border-color: #93c5fd; }
    .kb-file-label span { font-size: 13px; color: var(--muted); }
    .kb-file-label i { font-size: 18px; color: var(--accent); }
    input[type="file"] { display: none; }
    #file-name-display { font-size: 12px; color: var(--accent); margin-top: 4px; min-height: 16px; }

    /* select2 overrides */
    .select2-container--default .select2-selection--single {
        height: 44px !important;
        border: 1.5px solid var(--border) !important;
        border-radius: var(--radius-sm) !important;
        background: var(--surface) !important;
        padding: 0 14px !important;
        display: flex !important;
        align-items: center !important;
        font-family: 'Outfit', sans-serif !important;
        font-size: 14px !important;
        color: var(--text) !important;
        transition: border-color .15s, box-shadow .15s !important;
    }
    .select2-container--default.select2-container--focus .select2-selection--single {
        border-color: var(--accent) !important;
        box-shadow: 0 0 0 3px rgba(37,99,235,.1) !important;
        outline: none !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: var(--text) !important; line-height: 44px !important; padding: 0 !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: var(--sub) !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 44px !important; right: 10px !important;
    }
    .select2-dropdown {
        border: 1.5px solid var(--border2) !important;
        border-radius: var(--radius-sm) !important;
        font-family: 'Outfit', sans-serif !important;
        font-size: 14px !important;
        box-shadow: 0 8px 24px rgba(0,0,0,.08) !important;
    }
    .select2-container--default .select2-search--dropdown .select2-search__field {
        border: 1px solid var(--border2) !important;
        border-radius: 6px !important;
        padding: 7px 10px !important;
        font-family: 'Outfit', sans-serif !important;
        font-size: 13px !important;
        outline: none !important;
    }
    .select2-container--default .select2-results__option--highlighted {
        background: var(--accent) !important;
    }

    /* ─── SUBMIT BUTTON ─────────────────────────────────────── */
    .kb-submit {
        width: 100%;
        padding: 13px;
        background: var(--accent);
        color: #fff;
        border: none;
        border-radius: var(--radius-sm);
        font-family: 'Outfit', sans-serif;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        display: flex; align-items: center; justify-content: center; gap: 9px;
        transition: background .15s, transform .1s;
        letter-spacing: .01em;
    }
    .kb-submit:hover   { background: var(--accent-h); }
    .kb-submit:active  { transform: scale(.98); }

    /* ─── ALERT MESSAGES ────────────────────────────────────── */
    .kb-alert {
        display: flex; align-items: flex-start; gap: 12px;
        padding: 14px 18px; border-radius: var(--radius);
        font-size: 13.5px; font-weight: 400;
        margin-bottom: 20px;
    }
    .kb-alert i { font-size: 18px; flex-shrink: 0; margin-top: 1px; }
    .kb-alert.success { background: var(--success-bg); border: 1px solid var(--success-bd); color: #14532d; }
    .kb-alert.info    { background: var(--info-bg);    border: 1px solid var(--info-bd);    color: var(--info-txt); }
    .kb-alert.error   { background: var(--danger-bg);  border: 1px solid var(--danger-bd);  color: var(--danger-txt); }

    /* ─── OCCUPATION BADGE ──────────────────────────────────── */
    .occ-badge {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600;
    }
    .occ-ok   { background: #dbeafe; color: #1e40af; }
    .occ-warn { background: #fef3c7; color: #92400e; }
    .occ-full { background: #fee2e2; color: #991b1b; }

    /* ─── REQUIRED HINT ─────────────────────────────────────── */
    .required-hint { font-size: 12px; color: var(--sub); margin-bottom: 20px; }
    .required-hint span { color: #ef4444; }
    </style>
</head>
<body>

<?php include('../sidebar.php'); ?>
<div class="kb-content">

    <!-- ── Header ──────────────────────────────────────────────── -->
    <div class="page-header">
        <div>
            <div class="page-title">Agregar cliente KB</div>
            <div class="page-sub">Completa los datos del nuevo pasajero</div>
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

    <!-- ── Información del grupo ─────────────────────────────── -->
    <?php if ($grupo && !$grupo_lleno): ?>
        <?php
            $pct = $grupo['cantidad'] > 0 ? round(($grupo['registrados'] / $grupo['cantidad']) * 100) : 0;
            $faltan = $grupo['cantidad'] - $grupo['registrados'];
            $occ_class = $pct >= 90 ? 'occ-warn' : 'occ-ok';
        ?>
        <div class="group-banner">
            <div class="group-banner-icon"><i class="fas fa-users"></i></div>
            <div style="flex:1;">
                <div class="group-banner-name"><?= htmlspecialchars($grupo['nombre_grupo']) ?></div>
                <div class="group-banner-sub">
                    <?= $grupo['registrados'] ?> de <?= $grupo['cantidad'] ?> pasajeros registrados &nbsp;·&nbsp; Faltan <?= $faltan ?>
                </div>
                <div class="group-progress-bar" style="margin-top:8px;">
                    <div class="group-progress-fill" style="width:<?= $pct ?>%;"></div>
                </div>
            </div>
            <span class="occ-badge <?= $occ_class ?>">
                <i class="fas fa-circle" style="font-size:7px;"></i>
                <?= $pct ?>% ocupado
            </span>
        </div>
    <?php else: ?>
        <div class="new-group-box">
            <i class="fas fa-folder-plus"></i>
            <div>
                <p>Se creará un nuevo grupo automáticamente</p>
                <small>Ingresa la cantidad de pasajeros que tendrá este grupo para generarlo.</small>
            </div>
        </div>
    <?php endif; ?>

    <p class="required-hint">Los campos marcados con <span>*</span> son obligatorios.</p>

    <form method="POST" enctype="multipart/form-data" novalidate>

        <!-- ── Nuevo grupo (si aplica) ───────────────────────── -->
        <?php if (!$grupo || $grupo_lleno): ?>
        <div class="form-card">
            <div class="form-card-header">
                <div class="section-icon icon-amber"><i class="fas fa-layer-group"></i></div>
                <div>
                    <h5>Configurar nuevo grupo</h5>
                    <small>Define la capacidad del grupo que se va a crear</small>
                </div>
            </div>
            <div class="form-card-body">
                <div style="max-width:320px;">
                    <label class="kb-label">Cantidad de pasajeros <span class="req">*</span></label>
                    <div class="input-with-icon">
                        <i class="fas fa-users input-icon"></i>
                        <input type="number" name="cantidad_grupo" class="kb-input"
                               placeholder="Ej: 25" min="1" required>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ── Datos personales ──────────────────────────────── -->
        <div class="form-card">
            <div class="form-card-header">
                <div class="section-icon icon-blue"><i class="fas fa-user"></i></div>
                <div>
                    <h5>Datos personales</h5>
                    <small>Información básica del pasajero</small>
                </div>
            </div>
            <div class="form-card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="kb-label">Nombre <span class="req">*</span></label>
                        <div class="input-with-icon">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" name="nombre" class="kb-input"
                                   placeholder="Ej: Juan Carlos" required
                                   value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="kb-label">Apellido <span class="req">*</span></label>
                        <div class="input-with-icon">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" name="apellido" class="kb-input"
                                   placeholder="Ej: Mendoza Ríos" required
                                   value="<?= htmlspecialchars($_POST['apellido'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="kb-label">Género <span class="req">*</span></label>
                        <select name="genero" class="kb-input kb-select" required>
                            <option value="">— Seleccionar —</option>
                            <option value="Masculino" <?= ($_POST['genero'] ?? '') === 'Masculino' ? 'selected' : '' ?>>Masculino</option>
                            <option value="Femenino"  <?= ($_POST['genero'] ?? '') === 'Femenino'  ? 'selected' : '' ?>>Femenino</option>
                            <option value="Otro"      <?= ($_POST['genero'] ?? '') === 'Otro'      ? 'selected' : '' ?>>Otro</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="kb-label">Fecha de nacimiento</label>
                        <div class="input-with-icon">
                            <i class="fas fa-calendar input-icon"></i>
                            <input type="date" name="fecha_nacimiento" class="kb-input"
                                   value="<?= htmlspecialchars($_POST['fecha_nacimiento'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="kb-label">Teléfono / WhatsApp</label>
                        <div class="input-with-icon">
                            <i class="fab fa-whatsapp input-icon"></i>
                            <input type="text" name="telefono" class="kb-input"
                                   placeholder="+51 987 654 321"
                                   value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Documentos ───────────────────────────────────── -->
        <div class="form-card">
            <div class="form-card-header">
                <div class="section-icon icon-indigo"><i class="fas fa-passport"></i></div>
                <div>
                    <h5>Documentos</h5>
                    <small>Pasaporte e imagen del documento</small>
                </div>
            </div>
            <div class="form-card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="kb-label">Número de pasaporte <span class="req">*</span></label>
                        <div class="input-with-icon">
                            <i class="fas fa-id-card input-icon"></i>
                            <input type="text" name="nro_pasaporte" class="kb-input"
                                   placeholder="Ej: PE123456" required
                                   value="<?= htmlspecialchars($_POST['nro_pasaporte'] ?? '') ?>"
                                   style="font-family: monospace; letter-spacing: .05em;">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="kb-label">Foto del pasaporte</label>
                        <label class="kb-file-label" for="foto_pasaporte_input">
                            <i class="fas fa-camera"></i>
                            <span id="file-label-text">Haz clic para subir imagen (JPG, PNG)</span>
                        </label>
                        <input type="file" id="foto_pasaporte_input" name="foto_pasaporte" accept=".jpg,.jpeg,.png">
                        <div id="file-name-display"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Información adicional ─────────────────────────── -->
        <div class="form-card">
            <div class="form-card-header">
                <div class="section-icon icon-teal"><i class="fas fa-hotel"></i></div>
                <div>
                    <h5>Información adicional</h5>
                    <small>Nacionalidad, preferencia alimentaria y alojamiento</small>
                </div>
            </div>
            <div class="form-card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="kb-label">Nacionalidad</label>
                        <select name="nacionalidad" id="nacionalidad" class="kb-select">
                            <option value="">Buscar país…</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="kb-label">Restricción / preferencia de comida</label>
                        <div class="input-with-icon">
                            <i class="fas fa-utensils input-icon"></i>
                            <input type="text" name="Comida" class="kb-input"
                                   placeholder="Ej: Vegetariano, Sin gluten…"
                                   value="<?= htmlspecialchars($_POST['Comida'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="kb-label">Hotel</label>
                        <div class="input-with-icon">
                            <i class="fas fa-building input-icon"></i>
                            <input type="text" name="hotel" class="kb-input"
                                   placeholder="Nombre del hotel"
                                   value="<?= htmlspecialchars($_POST['hotel'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Submit ────────────────────────────────────────── -->
        <button type="submit" class="kb-submit">
            <i class="fas fa-user-check"></i>
            Guardar cliente
        </button>

    </form>
</div><!-- /content -->

<!-- ── Scripts ────────────────────────────────────────────── -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
// ── Select2 con países desde JSON ────────────────────────────────────────
fetch('../../../assets/json/paises.json')
    .then(r => r.json())
    .then(paises => {
        const sel = document.getElementById('nacionalidad');
        paises.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p; opt.textContent = p;
            <?php if (!empty($_POST['nacionalidad'])): ?>
            if (p === '<?= addslashes($_POST['nacionalidad']) ?>') opt.selected = true;
            <?php endif; ?>
            sel.appendChild(opt);
        });
        $('#nacionalidad').select2({ placeholder: 'Buscar país…', width: '100%' });
    })
    .catch(() => {
        $('#nacionalidad').select2({ placeholder: 'Buscar país…', width: '100%' });
    });

// ── Mostrar nombre de archivo seleccionado ───────────────────────────────
document.getElementById('foto_pasaporte_input').addEventListener('change', function () {
    const name = this.files[0]?.name || '';
    const disp = document.getElementById('file-name-display');
    const lbl  = document.getElementById('file-label-text');
    if (name) {
        lbl.textContent  = name;
        disp.textContent = '✓ Archivo listo para subir';
    }
});

// ── Validación cliente-side sencilla ────────────────────────────────────
document.querySelector('form').addEventListener('submit', function (e) {
    const req = this.querySelectorAll('[required]');
    let ok = true;
    req.forEach(el => {
        el.style.borderColor = '';
        if (!el.value.trim()) {
            el.style.borderColor = '#ef4444';
            el.style.boxShadow   = '0 0 0 3px rgba(239,68,68,.12)';
            ok = false;
        }
    });
    if (!ok) {
        e.preventDefault();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
});
</script>

</body>
</html>