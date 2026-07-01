<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: ../../index.php");
    exit();
}
include '../../conexion.php';
mysqli_set_charset($conexion, 'utf8mb4');

$mensaje = $_GET['ok']    ?? '';
$error   = $_GET['error'] ?? '';

$sql = "
SELECT
    s.id_salida,
    s.nombre_guia AS guia,
    s.fecha_salida,
    s.estado,
    i.nombre AS producto,
    i.tipo,
    st.talla,
    s.cantidad,
    s.garantia_original,
    COALESCE(SUM(d.cantidad_devuelta), 0) AS devuelto,
    (s.cantidad - COALESCE(SUM(d.cantidad_devuelta), 0)) AS pendiente
FROM almacen_salidas s
JOIN almacen_stock st ON s.id_stock = st.id_stock
JOIN almacen_items i  ON st.id_item = i.id_item
LEFT JOIN almacen_devoluciones d ON s.id_salida = d.id_salida
WHERE i.tipo IN ('Retornable','Garantia')
GROUP BY s.id_salida, s.nombre_guia, s.fecha_salida, s.estado,
         i.nombre, i.tipo, st.talla, s.cantidad, s.garantia_original
HAVING pendiente > 0
ORDER BY s.fecha_salida ASC, s.nombre_guia ASC
";
$res = mysqli_query($conexion, $sql);
if (!$res) die("Error SQL: " . mysqli_error($conexion));
$pendientes = mysqli_fetch_all($res, MYSQLI_ASSOC);

$tot_pendientes = count($pendientes);
$tot_garantia_retenida = 0;
foreach ($pendientes as $p) {
    if (strtolower($p['tipo']) === 'garantia' && $p['cantidad'] > 0) {
        $garantia_unit = $p['garantia_original'] / $p['cantidad'];
        $tot_garantia_retenida += $garantia_unit * $p['pendiente'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Devoluciones Pendientes — KB Tours</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<style>
:root {
    --brand:#1a56db; --brand-light:#dbeafe; --brand-dark:#1e40af;
    --surface:#fff; --surface-2:#f8fafc; --surface-3:#f1f5f9;
    --border:#e2e8f0; --text:#0f172a; --text-muted:#64748b;
    --success:#16a34a; --warning:#d97706; --danger:#dc2626;
    --radius:12px;
    --shadow:0 1px 3px rgba(0,0,0,.06),0 4px 16px rgba(0,0,0,.04);
    --shadow-md:0 4px 12px rgba(0,0,0,.08),0 12px 32px rgba(0,0,0,.06);
}
*,*::before,*::after{box-sizing:border-box}
body{font-family:'DM Sans',sans-serif;background:var(--surface-2);color:var(--text);font-size:14px;margin:0}

.page-header{background:var(--surface);border-bottom:1px solid var(--border);padding:16px 32px;
    display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;
    box-shadow:0 1px 0 var(--border)}
.page-header h1{font-family:'Outfit',sans-serif;font-size:20px;font-weight:700;margin:0}
.page-header .subtitle{color:var(--text-muted);font-size:13px;margin:0}

.main-content{max-width:1320px;margin:0 auto;padding:28px 24px 80px}

.kpi-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px;margin-bottom:24px}
.kpi-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);
    padding:18px;box-shadow:var(--shadow);position:relative;overflow:hidden}
.kpi-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;
    justify-content:center;font-size:17px;margin-bottom:12px}
.kpi-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;
    color:var(--text-muted);margin-bottom:4px}
.kpi-value{font-family:'Outfit',sans-serif;font-size:24px;font-weight:700}
.kpi-sub{font-size:11px;color:var(--text-muted);margin-top:3px}
.kpi-accent{position:absolute;bottom:0;left:0;right:0;height:3px}

.kb-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);
    box-shadow:var(--shadow);overflow:hidden;margin-bottom:24px}
.kb-card-header{padding:14px 20px;border-bottom:1px solid var(--border);background:var(--surface-2);
    display:flex;align-items:center;justify-content:space-between}
.section-title{display:flex;align-items:center;gap:8px;font-family:'Outfit',sans-serif;
    font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.7px}
.section-icon{width:28px;height:28px;border-radius:7px;display:flex;align-items:center;
    justify-content:center;font-size:13px;color:#fff}

table.dataTable thead th{background:var(--surface-2) !important;font-size:10px !important;
    font-weight:700 !important;text-transform:uppercase;letter-spacing:.7px;
    color:var(--text-muted) !important;border-bottom:2px solid var(--border) !important;
    padding:10px 14px !important;white-space:nowrap}
table.dataTable tbody td{padding:11px 14px !important;vertical-align:middle !important;
    border-bottom:1px solid var(--border) !important;font-size:13px !important}
table.dataTable tbody tr:hover{background:var(--surface-2) !important}
table.dataTable{border-collapse:separate !important;border-spacing:0 !important}
.dataTables_wrapper .dataTables_filter input{border:1px solid var(--border);border-radius:8px;
    padding:6px 12px;font-size:13px;background:var(--surface-2);outline:none}
.dataTables_wrapper .dataTables_paginate .paginate_button.current{
    background:var(--brand) !important;color:#fff !important;border-radius:6px !important;border:none !important}
.dataTables_wrapper .dataTables_info{font-size:12px;color:var(--text-muted)}

.badge-tipo{display:inline-block;padding:2px 10px;border-radius:20px;
    font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
.badge-retornable{background:#dcfce7;color:#166534}
.badge-garantia{background:#fef3c7;color:#92400e}

.avatar-sm{width:30px;height:30px;border-radius:50%;background:var(--brand-light);
    color:var(--brand-dark);display:flex;align-items:center;justify-content:center;
    font-size:10px;font-weight:700;flex-shrink:0}

.progress-mini{display:flex;align-items:center;gap:8px}
.progress-bar-wrap{flex:1;height:6px;background:var(--surface-3);border-radius:99px;overflow:hidden;min-width:60px}
.progress-bar-fill{height:100%;border-radius:99px;background:var(--success)}
.progress-txt{font-family:'DM Mono',monospace;font-size:11px;color:var(--text-muted);white-space:nowrap}

.pendiente-badge{display:inline-flex;align-items:center;gap:4px;background:#fee2e2;
    color:#b91c1c;border-radius:20px;padding:3px 10px;font-size:12px;font-weight:700}

.btn-kb{padding:7px 16px;border-radius:8px;font-size:12px;font-weight:600;
    font-family:'DM Sans',sans-serif;cursor:pointer;border:none;transition:all .15s;
    display:inline-flex;align-items:center;gap:6px;text-decoration:none}
.btn-warning-kb{background:#d97706;color:#fff}
.btn-warning-kb:hover{background:#b45309;color:#fff}
.btn-outline-kb{background:transparent;color:var(--text-muted);border:1px solid var(--border)}
.btn-outline-kb:hover{background:var(--surface-2);color:var(--text)}

.toast-kb{position:fixed;top:20px;right:20px;padding:14px 20px;border-radius:10px;
    font-size:13px;font-weight:600;display:flex;align-items:center;gap:10px;
    box-shadow:var(--shadow-md);z-index:9999;max-width:360px;transition:opacity .4s}
.toast-success{background:#dcfce7;color:#15803d;border:1px solid #bbf7d0}
.toast-error{background:#fee2e2;color:#b91c1c;border:1px solid #fca5a5}

/* MODAL DEVOLUCION */
.modal-header-kb{background:var(--surface-2);border-bottom:1px solid var(--border);padding:16px 20px}
.modal-title-kb{font-family:'Outfit',sans-serif;font-size:15px;font-weight:700;
    display:flex;align-items:center;gap:8px}
.form-label-kb{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;
    color:var(--text-muted);margin-bottom:5px;display:block}
.form-control-kb{background:var(--surface-2);border:1.5px solid var(--border);border-radius:9px;
    padding:9px 13px;font-size:13px;width:100%;outline:none;transition:border-color .15s}
.form-control-kb:focus{border-color:var(--brand);background:#fff}
.info-box{background:var(--brand-light);border:1px solid #bfdbfe;border-radius:9px;
    padding:12px 14px;margin-bottom:16px;font-size:12px;color:var(--brand-dark)}
.info-row{display:flex;justify-content:space-between;margin-bottom:4px}
.info-row:last-child{margin-bottom:0}
.info-label{color:var(--text-muted);font-weight:600}
.info-val{font-family:'DM Mono',monospace;font-weight:700}

@media(max-width:768px){
    .page-header{padding:14px 16px}
    .main-content{padding:14px 12px 60px}
}
</style>
</head>
<body>
<?php include '../sidebar.php'; ?>
<div class="kb-content">

<div class="page-header">
    <div>
        <h1><i class="bi bi-clock-history text-warning me-2"></i>Devoluciones Pendientes</h1>
        <p class="subtitle">Productos retornables y con garantía aún sin devolver · KB Tours</p>
    </div>
    <a href="dashboard_almacen.php" class="btn-kb btn-outline-kb">
        <i class="bi bi-arrow-left"></i> Volver al Almacén
    </a>
</div>

<div class="main-content">

<?php if ($mensaje): ?>
<div class="toast-kb toast-success" id="toastMsg">
    <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($mensaje) ?>
</div>
<?php elseif ($error): ?>
<div class="toast-kb toast-error" id="toastMsg">
    <i class="bi bi-x-circle-fill"></i> <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<!-- KPIs -->
<div class="kpi-grid">
    <div class="kpi-card">
        <div class="kpi-icon" style="background:#fee2e2;color:#dc2626">
            <i class="bi bi-exclamation-triangle-fill"></i>
        </div>
        <div class="kpi-label">Items Pendientes</div>
        <div class="kpi-value" style="color:#dc2626"><?= $tot_pendientes ?></div>
        <div class="kpi-sub">registros sin devolver</div>
        <div class="kpi-accent" style="background:#dc2626"></div>
    </div>
    <div class="kpi-card">
        <div class="kpi-icon" style="background:#fef3c7;color:#d97706">
            <i class="bi bi-cash-stack"></i>
        </div>
        <div class="kpi-label">Garantía Retenida</div>
        <div class="kpi-value" style="font-size:18px;color:#d97706">
            S/ <?= number_format($tot_garantia_retenida, 2) ?>
        </div>
        <div class="kpi-sub">pendiente de liberar</div>
        <div class="kpi-accent" style="background:#d97706"></div>
    </div>
</div>

<!-- TABLA -->
<div class="kb-card">
    <div class="kb-card-header">
        <div class="section-title">
            <span class="section-icon" style="background:#d97706">
                <i class="bi bi-table"></i>
            </span>
            Pendientes por Devolver
        </div>
    </div>
    <div style="overflow-x:auto">
        <table id="tablaPendientes" class="table align-middle" style="width:100%">
            <thead>
                <tr>
                    <th>Guía</th>
                    <th>Producto</th>
                    <th>Tipo</th>
                    <th style="text-align:center">Entregado</th>
                    <th>Progreso devolución</th>
                    <th style="text-align:center">Pendiente</th>
                    <th>Fecha salida</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($pendientes as $p):
                $pct = $p['cantidad'] > 0 ? round(($p['devuelto'] / $p['cantidad']) * 100) : 0;
                $tipo_class = strtolower($p['tipo']) === 'garantia' ? 'badge-garantia' : 'badge-retornable';
            ?>
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:8px">
                        <div class="avatar-sm"><?= strtoupper(substr($p['guia'],0,2)) ?></div>
                        <span style="font-weight:600;font-size:12px"><?= htmlspecialchars($p['guia']) ?></span>
                    </div>
                </td>
                <td style="font-size:12px">
                    <?= htmlspecialchars($p['producto']) ?>
                    <?= $p['talla'] ? '<span style="color:var(--text-muted)"> · '.htmlspecialchars($p['talla']).'</span>' : '' ?>
                </td>
                <td><span class="badge-tipo <?= $tipo_class ?>"><?= htmlspecialchars($p['tipo']) ?></span></td>
                <td style="text-align:center;font-family:'DM Mono',monospace;font-weight:600">
                    <?= $p['cantidad'] ?>
                </td>
                <td style="min-width:140px">
                    <div class="progress-mini">
                        <div class="progress-bar-wrap">
                            <div class="progress-bar-fill" style="width:<?= $pct ?>%"></div>
                        </div>
                        <span class="progress-txt"><?= $p['devuelto'] ?>/<?= $p['cantidad'] ?></span>
                    </div>
                </td>
                <td style="text-align:center">
                    <span class="pendiente-badge">
                        <i class="bi bi-exclamation-circle-fill" style="font-size:10px"></i>
                        <?= $p['pendiente'] ?>
                    </span>
                </td>
                <td style="font-family:'DM Mono',monospace;font-size:11px;white-space:nowrap">
                    <?= date('d/m/Y', strtotime($p['fecha_salida'])) ?>
                </td>
                <td>
                    <button class="btn-kb btn-warning-kb"
                            onclick="abrirModal(
                                <?= $p['id_salida'] ?>,
                                '<?= htmlspecialchars(addslashes($p['guia'])) ?>',
                                '<?= htmlspecialchars(addslashes($p['producto'])) ?>',
                                <?= $p['pendiente'] ?>,
                                '<?= strtolower($p['tipo']) ?>'
                            )">
                        <i class="bi bi-arrow-return-left"></i> Devolver
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($pendientes)): ?>
            <tr>
                <td colspan="8" style="text-align:center;padding:50px;color:var(--text-muted)">
                    <i class="bi bi-check-circle" style="font-size:36px;display:block;margin-bottom:10px;color:#16a34a"></i>
                    <div style="font-weight:600;font-size:14px">¡Todo al día!</div>
                    <div style="font-size:12px;margin-top:4px">No hay productos pendientes de devolución.</div>
                </td>
            </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</div><!-- /.main-content -->
</div><!-- /.kb-content -->

<!-- MODAL DEVOLUCIÓN -->
<div class="modal fade" id="modalDevolucion" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:var(--radius);border:1px solid var(--border)">
            <form action="acciones/devolucion_action.php" method="POST" id="formDevolucion">
                <input type="hidden" name="id_salida" id="md-id-salida">

                <div class="modal-header-kb">
                    <div class="modal-title-kb">
                        <span class="section-icon" style="background:#d97706">
                            <i class="bi bi-arrow-return-left"></i>
                        </span>
                        Registrar Devolución
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body" style="padding:20px">

                    <div class="info-box">
                        <div class="info-row">
                            <span class="info-label">Guía</span>
                            <span class="info-val" id="md-guia">—</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Producto</span>
                            <span class="info-val" id="md-producto">—</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Pendiente</span>
                            <span class="info-val" id="md-pendiente">—</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-kb">Cantidad a devolver *</label>
                        <input type="number" name="cantidad" id="md-cantidad"
                               class="form-control-kb" min="1" required>
                        <div style="font-size:11px;color:var(--text-muted);margin-top:4px">
                            Máximo: <strong id="md-max-txt">—</strong> unidades
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label-kb">Observación</label>
                        <textarea name="observacion" class="form-control-kb" rows="2"
                                  placeholder="Ej: Devuelto en buen estado, una unidad dañada…"></textarea>
                    </div>

                    <div id="md-garantia-info" style="display:none;background:#fef3c7;border:1px solid #fde68a;
                                border-radius:9px;padding:10px 14px;font-size:12px;color:#92400e">
                        <i class="bi bi-shield-check me-1"></i>
                        Este producto tiene garantía — el monto proporcional se liberará automáticamente.
                    </div>

                </div>

                <div class="modal-footer" style="padding:14px 20px;border-top:1px solid var(--border);
                            display:flex;gap:8px;justify-content:flex-end">
                    <button type="button" class="btn-kb btn-outline-kb" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-kb btn-warning-kb" id="md-submit">
                        <i class="bi bi-check-lg"></i> Confirmar Devolución
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script>
$(document).ready(function () {
    $('#tablaPendientes').DataTable({
        responsive: false,
        pageLength: 15,
        order: [[6, 'asc']],
        language: { url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json" },
        dom: '<"d-flex align-items-center justify-content-between mb-3 px-3 pt-3"f>rtip',
        columnDefs: [{ orderable: false, targets: [4, 7] }]
    });
});

let modalDev;
document.addEventListener('DOMContentLoaded', () => {
    modalDev = new bootstrap.Modal(document.getElementById('modalDevolucion'));
});

function abrirModal(idSalida, guia, producto, maxPendiente, tipo) {
    document.getElementById('md-id-salida').value = idSalida;
    document.getElementById('md-guia').textContent = guia;
    document.getElementById('md-producto').textContent = producto;
    document.getElementById('md-pendiente').textContent = maxPendiente + ' unidades';
    document.getElementById('md-cantidad').max = maxPendiente;
    document.getElementById('md-cantidad').value = '';
    document.getElementById('md-max-txt').textContent = maxPendiente;
    document.getElementById('md-garantia-info').style.display = (tipo === 'garantia') ? 'block' : 'none';
    modalDev.show();
}

document.getElementById('formDevolucion').addEventListener('submit', function (e) {
    const cant = parseInt(document.getElementById('md-cantidad').value) || 0;
    const max  = parseInt(document.getElementById('md-cantidad').max) || 0;
    if (cant < 1 || cant > max) {
        e.preventDefault();
        document.getElementById('md-cantidad').style.borderColor = '#dc2626';
        return;
    }
    const btn = document.getElementById('md-submit');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Procesando…';
});

const toast = document.getElementById('toastMsg');
if (toast) setTimeout(() => { toast.style.opacity = '0'; }, 3500);
</script>
</body>
</html>