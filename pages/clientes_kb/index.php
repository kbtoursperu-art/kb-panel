<?php
ini_set('display_errors', 1);
include('../../conexion.php');
include('../sidebar.php');

require_once('../../vendor/autoload.php');
use PhpOffice\PhpSpreadsheet\IOFactory;

// ── Grupos para selects ──────────────────────────────────────────────────
$grupos = mysqli_query($conexion, "SELECT id_grupo, nombre_grupo FROM grupos ORDER BY nombre_grupo ASC");

// ── Stats rápidas ────────────────────────────────────────────────────────
$total_clientes  = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) AS c FROM clientes_grupo WHERE tipo_cliente='KB'"))['c'] ?? 0;
$total_grupos    = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) AS c FROM grupos WHERE estado='abierto'"))['c'] ?? 0;
$con_comida      = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) AS c FROM datos_clientes WHERE comida IS NOT NULL AND comida != ''"))['c'] ?? 0;
$nuevos_mes      = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) AS c FROM datos_clientes WHERE MONTH(fecha_registro)=MONTH(NOW()) AND YEAR(fecha_registro)=YEAR(NOW())"))['c'] ?? 0;

// ── Importar Excel ───────────────────────────────────────────────────────
$msg_importar = '';
if (isset($_POST['importar_excel'])) {
    $id_grupo = intval($_POST['id_grupo']);
    if ($id_grupo > 0 && isset($_FILES['archivo_excel']['tmp_name'])) {
        $archivo = $_FILES['archivo_excel']['tmp_name'];
        try {
            $documento = IOFactory::load($archivo);
            $hoja      = $documento->getActiveSheet();
            $filas     = $hoja->toArray();
            $insertados = 0;

            for ($i = 1; $i < count($filas); $i++) {
                if (empty(trim($filas[$i][0] ?? ''))) continue;

                $nombre           = mysqli_real_escape_string($conexion, trim($filas[$i][0] ?? ''));
                $apellido         = mysqli_real_escape_string($conexion, trim($filas[$i][1] ?? ''));
                $genero           = mysqli_real_escape_string($conexion, trim($filas[$i][2] ?? ''));
                $pasaporte        = mysqli_real_escape_string($conexion, trim($filas[$i][3] ?? ''));
                $fecha_nacimiento = mysqli_real_escape_string($conexion, trim($filas[$i][4] ?? ''));
                $whatsapp         = mysqli_real_escape_string($conexion, trim($filas[$i][5] ?? ''));
                $nacionalidad     = mysqli_real_escape_string($conexion, trim($filas[$i][6] ?? ''));
                $comida           = mysqli_real_escape_string($conexion, trim($filas[$i][7] ?? ''));

                mysqli_query($conexion, "
                    INSERT INTO datos_clientes (nombre, apellido, genero, nro_pasaporte, comida, nacionalidad, fecha_nacimiento)
                    VALUES ('$nombre','$apellido','$genero','$pasaporte','$comida','$nacionalidad','$fecha_nacimiento')
                ");
                $id_cliente = mysqli_insert_id($conexion);

                mysqli_query($conexion, "
                    INSERT INTO clientes_grupo (id_cliente, id_grupo, tipo_cliente)
                    VALUES ($id_cliente, $id_grupo, 'KB')
                    ON DUPLICATE KEY UPDATE id_grupo = id_grupo
                ");

                // Actualizar teléfono en datos_clientes
                if (!empty($whatsapp)) {
                    $ws = mysqli_real_escape_string($conexion, $whatsapp);
                    mysqli_query($conexion, "UPDATE datos_clientes SET telefono='$ws' WHERE id_cliente=$id_cliente");
                }

                $insertados++;
            }
            $msg_importar = "success|Se importaron $insertados clientes correctamente.";
        } catch (Exception $e) {
            $msg_importar = "error|Error al leer el archivo: " . htmlspecialchars($e->getMessage());
        }
    } else {
        $msg_importar = "error|Debes seleccionar un grupo y subir un archivo Excel.";
    }
}

// ── Consulta principal ───────────────────────────────────────────────────
$query_kb = "
SELECT
    d.id_cliente,
    d.nombre,
    d.apellido,
    d.genero,
    d.nro_pasaporte,
    d.nacionalidad,
    d.comida,
    d.fecha_nacimiento,
    d.telefono,
    d.hotel,
    cg.id_grupo,
    g.nombre_grupo,
    grp.total_pasajeros AS pasajeros

FROM clientes_grupo cg

INNER JOIN datos_clientes d
    ON d.id_cliente = cg.id_cliente

INNER JOIN grupos g
    ON g.id_grupo = cg.id_grupo

INNER JOIN (
    SELECT
        id_grupo,
        COUNT(*) AS total_pasajeros
    FROM clientes_grupo
    WHERE tipo_cliente='KB'
    GROUP BY id_grupo
) grp
    ON grp.id_grupo = cg.id_grupo

WHERE cg.tipo_cliente='KB'

ORDER BY g.nombre_grupo, d.id_cliente DESC
";
$result_kb = mysqli_query($conexion, $query_kb);
if (!$result_kb) die("<pre>ERROR SQL: " . mysqli_error($conexion) . "\n\n$query_kb</pre>");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes KB – KB Adventures</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

    <style>
    /* ─── BASE ─────────────────────────────────────────── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
    --bg:      #f8fafc;
    --panel:   #ffffff;
    --card:    #ffffff;
    --border:  #e2e8f0;
    --accent:  #2563eb;
    --success: #15803d;
    --danger:  #dc2626;

    --text:    #0f172a;
    --muted:   #64748b;
    --sub:     #475569;

    --radius:  12px;
}

    body {
        font-family: 'Outfit', sans-serif;
        background: var(--bg);
        color: var(--text);
    }

    /* ─── CONTENT WRAPPER ──────────────────────────────── */
    .content {
        margin-left: 256px;          /* igual al ancho del sidebar */
        padding: 28px 28px 48px;
        min-height: 100vh;
        transition: margin-left .32s cubic-bezier(.4,0,.2,1);
    }
    body.sidebar-collapsed .content { margin-left: 64px; }
    @media (max-width: 992px) { .content { margin-left: 0 !important; } }

    /* ─── PAGE HEADER ──────────────────────────────────── */
    .page-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 14px;
        margin-bottom: 24px;
    }
    .page-title { font-size: 22px; font-weight: 600; color: var(--text); line-height: 1.2; }
    .page-sub   { font-size: 13px; color: var(--muted); margin-top: 4px; }

    /* ─── BOTONES ───────────────────────────────────────── */
    .kb-btn {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 9px 18px; border-radius: 9px; border: none;
        font-family: 'Outfit', sans-serif; font-size: 13.5px; font-weight: 500;
        cursor: pointer; text-decoration: none; transition: filter .15s;
    }
    .kb-btn:hover { filter: brightness(1.12); }
    .kb-btn-success { background: #166534; color: #86efac; }
    .kb-btn-primary { background: #1e3a8a; color: #93c5fd; }
    .kb-btn-info    { background: rgba(59,130,246,.12); color: #93c5fd; border: 1px solid rgba(59,130,246,.25); }

    /* ─── STATS GRID ────────────────────────────────────── */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(160px,1fr));
        gap: 12px;
        margin-bottom: 20px;
    }
    .stat-card {
        background: var(--panel);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 18px;
    }
    .stat-label { font-size: 11px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: .06em; margin-bottom: 7px; }
    .stat-val   { font-size: 26px; font-weight: 600; color: var(--text); line-height: 1; }
    .stat-sub   { font-size: 12px; color: #22c55e; margin-top: 5px; }
    .stat-sub.warn { color: #f59e0b; }
    .stat-sub.neutral { color: var(--muted); }

    /* ─── FILTER BAR ────────────────────────────────────── */
    .filter-bar {
        background: var(--panel);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: 16px 20px;
        margin-bottom: 16px;
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        align-items: flex-end;
    }
    .filter-group { display: flex; flex-direction: column; gap: 5px; flex: 1; min-width: 150px; }
    .filter-label { font-size: 11px; font-weight: 600; color: var(--sub); text-transform: uppercase; letter-spacing: .05em; }
    .filter-bar select, .filter-bar input {
        background: #ffffff;
        border: 1px solid rgba(14, 13, 13, 0.36);
        border-radius: 8px;
        padding: 8px 12px;
        font-family: 'Outfit', sans-serif;
        font-size: 13px;
        color: var(--text);
        outline: none;
    }
    .filter-bar select:focus,
    .filter-bar input:focus { border-color: var(--accent); }

    /* ─── TABLE CARD ────────────────────────────────────── */
    .table-card {
        background: var(--panel);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        overflow: hidden;
    }
    .table-card-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
        padding: 14px 20px;
        border-bottom: 1px solid var(--border);
    }
    .table-card-title { font-size: 14px; font-weight: 500; color: var(--text); }
    .table-card-title span { color: var(--muted); font-weight: 400; }

    /* ─── DATATABLES OVERRIDES ──────────────────────────── */
    #clientes-kb_wrapper .dataTables_filter { display: none; }  /* usamos el nuestro */
    #clientes-kb_wrapper .dataTables_length label,
    #clientes-kb_wrapper .dataTables_info { font-size: 12px; color: var(--muted); font-family: 'Outfit', sans-serif; }
    #clientes-kb_wrapper .dataTables_paginate { font-family: 'Outfit', sans-serif; font-size: 12px; }
    #clientes-kb_wrapper .dataTables_paginate .paginate_button {
        background: transparent !important;
        border: 1px solid rgba(255,255,255,.08) !important;
        color: var(--sub) !important;
        border-radius: 7px !important;
        padding: 4px 10px !important;
        margin: 0 2px;
        font-size: 12px;
    }
    #clientes-kb_wrapper .dataTables_paginate .paginate_button.current {
        background: #1d4ed8 !important;
        color: #fff !important;
        border-color: #1d4ed8 !important;
    }
    #clientes-kb_wrapper .dataTables_paginate .paginate_button:hover:not(.current) {
        background: rgba(255,255,255,.06) !important;
        color: var(--text) !important;
    }
    #clientes-kb_wrapper { padding: 0; }
    #clientes-kb_wrapper .dt-buttons { margin: 0; }

    /* tabla */
    table#clientes-kb { width: 100% !important; }
    table#clientes-kb thead th {
        background: #1a2030 !important;
        border-bottom: 1px solid var(--border) !important;
        color: var(--muted) !important;
        font-size: 11px !important;
        font-weight: 600 !important;
        text-transform: uppercase;
        letter-spacing: .06em;
        padding: 11px 14px !important;
        white-space: nowrap;
        font-family: 'Outfit', sans-serif;
    }
    table#clientes-kb tbody td {
        background: transparent !important;
        border-bottom: 1px solid rgba(255,255,255,.03) !important;
        color: var(--sub) !important;
        font-size: 13px !important;
        padding: 12px 14px !important;
        font-family: 'Outfit', sans-serif;
        vertical-align: middle;
    }
    table#clientes-kb tbody tr:hover td { background: rgba(255,255,255,.025) !important; }
    table#clientes-kb tbody tr:last-child td { border-bottom: none !important; }

    /* ─── BADGES ────────────────────────────────────────── */
    .badge-grupo  { display: inline-block; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; background: rgba(59,130,246,.15); color: #60a5fa; }
    .badge-m      { background: rgba(59,130,246,.12); color: #93c5fd; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 500; }
    .badge-f      { background: rgba(212,83,126,.15); color: #f9a8d4; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 500; }
    .badge-comida { background: rgba(29,158,117,.12); color: #34d399; padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 500; }
    .pax-pill     { display: inline-flex; align-items: center; gap: 4px; padding: 3px 9px; border-radius: 20px; background: rgba(99,153,34,.12); color: #86efac; font-size: 11px; font-weight: 600; }
    .passport-mono { font-family: monospace; font-size: 12px; color: var(--sub); letter-spacing: .5px; }

    /* ─── ACCIONES ──────────────────────────────────────── */
    .action-row   { display: flex; gap: 6px; align-items: center; }
    .act-btn      { width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 14px; border: none; transition: background .15s; }
    .act-edit     { background: rgba(245,158,11,.15); color: #fbbf24; }
    .act-edit:hover { background: rgba(245,158,11,.3); }
    .act-del      { background: rgba(239,68,68,.12); color: #f87171; }
    .act-del:hover { background: rgba(239,68,68,.28); }

    /* ─── NOMBRE CLIENTE ────────────────────────────────── */
    .client-name  { font-weight: 500; color: var(--text) !important; font-size: 13.5px; }
    .client-email { font-size: 11px; color: var(--muted) !important; }

    /* ─── MODAL ─────────────────────────────────────────── */
    .modal-content {
        background: var(--card) !important;
        border: 1px solid var(--border) !important;
        border-radius: var(--radius) !important;
        color: var(--text) !important;
    }
    .modal-header {
        background: rgba(29,78,216,.15) !important;
        border-bottom: 1px solid var(--border) !important;
        padding: 16px 20px !important;
    }
    .modal-title { font-size: 15px !important; font-weight: 500 !important; color: #93c5fd !important; }
    .modal-body  { padding: 20px !important; }
    .modal-footer { border-top: 1px solid var(--border) !important; padding: 14px 20px !important; }

    .modal-body .form-label   { font-size: 12px; font-weight: 600; color: var(--sub); text-transform: uppercase; letter-spacing: .05em; }
    .modal-body .form-select,
    .modal-body .form-control {
        background: #0f1420 !important;
        border: 1px solid rgba(255,255,255,.1) !important;
        border-radius: 8px !important;
        padding: 9px 12px !important;
        font-family: 'Outfit', sans-serif !important;
        font-size: 13px !important;
        color: var(--text) !important;
    }
    .modal-body .form-select:focus,
    .modal-body .form-control:focus { border-color: var(--accent) !important; box-shadow: none !important; }

    /* ─── TOAST ─────────────────────────────────────────── */
    .kb-toast {
        position: fixed; top: 80px; right: 24px; z-index: 9999;
        padding: 12px 20px; border-radius: 10px;
        font-family: 'Outfit', sans-serif; font-size: 13.5px; font-weight: 500;
        display: flex; align-items: center; gap: 10px;
        animation: slideIn .3s ease;
        max-width: 360px;
    }
    .kb-toast.success { background: #14532d; color: #86efac; border: 1px solid rgba(134,239,172,.25); }
    .kb-toast.error   { background: #450a0a; color: #fca5a5; border: 1px solid rgba(252,165,165,.25); }
    @keyframes slideIn { from { opacity:0; transform: translateX(40px); } to { opacity:1; transform: translateX(0); } }

    /* ─── SEARCH BOX CUSTOM ─────────────────────────────── */
    .kb-search {
        display: flex; align-items: center; gap: 8px;
        background: #0f1420;
        border: 1px solid rgba(255,255,255,.08);
        border-radius: 8px;
        padding: 7px 12px;
        min-width: 220px;
    }
    .kb-search input { background: transparent; border: none; outline: none; font-family: 'Outfit', sans-serif; font-size: 13px; color: var(--text); width: 100%; }
    .kb-search i { font-size: 14px; color: var(--muted); }

    /* ─── EXPORT BTN ─────────────────────────────────────── */
    .buttons-excel, .buttons-html5 {
        background: #166534 !important;
        color: #86efac !important;
        border: none !important;
        border-radius: 9px !important;
        padding: 8px 16px !important;
        font-family: 'Outfit', sans-serif !important;
        font-size: 13px !important;
        font-weight: 500 !important;
        cursor: pointer;
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px;
    }
    </style>
</head>
<body>

<?php
// ── Toast si hubo importación ────────────────────────────────────────────
if ($msg_importar):
    [$tipo, $texto] = explode('|', $msg_importar, 2);
?>
<div class="kb-toast <?= $tipo ?>" id="kb-toast" role="alert">
    <i class="fas <?= $tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
    <?= $texto ?>
</div>
<script>setTimeout(() => { const t = document.getElementById('kb-toast'); if(t) t.remove(); }, 4500);</script>
<?php endif; ?>

<!-- ════════════════════════════════════════════════════════
     CONTENIDO PRINCIPAL
════════════════════════════════════════════════════════ -->
<div class="kb-content">

    <!-- Header -->
    <div class="page-header">
        <div>
            <div class="page-title">Clientes KB</div>
            <div class="page-sub">Gestión de pasajeros registrados por grupo</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="agregar_kb.php" class="kb-btn kb-btn-success">
                <i class="fas fa-user-plus"></i> Agregar cliente
            </a>
            <button class="kb-btn kb-btn-primary" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="fas fa-file-upload"></i> Importar Excel
            </button>
        </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total clientes KB</div>
            <div class="stat-val"><?= number_format($total_clientes) ?></div>
            <div class="stat-sub">+<?= $nuevos_mes ?> este mes</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Grupos abiertos</div>
            <div class="stat-val"><?= $total_grupos ?></div>
            <div class="stat-sub warn">En operación</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Con pref. comida</div>
            <div class="stat-val"><?= $con_comida ?></div>
            <div class="stat-sub neutral">Registradas</div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Nuevos este mes</div>
            <div class="stat-val"><?= $nuevos_mes ?></div>
            <div class="stat-sub">Incorporados</div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="filter-bar">
        <div class="filter-group">
            <div class="filter-label">Grupo</div>
            <select id="filtroGrupo">
                <option value="">Todos los grupos</option>
                <?php mysqli_data_seek($grupos, 0); while ($g = mysqli_fetch_assoc($grupos)): ?>
                    <option value="<?= htmlspecialchars($g['nombre_grupo']) ?>">
                        <?= htmlspecialchars($g['nombre_grupo']) ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <div class="filter-group">
            <div class="filter-label">Género</div>
            <select id="filtroGenero">
                <option value="">Todos</option>
                <option value="M">Masculino</option>
                <option value="F">Femenino</option>
            </select>
        </div>
        <div class="filter-group">
            <div class="filter-label">Comida</div>
            <select id="filtroComida">
                <option value="">Todas</option>
                <option value="Vegetariano">Vegetariano</option>
                <option value="Vegano">Vegano</option>
                <option value="Sin gluten">Sin gluten</option>
                <option value="Sin lactosa">Sin lactosa</option>
            </select>
        </div>
        <button class="kb-btn kb-btn-info" id="btnLimpiarFiltros">
            <i class="fas fa-times"></i> Limpiar
        </button>
    </div>

    <!-- Tabla -->
    <div class="table-card">
        <div class="table-card-top">
            <div class="table-card-title">
                Lista de clientes <span id="total-label">(<?= mysqli_num_rows($result_kb) ?> registros)</span>
            </div>
            <div class="d-flex gap-2 align-items-center flex-wrap">
                <div class="kb-search">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Buscar nombre, pasaporte…">
                </div>
                <!-- Botón Excel inyectado por DataTables -->
                <div id="export-container"></div>
            </div>
        </div>

        <div class="table-responsive">
            <table id="clientes-kb" class="table" style="width:100%">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Pasajero</th>
                        <th>F. Nacimiento</th>
                        <th>Género</th>
                        <th>Pasaporte</th>
                        <th>Nacionalidad</th>
                        <th>Comida</th>
                        <th>WhatsApp</th>
                        <th>Pax</th>
                        <th>Grupo</th>
                        <th>Hotel</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($result_kb)): ?>
                    <tr>
                        <td style="color:var(--muted);font-size:12px;">#<?= $row['id_cliente'] ?></td>
                        <td>
                            <span class="client-name"><?= htmlspecialchars($row['nombre'] . ' ' . $row['apellido']) ?></span>
                        </td>
                        <td><?= htmlspecialchars($row['fecha_nacimiento'] ?? '—') ?></td>
                        <td>
                            <?php if ($row['genero'] === 'M'): ?>
                                <span class="badge-m">M</span>
                            <?php elseif ($row['genero'] === 'F'): ?>
                                <span class="badge-f">F</span>
                            <?php else: ?>
                                <span style="color:var(--muted);font-size:12px;">—</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="passport-mono"><?= htmlspecialchars($row['nro_pasaporte'] ?? '—') ?></span></td>
                        <td><?= htmlspecialchars($row['nacionalidad'] ?? '—') ?></td>
                        <td>
                            <?php if (!empty($row['comida'])): ?>
                                <span class="badge-comida"><?= htmlspecialchars($row['comida']) ?></span>
                            <?php else: ?>
                                <span style="color:var(--muted);font-size:12px;">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($row['telefono'] ?? '—') ?></td>
                        <td>
                            <span class="pax-pill">
                                <i class="fas fa-users" style="font-size:10px;"></i>
                                <?= (int)$row['pasajeros'] ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge-grupo"><?= htmlspecialchars($row['nombre_grupo']) ?></span>
                        </td>
                        <td style="font-size:12px;color:var(--sub);"><?= htmlspecialchars($row['hotel'] ?? '—') ?></td>
                        <td>
                            <div class="action-row">
                                <a href="editar_kb.php?id_cliente=<?= $row['id_cliente'] ?>&id_grupo=<?= $row['id_grupo'] ?>"
                                   class="act-btn act-edit" title="Editar" aria-label="Editar">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <form method="POST" action="eliminar_kb.php" style="display:inline;">
                                    <input type="hidden" name="id_cliente" value="<?= $row['id_cliente'] ?>">
                                    <button type="submit" class="act-btn act-del"
                                            onclick="return confirm('¿Eliminar a <?= htmlspecialchars(addslashes($row['nombre'] . ' ' . $row['apellido'])) ?>?')"
                                            title="Eliminar" aria-label="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- /content -->

<!-- ════════════════════════════════════════════════════════
     MODAL IMPORTAR EXCEL
════════════════════════════════════════════════════════ -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importModalLabel" aria-modal="true" role="dialog">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" enctype="multipart/form-data" class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title" id="importModalLabel">
            <i class="fas fa-file-excel me-2"></i> Importar Clientes KB
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <div class="mb-3">
            <label class="form-label">Seleccionar grupo</label>
            <select name="id_grupo" class="form-select" required>
                <option value="">— Selecciona un grupo —</option>
                <?php mysqli_data_seek($grupos, 0); while ($g = mysqli_fetch_assoc($grupos)): ?>
                    <option value="<?= $g['id_grupo'] ?>"><?= htmlspecialchars($g['nombre_grupo']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Archivo Excel (.xlsx / .xls)</label>
            <input type="file" name="archivo_excel" class="form-control" accept=".xlsx,.xls" required>
        </div>

        <div style="background:rgba(59,130,246,.07);border:1px solid rgba(59,130,246,.2);border-radius:9px;padding:12px 14px;">
            <p style="font-size:12px;color:#93c5fd;font-weight:600;margin-bottom:6px;">
                <i class="fas fa-info-circle me-1"></i> Formato requerido (columnas en orden):
            </p>
            <p style="font-size:12px;color:var(--sub);line-height:1.7;margin:0;">
                A: Nombre &nbsp;|&nbsp; B: Apellido &nbsp;|&nbsp; C: Género (M/F) &nbsp;|&nbsp; D: Pasaporte<br>
                E: F. Nacimiento &nbsp;|&nbsp; F: WhatsApp &nbsp;|&nbsp; G: Nacionalidad &nbsp;|&nbsp; H: Comida (opcional)
            </p>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="kb-btn" data-bs-dismiss="modal"
                style="background:rgba(255,255,255,.05);color:var(--sub);border:1px solid var(--border);">
            Cancelar
        </button>
        <button type="submit" name="importar_excel" class="kb-btn kb-btn-success">
            <i class="fas fa-file-import"></i> Importar
        </button>
      </div>

    </form>
  </div>

</div>
<!-- ════════════════════════════════════════════════════════
     SCRIPTS
════════════════════════════════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

<script>
$(function () {
    // ── DataTable ───────────────────────────────────────────────────────
    const tabla = new DataTable('#clientes-kb', {
        dom: 'Brtip',   // sin "f" (buscador propio), sin "l" (lo ponemos fuera si se desea)
        buttons: [{
            extend: 'excelHtml5',
            text: '<i class="fas fa-table" style="margin-right:6px"></i> Exportar Excel',
            title: 'Clientes KB – KB Adventures',
            exportOptions: { columns: ':not(:last-child)' }
        }],
        language: {
            url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/Spanish.json'
        },
        pageLength: 15,
        order: [[0, 'desc']],
        scrollX: false,
        drawCallback: function () {
            const info = this.api().page.info();
            $('#total-label').text('(' + info.recordsDisplay + ' registros)');
        }
    });

    // Mover botón Export al contenedor personalizado
    tabla.buttons().container().appendTo('#export-container');

    // ── Buscador personalizado ──────────────────────────────────────────
    $('#searchInput').on('keyup', function () {
        tabla.search(this.value).draw();
    });

    // ── Filtro grupo ────────────────────────────────────────────────────
    $('#filtroGrupo').on('change', function () {
        tabla.column(9).search(this.value).draw();
    });

    // ── Filtro género ───────────────────────────────────────────────────
    $('#filtroGenero').on('change', function () {
        tabla.column(3).search(this.value).draw();
    });

    // ── Filtro comida ───────────────────────────────────────────────────
    $('#filtroComida').on('change', function () {
        tabla.column(6).search(this.value).draw();
    });

    // ── Limpiar filtros ─────────────────────────────────────────────────
    $('#btnLimpiarFiltros').on('click', function () {
        $('#filtroGrupo, #filtroGenero, #filtroComida').val('');
        $('#searchInput').val('');
        tabla.search('').columns().search('').draw();
    });
});
</script>

</body>
</html>