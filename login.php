<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

include('conexion.php');

$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario_ingresado    = trim($_POST["usuario"]    ?? '');
    $contrasena_ingresada = trim($_POST["contrasena"] ?? '');
    $area_seleccionada    = trim($_POST["area"]        ?? '');

    if (empty($area_seleccionada)) {
        $error = "Por favor selecciona un área.";
    } else {
        $sql  = "SELECT id, usuario, contrasena, area, es_admin, rol FROM usuarios WHERE usuario = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("s", $usuario_ingresado);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $row             = $result->fetch_assoc();
            $contrasena_hash = $row["contrasena"];
            $area_usuario    = $row["area"];
            $es_admin        = $row["es_admin"];

            if (password_verify($contrasena_ingresada, $contrasena_hash)) {
                // Área de operaciones puede acceder a contabilidad y planificación
                $areas_permitidas = [$area_usuario];
                if ($area_usuario === "Operaciones") {
                    $areas_permitidas[] = "Contabilidad";
                    $areas_permitidas[] = "Planificación";
                }
                if ($es_admin) {
                    $areas_permitidas[] = "Administrador";
                }

                if (in_array($area_seleccionada, $areas_permitidas)) {
                    $_SESSION["id"]      = $row["id"];
                    $_SESSION["usuario"] = $row["usuario"];
                    $_SESSION["area"]    = $area_usuario;
                    $_SESSION["EsAdmin"] = $es_admin;
                    header("Location: pages/principal.php");
                    exit();
                } else {
                    $error = "El área seleccionada no está permitida para este usuario.";
                }
            } else {
                $error = "Usuario o contraseña incorrectos.";
            }
        } else {
            $error = "Usuario o contraseña incorrectos.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>KB Tours — Iniciar Sesión</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@500&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{
    font-family:'DM Sans',sans-serif;
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    background:#0f172a;
    position:relative;
    overflow:hidden;
}

/* FONDO ANIMADO */
.bg-layer{position:fixed;inset:0;z-index:0;overflow:hidden}
.bg-orb{position:absolute;border-radius:50%;filter:blur(80px);opacity:.35;animation:floatOrb 12s ease-in-out infinite alternate}
.bg-orb-1{width:500px;height:500px;background:#1a56db;top:-120px;left:-100px;animation-delay:0s}
.bg-orb-2{width:400px;height:400px;background:#7c3aed;bottom:-100px;right:-80px;animation-delay:3s}
.bg-orb-3{width:300px;height:300px;background:#0891b2;top:50%;left:55%;animation-delay:6s}
@keyframes floatOrb{from{transform:translate(0,0) scale(1)}to{transform:translate(30px,40px) scale(1.1)}}

/* GRID PATTERN */
.bg-grid{
    position:fixed;inset:0;z-index:1;
    background-image:linear-gradient(rgba(255,255,255,.03) 1px,transparent 1px),
                     linear-gradient(90deg,rgba(255,255,255,.03) 1px,transparent 1px);
    background-size:40px 40px;
}

/* CARD */
.login-wrap{position:relative;z-index:10;width:100%;max-width:420px;padding:16px}
.login-card{
    background:rgba(255,255,255,.04);
    backdrop-filter:blur(24px);
    -webkit-backdrop-filter:blur(24px);
    border:1px solid rgba(255,255,255,.1);
    border-radius:20px;
    padding:40px 36px;
    box-shadow:0 24px 60px rgba(0,0,0,.5);
}

/* LOGO */
.brand{text-align:center;margin-bottom:32px}
.brand-icon{
    width:64px;height:64px;border-radius:16px;
    background:linear-gradient(135deg,#1a56db,#7c3aed);
    display:flex;align-items:center;justify-content:center;
    font-size:28px;color:#fff;margin:0 auto 14px;
    box-shadow:0 8px 24px rgba(26,86,219,.4);
}
.brand-title{font-size:22px;font-weight:700;color:#fff;letter-spacing:-.3px}
.brand-sub{font-size:13px;color:rgba(255,255,255,.45);margin-top:4px}

/* FORM */
.field-group{margin-bottom:14px}
.field-label{
    display:block;font-size:11px;font-weight:700;
    text-transform:uppercase;letter-spacing:.7px;
    color:rgba(255,255,255,.5);margin-bottom:6px;
}
.field-wrap{position:relative}
.field-icon{
    position:absolute;left:14px;top:50%;transform:translateY(-50%);
    color:rgba(255,255,255,.3);font-size:16px;pointer-events:none;
}
.field-input{
    width:100%;
    background:rgba(255,255,255,.07);
    border:1.5px solid rgba(255,255,255,.1);
    border-radius:11px;
    padding:11px 14px 11px 42px;
    font-size:14px;font-family:'DM Sans',sans-serif;
    color:#fff;outline:none;
    transition:border-color .2s,background .2s,box-shadow .2s;
}
.field-input::placeholder{color:rgba(255,255,255,.25)}
.field-input:focus{
    border-color:rgba(99,140,255,.6);
    background:rgba(255,255,255,.1);
    box-shadow:0 0 0 3px rgba(26,86,219,.2);
}
.field-input option{background:#1e293b;color:#fff}
select.field-input{cursor:pointer}

/* TOGGLE PASSWORD */
.toggle-pass{
    position:absolute;right:14px;top:50%;transform:translateY(-50%);
    background:none;border:none;color:rgba(255,255,255,.3);
    cursor:pointer;padding:0;font-size:16px;transition:color .15s;
}
.toggle-pass:hover{color:rgba(255,255,255,.7)}

/* AREA BADGES */
.areas-hint{display:flex;flex-wrap:wrap;gap:5px;margin-top:7px}
.area-chip{
    display:inline-flex;align-items:center;gap:3px;
    padding:2px 9px;border-radius:20px;
    font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.4px;
    background:rgba(255,255,255,.08);color:rgba(255,255,255,.4);
    border:1px solid rgba(255,255,255,.08);
    transition:all .15s;cursor:default;
}

/* SUBMIT */
.btn-login{
    width:100%;margin-top:20px;
    background:linear-gradient(135deg,#1a56db,#7c3aed);
    color:#fff;border:none;padding:13px;
    border-radius:11px;font-size:15px;font-weight:700;
    font-family:'DM Sans',sans-serif;cursor:pointer;
    display:flex;align-items:center;justify-content:center;gap:8px;
    transition:opacity .2s,transform .15s,box-shadow .2s;
    box-shadow:0 4px 20px rgba(26,86,219,.4);
    letter-spacing:.2px;
}
.btn-login:hover{opacity:.92;transform:translateY(-1px);box-shadow:0 6px 28px rgba(26,86,219,.5)}
.btn-login:active{transform:translateY(0);opacity:1}
.btn-login .spinner{
    width:16px;height:16px;border:2px solid rgba(255,255,255,.3);
    border-top-color:#fff;border-radius:50%;
    animation:spin .6s linear infinite;display:none;
}
@keyframes spin{to{transform:rotate(360deg)}}

/* ERROR */
.error-box{
    background:rgba(220,38,38,.15);
    border:1px solid rgba(220,38,38,.3);
    border-radius:10px;padding:11px 14px;
    display:flex;align-items:center;gap:9px;
    color:#fca5a5;font-size:13px;font-weight:500;
    margin-bottom:18px;
    animation:shakeIn .4s ease;
}
@keyframes shakeIn{
    0%{transform:translateX(-6px);opacity:0}
    40%{transform:translateX(4px)}
    70%{transform:translateX(-2px)}
    100%{transform:translateX(0);opacity:1}
}

/* FOOTER */
.login-footer{
    text-align:center;margin-top:24px;
    font-size:12px;color:rgba(255,255,255,.25);
}
.login-footer span{font-family:'DM Mono',monospace}

/* DIVIDER */
.divider{height:1px;background:rgba(255,255,255,.08);margin:18px 0}

@media(max-width:480px){
    .login-card{padding:28px 20px}
    .brand-icon{width:54px;height:54px;font-size:24px}
}
</style>
</head>
<body>

<div class="bg-layer">
    <div class="bg-orb bg-orb-1"></div>
    <div class="bg-orb bg-orb-2"></div>
    <div class="bg-orb bg-orb-3"></div>
</div>
<div class="bg-grid"></div>

<div class="login-wrap">
<div class="login-card">

    <!-- BRAND -->
    <div class="brand">
        <div class="brand-icon"><i class="bi bi-compass-fill"></i></div>
        <div class="brand-title">KB Tours</div>
        <div class="brand-sub">Sistema de gestión turística · Cusco</div>
    </div>

    <!-- ERROR -->
    <?php if (!empty($error)): ?>
    <div class="error-box">
        <i class="bi bi-exclamation-circle-fill" style="font-size:16px;flex-shrink:0"></i>
        <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <!-- FORM -->
    <form method="POST" id="loginForm" autocomplete="off" novalidate>

        <!-- Usuario -->
        <div class="field-group">
            <label class="field-label" for="usuario">
                <i class="bi bi-person me-1"></i>Usuario
            </label>
            <div class="field-wrap">
                <i class="bi bi-person field-icon"></i>
                <input type="text" id="usuario" name="usuario" class="field-input"
                    placeholder="Tu nombre de usuario" required autocomplete="username"
                    value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>">
            </div>
        </div>

        <!-- Contraseña -->
        <div class="field-group">
            <label class="field-label" for="contrasena">
                <i class="bi bi-lock me-1"></i>Contraseña
            </label>
            <div class="field-wrap">
                <i class="bi bi-lock field-icon"></i>
                <input type="password" id="contrasena" name="contrasena" class="field-input"
                    placeholder="••••••••" required autocomplete="current-password"
                    style="padding-right:42px">
                <button type="button" class="toggle-pass" id="togglePass" title="Mostrar/ocultar">
                    <i class="bi bi-eye" id="eyeIcon"></i>
                </button>
            </div>
        </div>

        <div class="divider"></div>

        <!-- Área -->
        <div class="field-group">
            <label class="field-label" for="area">
                <i class="bi bi-grid me-1"></i>Área de acceso
            </label>
            <div class="field-wrap">
                <i class="bi bi-grid field-icon"></i>
                <select id="area" name="area" class="field-input" required>
                    <option value="" disabled <?= empty($_POST['area'])?'selected':'' ?>>Selecciona tu área…</option>
                    <?php
                    $areas = [
                        ['Operaciones',  'bi-clipboard2-data'],
                        ['Contabilidad', 'bi-file-earmark-text'],
                        ['Planificación','bi-calendar-check'],
                        ['Almacén',      'bi-box-seam'],
                        ['Administrador','bi-shield-check'],
                    ];
                    foreach ($areas as [$a, $icon]):
                        $sel = ($_POST['area'] ?? '') === $a ? 'selected' : '';
                    ?>
                    <option value="<?= $a ?>" <?= $sel ?>><?= $a ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="areas-hint" id="areasHint">
                <?php foreach ($areas as [$a, $icon]): ?>
                <span class="area-chip" data-area="<?= $a ?>">
                    <i class="bi <?= $icon ?>"></i> <?= $a ?>
                </span>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- SUBMIT -->
        <button type="submit" class="btn-login" id="btnLogin">
            <span class="spinner" id="spinner"></span>
            <i class="bi bi-box-arrow-in-right" id="btnIcon"></i>
            <span id="btnText">Ingresar al sistema</span>
        </button>

    </form>

    <!-- FOOTER -->
    <div class="login-footer">
        <span>KB Tours Admin</span> · <?= date('Y') ?> · Cusco, Perú
    </div>

</div>
</div>

<script>
// ── TOGGLE PASSWORD ──
document.getElementById('togglePass').addEventListener('click', function() {
    const inp  = document.getElementById('contrasena');
    const icon = document.getElementById('eyeIcon');
    if (inp.type === 'password') {
        inp.type  = 'text';
        icon.className = 'bi bi-eye-slash';
        this.style.color = 'rgba(255,255,255,.6)';
    } else {
        inp.type  = 'password';
        icon.className = 'bi bi-eye';
        this.style.color = '';
    }
});

// ── AREA CHIP HIGHLIGHT ──
const sel   = document.getElementById('area');
const chips = document.querySelectorAll('.area-chip');
function highlightChip() {
    chips.forEach(c => {
        if (c.dataset.area === sel.value) {
            c.style.background    = 'rgba(26,86,219,.25)';
            c.style.color         = 'rgba(99,163,255,.9)';
            c.style.borderColor   = 'rgba(26,86,219,.4)';
        } else {
            c.style.background  = '';
            c.style.color       = '';
            c.style.borderColor = '';
        }
    });
}
sel.addEventListener('change', highlightChip);
chips.forEach(c => c.addEventListener('click', () => { sel.value = c.dataset.area; highlightChip(); }));
highlightChip();

// ── SUBMIT FEEDBACK ──
document.getElementById('loginForm').addEventListener('submit', function(e) {
    const u  = document.getElementById('usuario').value.trim();
    const p  = document.getElementById('contrasena').value.trim();
    const a  = document.getElementById('area').value;
    if (!u || !p || !a) { e.preventDefault(); return; }

    document.getElementById('spinner').style.display = 'block';
    document.getElementById('btnIcon').style.display = 'none';
    document.getElementById('btnText').textContent    = 'Ingresando…';
    document.getElementById('btnLogin').style.opacity = '.8';
    document.getElementById('btnLogin').disabled      = true;
});

// ── FOCUS FIRST FIELD ──
window.addEventListener('load', () => {
    const u = document.getElementById('usuario');
    if (!u.value) u.focus();
    else document.getElementById('contrasena').focus();
});
</script>
</body>
</html>
