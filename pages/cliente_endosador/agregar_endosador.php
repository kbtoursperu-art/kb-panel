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
date_default_timezone_set("America/Lima");

$mensaje     = '';
$msg_type    = 'info'; // success | error | info

// ══════════════════════════════════════
// GRUPO ACTIVO
// ══════════════════════════════════════
function getGrupoActivo($conexion) {

    $sql = "
        SELECT
            g.*,
            COUNT(cg.id) AS registrados
        FROM grupos g
        LEFT JOIN clientes_grupo cg
            ON cg.id_grupo = g.id_grupo
        WHERE g.nombre_grupo LIKE 'C-END-%'
        GROUP BY g.id_grupo
        HAVING registrados < g.cantidad
        ORDER BY g.id_grupo DESC
        LIMIT 1
    ";
    

    $q = mysqli_query($conexion, $sql);

    return mysqli_fetch_assoc($q);
}
// Obtener grupo activo actual
$grupo = getGrupoActivo($conexion);
$hayGrupoActivo = !empty($grupo);
$mostrarCantidadGrupo = empty($grupo);
// ══════════════════════════════════════
// PROCESAR POST
// ══════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    mysqli_begin_transaction($conexion);
    try {
        $nombre             = trim($_POST['nombre']             ?? '');
        $apellido           = trim($_POST['apellido']           ?? '');
        $genero             = $_POST['genero']                  ?? '';
        $nro_pasaporte      = trim($_POST['nro_pasaporte']      ?? '');
        $empresa_endosadora = trim($_POST['empresa_endosadora'] ?? '');
        $contacto           = trim($_POST['contacto']           ?? '');
        $telefono_contacto  = trim($_POST['telefono_contacto']  ?? '');
        $email_contacto     = trim($_POST['email_contacto']     ?? '');

        // Crear grupo si no existe
        if (!$grupo) {
            $cantidad = intval($_POST['cantidad_grupo'] ?? 0);
            if ($cantidad <= 0) throw new Exception("Ingresa una cantidad válida para el grupo.");

            $stmt = mysqli_prepare($conexion,"
            INSERT INTO grupos (
    nombre_grupo,
    cantidad
)
VALUES ('TEMP', ?)");
            mysqli_stmt_bind_param($stmt,'i',$cantidad);
            mysqli_stmt_execute($stmt);
            $id_grupo = mysqli_insert_id($conexion);

            $codigo = 'C-END-'.str_pad($id_grupo,3,'0',STR_PAD_LEFT);
            $st2 = mysqli_prepare($conexion,"UPDATE grupos SET nombre_grupo=? WHERE id_grupo=?");
            mysqli_stmt_bind_param($st2,'si',$codigo,$id_grupo);
            mysqli_stmt_execute($st2);

            $grupo = getGrupoActivo($conexion) ?: ['id_grupo'=>$id_grupo,'nombre_grupo'=>$codigo,'cantidad'=>$cantidad,'registrados'=>0];
        }

        $id_grupo     = $grupo['id_grupo'];
        $codigo_grupo = $grupo['nombre_grupo'];

        // Validar cupo
        if ($grupo['registrados'] >= $grupo['cantidad'])
            throw new Exception("El grupo <b>$codigo_grupo</b> ya está completo.");

        // Validar pasaporte duplicado
        $chk = mysqli_prepare($conexion,"SELECT id_cliente FROM datos_clientes WHERE nro_pasaporte=? LIMIT 1");
        mysqli_stmt_bind_param($chk,'s',$nro_pasaporte);
        mysqli_stmt_execute($chk);
        mysqli_stmt_store_result($chk);
        if (mysqli_stmt_num_rows($chk) > 0)
            throw new Exception("El pasaporte <b>$nro_pasaporte</b> ya está registrado.");

        // Insertar cliente
        $sc = mysqli_prepare($conexion,"INSERT INTO datos_clientes (nombre,apellido,genero,nro_pasaporte) VALUES (?,?,?,?)");
        mysqli_stmt_bind_param($sc,'ssss',$nombre,$apellido,$genero,$nro_pasaporte);
        mysqli_stmt_execute($sc);
        $id_cliente = mysqli_insert_id($conexion);

        // Insertar en clientes_grupo
        $sg = mysqli_prepare($conexion,"
            INSERT INTO clientes_grupo (id_cliente,id_grupo,tipo_cliente,empresa_endosadora,contacto,telefono_contacto,email_contacto)
            VALUES (?,?,'ENDOSADOR',?,?,?,?)
        ");
        mysqli_stmt_bind_param($sg,'iissss',$id_cliente,$id_grupo,$empresa_endosadora,$contacto,$telefono_contacto,$email_contacto);
        mysqli_stmt_execute($sg);

        // Actualizar contador
        
        mysqli_commit($conexion);

       $grupo = getGrupoActivo($conexion);
$hayGrupoActivo = !empty($grupo);
$mostrarCantidadGrupo = empty($grupo);
        $ga = mysqli_fetch_assoc(
    mysqli_query(
        $conexion,
        "
        SELECT
            g.*,
            COUNT(cg.id) AS registrados
        FROM grupos g
        LEFT JOIN clientes_grupo cg
            ON cg.id_grupo = g.id_grupo
        WHERE g.id_grupo = $id_grupo
        GROUP BY g.id_grupo
        "
    )
);
        $faltan = $ga['cantidad'] - $ga['registrados'];

        $msg_type = 'success';
        $mensaje  = $faltan <= 0
            ? "Grupo <b>$codigo_grupo</b> completado. El siguiente registro abrirá un grupo nuevo."
            : "Cliente agregado a <b>$codigo_grupo</b> · Faltan <b>$faltan</b> persona(s).";

    } catch (Exception $e) {
        mysqli_rollback($conexion);
        $msg_type = 'error';
        $mensaje  = $e->getMessage();
    }
}

// Historial reciente de endosadores
$qRecientes = mysqli_query($conexion,"
    SELECT dc.nombre, dc.apellido, dc.nro_pasaporte,
           cg.empresa_endosadora, cg.contacto,
           g.nombre_grupo
    FROM clientes_grupo cg
    JOIN datos_clientes dc ON dc.id_cliente = cg.id_cliente
    JOIN grupos g ON g.id_grupo = cg.id_grupo
    WHERE cg.tipo_cliente = 'ENDOSADOR'
    ORDER BY cg.id DESC LIMIT 5
");
$recientes = [];
while ($r = mysqli_fetch_assoc($qRecientes)) $recientes[] = $r;

// Grupos endosadores activos
$qGrupos = mysqli_query($conexion,"
SELECT
    g.*,
    COUNT(cg.id) AS registrados
FROM grupos g
LEFT JOIN clientes_grupo cg
    ON cg.id_grupo = g.id_grupo
WHERE g.nombre_grupo LIKE 'C-END-%'
GROUP BY g.id_grupo
ORDER BY g.id_grupo DESC
LIMIT 5
");
$grupos_end = [];
while ($g2 = mysqli_fetch_assoc($qGrupos)) $grupos_end[] = $g2;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Agregar Cliente Endosador — KB Tours</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root{
    --brand:#1a56db;--brand-dark:#1e40af;--brand-light:#dbeafe;
    --surface:#fff;--surface-2:#f8fafc;--border:#e2e8f0;
    --text:#0f172a;--text-muted:#64748b;
    --success:#16a34a;--warning:#d97706;--danger:#dc2626;
    --radius:12px;--shadow:0 1px 3px rgba(0,0,0,.06),0 4px 16px rgba(0,0,0,.04);
}
*,*::before,*::after{box-sizing:border-box}
body{font-family:'DM Sans',sans-serif;background:var(--surface-2);color:var(--text);font-size:14px}

/* PAGE HEADER */
.page-header{background:var(--surface);border-bottom:1px solid var(--border);padding:16px 32px;display:flex;align-items:center;gap:14px;position:sticky;top:0;z-index:100}
.back-btn{display:flex;align-items:center;gap:6px;color:var(--text-muted);text-decoration:none;font-size:13px;font-weight:500;padding:6px 12px;border-radius:8px;border:1px solid var(--border);transition:all .15s;white-space:nowrap}
.back-btn:hover{background:var(--surface-2);color:var(--text)}
.page-header h1{font-size:17px;font-weight:700;margin:0}
.page-header .subtitle{color:var(--text-muted);font-size:12px;margin:0}

/* LAYOUT */
.main-content{max-width:1060px;margin:0 auto;padding:28px 24px 60px}
.form-grid{display:grid;grid-template-columns:1fr 320px;gap:24px;align-items:start}

/* CARDS */
.kb-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow);margin-bottom:20px;overflow:hidden}
.kb-card-header{display:flex;align-items:center;justify-content:space-between;padding:13px 18px;border-bottom:1px solid var(--border);background:var(--surface-2)}
.section-title{display:flex;align-items:center;gap:9px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--text)}
.section-icon{width:26px;height:26px;border-radius:7px;display:flex;align-items:center;justify-content:center;font-size:13px;color:#fff;flex-shrink:0}
.kb-card-body{padding:18px}

/* FORM FIELDS */
.field-grid-2{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.field-grid-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px}
.field-group{display:flex;flex-direction:column;gap:5px;margin-bottom:0}
.field-label{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--text-muted)}
.field-label .req{color:#dc2626;margin-left:2px}
.field-input{background:var(--surface-2);border:1.5px solid var(--border);border-radius:9px;padding:9px 13px;font-size:13px;font-family:'DM Sans',sans-serif;color:var(--text);width:100%;outline:none;transition:border-color .15s,background .15s}
.field-input:focus{border-color:var(--brand);background:#fff;box-shadow:0 0 0 3px rgba(26,86,219,.07)}
.field-input::placeholder{color:#94a3b8}
select.field-input{cursor:pointer}
.field-hint{font-size:11px;color:var(--text-muted);margin-top:1px}

/* DIVIDER */
.form-divider{display:flex;align-items:center;gap:10px;margin:18px 0;color:var(--text-muted);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.7px}
.form-divider::before,.form-divider::after{content:'';flex:1;height:1px;background:var(--border)}

/* GRUPO BANNER */
.grupo-banner{border-radius:10px;padding:14px 16px;display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:18px}
.grupo-banner.active{background:#dcfce7;border:1px solid #bbf7d0}
.grupo-banner.new{background:#dbeafe;border:1px solid #bfdbfe}
.grupo-banner .grupo-info{display:flex;align-items:center;gap:10px}
.grupo-banner .grupo-icon{width:36px;height:36px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:17px;flex-shrink:0}
.grupo-banner.active .grupo-icon{background:#bbf7d0;color:#15803d}
.grupo-banner.new .grupo-icon{background:#bfdbfe;color:#1a56db}
.grupo-code{font-family:'DM Mono',monospace;font-weight:700;font-size:14px}
.grupo-sub{font-size:12px;color:var(--text-muted);margin-top:1px}
.grupo-counter{text-align:right}
.grupo-counter .num{font-size:20px;font-weight:700;font-family:'DM Mono',monospace;line-height:1}
.grupo-counter .label{font-size:11px;color:var(--text-muted)}

/* PROGRESS */
.prog-bar{height:6px;background:var(--border);border-radius:3px;overflow:hidden;margin-top:8px}
.prog-fill{height:100%;border-radius:3px;transition:width .4s}

/* SUBMIT */
.btn-submit{width:100%;display:flex;align-items:center;justify-content:center;gap:8px;background:var(--brand);color:#fff;border:none;padding:12px;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;font-family:'DM Sans',sans-serif;transition:background .15s,transform .1s;letter-spacing:.2px}
.btn-submit:hover{background:var(--brand-dark)}
.btn-submit:active{transform:scale(.98)}

/* ALERTS */
.alert-kb{padding:12px 16px;border-radius:10px;font-size:13px;font-weight:500;display:flex;align-items:flex-start;gap:10px;margin-bottom:18px}
.alert-kb i{font-size:16px;flex-shrink:0;margin-top:1px}
.alert-success-kb{background:#dcfce7;border:1px solid #bbf7d0;color:#15803d}
.alert-error-kb{background:#fee2e2;border:1px solid #fecaca;color:#b91c1c}
.alert-info-kb{background:#dbeafe;border:1px solid #bfdbfe;color:#1e40af}

/* SIDEBAR CARDS */
.reciente-item{display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--border)}
.reciente-item:last-child{border-bottom:none}
.r-avatar{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:#fff;flex-shrink:0}
.r-name{font-weight:600;font-size:13px}
.r-sub{font-size:11px;color:var(--text-muted)}

.estado-badge{display:inline-flex;align-items:center;gap:4px;padding:2px 9px;border-radius:20px;font-size:11px;font-weight:600}
.estado-abierto{background:#dcfce7;color:#15803d}
.estado-cerrado{background:#fee2e2;color:#b91c1c}

@media(max-width:900px){.form-grid{grid-template-columns:1fr}.field-grid-3{grid-template-columns:1fr 1fr}.page-header{padding:13px 16px}}
@media(max-width:600px){.field-grid-2,.field-grid-3{grid-template-columns:1fr}.main-content{padding:14px 12px 60px}}
</style>
</head>
<body>
<?php include './../sidebar.php'; ?>
<div class="kb-content"> 
<!-- ════ HEADER ════ -->
<div class="page-header">
    <a href="javascript:history.back()" class="back-btn">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
    <div>
        <h1><i class="bi bi-building-fill-add text-primary me-2"></i>Agregar Cliente Endosador</h1>
        <p class="subtitle">Registro de clientes de empresas endosadoras</p>
    </div>
    <?php if (!empty($grupo)): ?>
    <div class="ms-auto d-flex align-items-center gap-2" style="font-size:13px;color:var(--text-muted)">
        <i class="bi bi-collection"></i>
        Grupo activo: <span style="font-family:'DM Mono',monospace;font-weight:700;color:#15803d"><?= htmlspecialchars($grupo['nombre_grupo']) ?></span>
    </div>
    <?php endif; ?>
</div>

<div class="main-content">

<!-- ════ MENSAJE ════ -->
<?php if ($mensaje): ?>
<div class="alert-kb alert-<?= $msg_type === 'success' ? 'success' : ($msg_type === 'error' ? 'error' : 'info') ?>-kb">
    <i class="bi bi-<?= $msg_type === 'success' ? 'check-circle-fill' : ($msg_type === 'error' ? 'x-circle-fill' : 'info-circle-fill') ?>"></i>
    <div><?= $mensaje ?></div>
</div>
<?php endif; ?>

<div class="form-grid">

    <!-- ════ COLUMNA IZQUIERDA: FORMULARIO ════ -->
    <div>
    <form method="POST" autocomplete="off">

        <!-- BANNER GRUPO -->
        <?php if ($mostrarCantidadGrupo): ?>
        <div class="grupo-banner new">
            <div class="grupo-info">
                <div class="grupo-icon"><i class="bi bi-plus-circle-fill"></i></div>
                <div>
                    <div class="grupo-code">Nuevo grupo</div>
                    <div class="grupo-sub">Se creará automáticamente al guardar</div>
                </div>
            </div>
        </div>
        <?php else:
            $pct = $grupo['cantidad'] > 0 ? round($grupo['registrados']/$grupo['cantidad']*100) : 0;
        ?>
        <div class="grupo-banner active">
            <div class="grupo-info">
                <div class="grupo-icon"><i class="bi bi-people-fill"></i></div>
                <div>
                    <div class="grupo-code"><?= htmlspecialchars($grupo['nombre_grupo']) ?></div>
                    <div class="grupo-sub">Grupo activo — <?= $grupo['cantidad'] - $grupo['registrados'] ?> cupo(s) disponibles</div>
                    <div class="prog-bar" style="width:140px">
                        <div class="prog-fill" style="width:<?= $pct ?>%;background:#16a34a"></div>
                    </div>
                </div>
            </div>
            <div class="grupo-counter">
                <div class="num" style="color:#15803d"><?= $grupo['registrados'] ?><span style="color:var(--text-muted);font-size:13px"> / <?= $grupo['cantidad'] ?></span></div>
                <div class="label">registrados</div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ── CARD: DATOS CLIENTE ── -->
        <div class="kb-card">
            <div class="kb-card-header">
                <div class="section-title">
                    <span class="section-icon" style="background:#1a56db"><i class="bi bi-person-fill"></i></span>
                    Datos del Cliente
                </div>
            </div>
            <div class="kb-card-body">

                <?php if (!$hayGrupoActivo): ?>
                <!-- Cantidad solo si no hay grupo -->
                <div class="field-group" style="margin-bottom:16px">
                    <label class="field-label" for="cantidad_grupo">
                        <i class="bi bi-people me-1"></i>Capacidad del nuevo grupo<span class="req">*</span>
                    </label>
                    <input type="number" class="field-input" name="cantidad_grupo" id="cantidad_grupo"
                        min="1" max="100" placeholder="Ej: 10" required>
                    <span class="field-hint">Número total de clientes que tendrá el grupo</span>
                </div>
                <div class="form-divider">Datos personales</div>
                <?php endif; ?>

                <div class="field-grid-2" style="margin-bottom:14px">
                    <div class="field-group">
                        <label class="field-label" for="nombre">Nombre<span class="req">*</span></label>
                        <input type="text" class="field-input" name="nombre" id="nombre"
                            placeholder="Ej: Carlos" required
                            value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>">
                    </div>
                    <div class="field-group">
                        <label class="field-label" for="apellido">Apellido<span class="req">*</span></label>
                        <input type="text" class="field-input" name="apellido" id="apellido"
                            placeholder="Ej: López" required
                            value="<?= htmlspecialchars($_POST['apellido'] ?? '') ?>">
                    </div>
                </div>

                <div class="field-grid-2">
                    <div class="field-group">
                        <label class="field-label" for="genero">Género<span class="req">*</span></label>
                        <select class="field-input" name="genero" id="genero" required>
                            <option value="">Seleccionar…</option>
                            <option value="M" <?= ($_POST['genero']??'')==='M'?'selected':'' ?>>Masculino</option>
                            <option value="F" <?= ($_POST['genero']??'')==='F'?'selected':'' ?>>Femenino</option>
                            <option value="Otro" <?= ($_POST['genero']??'')==='Otro'?'selected':'' ?>>Otro</option>
                        </select>
                    </div>
                    <div class="field-group">
                        <label class="field-label" for="nro_pasaporte">N° Pasaporte<span class="req">*</span></label>
                        <input type="text" class="field-input" name="nro_pasaporte" id="nro_pasaporte"
                            placeholder="Ej: AB123456" required
                            value="<?= htmlspecialchars($_POST['nro_pasaporte'] ?? '') ?>">
                        <span class="field-hint">Debe ser único en el sistema</span>
                    </div>
                </div>

            </div>
        </div>

        <!-- ── CARD: DATOS EMPRESA ── -->
        <div class="kb-card">
            <div class="kb-card-header">
                <div class="section-title">
                    <span class="section-icon" style="background:#7c3aed"><i class="bi bi-building-fill"></i></span>
                    Empresa Endosadora
                </div>
            </div>
            <div class="kb-card-body">

                <div class="field-group" style="margin-bottom:14px">
                    <label class="field-label" for="empresa_endosadora">Nombre de la empresa<span class="req">*</span></label>
                    <input type="text" class="field-input" name="empresa_endosadora" id="empresa_endosadora"
                        placeholder="Ej: Turismo Andes S.A.C." required
                        value="<?= htmlspecialchars($_POST['empresa_endosadora'] ?? '') ?>">
                </div>

                <div class="field-group" style="margin-bottom:14px">
                    <label class="field-label" for="contacto">Persona de contacto</label>
                    <input type="text" class="field-input" name="contacto" id="contacto"
                        placeholder="Ej: Ana García"
                        value="<?= htmlspecialchars($_POST['contacto'] ?? '') ?>">
                </div>

                <div class="field-grid-2">
                    <div class="field-group">
                        <label class="field-label" for="telefono_contacto">
                            <i class="bi bi-telephone me-1"></i>Teléfono
                        </label>
                        <input type="text" class="field-input" name="telefono_contacto" id="telefono_contacto"
                            placeholder="Ej: +51 984 000 000"
                            value="<?= htmlspecialchars($_POST['telefono_contacto'] ?? '') ?>">
                    </div>
                    <div class="field-group">
                        <label class="field-label" for="email_contacto">
                            <i class="bi bi-envelope me-1"></i>Email
                        </label>
                        <input type="email" class="field-input" name="email_contacto" id="email_contacto"
                            placeholder="contacto@empresa.com"
                            value="<?= htmlspecialchars($_POST['email_contacto'] ?? '') ?>">
                    </div>
                </div>

            </div>
        </div>

        <!-- ── BOTÓN GUARDAR ── -->
        <button type="submit" class="btn-submit">
            <i class="bi bi-floppy-fill"></i>
            Guardar Cliente Endosador
        </button>

    </form>
    </div>

    <!-- ════ COLUMNA DERECHA: SIDEBAR INFO ════ -->
    <div>

        <!-- Grupos endosadores -->
        <div class="kb-card">
            <div class="kb-card-header">
                <div class="section-title">
                    <span class="section-icon" style="background:#d97706"><i class="bi bi-collection-fill"></i></span>
                    Grupos Endosadores
                </div>
            </div>
            <div class="kb-card-body" style="padding:14px 16px">
            <?php if (empty($grupos_end)): ?>
                <div style="text-align:center;color:var(--text-muted);padding:16px;font-size:13px">
                    <i class="bi bi-collection" style="font-size:22px;opacity:.3;display:block;margin-bottom:6px"></i>
                    Sin grupos creados aún
                </div>
            <?php else: ?>
                <?php foreach ($grupos_end as $ge):
                    $pct2 = $ge['cantidad'] > 0 ? round($ge['registrados']/$ge['cantidad']*100) : 0;
                    $full = $ge['registrados'] >= $ge['cantidad'];
                ?>
                <div style="padding:10px 0;border-bottom:1px solid var(--border)">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:5px">
                        <span style="font-family:'DM Mono',monospace;font-weight:700;font-size:13px">
                            <?= htmlspecialchars($ge['nombre_grupo']) ?>
                        </span>
                        <span class="estado-badge <?= $full?'estado-cerrado':'estado-abierto' ?>">
                            <i class="bi bi-circle-fill" style="font-size:6px"></i>
                            <?= $full ? 'Lleno' : 'Activo' ?>
                        </span>
                    </div>
                    <div style="display:flex;justify-content:space-between;font-size:11px;color:var(--text-muted);margin-bottom:5px">
                        <span><?= $ge['registrados'] ?> / <?= $ge['cantidad'] ?> registrados</span>
                        <span><?= $pct2 ?>%</span>
                    </div>
                    <div class="prog-bar">
                        <div class="prog-fill" style="width:<?= $pct2 ?>%;background:<?= $full?'#dc2626':'#16a34a' ?>"></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <div style="border-bottom:none"></div>
            <?php endif; ?>
            </div>
        </div>

        <!-- Recientes -->
        <div class="kb-card">
            <div class="kb-card-header">
                <div class="section-title">
                    <span class="section-icon" style="background:#0891b2"><i class="bi bi-clock-history"></i></span>
                    Últimos Registrados
                </div>
            </div>
            <div class="kb-card-body" style="padding:14px 16px">
            <?php if (empty($recientes)): ?>
                <div style="text-align:center;color:var(--text-muted);padding:16px;font-size:13px">Sin registros aún</div>
            <?php else:
                $av_cols=['#1a56db','#16a34a','#d97706','#dc2626','#7c3aed'];
                foreach ($recientes as $idx => $r):
                    $ac = $av_cols[$idx % count($av_cols)];
            ?>
            <div class="reciente-item">
                <div class="r-avatar" style="background:<?= $ac ?>">
                    <?= strtoupper(substr($r['nombre'],0,1).substr($r['apellido']??'',0,1)) ?>
                </div>
                <div style="flex:1;min-width:0">
                    <div class="r-name"><?= htmlspecialchars($r['nombre'].' '.($r['apellido'] ?? '')) ?></div>
                    <div class="r-sub">
                        <i class="bi bi-building me-1"></i><?= htmlspecialchars($r['empresa_endosadora'] ?? '—') ?>
                        &nbsp;·&nbsp;
                        <span style="font-family:'DM Mono',monospace"><?= htmlspecialchars($r['nombre_grupo']) ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; endif; ?>
            </div>
        </div>

        <!-- Tips -->
        <div style="background:#fefce8;border:1px solid #fde68a;border-radius:var(--radius);padding:14px 16px">
            <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#92400e;margin-bottom:8px">
                <i class="bi bi-lightbulb-fill me-1"></i>Info
            </div>
            <ul style="margin:0;padding-left:16px;font-size:12px;color:#78350f;line-height:1.7">
                <li>Los grupos se nombran automáticamente como <b>C-END-XXX</b>.</li>
                <li>Si hay un grupo activo con cupo, el cliente se agrega a ese grupo.</li>
                <li>Cuando el grupo se llena, el siguiente registro abre uno nuevo.</li>
                <li>El número de pasaporte debe ser único.</li>
            </ul>
        </div>

    </div>
</div><!-- /.form-grid -->
</div>
</div><!-- /.main-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Resaltar campo al enfocar
document.querySelectorAll('.field-input').forEach(el => {
    el.addEventListener('input', function() {
        if (this.value.trim()) this.style.borderColor = '#16a34a';
        else this.style.borderColor = '';
    });
});

// Pasaporte en mayúsculas
document.getElementById('nro_pasaporte')?.addEventListener('input', function() {
    this.value = this.value.toUpperCase();
});
</script>
</body>
</html>