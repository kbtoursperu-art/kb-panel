<?php
session_start(); 

if (!isset($_SESSION["usuario"])) {
    header("Location: ../../index.php");
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);
include('../../conexion.php');

/* ═══════════════════════════════════════════════════════════
   CONSULTA PRINCIPAL — adaptada a la BD real (clientes_grupo)
═══════════════════════════════════════════════════════════ */
$query = "
SELECT
    g.id_grupo,
    g.nombre_grupo,
    g.estado,
    g.cantidad,

    /* Primer cliente del grupo */
    (
        SELECT CONCAT(d.nombre, ' ', d.apellido)
        FROM datos_clientes d
        JOIN clientes_grupo cg2 ON cg2.id_cliente = d.id_cliente
        WHERE cg2.id_grupo = g.id_grupo
        ORDER BY d.id_cliente ASC
        LIMIT 1
    ) AS primer_cliente,

    /* Total pasajeros */
    (
        SELECT COUNT(*)
        FROM clientes_grupo cg3
        WHERE cg3.id_grupo = g.id_grupo
    ) AS pasajeros,

    /* Planificación */
    p.id_planificacion,
    p.nombre_guia,
    p.nombre_cocinero,
    p.nombre_asistente,
    p.grupo_operativo

FROM grupos g
LEFT JOIN planificacion p ON p.id_grupo = g.id_grupo
GROUP BY
    g.id_grupo, g.nombre_grupo, g.estado, g.cantidad,
    p.id_planificacion, p.nombre_guia, p.nombre_cocinero,
    p.nombre_asistente, p.grupo_operativo
ORDER BY g.id_grupo DESC
";

$resultado = mysqli_query($conexion, $query);
if (!$resultado) die("Error SQL: " . mysqli_error($conexion));

// ── Stats rápidas ────────────────────────────────────────────────────────
$total_grupos    = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) c FROM grupos"))['c'] ?? 0;
$grupos_abiertos = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) c FROM grupos WHERE estado='abierto'"))['c'] ?? 0;
$con_plan        = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(DISTINCT id_grupo) c FROM planificacion"))['c'] ?? 0;
$sin_plan        = $total_grupos - $con_plan;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planificación – KB Adventures</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.5/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css">

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

    /* ── PAGE HEADER ── */
    .page-header { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 24px; }
    .page-title  { font-size: 22px; font-weight: 600; color: var(--text); }
    .page-sub    { font-size: 13px; color: var(--muted); margin-top: 3px; }

    /* ── STATS ── */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 12px; margin-bottom: 20px;
    }
    .stat-card {
        background: var(--surface); border: 1px solid var(--border);
        border-radius: var(--radius); padding: 16px 18px;
        display: flex; align-items: center; gap: 12px;
    }
    .stat-icon {
        width: 40px; height: 40px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 18px; flex-shrink: 0;
    }
    .si-blue   { background: var(--info-bg);   color: var(--accent); }
    .si-green  { background: var(--green-bg);  color: var(--green); }
    .si-amber  { background: var(--amber-bg);  color: var(--amber); }
    .si-red    { background: var(--danger-bg); color: var(--danger); }
    .stat-val  { font-size: 24px; font-weight: 600; color: var(--text); line-height: 1.1; }
    .stat-lbl  { font-size: 12px; color: var(--muted); }

    /* ── TABLE CARD ── */
    .table-card {
        background: var(--surface); border: 1px solid var(--border);
        border-radius: var(--radius); overflow: hidden;
    }
    .table-card-top {
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: 10px;
        padding: 14px 20px; border-bottom: 1px solid var(--border);
        background: var(--surface2);
    }
    .table-card-title { font-size: 14px; font-weight: 600; color: var(--text); }
    .table-card-title span { color: var(--muted); font-weight: 400; font-size: 13px; }

    /* ── SEARCH BOX ── */
    .kb-search {
        display: flex; align-items: center; gap: 8px;
        background: var(--surface); border: 1.5px solid var(--border);
        border-radius: var(--radius-sm); padding: 7px 12px; min-width: 220px;
    }
    .kb-search input {
        background: transparent; border: none; outline: none;
        font-family: 'Outfit', sans-serif; font-size: 13px; color: var(--text); width: 100%;
    }
    .kb-search i { font-size: 14px; color: var(--muted); }

    /* ── DATATABLES OVERRIDES ── */
    #tablaPlanificacion_wrapper .dataTables_filter { display: none; }
    #tablaPlanificacion_wrapper .dataTables_length label,
    #tablaPlanificacion_wrapper .dataTables_info { font-size: 12px; color: var(--muted); font-family: 'Outfit', sans-serif; }
    #tablaPlanificacion_wrapper .dataTables_paginate { font-family: 'Outfit', sans-serif; font-size: 12px; }
    #tablaPlanificacion_wrapper .dataTables_paginate .paginate_button {
        background: transparent !important; border: 1px solid var(--border) !important;
        color: var(--sub) !important; border-radius: 7px !important;
        padding: 4px 10px !important; margin: 0 2px; font-size: 12px;
    }
    #tablaPlanificacion_wrapper .dataTables_paginate .paginate_button.current {
        background: var(--accent) !important; color: #fff !important; border-color: var(--accent) !important;
    }
    #tablaPlanificacion_wrapper .dataTables_paginate .paginate_button:hover:not(.current) {
        background: var(--accent-lt) !important; color: var(--accent) !important;
    }
    #tablaPlanificacion_wrapper .dt-buttons .btn { display: none; }

    /* ── TABLE ── */
    table#tablaPlanificacion { width: 100% !important; }
    table#tablaPlanificacion thead th {
        background: #f1f5f9 !important; border-bottom: 1px solid var(--border) !important;
        color: var(--muted) !important; font-size: 11px !important; font-weight: 600 !important;
        text-transform: uppercase; letter-spacing: .06em;
        padding: 10px 14px !important; white-space: nowrap;
        font-family: 'Outfit', sans-serif !important;
    }
    table#tablaPlanificacion tbody td {
        background: transparent !important; border-bottom: 1px solid #f1f5f9 !important;
        color: var(--muted) !important; font-size: 13px !important;
        padding: 12px 14px !important; font-family: 'Outfit', sans-serif !important;
        vertical-align: middle;
    }
    table#tablaPlanificacion tbody tr:hover td { background: #fafbff !important; }
    table#tablaPlanificacion tbody tr:last-child td { border-bottom: none !important; }

    /* ── BADGES ── */
    .badge-grupo {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 4px 10px; border-radius: 20px;
        font-size: 12px; font-weight: 600;
        background: var(--info-bg); color: var(--info-txt);
    }
    .badge-abierto { background: var(--green-bg); color: var(--green); padding: 3px 9px; border-radius: 20px; font-size: 11px; font-weight: 600; }
    .badge-cerrado { background: #f1f5f9; color: var(--muted); padding: 3px 9px; border-radius: 20px; font-size: 11px; font-weight: 600; }

    .pax-pill {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 3px 9px; border-radius: 20px;
        background: #f1f5f9; color: var(--muted); font-size: 11px; font-weight: 600;
    }

    .staff-chip {
        display: inline-flex; align-items: center; gap: 5px;
        background: var(--surface2); border: 1px solid var(--border);
        border-radius: 6px; padding: 3px 8px; font-size: 12px; color: var(--text);
        font-weight: 500;
    }
    .staff-chip i { font-size: 11px; color: var(--sub); }

    .empty-dash { color: var(--sub); font-size: 13px; }

    .client-name { font-weight: 500; color: var(--text) !important; }

    /* ── ACTION BTNS ── */
    .action-row { display: flex; gap: 6px; align-items: center; }
    .act-btn {
        display: inline-flex; align-items: center; gap: 5px;
        padding: 6px 12px; border-radius: 7px; border: none;
        font-family: 'Outfit', sans-serif; font-size: 12px; font-weight: 500;
        cursor: pointer; text-decoration: none; white-space: nowrap; transition: filter .15s;
    }
    .act-btn:hover { filter: brightness(1.1); }
    .act-plan   { background: var(--green-bg);  color: var(--green);  border: 1px solid var(--green-bd); }
    .act-edit   { background: var(--amber-bg);  color: var(--amber);  border: 1px solid var(--amber-bd); }
    .act-delete { background: var(--danger-bg); color: var(--danger); border: 1px solid var(--danger-bd); }

    /* ── FILTER CHIPS ── */
    .filter-row { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; padding: 12px 20px; border-bottom: 1px solid var(--border); }
    .filter-chip {
        padding: 5px 14px; border-radius: 20px; font-size: 12px; font-weight: 500;
        border: 1.5px solid var(--border); background: var(--surface); color: var(--muted);
        cursor: pointer; transition: all .15s;
    }
    .filter-chip:hover  { border-color: var(--accent); color: var(--accent); background: var(--accent-lt); }
    .filter-chip.active { border-color: var(--accent); color: var(--accent); background: var(--accent-lt); font-weight: 600; }

    /* ── TOAST ── */
    .kb-toast {
        position: fixed; top: 72px; right: 24px; z-index: 9999;
        padding: 12px 18px; border-radius: 10px; font-family: 'Outfit', sans-serif;
        font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 9px;
        animation: slideIn .3s ease;
    }
    .kb-toast.success { background: var(--green-bg); border: 1px solid var(--green-bd); color: #14532d; }
    @keyframes slideIn { from { opacity:0; transform: translateX(40px); } to { opacity:1; transform: translateX(0); } }

    /* DT length wrapper */
    #tablaPlanificacion_wrapper { padding: 0; }
    .dt-bottom-row { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; padding: 12px 20px; border-top: 1px solid var(--border); }
    </style>
</head>
<body>
<div class="kb-content">
<?php include './../sidebar.php'; ?>

<?php if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'ok'): ?>
<div class="kb-toast success" id="kb-toast">
    <i class="fas fa-check-circle"></i> Planificación guardada correctamente.
</div>
<script>setTimeout(() => document.getElementById('kb-toast')?.remove(), 4000);</script>
<?php endif; ?>

<div class="content">

    <!-- ── Header ─────────────────────────────────────────── -->
    <div class="page-header">
        <div>
            <div class="page-title">Planificación por grupos</div>
            <div class="page-sub">Asigna guías, cocineros y asistentes a cada grupo de viaje</div>
        </div>
    </div>

    <!-- ── Stats ──────────────────────────────────────────── -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon si-blue"><i class="fas fa-layer-group"></i></div>
            <div><div class="stat-val"><?= $total_grupos ?></div><div class="stat-lbl">Total grupos</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon si-green"><i class="fas fa-door-open"></i></div>
            <div><div class="stat-val"><?= $grupos_abiertos ?></div><div class="stat-lbl">Grupos abiertos</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon si-amber"><i class="fas fa-clipboard-check"></i></div>
            <div><div class="stat-val"><?= $con_plan ?></div><div class="stat-lbl">Con planificación</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon si-red"><i class="fas fa-clock"></i></div>
            <div><div class="stat-val"><?= $sin_plan ?></div><div class="stat-lbl">Sin planificar</div></div>
        </div>
    </div>

    <!-- ── Table card ─────────────────────────────────────── -->
    <div class="table-card">

        <!-- Top bar -->
        <div class="table-card-top">
            <div class="table-card-title">
                Grupos registrados <span id="count-label"></span>
            </div>
            <div class="d-flex gap-2 align-items-center flex-wrap">
                <div class="kb-search">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Buscar grupo, guía, cliente…">
                </div>
            </div>
        </div>

        <!-- Filter chips -->
        <div class="filter-row">
            <span style="font-size:11px;font-weight:600;color:var(--sub);text-transform:uppercase;letter-spacing:.05em;">Filtrar:</span>
            <button class="filter-chip active" data-filter="all">Todos</button>
            <button class="filter-chip" data-filter="abierto">Abiertos</button>
            <button class="filter-chip" data-filter="cerrado">Cerrados</button>
            <button class="filter-chip" data-filter="sin_plan">Sin planificar</button>
            <button class="filter-chip" data-filter="con_plan">Con planificación</button>
        </div>

        <!-- Table -->
        <div style="overflow-x:auto;">
            <table id="tablaPlanificacion" style="width:100%;">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Grupo</th>
                        <th>Estado</th>
                        <th>Primer cliente</th>
                        <th>Pax</th>
                        <th>Guía</th>
                        <th>Cocinero</th>
                        <th>Asistente</th>
                        <th>Grupo operativo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                mysqli_data_seek($resultado, 0);
                while ($fila = mysqli_fetch_assoc($resultado)):
                    $tienePlan = !empty($fila['id_planificacion']);
                    $estado    = $fila['estado'] ?? 'abierto';
                ?>
                <tr data-estado="<?= $estado ?>" data-plan="<?= $tienePlan ? 'con_plan' : 'sin_plan' ?>">
                    <td style="color:var(--sub);font-size:12px;"><?= $fila['id_grupo'] ?></td>
                    <td>
                        <span class="badge-grupo">
                            <i class="fas fa-users" style="font-size:10px;"></i>
                            <?= htmlspecialchars($fila['nombre_grupo']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($estado === 'abierto'): ?>
                            <span class="badge-abierto"><i class="fas fa-circle" style="font-size:7px;"></i> Abierto</span>
                        <?php else: ?>
                            <span class="badge-cerrado"><i class="fas fa-lock" style="font-size:9px;"></i> Cerrado</span>
                        <?php endif; ?>
                    </td>
                    <td class="client-name"><?= htmlspecialchars($fila['primer_cliente'] ?? '—') ?></td>
                    <td>
                        <span class="pax-pill">
                            <i class="fas fa-user" style="font-size:9px;"></i>
                            <?= (int)($fila['pasajeros'] ?? 0) ?>
                        </span>
                    </td>
                    <td>
                        <?php if (!empty($fila['nombre_guia'])): ?>
                            <span class="staff-chip">
                                <i class="fas fa-hiking"></i>
                                <?= htmlspecialchars($fila['nombre_guia']) ?>
                            </span>
                        <?php else: ?>
                            <span class="empty-dash">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($fila['nombre_cocinero'])): ?>
                            <span class="staff-chip">
                                <i class="fas fa-utensils"></i>
                                <?= htmlspecialchars($fila['nombre_cocinero']) ?>
                            </span>
                        <?php else: ?>
                            <span class="empty-dash">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($fila['nombre_asistente'])): ?>
                            <span class="staff-chip">
                                <i class="fas fa-user-tie"></i>
                                <?= htmlspecialchars($fila['nombre_asistente']) ?>
                            </span>
                        <?php else: ?>
                            <span class="empty-dash">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($fila['grupo_operativo'])): ?>
                            <span class="staff-chip">
                                <i class="fas fa-cog"></i>
                                <?= htmlspecialchars($fila['grupo_operativo']) ?>
                            </span>
                        <?php else: ?>
                            <span class="empty-dash">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="action-row">
                            <?php if (!$tienePlan): ?>
                                <a href="agregar.php?id_grupo=<?= $fila['id_grupo'] ?>" class="act-btn act-plan">
                                    <i class="fas fa-plus"></i> Planificar
                                </a>
                            <?php else: ?>
                                <a href="editar.php?id=<?= $fila['id_planificacion'] ?>" class="act-btn act-edit">
                                    <i class="fas fa-pen"></i> Editar
                                </a>
                                <a href="eliminar.php?id=<?= $fila['id_planificacion'] ?>" class="act-btn act-delete"
                                   onclick="return confirm('¿Eliminar la planificación de <?= htmlspecialchars(addslashes($fila['nombre_grupo'])) ?>?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="dt-bottom-row">
            <div id="tablaPlanificacion_info" style="font-size:12px;color:var(--muted);"></div>
            <div id="tablaPlanificacion_paginate"></div>
        </div>

    </div>
</div>
</div>
<!-- ── Scripts ─────────────────────────────────────────── -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(function () {

    // ── DataTable ──────────────────────────────────────────────────────
    const tabla = new DataTable('#tablaPlanificacion', {
        dom: 'rtip',
        language: { url: '//cdn.datatables.net/plug-ins/1.13.5/i18n/es-ES.json' },
        pageLength: 12,
        order: [[0, 'desc']],
        drawCallback: function () {
            const info = this.api().page.info();
            $('#count-label').text('(' + info.recordsDisplay + ' grupos)');
            // mover pagination al contenedor custom
            $('#tablaPlanificacion_paginate').html($('#tablaPlanificacion_wrapper .dataTables_paginate').html());
        }
    });

    // ── Buscador ───────────────────────────────────────────────────────
    $('#searchInput').on('keyup', function () { tabla.search(this.value).draw(); });

    // ── Filter chips ───────────────────────────────────────────────────
    $('.filter-chip').on('click', function () {
        $('.filter-chip').removeClass('active');
        $(this).addClass('active');

        const f = $(this).data('filter');
        $.fn.dataTable.ext.search = [];

        if (f !== 'all') {
            $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                const row = $(tabla.row(dataIndex).node());
                if (f === 'abierto')   return row.data('estado') === 'abierto';
                if (f === 'cerrado')   return row.data('estado') === 'cerrado';
                if (f === 'sin_plan')  return row.data('plan')   === 'sin_plan';
                if (f === 'con_plan')  return row.data('plan')   === 'con_plan';
                return true;
            });
        }
        tabla.draw();
    });

});
</script>
</body>
</html>