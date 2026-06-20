<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["usuario"])) {
    header("Location: ../index.php");
    exit();
}

function limpiar($v) { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

$usuario = limpiar($_SESSION["usuario"] ?? "Usuario");
$area    = limpiar($_SESSION["area"]    ?? "Área");
$esAdmin = $_SESSION["EsAdmin"] ?? 0;

// Iniciales del usuario para el avatar
$partes   = explode(" ", $usuario);
$iniciales = strtoupper(substr($partes[0], 0, 1) . (isset($partes[1]) ? substr($partes[1], 0, 1) : ''));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel KB Adventures</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
    /* ─── RESET & BASE ─────────────────────────────────────────── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
        --kb-bg:      #f8fafc;
        --kb-panel:   #161b26;
        --kb-card:    #ffffff;
        --kb-border:  #e2e8f0;
        --kb-accent:  #f59e0b;
        --kb-accent2: #3b82f6;
        --kb-text:    #1e293b;
        --kb-muted:   #64748b;
        --kb-hover:   rgba(245,158,11,0.08);
        --kb-active:  rgba(245,158,11,0.14);
        --transition: 0.32s cubic-bezier(0.4,0,0.2,1);

  

    }

    html, body { height: 100%; }

    body {
        font-family: 'Outfit', sans-serif;
        background: var(--kb-bg);
        color: var(--kb-text);
        display: flex;
        flex-direction: column;
        overflow-x: hidden;
    }

    /* ─── NAVBAR ────────────────────────────────────────────────── */
    .kb-navbar {
        position: fixed;
        top: 0; left: 0; right: 0;
        height: 56px;
        background: var(--kb-panel);
        border-bottom: 1px solid var(--kb-border);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 20px;
        z-index: 100;
        gap: 16px;
    }

    .kb-navbar .nav-left  { display: flex; align-items: center; gap: 12px; }
    .kb-navbar .nav-right { display: flex; align-items: center; gap: 8px; }

    .toggle-sidebar-btn {
        width: 36px; height: 36px;
        border-radius: 9px;
        background: transparent;
        border: 1px solid var(--kb-border);
        color: var(--kb-muted);
        display: flex; align-items: center; justify-content: center;
        cursor: pointer;
        transition: background var(--transition), color var(--transition);
        font-size: 16px;
    }
    .toggle-sidebar-btn:hover { background: var(--kb-hover); color: var(--kb-accent); }

    .kb-brand { display: flex; align-items: center; gap: 10px; text-decoration: none; }

    .kb-logo {
        width: 32px; height: 32px;
        border-radius: 9px;
        background: linear-gradient(135deg, #f59e0b, #d97706);
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: 14px; color: #0f1117;
        box-shadow: 0 0 18px rgba(245,158,11,0.25);
        letter-spacing: -0.5px;
    }

    .kb-brandname {
        font-size: 15px; font-weight: 600; color: var(--kb-text);
        white-space: nowrap;
    }

    .nav-search {
        background: rgba(255,255,255,0.04);
        border: 1px solid var(--kb-border);
        border-radius: 8px;
        padding: 7px 14px;
        display: flex; align-items: center; gap: 8px;
        min-width: 220px;
    }
    .nav-search span { font-size: 13px; color: var(--kb-muted); }
    .nav-search i    { font-size: 13px; color: var(--kb-muted); }

    .nav-icon-btn {
        width: 34px; height: 34px;
        border-radius: 8px;
        background: rgba(255,255,255,0.04);
        border: 1px solid var(--kb-border);
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; color: var(--kb-muted); font-size: 15px;
        transition: background var(--transition), color var(--transition);
        text-decoration: none; position: relative;
    }
    .nav-icon-btn:hover { background: var(--kb-hover); color: var(--kb-accent); }

    .notif-badge::after {
        content: '';
        width: 7px; height: 7px;
        background: var(--kb-accent);
        border-radius: 50%;
        position: absolute; top: -1px; right: -1px;
        border: 2px solid var(--kb-panel);
    }

    .nav-avatar {
        width: 32px; height: 32px;
        border-radius: 50%;
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        display: flex; align-items: center; justify-content: center;
        font-size: 12px; font-weight: 600; color: #fff;
        cursor: pointer;
        border: 2px solid rgba(59,130,246,0.3);
    }

    .nav-area-label {
        font-size: 13px; color: var(--kb-muted);
        white-space: nowrap;
    }

    /* ─── LAYOUT ────────────────────────────────────────────────── */
    .kb-layout {
    display: flex;
    min-height: 100vh;
}

    /* ─── SIDEBAR ───────────────────────────────────────────────── */
    .kb-sidebar {
        position: fixed;
        top: 56px; left: 0; bottom: 0;
        width: 256px;
        background: var(--kb-panel);
        border-right: 1px solid var(--kb-border);
        display: flex;
        flex-direction: column;
        transition: width var(--transition), transform var(--transition);
        overflow: hidden;
        z-index: 90;
    }

    /* collapsed desktop */
    .kb-sidebar.collapsed { width: 64px; }

    /* mobile: hidden off-screen */
    @media (max-width: 992px) {
        .kb-sidebar { transform: translateX(-100%); width: 256px !important; }
        .kb-sidebar.mobile-open { transform: translateX(0); }
    }

    /* ── user section ── */
    .sb-user {
        padding: 16px;
        border-bottom: 1px solid var(--kb-border);
        display: flex; align-items: center; gap: 10px;
        overflow: hidden;
    }

    .sb-avatar {
        width: 36px; height: 36px; min-width: 36px;
        border-radius: 50%;
        background: linear-gradient(135deg, #3b82f6, #1d4ed8);
        display: flex; align-items: center; justify-content: center;
        font-size: 13px; font-weight: 600; color: #fff;
        border: 2px solid rgba(59,130,246,0.25);
        flex-shrink: 0;
    }

    .sb-user-meta { overflow: hidden; white-space: nowrap; }
    .sb-user-meta h5  { font-size: 13px; font-weight: 500; color: var(--kb-text); line-height: 1.3; }
    .sb-user-meta .badge-role {
        display: inline-block;
        font-size: 10px;
        background: rgba(245,158,11,0.15);
        color: var(--kb-accent);
        border-radius: 4px;
        padding: 1px 7px;
        font-weight: 500;
        margin-top: 2px;
    }

    /* hide labels when collapsed */
    .kb-sidebar.collapsed .sb-user-meta,
    .kb-sidebar.collapsed .nav-label,
    .kb-sidebar.collapsed .chevron,
    .kb-sidebar.collapsed .section-label { opacity: 0; width: 0; overflow: hidden; pointer-events: none; }

    /* ── nav scroll area ── */
    .sb-nav {
        flex: 1;
        overflow-y: auto;
        padding: 10px 8px;
        scrollbar-width: thin;
        scrollbar-color: rgba(255,255,255,0.08) transparent;
    }
    .sb-nav::-webkit-scrollbar { width: 4px; }
    .sb-nav::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.08); border-radius: 2px; }

    .section-label {
        font-size: 10px; font-weight: 600;
        color: var(--kb-muted);
        letter-spacing: 0.08em;
        text-transform: uppercase;
        padding: 10px 10px 4px;
        white-space: nowrap;
        transition: opacity var(--transition);
    }

    /* ── nav items ── */
    .nav-item {
        display: flex; align-items: center; gap: 10px;
        padding: 9px 10px;
        border-radius: 9px;
        cursor: pointer; transition: background var(--transition), color var(--transition);
        color: var(--kb-muted);
        font-size: 13.5px; font-weight: 400;
        text-decoration: none;
        white-space: nowrap;
        user-select: none;
        position: relative;
    }
    .nav-item:hover  { background: var(--kb-hover); color: var(--kb-text); }
    .nav-item.active { background: var(--kb-active); color: var(--kb-accent); }
    .nav-item.active .nav-icon-wrap { color: var(--kb-accent); }

    .nav-icon-wrap {
        width: 20px; min-width: 20px;
        display: flex; align-items: center; justify-content: center;
        font-size: 16px;
    }

    .nav-label { flex: 1; transition: opacity var(--transition), width var(--transition); }

    .chevron {
        font-size: 11px; transition: transform 0.25s;
        color: var(--kb-muted);
    }
    .chevron.open { transform: rotate(90deg); }

    /* ── submenus ── */
    .submenu { overflow: hidden; max-height: 0; transition: max-height 0.32s cubic-bezier(0.4,0,0.2,1); }
    .submenu.open { max-height: 400px; }

    .submenu .nav-item { padding-left: 36px; font-size: 12.5px; }

    .sub-dot {
        width: 5px; height: 5px; border-radius: 50%;
        background: var(--kb-muted); min-width: 5px;
        transition: background var(--transition);
    }
    .submenu .nav-item:hover .sub-dot,
    .submenu .nav-item.active .sub-dot { background: var(--kb-accent); }

    /* ── tooltip when collapsed ── */
    .kb-sidebar:not(.collapsed) .sb-tooltip { display: none; }
    .sb-tooltip {
        position: absolute;
        left: calc(100% + 10px); top: 50%;
        transform: translateY(-50%) translateX(-6px);
        background: var(--kb-card);
        border: 1px solid var(--kb-border);
        color: var(--kb-text);
        font-size: 12px;
        padding: 5px 10px; border-radius: 7px;
        white-space: nowrap; opacity: 0; pointer-events: none;
        transition: opacity 0.15s, transform 0.15s;
        z-index: 200;
    }
    .nav-item:hover .sb-tooltip {
        opacity: 1;
        transform: translateY(-50%) translateX(0);
    }

    /* ── bottom section ── */
    .sb-bottom {
        padding: 10px 8px;
        border-top: 1px solid var(--kb-border);
    }
    .nav-item.danger:hover { background: rgba(239,68,68,0.1); color: #ef4444; }

    /* ─── CONTENT ───────────────────────────────────────────────── */
    .kb-content {
         margin-left: 256px;
    padding: 20px;
    margin-top:56px;
        flex: 1;
        transition: margin-left var(--transition);
        min-height: calc(100vh - 56px);
    }

    .kb-sidebar.collapsed ~ .kb-content { margin-left: 64px; }

    @media (max-width: 992px) {
        .kb-content { margin-left: 0 !important; }
    }

    /* ─── OVERLAY (mobile) ──────────────────────────────────────── */
    .kb-overlay {
        display: none;
        position: fixed; inset: 0;
        background: rgba(0,0,0,0.5);
        z-index: 89;
        backdrop-filter: blur(2px);
    }
    .kb-overlay.active { display: block; }
    </style>
</head>
<body>

<!-- ═══ NAVBAR ══════════════════════════════════════════════════ -->
<nav class="kb-navbar" role="navigation" aria-label="Barra principal">
    <div class="nav-left">
        <button class="toggle-sidebar-btn" id="toggle-sidebar" aria-label="Alternar menú lateral" aria-expanded="true">
            <i class="fas fa-bars"></i>
        </button>
        <a href="/pages/principal.php" class="kb-brand" aria-label="KB Adventures – Inicio">
            <span class="kb-logo">KB</span>
            <span class="kb-brandname">KB Adventures</span>
        </a>
    </div>

    <div class="nav-search d-none d-md-flex" role="search">
        <i class="fas fa-search"></i>
        <span>Buscar...</span>
    </div>

    <div class="nav-right">
        <span class="nav-area-label d-none d-sm-block"><?= $area ?></span>
        <a href="#" class="nav-icon-btn notif-badge" title="Notificaciones" aria-label="Notificaciones">
            <i class="fas fa-bell"></i>
        </a>
        <a href="../index.php" class="nav-icon-btn" title="Cerrar sesión" aria-label="Cerrar sesión">
            <i class="fas fa-sign-out-alt"></i>
        </a>
        <div class="nav-avatar" title="<?= $usuario ?>" aria-label="Usuario <?= $usuario ?>">
            <?= $iniciales ?>
        </div>
    </div>
</nav>

<!-- ═══ OVERLAY MOBILE ══════════════════════════════════════════ -->
<div class="kb-overlay" id="kb-overlay" aria-hidden="true"></div>

<!-- ═══ LAYOUT ══════════════════════════════════════════════════ -->
<div class="kb-layout">

    <!-- ── SIDEBAR ────────────────────────────────────────── -->
    <aside class="kb-sidebar" id="kb-sidebar" role="navigation" aria-label="Menú lateral">

        <!-- Usuario -->
        <div class="sb-user">
            <div class="sb-avatar" aria-hidden="true"><?= $iniciales ?></div>
            <div class="sb-user-meta">
                <h5><?= $usuario ?></h5>
                <?php if ($esAdmin == 1): ?>
                    <span class="badge-role">Admin</span>
                <?php else: ?>
                    <span class="badge-role"><?= $area ?></span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Navegación -->
        <div class="sb-nav">

            <div class="section-label">Principal</div>

            <a href="/pages/principal.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) == 'principal.php' ? 'active' : '' ?>">
                <span class="nav-icon-wrap"><i class="fas fa-home" aria-hidden="true"></i></span>
                <span class="nav-label">Inicio</span>
                <span class="sb-tooltip">Inicio</span>
            </a>

            <?php if ($esAdmin == 1 || $area == "Operaciones" || $area == "Clientes"): ?>
                <div class="section-label">Módulos</div>

                <div class="nav-item submenu-toggle" data-target="sub-operaciones" role="button" aria-expanded="false" aria-controls="sub-operaciones" tabindex="0">
                    <span class="nav-icon-wrap"><i class="fas fa-tasks" aria-hidden="true"></i></span>
                    <span class="nav-label">Operaciones</span>
                    <i class="fas fa-chevron-right chevron" aria-hidden="true"></i>
                    <span class="sb-tooltip">Operaciones</span>
                </div>
                <div class="submenu" id="sub-operaciones">
                    <a href="/pages/clientes_kb/index.php" class="nav-item">
                        <span class="sub-dot" aria-hidden="true"></span>
                        <span class="nav-label">Clientes KB</span>
                    </a>
                    <a href="/pages/cliente_endosador/index.php" class="nav-item">
                        <span class="sub-dot" aria-hidden="true"></span>
                        <span class="nav-label">Clientes Endosador</span>
                    </a>
                    <a href="/pages/Area_Operaciones/ope-KB/index.php" class="nav-item">
                        <span class="sub-dot" aria-hidden="true"></span>
                        <span class="nav-label">Operaciones KB</span>
                    </a>
                    <a href="/pages/Area_Operaciones/ope-ENDOSAD/index.php" class="nav-item">
                        <span class="sub-dot" aria-hidden="true"></span>
                        <span class="nav-label">Operaciones Endosador</span>
                    </a>
                    <a href="/pages/alma/dashboard_almacen.php" class="nav-item">
                        <span class="sub-dot" aria-hidden="true"></span>
                        <span class="nav-label">Almacén</span>
                    </a>
                </div>
            <?php endif; ?>

            <?php if ($esAdmin == 1 || $area == "Planificación"): ?>
                <a href="/pages/Area_planificacion/index.php" class="nav-item">
                    <span class="nav-icon-wrap"><i class="fas fa-cogs" aria-hidden="true"></i></span>
                    <span class="nav-label">Planificación</span>
                    <span class="sb-tooltip">Planificación</span>
                </a>
            <?php endif; ?>

            <?php if ($esAdmin == 1 || $area == "Contabilidad"): ?>
                <a href="/pages/Area_contabilidad/index.php" class="nav-item">
                    <span class="nav-icon-wrap"><i class="fas fa-calculator" aria-hidden="true"></i></span>
                    <span class="nav-label">Contabilidad</span>
                    <span class="sb-tooltip">Contabilidad</span>
                </a>
            <?php endif; ?>

            <?php if ($esAdmin == 1): ?>
                <a href="/pages/admin/resumen.php" class="nav-item">
                    <span class="nav-icon-wrap"><i class="fas fa-chart-bar" aria-hidden="true"></i></span>
                    <span class="nav-label">Resumen</span>
                    <span class="sb-tooltip">Resumen</span>
                </a>
            <?php endif; ?>

        </div><!-- /sb-nav -->

        <!-- Logout abajo -->
        <div class="sb-bottom">
            <a href="../index.php" class="nav-item danger">
                <span class="nav-icon-wrap"><i class="fas fa-sign-out-alt" aria-hidden="true"></i></span>
                <span class="nav-label">Cerrar sesión</span>
                <span class="sb-tooltip">Cerrar sesión</span>
            </a>
        </div>

    </aside><!-- /kb-sidebar -->




<!-- ═══ SCRIPTS ═════════════════════════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    const sidebar   = document.getElementById('kb-sidebar');
    const overlay   = document.getElementById('kb-overlay');
    const toggleBtn = document.getElementById('toggle-sidebar');
    const isMobile  = () => window.innerWidth <= 992;

    /* ── toggle ── */
    function toggleSidebar() {
        if (isMobile()) {
            const open = sidebar.classList.toggle('mobile-open');
            overlay.classList.toggle('active', open);
            toggleBtn.setAttribute('aria-expanded', open);
        } else {
            const collapsed = sidebar.classList.toggle('collapsed');
            toggleBtn.setAttribute('aria-expanded', !collapsed);
        }
    }

    toggleBtn.addEventListener('click', toggleSidebar);
    overlay.addEventListener('click', () => {
        sidebar.classList.remove('mobile-open');
        overlay.classList.remove('active');
    });

    /* ── submenus ── */
    document.querySelectorAll('.submenu-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            if (sidebar.classList.contains('collapsed')) return; // no abrir si colapsado
            const target   = document.getElementById(btn.dataset.target);
            const chevron  = btn.querySelector('.chevron');
            const isOpen   = target.classList.toggle('open');
            chevron.classList.toggle('open', isOpen);
            btn.setAttribute('aria-expanded', isOpen);
        });

        /* accesibilidad teclado */
        btn.addEventListener('keydown', e => {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); btn.click(); }
        });
    });

    /* ── active link ── */
    const current = window.location.pathname;
    document.querySelectorAll('.kb-sidebar .nav-item[href]').forEach(link => {
        if (link.getAttribute('href') === current) {
            link.classList.add('active');
            /* abrir submenú padre si existe */
            const parent = link.closest('.submenu');
            if (parent) {
                parent.classList.add('open');
                const toggle = document.querySelector(`[data-target="${parent.id}"]`);
                if (toggle) {
                    toggle.querySelector('.chevron')?.classList.add('open');
                    toggle.setAttribute('aria-expanded', 'true');
                }
            }
        }
    });

    /* ── responsive: al ampliar pantalla cerrar overlay ── */
    window.addEventListener('resize', () => {
        if (!isMobile()) {
            sidebar.classList.remove('mobile-open');
            overlay.classList.remove('active');
        }
    });
})();
</script>

</body>
</html>