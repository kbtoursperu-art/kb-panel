<?php
session_start();

if (!isset($_SESSION["usuario"])) {
    header("Location: ../index.php");
    exit();
}
include '../../conexion.php';

/* ═══════════════════════════════════════════════════════════
   IMPORTAR DESDE EXCEL
═══════════════════════════════════════════════════════════ */
require '../../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$msg_importar = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['archivo_excel'])) {
    $archivo = $_FILES['archivo_excel']['tmp_name'];
    if ($archivo) {
        try {
            $documento  = IOFactory::load($archivo);
            $hoja       = $documento->getActiveSheet();
            $filas      = $hoja->toArray();
            $importados = 0;

            foreach ($filas as $i => $fila) {
                if ($i === 0) continue; // saltar encabezado

                $nombre      = trim($fila[0] ?? '');
                $apellido    = trim($fila[1] ?? '');
                $genero      = trim($fila[2] ?? '');
                $pasaporte   = trim($fila[3] ?? '');
                $empresa     = trim($fila[4] ?? '');
                $grupoNombre = trim($fila[5] ?? '');
                $contacto    = trim($fila[6] ?? '');
                $telefono    = trim($fila[7] ?? '');
                $email       = trim($fila[8] ?? '');

                if ($nombre === '' || $pasaporte === '') continue;

                // Validar pasaporte duplicado
                $v = $conexion->prepare("SELECT id_cliente FROM datos_clientes WHERE nro_pasaporte = ?");
                $v->bind_param("s", $pasaporte);
                $v->execute();
                $v->store_result();
                if ($v->num_rows > 0) continue;

                // Insertar cliente
                $c = $conexion->prepare("INSERT INTO datos_clientes (nombre, apellido, genero, nro_pasaporte) VALUES (?, ?, ?, ?)");
                $c->bind_param("ssss", $nombre, $apellido, $genero, $pasaporte);
                $c->execute();
                $id_cliente = $c->insert_id;

                // Determinar o crear grupo C-END
                $id_grupo_end = null;
                if (!empty($grupoNombre) && str_starts_with($grupoNombre, 'C-END-')) {
                    $g = $conexion->prepare("SELECT id_grupo FROM grupos WHERE nombre_grupo = ? LIMIT 1");
                    $g->bind_param("s", $grupoNombre);
                    $g->execute();
                    $r = $g->get_result();
                    if ($r->num_rows > 0) $id_grupo_end = $r->fetch_assoc()['id_grupo'];
                }

                if (!$id_grupo_end) {
                    $ng = $conexion->prepare("INSERT INTO grupos (nombre_grupo, cantidad, estado) VALUES ('TEMP', 1, 'abierto')");
                    $ng->execute();
                    $id_grupo_end = $ng->insert_id;
                    $codigo = 'C-END-' . str_pad($id_grupo_end, 3, '0', STR_PAD_LEFT);
                    $conexion->query("UPDATE grupos SET nombre_grupo='$codigo' WHERE id_grupo=$id_grupo_end");
                }

                // Insertar relación
                $e = $conexion->prepare("
                    INSERT INTO clientes_grupo (id_cliente, id_grupo, tipo_cliente, empresa_endosadora, contacto, telefono_contacto, email_contacto)
                    VALUES (?, ?, 'ENDOSADOR', ?, ?, ?, ?)
                ");
                $e->bind_param("iissss", $id_cliente, $id_grupo_end, $empresa, $contacto, $telefono, $email);
                $e->execute();
                $importados++;
            }

            $msg_importar = "success|Se importaron $importados clientes endosadores correctamente.";
        } catch (Exception $ex) {
            $msg_importar = "error|Error al leer el archivo: " . htmlspecialchars($ex->getMessage());
        }
    }
}

/* ═══════════════════════════════════════════════════════════
   STATS RÁPIDAS
═══════════════════════════════════════════════════════════ */
$total_end   = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) c FROM clientes_grupo WHERE tipo_cliente='ENDOSADOR'"))['c'] ?? 0;
$total_emp   = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(DISTINCT empresa_endosadora) c FROM clientes_grupo WHERE tipo_cliente='ENDOSADOR' AND empresa_endosadora != ''"))['c'] ?? 0;
$total_grp   = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(DISTINCT id_grupo) c FROM clientes_grupo WHERE tipo_cliente='ENDOSADOR'"))['c'] ?? 0;
$nuevos_mes  = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT COUNT(*) c FROM datos_clientes d JOIN clientes_grupo cg ON d.id_cliente = cg.id_cliente WHERE cg.tipo_cliente='ENDOSADOR' AND MONTH(d.fecha_registro)=MONTH(NOW()) AND YEAR(d.fecha_registro)=YEAR(NOW())"))['c'] ?? 0;

/* ═══════════════════════════════════════════════════════════
   CONSULTA PRINCIPAL
═══════════════════════════════════════════════════════════ */
$sql = "
    SELECT
        d.id_cliente,
        d.nombre,
        d.apellido,
        d.nro_pasaporte,
        cg.empresa_endosadora,
        IFNULL(g.nombre_grupo, 'Sin grupo') AS grupo,
        cg.contacto,
        cg.telefono_contacto,
        cg.email_contacto,
        cg.id_grupo
    FROM datos_clientes d
    JOIN  clientes_grupo cg ON d.id_cliente = cg.id_cliente
    LEFT JOIN grupos g      ON g.id_grupo   = cg.id_grupo
    WHERE cg.tipo_cliente = 'ENDOSADOR'
    ORDER BY d.id_cliente DESC
";
$result = mysqli_query($conexion, $sql);
if (!$result) die("Error SQL: " . mysqli_error($conexion));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes Endosadores – KB Adventures</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

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
        margin-left: 256px; padding: 28px 28px 64px;
        min-height: 100vh; transition: margin-left .32s cubic-bezier(.4,0,.2,1);
    }
    body.sidebar-collapsed .content { margin-left: 64px; }
    @media (max-width: 992px) { .content { margin-left: 0 !important; padding: 16px 14px 48px; } }

    /* ── PAGE HEADER ── */
    .page-header { display: flex; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-bottom: 24px; }
    .page-title  { font-size: 22px; font-weight: 600; color: var(--text); }
    .page-sub    { font-size: 13px; color: var(--muted); margin-top: 3px; }

    /* ── BTN ── */
    .kb-btn {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 9px 18px; border-radius: var(--radius-sm); border: none;
        font-family: 'Outfit', sans-serif; font-size: 13.5px; font-weight: 500;
        cursor: pointer; text-decoration: none; transition: filter .15s, transform .1s; white-space: nowrap;
    }
    .kb-btn:hover  { filter: brightness(1.1); }
    .kb-btn:active { transform: scale(.97); }
    .kb-btn-success { background: #166534; color: #dcfce7; }
    .kb-btn-primary { background: #1e3a8a; color: #93c5fd; }

    /* ── STATS ── */
    .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px,1fr)); gap: 12px; margin-bottom: 20px; }
    .stat-card  { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 16px 18px; display: flex; align-items: center; gap: 12px; }
    .stat-icon  { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
    .si-blue    { background: var(--info-bg);   color: var(--accent); }
    .si-violet  { background: var(--violet-bg); color: var(--violet); }
    .si-green   { background: var(--green-bg);  color: var(--green); }
    .si-amber   { background: var(--amber-bg);  color: var(--amber); }
    .stat-val   { font-size: 24px; font-weight: 600; color: var(--text); line-height: 1.1; }
    .stat-lbl   { font-size: 12px; color: var(--muted); }

    /* ── TABLE CARD ── */
    .table-card     { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); overflow: hidden; }
    .table-card-top {
        display: flex; align-items: center; justify-content: space-between;
        flex-wrap: wrap; gap: 10px; padding: 14px 20px;
        border-bottom: 1px solid var(--border); background: var(--surface2);
    }
    .table-card-title { font-size: 14px; font-weight: 600; color: var(--text); }
    .table-card-title span { color: var(--muted); font-weight: 400; font-size: 13px; }

    /* ── SEARCH ── */
    .kb-search { display: flex; align-items: center; gap: 8px; background: var(--surface); border: 1.5px solid var(--border); border-radius: var(--radius-sm); padding: 7px 12px; min-width: 220px; }
    .kb-search input { background: transparent; border: none; outline: none; font-family: 'Outfit', sans-serif; font-size: 13px; color: var(--text); width: 100%; }
    .kb-search i { font-size: 14px; color: var(--muted); }

    /* ── DATATABLES OVERRIDES ── */
    #tabla_wrapper .dataTables_filter,
    #tabla_wrapper .dataTables_length { display: none; }
    #tabla_wrapper .dataTables_info     { font-size: 12px; color: var(--muted); font-family: 'Outfit', sans-serif; }
    #tabla_wrapper .dataTables_paginate { font-family: 'Outfit', sans-serif; font-size: 12px; }
    #tabla_wrapper .dataTables_paginate .paginate_button {
        background: transparent !important; border: 1px solid var(--border) !important;
        color: var(--sub) !important; border-radius: 7px !important;
        padding: 4px 10px !important; margin: 0 2px; font-size: 12px;
    }
    #tabla_wrapper .dataTables_paginate .paginate_button.current { background: var(--accent) !important; color: #fff !important; border-color: var(--accent) !important; }
    #tabla_wrapper .dataTables_paginate .paginate_button:hover:not(.current) { background: var(--accent-lt) !important; color: var(--accent) !important; }
    #tabla_wrapper { padding: 0; }

    /* ── TABLE ── */
    table#tabla { width: 100% !important; }
    table#tabla thead th {
        background: #f1f5f9 !important; border-bottom: 1px solid var(--border) !important;
        color: var(--muted) !important; font-size: 11px !important; font-weight: 600 !important;
        text-transform: uppercase; letter-spacing: .06em;
        padding: 10px 14px !important; white-space: nowrap; font-family: 'Outfit', sans-serif !important;
    }
    table#tabla tbody td {
        background: transparent !important; border-bottom: 1px solid #f1f5f9 !important;
        color: var(--muted) !important; font-size: 13px !important;
        padding: 12px 14px !important; font-family: 'Outfit', sans-serif !important; vertical-align: middle;
    }
    table#tabla tbody tr:hover td { background: #fafbff !important; }
    table#tabla tbody tr:last-child td { border-bottom: none !important; }

    /* ── BADGES ── */
    .badge-grupo  { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; background: var(--violet-bg); color: var(--violet); }
    .badge-emp    { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; background: var(--info-bg); color: var(--info-txt); }
    .client-name  { font-weight: 500; color: var(--text) !important; font-size: 13.5px; }
    .passport-mono { font-family: monospace; font-size: 12px; color: var(--sub); letter-spacing: .5px; }

    /* contact info */
    .contact-line { font-size: 12px; color: var(--muted); display: flex; align-items: center; gap: 4px; }
    .contact-line i { font-size: 11px; color: var(--sub); }

    /* ── ACTION BTNS ── */
    .action-row { display: flex; gap: 6px; align-items: center; }
    .act-btn    { width: 30px; height: 30px; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 14px; border: none; transition: background .15s; }
    .act-edit   { background: rgba(245,158,11,.15); color: #b45309; }
    .act-edit:hover   { background: rgba(245,158,11,.3); }
    .act-delete { background: var(--danger-bg); color: var(--danger); border: 1px solid var(--danger-bd); }
    .act-delete:hover { background: #fee2e2; }

    /* avatar */
    .table-avatar {
        width: 32px; height: 32px; border-radius: 50%;
        background: linear-gradient(135deg,#7c3aed,#4f46e5);
        display: inline-flex; align-items: center; justify-content: center;
        font-size: 11px; font-weight: 600; color: #fff; flex-shrink: 0; text-transform: uppercase;
    }
    .client-cell { display: flex; align-items: center; gap: 9px; }

    /* ── MODAL ── */
    .modal-content  { border: 1px solid var(--border) !important; border-radius: var(--radius) !important; background: var(--surface) !important; }
    .modal-header   { background: var(--surface2) !important; border-bottom: 1px solid var(--border) !important; padding: 16px 20px !important; }
    .modal-title    { font-size: 15px !important; font-weight: 600 !important; color: var(--text) !important; font-family: 'Outfit', sans-serif !important; }
    .modal-body     { padding: 20px !important; }
    .modal-footer   { border-top: 1px solid var(--border) !important; padding: 14px 20px !important; }
    .modal-body .form-label { font-size: 11px; font-weight: 600; color: var(--sub); text-transform: uppercase; letter-spacing: .05em; }
    .modal-body .form-control { background: var(--surface2) !important; border: 1.5px solid var(--border) !important; border-radius: var(--radius-sm) !important; font-family: 'Outfit', sans-serif !important; font-size: 13px !important; color: var(--text) !important; padding: 9px 12px !important; }
    .modal-body .form-control:focus { border-color: var(--accent) !important; box-shadow: 0 0 0 3px rgba(37,99,235,.1) !important; }

    /* ── TOAST ── */
    .kb-toast { position: fixed; top: 72px; right: 24px; z-index: 9999; padding: 12px 18px; border-radius: 10px; font-family: 'Outfit', sans-serif; font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 9px; animation: slideIn .3s ease; max-width: 360px; }
    .kb-toast.success { background: var(--green-bg); border: 1px solid var(--green-bd); color: #14532d; }
    .kb-toast.error   { background: var(--danger-bg); border: 1px solid var(--danger-bd); color: #9f1239; }
    @keyframes slideIn { from { opacity:0; transform: translateX(40px); } to { opacity:1; transform: translateX(0); } }

    /* info tip */
    .info-tip { background: var(--info-bg); border: 1px solid var(--info-bd); border-radius: var(--radius-sm); padding: 10px 14px; font-size: 12px; color: var(--info-txt); }
    .info-tip i { margin-right: 5px; }

    /* DT bottom row */
    .dt-bottom-row { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; padding: 12px 20px; border-top: 1px solid var(--border); }
    </style>
</head>
<body>

<?php include './../sidebar.php'; ?>
<div class="kb-content"> 
<?php if ($msg_importar): [$tipo, $texto] = explode('|', $msg_importar, 2); ?>
<div class="kb-toast <?= $tipo ?>" id="kb-toast">
    <i class="fas <?= $tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
    <?= $texto ?>
</div>
<script>setTimeout(() => document.getElementById('kb-toast')?.remove(), 4500);</script>
<?php endif; ?>



    <!-- ── Header ─────────────────────────────────────────── -->
    <div class="page-header">
        <div>
            <div class="page-title">Clientes Endosadores</div>
            <div class="page-sub">Pasajeros registrados vía empresa endosadora</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="agregar_endosador.php" class="kb-btn kb-btn-success">
                <i class="fas fa-user-plus"></i> Agregar cliente
            </a>
            <button class="kb-btn kb-btn-primary" data-bs-toggle="modal" data-bs-target="#importModal">
                <i class="fas fa-file-upload"></i> Importar Excel
            </button>
        </div>
    </div>

    <!-- ── Stats ──────────────────────────────────────────── -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon si-violet"><i class="fas fa-users"></i></div>
            <div><div class="stat-val"><?= number_format($total_end) ?></div><div class="stat-lbl">Total endosadores</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon si-blue"><i class="fas fa-building"></i></div>
            <div><div class="stat-val"><?= $total_emp ?></div><div class="stat-lbl">Empresas</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon si-green"><i class="fas fa-layer-group"></i></div>
            <div><div class="stat-val"><?= $total_grp ?></div><div class="stat-lbl">Grupos asignados</div></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon si-amber"><i class="fas fa-calendar-plus"></i></div>
            <div><div class="stat-val"><?= $nuevos_mes ?></div><div class="stat-lbl">Nuevos este mes</div></div>
        </div>
    </div>

    <!-- ── Table card ─────────────────────────────────────── -->
    <div class="table-card">
        <div class="table-card-top">
            <div class="table-card-title">
                Lista de clientes <span id="count-label">(<?= mysqli_num_rows($result) ?> registros)</span>
            </div>
            <div class="d-flex gap-2 align-items-center flex-wrap">
                <div class="kb-search">
                    <i class="fas fa-search"></i>
                    <input type="text" id="searchInput" placeholder="Buscar nombre, empresa, pasaporte…">
                </div>
                <button class="kb-btn" style="background:var(--green-bg);color:var(--green);border:1px solid var(--green-bd);padding:7px 14px;font-size:12px;" id="export-excel">
                    <i class="fas fa-table"></i> Excel
                </button>
            </div>
        </div>

        <div style="overflow-x:auto;">
            <table id="tabla" style="width:100%;">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Pasajero</th>
                        <th>Pasaporte</th>
                        <th>Empresa</th>
                        <th>Grupo</th>
                        <th>Contacto</th>
                        <th>Teléfono</th>
                        <th>Email</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                mysqli_data_seek($result, 0);
                while ($row = mysqli_fetch_assoc($result)):
                    $iniciales = strtoupper(substr($row['nombre'],0,1) . substr($row['apellido'],0,1));
                ?>
                <tr>
                    <td style="color:var(--sub);font-size:12px;">#<?= $row['id_cliente'] ?></td>
                    <td>
                        <div class="client-cell">
                            <div class="table-avatar"><?= $iniciales ?></div>
                            <span class="client-name"><?= htmlspecialchars($row['nombre'] . ' ' . $row['apellido']) ?></span>
                        </div>
                    </td>
                    <td><span class="passport-mono"><?= htmlspecialchars($row['nro_pasaporte'] ?? '—') ?></span></td>
                    <td>
                        <?php if (!empty($row['empresa_endosadora'])): ?>
                            <span class="badge-emp">
                                <i class="fas fa-building" style="font-size:9px;"></i>
                                <?= htmlspecialchars($row['empresa_endosadora']) ?>
                            </span>
                        <?php else: ?>
                            <span style="color:var(--sub);font-size:12px;">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge-grupo">
                            <i class="fas fa-users" style="font-size:9px;"></i>
                            <?= htmlspecialchars($row['grupo']) ?>
                        </span>
                    </td>
                    <td style="color:var(--muted);font-size:12px;"><?= htmlspecialchars($row['contacto'] ?? '—') ?></td>
                    <td style="color:var(--muted);font-size:12px;"><?= htmlspecialchars($row['telefono_contacto'] ?? '—') ?></td>
                    <td style="color:var(--muted);font-size:12px;"><?= htmlspecialchars($row['email_contacto'] ?? '—') ?></td>
                    <td>
                        <div class="action-row">
                            <a href="editar_endosador.php?id_cliente=<?= $row['id_cliente'] ?>&id_grupo=<?= $row['id_grupo'] ?>"
                               class="act-btn act-edit" title="Editar" aria-label="Editar">
                                <i class="fas fa-pen"></i>
                            </a>
                            <button class="act-btn act-delete btn-eliminar"
                                    data-id_cliente="<?= $row['id_cliente'] ?>"
                                    data-id_grupo="<?= $row['id_grupo'] ?>"
                                    data-nombre="<?= htmlspecialchars($row['nombre'] . ' ' . $row['apellido']) ?>"
                                    title="Eliminar" aria-label="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="dt-bottom-row">
            <div id="tabla_info_custom" style="font-size:12px;color:var(--muted);"></div>
            <div id="tabla_pag_custom"></div>
        </div>
    </div>

<!-- /content -->
</div>
<!-- ═══════════════════════════════════════════════════════════
     MODAL IMPORTAR EXCEL
═══════════════════════════════════════════════════════════ -->
<div class="modal fade" id="importModal" tabindex="-1" aria-labelledby="importLabel" aria-modal="true" role="dialog">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" enctype="multipart/form-data" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="importLabel">
            <i class="fas fa-file-excel me-2" style="color:#15803d;"></i>
            Importar Clientes Endosadores
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
            <label class="form-label">Archivo Excel (.xlsx / .xls)</label>
            <input type="file" name="archivo_excel" class="form-control" accept=".xlsx,.xls" required>
        </div>
        <div class="info-tip">
            <i class="fas fa-info-circle"></i>
            <strong>Formato requerido (columnas en orden):</strong><br>
            <span style="font-size:11px;line-height:1.8;">
                A: Nombre &nbsp;|&nbsp; B: Apellido &nbsp;|&nbsp; C: Género &nbsp;|&nbsp; D: Pasaporte<br>
                E: Empresa &nbsp;|&nbsp; F: Grupo (C-END-XXX) &nbsp;|&nbsp; G: Contacto<br>
                H: Teléfono &nbsp;|&nbsp; I: Email
            </span>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="kb-btn" data-bs-dismiss="modal"
                style="background:var(--surface);color:var(--muted);border:1.5px solid var(--border);">
            Cancelar
        </button>
        <button type="submit" class="kb-btn kb-btn-success">
            <i class="fas fa-file-import"></i> Importar
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════
     MODAL CONFIRMAR ELIMINACIÓN
═══════════════════════════════════════════════════════════ -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-modal="true" role="dialog">
  <div class="modal-dialog modal-dialog-centered" style="max-width:400px;">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" style="color:var(--danger) !important;">
            <i class="fas fa-exclamation-triangle me-2" style="color:var(--danger);"></i>
            Confirmar eliminación
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p style="font-size:14px;color:var(--muted);">
            ¿Deseas eliminar a <strong id="delete-name" style="color:var(--text);"></strong>?
        </p>
        <p style="font-size:12px;color:var(--sub);margin-top:6px;">Esta acción no se puede deshacer.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="kb-btn" data-bs-dismiss="modal"
                style="background:var(--surface);color:var(--muted);border:1.5px solid var(--border);">
            Cancelar
        </button>
        <button type="button" class="kb-btn" id="confirm-delete"
                style="background:var(--danger-bg);color:var(--danger);border:1px solid var(--danger-bd);">
            <i class="fas fa-trash"></i> Eliminar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ── Scripts ─────────────────────────────────────────── -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

<script>
$(function () {

    // ── DataTable ──────────────────────────────────────────────────────
    const tabla = new DataTable('#tabla', {
        dom: 'rtip',
        language: { url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/Spanish.json' },
        pageLength: 15,
        order: [[0, 'desc']],
        buttons: [{ extend: 'excelHtml5', title: 'Clientes Endosadores – KB Adventures', exportOptions: { columns: ':not(:last-child)' } }],
        drawCallback: function () {
            const info = this.api().page.info();
            $('#count-label').text('(' + info.recordsDisplay + ' registros)');
            // mover paginación
            const pagHtml = $('#tabla_wrapper .dataTables_paginate').prop('outerHTML');
            const infoHtml = $('#tabla_wrapper .dataTables_info').prop('outerHTML');
            if (pagHtml)  $('#tabla_pag_custom').html(pagHtml);
            if (infoHtml) $('#tabla_info_custom').html(infoHtml);
        }
    });

    // Buscador propio
    $('#searchInput').on('keyup', function () { tabla.search(this.value).draw(); });

    // Botón Excel
    $('#export-excel').on('click', function () { tabla.button(0).trigger(); });

    // ── Eliminar con modal de confirmación ─────────────────────────────
    let pendingIdCliente = null;
    let pendingIdGrupo   = null;
    let pendingRow       = null;

    $(document).on('click', '.btn-eliminar', function () {
        pendingIdCliente = $(this).data('id_cliente');
        pendingIdGrupo   = $(this).data('id_grupo');
        pendingRow       = $(this).closest('tr');
        $('#delete-name').text($(this).data('nombre'));
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    });

    $('#confirm-delete').on('click', function () {
        bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();

        $.ajax({
            url: 'eliminar_endosador.php',
            type: 'POST',
            data: { id_cliente: pendingIdCliente, id_grupo: pendingIdGrupo },
            dataType: 'json',
            success: function (data) {
                if (data.success) {
                    tabla.row(pendingRow).remove().draw();
                    showToast('success', 'Cliente eliminado correctamente.');
                } else {
                    showToast('error', 'Error: ' + data.msg);
                }
            },
            error: function () {
                showToast('error', 'Error en la petición. Intenta nuevamente.');
            }
        });
    });

    // ── Toast helper ───────────────────────────────────────────────────
    function showToast(tipo, texto) {
        const existing = document.getElementById('kb-toast-dyn');
        if (existing) existing.remove();
        const icon = tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
        const t = document.createElement('div');
        t.id        = 'kb-toast-dyn';
        t.className = `kb-toast ${tipo}`;
        t.innerHTML = `<i class="fas ${icon}"></i> ${texto}`;
        document.body.appendChild(t);
        setTimeout(() => t.remove(), 4500);
    }
});
</script>
</body>
</html>