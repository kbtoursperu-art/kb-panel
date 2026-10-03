<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: ../../index.php");
    exit();
}

include '../../conexion.php';
mysqli_set_charset($conexion, 'utf8mb4');

// ── QUERY REAL basada en schema existente ──
// almacen_salidas.garantia_original = monto total de garantía entregado
// almacen_devoluciones.monto_devuelto = calculado por trigger trg_calcular_monto_devuelto
$res = mysqli_query($conexion, "
    SELECT
        s.nombre_guia AS guia,
        COUNT(DISTINCT s.id_salida) AS total_salidas,
        IFNULL(SUM(s.garantia_original), 0) AS total_entregado,
        IFNULL(SUM(dev_agg.monto_devuelto_total), 0) AS total_devuelto,
        IFNULL(SUM(s.garantia_original), 0)
            - IFNULL(SUM(dev_agg.monto_devuelto_total), 0) AS pendiente
    FROM almacen_salidas s
    JOIN almacen_stock st ON st.id_stock = s.id_stock
    JOIN almacen_items i ON i.id_item = st.id_item
    LEFT JOIN (
        SELECT id_salida, SUM(monto_devuelto) AS monto_devuelto_total
        FROM almacen_devoluciones
        GROUP BY id_salida
    ) dev_agg ON dev_agg.id_salida = s.id_salida
    WHERE i.tipo = 'Garantia'
    GROUP BY s.nombre_guia
    ORDER BY s.nombre_guia ASC
");

// Subqueries correlacionadas dentro de SUM no son válidas en MySQL GROUP BY
// — reescribir con JOIN a subquery agrupada:
$res = mysqli_query($conexion, "
    SELECT
        s.nombre_guia                                          AS guia,
        COUNT(DISTINCT s.id_salida)                            AS total_salidas,
        IFNULL(SUM(s.garantia_original), 0)                    AS total_entregado,
        IFNULL(SUM(dev_agg.monto_devuelto_total), 0)           AS total_devuelto,
        IFNULL(SUM(s.garantia_original), 0)
            - IFNULL(SUM(dev_agg.monto_devuelto_total), 0)     AS pendiente
    FROM almacen_salidas s
    JOIN almacen_stock st  ON st.id_stock = s.id_stock
    JOIN almacen_items i   ON i.id_item   = st.id_item
    LEFT JOIN (
        SELECT id_salida, SUM(monto_devuelto) AS monto_devuelto_total
        FROM almacen_devoluciones
        GROUP BY id_salida
    ) dev_agg ON dev_agg.id_salida = s.id_salida
    WHERE i.tipo = 'Garantia'
    GROUP BY s.nombre_guia
    ORDER BY s.nombre_guia ASC
");

if (!$res) die("Error SQL: " . mysqli_error($conexion));
$garantias = mysqli_fetch_all($res, MYSQLI_ASSOC);

// KPIs globales
$tot_entregado = array_sum(array_column($garantias, 'total_entregado'));
$tot_devuelto  = array_sum(array_column($garantias, 'total_devuelto'));
$tot_pendiente = array_sum(array_column($garantias, 'pendiente'));
$tot_guias     = count($garantias);
$guias_con_pend = count(array_filter($garantias, fn($g) => $g['pendiente'] > 0));

$mensaje = $_GET['ok']    ?? '';
$error   = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Garantías por Guía — KB Tours</title>
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
.main-content{max-width:1200px;margin:0 auto;padding:28px 24px 80px}

.kpi-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(175px,1fr));gap:14px;margin-bottom:24px}
.kpi-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);
    padding:18px;box-shadow:var(--shadow);position:relative;overflow:hidden;
    transition:transform .15s,box-shadow .15s}
.kpi-card:hover{transform:translateY(-2px);box-shadow:var(--shadow-md)}
.kpi-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;
    justify-content:center;font-size:17px;margin-bottom:12px}
.kpi-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;
    color:var(--text-muted);margin-bottom:4px}
.kpi-value{font-family:'Outfit',sans-serif;font-size:22px;font-weight:700;line-height:1}
.kpi-sub{font-size:11px;color:var(--text-muted);margin-top:4px}
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
table.dataTable tbody td{padding:12px 14px !important;vertical-align:middle !important;
    border-bottom:1px solid var(--border) !important;font-size:13px !important}
table.dataTable tbody tr:hover{background:var(--surface-2) !important}
table.dataTable{border-collapse:separate !important;border-spacing:0 !important}
table.dataTable tfoot td{padding:11px 14px !important;font-weight:700;font-size:12px;
    background:var(--surface-3);border-top:2px solid var(--border) !important}
.dataTables_wrapper .dataTables_filter input{border:1px solid var(--border);border-radius:8px;
    padding:6px 12px;font-size:13px;background:var(--surface-2);outline:none}
.dataTables_wrapper .dataTables_paginate .paginate_button.current{
    background:var(--brand) !important;color:#fff !important;border-radius:6px !important;border:none !important}
.dataTables_wrapper .dataTables_info{font-size:12px;color:var(--text-muted)}

.monto-val{font-family:'DM Mono',monospace;font-weight:600;font-size:13px}
.monto-ok{color:#15803d}
.monto-pend{color:#dc2626}
.monto-muted{color:var(--text-muted)}

.avatar-md{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;
    justify-content:center;font-size:12px;font-weight:700;color:#fff;flex-shrink:0}

.estado-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;
    border-radius:20px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
.est-ok{background:#dcfce7;color:#15803d}
.est-pend{background:#fef9c3;color:#a16207}
.est-alto{background:#fee2e2;color:#b91c1c}

/* barra garantía */
.garantia-bar-wrap{display:flex;align-items:center;gap:8px}
.garantia-bar{flex:1;height:7px;background:var(--surface-3);border-radius:99px;overflow:hidden;min-width:80px}
.garantia-bar-fill{height:100%;border-radius:99px;transition:width .3s}
.garantia-pct{font-family:'DM Mono',monospace;font-size:11px;color:var(--text-muted);min-width:36px}

/* modal */
.modal-header-kb{background:var(--surface-2);border-bottom:1px solid var(--border);padding:16px 20px}
.modal-title-kb{font-family:'Outfit',sans-serif;font-size:15px;font-weight:700;
    display:flex;align-items:center;gap:8px}
.form-label-kb{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;
    color:var(--text-muted);margin-bottom:5px;display:block}
.form-control-kb{background:var(--surface-2);border:1.5px solid var(--border);border-radius:9px;
    padding:9px 13px;font-size:13px;width:100%;outline:none;transition:border-color .15s}
.form-control-kb:focus{border-color:var(--brand);background:#fff}
.info-box{background:linear-gradient(135deg,#fef3c7,#fef9c3);border:1px solid #fde68a;
    border-radius:9px;padding:14px 16px;margin-bottom:16px}
.info-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:6px}
.info-row:last-child{margin-bottom:0}
.info-label{font-size:11px;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.info-val{font-family:'DM Mono',monospace;font-size:13px;font-weight:700;color:#92400e}

.btn-kb{padding:8px 18px;border-radius:9px;font-size:13px;font-weight:600;
    font-family:'DM Sans',sans-serif;cursor:pointer;border:none;transition:all .15s;
    display:inline-flex;align-items:center;gap:7px;text-decoration:none}
.btn-warning-kb{background:#d97706;color:#fff}
.btn-warning-kb:hover{background:#b45309;color:#fff}
.btn-outline-kb{background:transparent;color:var(--text-muted);border:1.5px solid var(--border)}
.btn-outline-kb:hover{background:var(--surface-2);color:var(--text)}
.btn-sm-kb{padding:5px 12px;font-size:11px;border-radius:7px}

.toast-kb{position:fixed;top:20px;right:20px;padding:14px 20px;border-radius:10px;
    font-size:13px;font-weight:600;display:flex;align-items:center;gap:10px;
    box-shadow:var(--shadow-md);z-index:9999;max-width:360px;transition:opacity .4s}
.toast-success{background:#dcfce7;color:#15803d;border:1px solid #bbf7d0}
.toast-error{background:#fee2e2;color:#b91c1c;border:1px solid #fca5a5}

@media(max-width:768px){
    .page-header{padding:14px 16px}
    .main-content{padding:14px 12px 60px}
    .kpi-grid{grid-template-columns:repeat(2,1fr)}
}
</style>
</head>
<body>
<?php include '../sidebar.php'; ?>
<div class="kb-content">

<div class="page-header">
    <div>
        <h1><i class="bi bi-shield-lock text-warning me-2"></i>Garantías por Guía</h1>
        <p class="subtitle">Resumen de garantías entregadas, devueltas y pendientes · KB Tours</p>
    </div>
    <div class="d-flex gap-2">
        <a href="pendientes.php" class="btn-kb btn-outline-kb">
            <i class="bi bi-clock-history"></i> Ver pendientes
        </a>
        <a href="dashboard_almacen.php" class="btn-kb btn-outline-kb">
            <i class="bi bi-arrow-left"></i> Volver
        </a>
    </div>
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
        <div class="kpi-icon" style="background:#dbeafe;color:#1a56db">
            <i class="bi bi-people-fill"></i>
        </div>
        <div class="kpi-label">Guías con Garantía</div>
        <div class="kpi-value" style="color:#1a56db"><?= $tot_guias ?></div>
        <div class="kpi-sub"><?= $guias_con_pend ?> con saldo pendiente</div>
        <div class="kpi-accent" style="background:#1a56db"></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon" style="background:#fef3c7;color:#d97706">
            <i class="bi bi-cash-stack"></i>
        </div>
        <div class="kpi-label">Total Entregado</div>
        <div class="kpi-value" style="font-size:16px;color:#d97706">
            S/ <?= number_format($tot_entregado, 2) ?>
        </div>
        <div class="kpi-sub">garantías emitidas</div>
        <div class="kpi-accent" style="background:#d97706"></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon" style="background:#dcfce7;color:#16a34a">
            <i class="bi bi-arrow-return-left"></i>
        </div>
        <div class="kpi-label">Total Devuelto</div>
        <div class="kpi-value" style="font-size:16px;color:#16a34a">
            S/ <?= number_format($tot_devuelto, 2) ?>
        </div>
        <div class="kpi-sub">liberadas correctamente</div>
        <div class="kpi-accent" style="background:#16a34a"></div>
    </div>

    <div class="kpi-card">
        <div class="kpi-icon" style="background:#fee2e2;color:#dc2626">
            <i class="bi bi-exclamation-triangle-fill"></i>
        </div>
        <div class="kpi-label">Pendiente</div>
        <div class="kpi-value" style="font-size:16px;color:<?= $tot_pendiente > 0 ? '#dc2626' : '#16a34a' ?>">
            S/ <?= number_format($tot_pendiente, 2) ?>
        </div>
        <div class="kpi-sub">aún por liberar</div>
        <div class="kpi-accent" style="background:#dc2626"></div>
    </div>
</div>
<!-- TABLA -->
<div class="kb-card">
    <div class="kb-card-header">
        <div class="section-title">
            <span class="section-icon" style="background:#d97706">
                <i class="bi bi-shield-lock-fill"></i>
            </span>
            Garantías por Guía
        </div>
        <span style="font-size:11px;color:var(--text-muted)">
            <?= $tot_guias ?> guía(s) con garantías registradas
        </span>
    </div>

    <div style="overflow-x:auto">
        <table id="tablaGarantias" class="table align-middle" style="width:100%">
            <thead>
                <tr>
                    <th>Guía</th>
                    <th style="text-align:center">Salidas</th>
                    <th class="text-end">Entregado</th>
                    <th class="text-end">Devuelto</th>
                    <th>Progreso</th>
                    <th class="text-end">Pendiente</th>
                    <th style="text-align:center">Estado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $colores = ['#1a56db','#16a34a','#d97706','#dc2626','#7c3aed','#0891b2'];
            $ci = 0;
            foreach ($garantias as $g):
                $pct_devuelto = $g['total_entregado'] > 0
                    ? min(100, round(($g['total_devuelto'] / $g['total_entregado']) * 100))
                    : 0;
                $bar_color = $pct_devuelto >= 100 ? '#16a34a' : ($pct_devuelto >= 50 ? '#d97706' : '#dc2626');
                $av_color  = $colores[$ci++ % count($colores)];

                if ($g['pendiente'] <= 0) {
                    $est_class = 'est-ok'; $est_txt = 'Saldado';
                } elseif ($g['pendiente'] < $g['total_entregado'] * 0.5) {
                    $est_class = 'est-pend'; $est_txt = 'Parcial';
                } else {
                    $est_class = 'est-alto'; $est_txt = 'Pendiente';
                }
            ?>
            <tr>
                <!-- GUÍA -->
                <td>
                    <div style="display:flex;align-items:center;gap:10px">
                        <div class="avatar-md" style="background:<?= $av_color ?>">
                            <?= strtoupper(substr($g['guia'],0,2)) ?>
                        </div>
                        <span style="font-weight:600"><?= htmlspecialchars($g['guia']) ?></span>
                    </div>
                </td>

                <!-- SALIDAS -->
                <td style="text-align:center">
                    <span style="background:var(--brand-light);color:var(--brand-dark);
                                 border-radius:20px;padding:2px 10px;font-size:11px;font-weight:700">
                        <?= $g['total_salidas'] ?>
                    </span>
                </td>

                <!-- ENTREGADO -->
                <td class="text-end">
                    <span class="monto-val monto-muted">
                        S/ <?= number_format($g['total_entregado'], 2) ?>
                    </span>
                </td>

                <!-- DEVUELTO -->
                <td class="text-end">
                    <span class="monto-val monto-ok">
                        S/ <?= number_format($g['total_devuelto'], 2) ?>
                    </span>
                </td>

                <!-- BARRA PROGRESO -->
                <td style="min-width:140px">
                    <div class="garantia-bar-wrap">
                        <div class="garantia-bar">
                            <div class="garantia-bar-fill"
                                 style="width:<?= $pct_devuelto ?>%;background:<?= $bar_color ?>"></div>
                        </div>
                        <span class="garantia-pct"><?= $pct_devuelto ?>%</span>
                    </div>
                </td>

                <!-- PENDIENTE -->
                <td class="text-end">
                    <span class="monto-val <?= $g['pendiente'] > 0 ? 'monto-pend' : 'monto-ok' ?>">
                        S/ <?= number_format(max(0, $g['pendiente']), 2) ?>
                    </span>
                </td>

                <!-- ESTADO -->
                <td style="text-align:center">
                    <span class="estado-badge <?= $est_class ?>">
                        <?= $est_txt ?>
                    </span>
                </td>

                <!-- ACCIÓN -->
                <td>
                    <?php if ($g['pendiente'] > 0): ?>
                    <button class="btn-kb btn-warning-kb btn-sm-kb"
                            onclick="abrirModal(
                                '<?= htmlspecialchars(addslashes($g['guia'])) ?>',
                                <?= number_format($g['pendiente'], 2, '.', '') ?>
                            )">
                        <i class="bi bi-arrow-return-left"></i> Devolver
                    </button>
                    <?php else: ?>
                    <span style="color:var(--text-muted);font-size:12px">
                        <i class="bi bi-check-circle text-success me-1"></i>Al día
                    </span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($garantias)): ?>
            <tr>
                <td colspan="8" style="text-align:center;padding:50px;color:var(--text-muted)">
                    <i class="bi bi-shield-check" style="font-size:36px;display:block;
                       margin-bottom:10px;color:#16a34a"></i>
                    <div style="font-weight:600;font-size:14px">Sin garantías registradas</div>
                    <div style="font-size:12px;margin-top:4px">
                        Las garantías aparecen cuando se registran salidas de productos tipo "Garantía".
                    </div>
                </td>
            </tr>
            <?php endif; ?>
            </tbody>
            <?php if (!empty($garantias)): ?>
            <tfoot>
                <tr>
                    <td colspan="2" style="color:var(--text-muted);font-size:11px;text-transform:uppercase">
                        <i class="bi bi-sigma me-1"></i> Totales
                    </td>
                    <td class="text-end">
                        <span class="monto-val">S/ <?= number_format($tot_entregado, 2) ?></span>
                    </td>
                    <td class="text-end">
                        <span class="monto-val monto-ok">S/ <?= number_format($tot_devuelto, 2) ?></span>
                    </td>
                    <td></td>
                    <td class="text-end">
                        <span class="monto-val monto-pend">S/ <?= number_format(max(0,$tot_pendiente), 2) ?></span>
                    </td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

</div><!-- /.main-content -->
</div><!-- /.kb-content -->

<!-- MODAL: Devolver garantía completa a guía -->
<div class="modal fade" id="modalGarantia" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:var(--radius);border:1px solid var(--border)">
            <form action="acciones/devolucion_garantia_action.php" method="POST" id="formGarantia">

                <div class="modal-header-kb">
                    <div class="modal-title-kb">
                        <span class="section-icon" style="background:#d97706">
                            <i class="bi bi-shield-check"></i>
                        </span>
                        Devolver Garantía
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body" style="padding:20px">

                    <div class="info-box">
                        <div class="info-row">
                            <span class="info-label">Guía</span>
                            <span class="info-val" id="mg-guia">—</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Monto pendiente</span>
                            <span class="info-val" id="mg-monto-txt">—</span>
                        </div>
                    </div>

                    <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:9px;
                                padding:10px 14px;margin-bottom:16px;font-size:12px;color:#9a3412">
                        <i class="bi bi-info-circle me-1"></i>
                        Esto registrará devoluciones individuales para <strong>cada salida pendiente</strong>
                        de este guía. Los triggers de la BD actualizarán el stock y estado automáticamente.
                    </div>

                    <input type="hidden" name="nombre_guia" id="mg-nombre-guia">

                    <div class="mb-3">
                        <label class="form-label-kb">Observación</label>
                        <textarea name="observacion" class="form-control-kb" rows="2"
                                  placeholder="Ej: Liquidación final temporada, conformidad del guía…"></textarea>
                    </div>

                </div>

                <div class="modal-footer" style="padding:14px 20px;border-top:1px solid var(--border);
                            display:flex;gap:8px;justify-content:flex-end">
                    <button type="button" class="btn-kb btn-outline-kb" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn-kb btn-warning-kb" id="mg-submit">
                        <i class="bi bi-check-lg"></i> Confirmar Devolución Total </button>
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
    $('#tablaGarantias').DataTable({
        pageLength: 15,
        order: [[5, 'desc']],
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
        dom: '<"d-flex align-items-center justify-content-between mb-3 px-3 pt-3"f>rtip',
        columnDefs: [{ orderable: false, targets: [4, 7] }]
    });
});

let modalGar;
document.addEventListener('DOMContentLoaded', () => {
    modalGar = new bootstrap.Modal(document.getElementById('modalGarantia'));
});

function abrirModal(guia, pendiente) {
    document.getElementById('mg-nombre-guia').value = guia;
    document.getElementById('mg-guia').textContent  = guia;
    document.getElementById('mg-monto-txt').textContent = 'S/ ' + parseFloat(pendiente).toFixed(2);
    modalGar.show();
}

document.getElementById('formGarantia').addEventListener('submit', function () {
    const btn = document.getElementById('mg-submit');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Procesando…';
});

const toast = document.getElementById('toastMsg');
if (toast) setTimeout(() => { toast.style.opacity = '0'; }, 3500);
</script>
</body>
</html>