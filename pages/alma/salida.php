<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: ../../dashboard_almacen.php");
    exit();
}
include '../../conexion.php';
mysqli_set_charset($conexion, 'utf8mb4');

// ── Guías desde planificacion ──
$guias_res = mysqli_query($conexion,"
    SELECT DISTINCT nombre_guia FROM planificacion
    WHERE nombre_guia IS NOT NULL AND nombre_guia != ''
    ORDER BY nombre_guia ASC
");
$guias = mysqli_fetch_all($guias_res, MYSQLI_ASSOC);

// ── Stock disponible ──
$stock_res = mysqli_query($conexion,"
    SELECT st.id_stock, i.nombre, i.tipo, st.talla, st.cantidad_disponible
    FROM almacen_stock st
    JOIN almacen_items i ON i.id_item = st.id_item
    WHERE st.cantidad_disponible > 0
    ORDER BY i.nombre, st.talla
");
$stocks = mysqli_fetch_all($stock_res, MYSQLI_ASSOC);

// ── Salidas recientes (últimas 10) ──
$recientes_res = mysqli_query($conexion,"
    SELECT s.id_salida, s.nombre_guia, s.cantidad, s.fecha_salida,
           s.garantia_original, s.estado, s.observacion,
           i.nombre AS producto, i.tipo AS tipo_producto, st.talla,
           (SELECT COALESCE(SUM(d.cantidad_devuelta),0)
              FROM almacen_devoluciones d WHERE d.id_salida = s.id_salida) AS total_devuelto
    FROM almacen_salidas s
    JOIN almacen_stock st ON st.id_stock = s.id_stock
    JOIN almacen_items i ON i.id_item = st.id_item
    ORDER BY s.id_salida DESC
    LIMIT 10
");
$recientes = mysqli_fetch_all($recientes_res, MYSQLI_ASSOC);

$mensaje = $_GET['ok']    ?? '';
$error   = $_GET['error'] ?? '';

$hay_guias  = count($guias)  > 0;
$hay_stocks = count($stocks) > 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Salida a Guías — KB Tours</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
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

.main-content{max-width:1100px;margin:0 auto;padding:28px 24px 80px}
.two-col{display:grid;grid-template-columns:420px 1fr;gap:24px;align-items:start}

.kb-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);
    box-shadow:var(--shadow);overflow:hidden;margin-bottom:24px}
.kb-card-header{padding:14px 20px;border-bottom:1px solid var(--border);background:var(--surface-2);
    display:flex;align-items:center;justify-content:space-between}
.section-title{display:flex;align-items:center;gap:8px;font-family:'Outfit',sans-serif;
    font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.7px}
.section-icon{width:28px;height:28px;border-radius:7px;display:flex;align-items:center;
    justify-content:center;font-size:13px;color:#fff}

.form-body{padding:24px}
.field-group{margin-bottom:18px}
.field-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;
    color:var(--text-muted);margin-bottom:6px;display:block}
.field-required::after{content:' *';color:var(--danger)}

.kb-input,.kb-select,.kb-textarea{background:var(--surface-2);border:1.5px solid var(--border);
    border-radius:9px;padding:9px 13px;font-size:13px;font-family:'DM Sans',sans-serif;
    color:var(--text);width:100%;outline:none;
    transition:border-color .15s,box-shadow .15s,background .15s;appearance:none}
.kb-input:focus,.kb-select:focus,.kb-textarea:focus{
    border-color:var(--brand);background:#fff;box-shadow:0 0 0 3px rgba(26,86,219,.1)}
.kb-input:disabled{opacity:.5;cursor:not-allowed;background:var(--surface-3)}
.kb-input.error,.kb-select.error{border-color:var(--danger)}
.kb-textarea{resize:vertical;min-height:72px}

.select-wrap{position:relative}
.select-wrap .kb-select{padding-right:36px}
.select-wrap .chevron{position:absolute;right:12px;top:50%;transform:translateY(-50%);
    pointer-events:none;color:var(--text-muted);font-size:14px}

/* preview producto */
.preview-card{background:linear-gradient(135deg,#f0fdf4,#dcfce7);border:1.5px solid #bbf7d0;
    border-radius:10px;padding:14px 16px;margin-top:10px;display:none}
.preview-card.visible{display:block}
.preview-card.warning{background:linear-gradient(135deg,#fef9c3,#fef3c7);border-color:#fde68a}
.preview-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:6px}
.preview-row:last-child{margin-bottom:0}
.preview-label{font-size:11px;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.preview-val{font-family:'DM Mono',monospace;font-size:12px;font-weight:700;color:#166534}
.preview-card.warning .preview-val{color:#92400e}

/* garantia highlight */
.garantia-active{border-color:#d97706 !important;background:#fffbeb !important;
    box-shadow:0 0 0 3px rgba(217,119,6,.12) !important}
.garantia-badge{display:inline-flex;align-items:center;gap:5px;background:#fef3c7;
    color:#92400e;border-radius:8px;padding:6px 12px;font-size:12px;font-weight:600;
    margin-bottom:8px}

.form-divider{border:none;border-top:1px solid var(--border);margin:20px 0}

.btn-kb{padding:10px 22px;border-radius:9px;font-size:13px;font-weight:600;
    font-family:'DM Sans',sans-serif;cursor:pointer;border:none;transition:all .15s;
    display:inline-flex;align-items:center;gap:7px;text-decoration:none}
.btn-danger-kb{background:var(--danger);color:#fff}
.btn-danger-kb:hover{background:#b91c1c;color:#fff}
.btn-outline-kb{background:transparent;color:var(--text-muted);border:1.5px solid var(--border)}
.btn-outline-kb:hover{background:var(--surface-2);color:var(--text)}
.btn-lg-kb{padding:12px 0;font-size:14px;border-radius:10px;width:100%;justify-content:center}

/* toast */
.toast-kb{position:fixed;top:20px;right:20px;padding:14px 20px;border-radius:10px;
    font-size:13px;font-weight:600;display:flex;align-items:center;gap:10px;
    box-shadow:var(--shadow-md);z-index:9999;animation:slideIn .25s ease;
    max-width:360px;transition:opacity .4s}
.toast-success{background:#dcfce7;color:#15803d;border:1px solid #bbf7d0}
.toast-error{background:#fee2e2;color:#b91c1c;border:1px solid #fca5a5}
@keyframes slideIn{from{opacity:0;transform:translateX(20px)}to{opacity:1;transform:translateX(0)}}

/* tabla recientes */
.kb-table thead th{background:var(--surface-2);font-size:10px;font-weight:700;
    text-transform:uppercase;letter-spacing:.7px;color:var(--text-muted);
    border-bottom:2px solid var(--border);padding:10px 14px;white-space:nowrap}
.kb-table tbody td{padding:11px 14px;vertical-align:middle;
    border-bottom:1px solid var(--border);font-size:13px}
.kb-table tbody tr:hover{background:var(--surface-2)}

.estado-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;
    border-radius:20px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
.est-pendiente{background:#fef9c3;color:#a16207}
.est-parcial{background:#e0f2fe;color:#0369a1}
.est-devuelto{background:#dcfce7;color:#15803d}

.avatar-sm{width:30px;height:30px;border-radius:50%;background:var(--brand-light);
    color:var(--brand-dark);display:flex;align-items:center;justify-content:center;
    font-size:10px;font-weight:700;flex-shrink:0}

/* alerta sin datos */
.warn-box{background:linear-gradient(135deg,#fef3c7,#fef9c3);border:1.5px solid #fde68a;
    border-radius:10px;padding:14px 18px;margin-bottom:18px;display:flex;
    align-items:flex-start;gap:10px;font-size:12px;color:#92400e}
.warn-box i{font-size:18px;flex-shrink:0;margin-top:2px}

/* ── acciones (editar/eliminar) ── */
.action-btns{display:flex;align-items:center;gap:6px;justify-content:flex-end}
.icon-btn{width:30px;height:30px;border-radius:8px;border:1.5px solid var(--border);
    background:var(--surface);color:var(--text-muted);display:inline-flex;
    align-items:center;justify-content:center;font-size:13px;cursor:pointer;
    transition:all .15s;flex-shrink:0}
.icon-btn:hover{transform:translateY(-1px);box-shadow:var(--shadow)}
.icon-btn.edit:hover{border-color:var(--brand);color:var(--brand);background:var(--brand-light)}
.icon-btn.delete:hover{border-color:var(--danger);color:var(--danger);background:#fee2e2}
.icon-btn:disabled{opacity:.35;cursor:not-allowed;transform:none;box-shadow:none}

/* modal */
.modal-content{border:none;border-radius:16px;overflow:hidden;box-shadow:var(--shadow-md)}
.modal-header{background:var(--surface-2);border-bottom:1px solid var(--border);padding:18px 24px}
.modal-header .modal-title{font-family:'Outfit',sans-serif;font-weight:700;font-size:16px;
    display:flex;align-items:center;gap:8px}
.modal-body{padding:24px}
.modal-footer{border-top:1px solid var(--border);padding:16px 24px}
.modal-footer .btn-kb{padding:9px 20px}
.readonly-chip{background:var(--surface-3);border:1px dashed var(--border);border-radius:9px;
    padding:9px 13px;font-size:13px;color:var(--text-muted);display:flex;align-items:center;gap:8px}
.delete-warning-box{background:linear-gradient(135deg,#fee2e2,#fef2f2);border:1.5px solid #fecaca;
    border-radius:10px;padding:16px 18px;display:flex;gap:12px;align-items:flex-start}
.delete-warning-box i{font-size:22px;color:var(--danger);flex-shrink:0;margin-top:1px}
.delete-warning-box strong{display:block;margin-bottom:3px;color:#991b1b}

@media(max-width:860px){
    .two-col{grid-template-columns:1fr}
    .page-header{padding:14px 16px}
    .main-content{padding:16px 12px 60px}
}
</style>
</head>
<body>
<?php include '../sidebar.php'; ?>
<div class="kb-content">

<!-- PAGE HEADER -->
<div class="page-header">
    <div>
        <h1><i class="bi bi-box-arrow-right text-primary me-2"></i>Salida a Guías</h1>
        <p class="subtitle">Registrar entrega de productos del almacén · KB Tours</p>
    </div>
    <a href="dashboard_almacen.php" class="btn-kb btn-outline-kb">
        <i class="bi bi-arrow-left"></i> Volver
    </a>
</div>

<div class="main-content">

<!-- TOAST -->
<?php if ($mensaje): ?>
<div class="toast-kb toast-success" id="toastMsg">
    <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($mensaje) ?>
</div>
<?php elseif ($error): ?>
<div class="toast-kb toast-error" id="toastMsg">
    <i class="bi bi-x-circle-fill"></i> <?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>

<div class="two-col">

<!-- ═══ FORMULARIO ═══ -->
<div>
<div class="kb-card">
    <div class="kb-card-header">
        <div class="section-title">
            <span class="section-icon" style="background:var(--brand)">
                <i class="bi bi-box-arrow-right"></i>
            </span>
            Nueva Salida
        </div>
        <span style="font-size:11px;color:var(--text-muted)">
            <i class="bi bi-info-circle me-1"></i>* Obligatorio
        </span>
    </div>

    <div class="form-body">

        <!-- ALERTAS -->
        <?php if (!$hay_guias): ?>
        <div class="warn-box">
            <i class="bi bi-person-x-fill"></i>
            <div>
                <strong>Sin guías registrados.</strong><br>
                Agrega guías en la sección de Planificación para poder registrar salidas.
            </div>
        </div>
        <?php endif; ?>

        <?php if (!$hay_stocks): ?>
        <div class="warn-box">
            <i class="bi bi-inbox-fill"></i>
            <div>
                <strong>Sin stock disponible.</strong><br>
                Registra productos en
                <a href="ingreso.php" style="color:#d97706;font-weight:600">Ingreso de Stock</a>
                antes de registrar salidas.
            </div>
        </div>
        <?php endif; ?>

        <form action="acciones/salida_action.php" method="POST" id="formSalida" novalidate>

            <!-- GUÍA -->
            <div class="field-group">
                <label class="field-label field-required">Guía</label>
                <div class="select-wrap">
                    <select name="nombre_guia" class="kb-select" id="selGuia" required
                            <?= !$hay_guias ? 'disabled' : '' ?>>
                        <option value="">— Seleccione guía —</option>
                        <?php foreach ($guias as $g): ?>
                        <option value="<?= htmlspecialchars($g['nombre_guia']) ?>">
                            <?= htmlspecialchars($g['nombre_guia']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <i class="bi bi-chevron-down chevron"></i>
                </div>
                <?php if (!$hay_guias): ?>
                <div style="font-size:11px;color:var(--danger);margin-top:4px">
                    <i class="bi bi-exclamation-circle me-1"></i>No hay guías en planificación
                </div>
                <?php endif; ?>
            </div>

            <!-- PRODUCTO -->
            <div class="field-group">
                <label class="field-label field-required">Producto</label>
                <div class="select-wrap">
                    <select name="id_stock" class="kb-select" id="selProducto" required
                            <?= !$hay_stocks ? 'disabled' : '' ?>>
                        <option value="">— Seleccione producto —</option>
                        <?php foreach ($stocks as $s): ?>
                        <option value="<?= $s['id_stock'] ?>"
                                data-tipo="<?= htmlspecialchars($s['tipo']) ?>"
                                data-disponible="<?= $s['cantidad_disponible'] ?>"
                                data-nombre="<?= htmlspecialchars($s['nombre']) ?>"
                                data-talla="<?= htmlspecialchars($s['talla'] ?? '') ?>">
                            <?= htmlspecialchars($s['nombre']) ?>
                            <?= $s['talla'] ? ' · Talla '.$s['talla'] : '' ?>
                            — <?= $s['cantidad_disponible'] ?> disp.
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <i class="bi bi-chevron-down chevron"></i>
                </div>

                <!-- Preview producto -->
                <div class="preview-card" id="previewProducto">
                    <div class="preview-row">
                        <span class="preview-label">Producto</span>
                        <span class="preview-val" id="pv-nombre">—</span>
                    </div>
                    <div class="preview-row">
                        <span class="preview-label">Disponible</span>
                        <span class="preview-val" id="pv-disp">—</span>
                    </div>
                    <div class="preview-row">
                        <span class="preview-label">Tipo</span>
                        <span class="preview-val" id="pv-tipo">—</span>
                    </div>
                </div>
            </div>

            <!-- CANTIDAD + FECHA -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="field-group">
                    <label class="field-label field-required">Cantidad</label>
                    <input type="number" name="cantidad" id="inputCantidad"
                           class="kb-input" min="1" max="9999"
                           placeholder="Ej: 5" required>
                    <div style="font-size:11px;color:var(--text-muted);margin-top:4px"
                         id="maxDisp" hidden>
                        Máx. disponible: <strong id="maxDispVal">—</strong>
                    </div>
                </div>
                <div class="field-group">
                    <label class="field-label field-required">Fecha de salida</label>
                    <input type="date" name="fecha_salida" class="kb-input"
                           value="<?= date('Y-m-d') ?>" required>
                </div>
            </div>

            <!-- GARANTÍA (solo tipo Garantia) -->
            <div class="field-group" id="grupoGarantia" style="display:none">
                <div class="garantia-badge">
                    <i class="bi bi-shield-lock-fill"></i>
                    Producto con garantía — ingrese el monto
                </div>
                <label class="field-label field-required">Monto de garantía (S/)</label>
                <div style="position:relative">
                    <span style="position:absolute;left:13px;top:50%;transform:translateY(-50%);
                                 color:var(--text-muted);font-weight:600;font-size:13px">S/</span>
                    <input type="number" step="0.01" min="0" name="garantia"
                           id="inputGarantia" class="kb-input garantia-active"
                           placeholder="0.00" style="padding-left:34px">
                </div>
                <div style="font-size:11px;color:#d97706;margin-top:4px">
                    <i class="bi bi-info-circle me-1"></i>
                    Se registrará como garantía retenida hasta la devolución
                </div>
            </div>

            <!-- OBSERVACIÓN -->
            <div class="field-group">
                <label class="field-label">Observación</label>
                <textarea name="observacion" class="kb-textarea"
                          placeholder="Ej: Para operación Inca Trail 20/06, entregado en oficina…"></textarea>
            </div>

            <hr class="form-divider">

            <button type="submit" class="btn-kb btn-danger-kb btn-lg-kb" id="btnSubmit">
                <i class="bi bi-box-arrow-right"></i> Registrar Salida
            </button>

        </form>
    </div>
</div>
</div><!-- /col form -->

<!-- ═══ SALIDAS RECIENTES ═══ -->
<div>
<div class="kb-card">
    <div class="kb-card-header">
        <div class="section-title">
            <span class="section-icon" style="background:#d97706">
                <i class="bi bi-clock-history"></i>
            </span>
            Salidas Recientes
        </div>
        <a href="pendientes.php" class="btn-kb btn-outline-kb"
           style="font-size:11px;padding:5px 12px">
            Ver todas →
        </a>
    </div>
    <div style="overflow-x:auto">
        <table class="table kb-table align-middle" style="width:100%;margin:0">
            <thead>
                <tr>
                    <th>Guía</th>
                    <th>Producto</th>
                    <th style="text-align:center">Cant.</th>
                    <th>Fecha</th>
                    <th>Garantía</th>
                    <th>Estado</th>
                    <th style="text-align:right">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($recientes)): ?>
            <tr>
                <td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted)">
                    <i class="bi bi-inbox" style="font-size:28px;display:block;margin-bottom:8px;color:#cbd5e1"></i>
                    <div style="font-weight:600;margin-bottom:4px">Sin salidas registradas</div>
                    <div style="font-size:12px">Las salidas que registres aparecerán aquí.</div>
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($recientes as $r):
                $est_class = match($r['estado']) {
                    'Parcial'  => 'est-parcial',
                    'Devuelto' => 'est-devuelto',
                    default    => 'est-pendiente',
                };
                $tiene_devoluciones = $r['total_devuelto'] > 0;
            ?>
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:8px">
                        <div class="avatar-sm">
                            <?= strtoupper(substr($r['nombre_guia'], 0, 2)) ?>
                        </div>
                        <span style="font-weight:600;font-size:12px">
                            <?= htmlspecialchars($r['nombre_guia']) ?>
                        </span>
                    </div>
                </td>
                <td style="font-size:12px">
                    <?= htmlspecialchars($r['producto']) ?>
                    <?= $r['talla'] ? '<span style="color:var(--text-muted)"> · '.$r['talla'].'</span>' : '' ?>
                </td>
                <td style="text-align:center;font-family:'DM Mono',monospace;font-weight:700">
                    <?= $r['cantidad'] ?>
                </td>
                <td style="font-family:'DM Mono',monospace;font-size:11px;white-space:nowrap">
                    <?= date('d/m/Y', strtotime($r['fecha_salida'])) ?>
                </td>
                <td>
                    <?php if ($r['garantia_original'] > 0): ?>
                    <span style="font-family:'DM Mono',monospace;font-size:12px;
                                 font-weight:600;color:#dc2626">
                        S/ <?= number_format($r['garantia_original'], 2) ?>
                    </span>
                    <?php else: ?>
                    <span style="color:var(--text-muted)">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <span class="estado-badge <?= $est_class ?>">
                        <?= $r['estado'] ?>
                    </span>
                </td>
                <td>
                    <div class="action-btns">
                        <button type="button" class="icon-btn edit"
                                title="Editar salida"
                                data-id="<?= $r['id_salida'] ?>"
                                data-guia="<?= htmlspecialchars($r['nombre_guia'], ENT_QUOTES) ?>"
                                data-cantidad="<?= $r['cantidad'] ?>"
                                data-fecha="<?= htmlspecialchars($r['fecha_salida']) ?>"
                                data-garantia="<?= $r['garantia_original'] ?>"
                                data-observacion="<?= htmlspecialchars($r['observacion'] ?? '', ENT_QUOTES) ?>"
                                data-producto="<?= htmlspecialchars($r['producto'] . ($r['talla'] ? ' · '.$r['talla'] : ''), ENT_QUOTES) ?>"
                                data-tipo="<?= htmlspecialchars($r['tipo_producto']) ?>"
                                data-devuelto="<?= $r['total_devuelto'] ?>"
                                onclick="abrirModalEditar(this)">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button type="button" class="icon-btn delete"
                                title="<?= $tiene_devoluciones ? 'No se puede eliminar: ya tiene devoluciones' : 'Eliminar salida' ?>"
                                data-id="<?= $r['id_salida'] ?>"
                                data-guia="<?= htmlspecialchars($r['nombre_guia'], ENT_QUOTES) ?>"
                                data-producto="<?= htmlspecialchars($r['producto'] . ($r['talla'] ? ' · '.$r['talla'] : ''), ENT_QUOTES) ?>"
                                data-cantidad="<?= $r['cantidad'] ?>"
                                <?= $tiene_devoluciones ? 'disabled' : 'onclick="abrirModalEliminar(this)"' ?>>
                            <i class="bi bi-trash3"></i>
                        </button>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- RESUMEN STOCK DISPONIBLE -->
<div class="kb-card">
    <div class="kb-card-header">
        <div class="section-title">
            <span class="section-icon" style="background:#16a34a">
                <i class="bi bi-boxes"></i>
            </span>
            Stock Disponible Ahora
        </div>
    </div>
    <div style="padding:16px 20px">
        <?php if (empty($stocks)): ?>
        <div style="text-align:center;padding:20px;color:var(--text-muted);font-size:13px">
            <i class="bi bi-inbox" style="font-size:24px;display:block;margin-bottom:6px;color:#cbd5e1"></i>
            Sin stock disponible
        </div>
        <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:10px">
        <?php foreach ($stocks as $s):
            $tipo_color = match(strtolower($s['tipo'] ?? '')) {
                'retornable' => '#16a34a',
                'garantia'   => '#d97706',
                default      => '#1a56db',
            };
        ?>
        <div style="display:flex;align-items:center;justify-content:space-between;
                    padding:10px 14px;background:var(--surface-2);border-radius:9px;
                    border:1px solid var(--border)">
            <div>
                <div style="font-weight:600;font-size:13px">
                    <?= htmlspecialchars($s['nombre']) ?>
                    <?= $s['talla'] ? '<span style="color:var(--text-muted);font-size:11px"> · '.$s['talla'].'</span>' : '' ?>
                </div>
                <div style="font-size:11px;color:<?= $tipo_color ?>;font-weight:600;margin-top:2px">
                    <?= htmlspecialchars($s['tipo']) ?>
                </div>
            </div>
            <div style="font-family:'DM Mono',monospace;font-size:16px;font-weight:700;
                        color:<?= $s['cantidad_disponible'] > 5 ? '#16a34a' : '#dc2626' ?>">
                <?= $s['cantidad_disponible'] ?>
                <span style="font-size:11px;color:var(--text-muted);font-weight:400"> uds.</span>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

</div><!-- /col derecha -->
</div><!-- /.two-col -->

</div><!-- /.main-content -->
</div><!-- /.kb-content -->

<!-- ═══ MODAL EDITAR SALIDA ═══ -->
<div class="modal fade" id="modalEditar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="acciones/editar_salida_action.php" method="POST" id="formEditar" novalidate>
        <input type="hidden" name="id_salida" id="edit_id_salida">
        <div class="modal-header">
          <div class="modal-title"><i class="bi bi-pencil-square text-primary"></i> Editar Salida</div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">

          <div class="field-group">
            <label class="field-label">Producto</label>
            <div class="readonly-chip">
              <i class="bi bi-box-seam"></i>
              <span id="edit_producto_label">—</span>
            </div>
          </div>

          <div class="field-group">
            <label class="field-label field-required">Guía</label>
            <div class="select-wrap">
              <select name="nombre_guia" id="edit_nombre_guia" class="kb-select" required>
                <?php foreach ($guias as $g): ?>
                <option value="<?= htmlspecialchars($g['nombre_guia']) ?>">
                    <?= htmlspecialchars($g['nombre_guia']) ?>
                </option>
                <?php endforeach; ?>
              </select>
              <i class="bi bi-chevron-down chevron"></i>
            </div>
          </div>

          <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
            <div class="field-group">
              <label class="field-label field-required">Cantidad</label>
              <input type="number" name="cantidad" id="edit_cantidad" class="kb-input" min="1" max="9999" required>
              <div style="font-size:11px;color:var(--text-muted);margin-top:4px" id="edit_devuelto_hint" hidden>
                Ya se devolvieron <strong id="edit_devuelto_val">0</strong> und. — no puede bajar de ahí.
              </div>
            </div>
            <div class="field-group">
              <label class="field-label field-required">Fecha de salida</label>
              <input type="date" name="fecha_salida" id="edit_fecha" class="kb-input" required>
            </div>
          </div>

          <div class="field-group" id="edit_grupoGarantia" style="display:none">
            <div class="garantia-badge">
              <i class="bi bi-shield-lock-fill"></i>
              Producto con garantía — ingrese el monto
            </div>
            <label class="field-label field-required">Monto de garantía (S/)</label>
            <div style="position:relative">
              <span style="position:absolute;left:13px;top:50%;transform:translateY(-50%);
                           color:var(--text-muted);font-weight:600;font-size:13px">S/</span>
              <input type="number" step="0.01" min="0" name="garantia" id="edit_garantia"
                     class="kb-input garantia-active" placeholder="0.00" style="padding-left:34px">
            </div>
          </div>

          <div class="field-group">
            <label class="field-label">Observación</label>
            <textarea name="observacion" id="edit_observacion" class="kb-textarea"></textarea>
          </div>

        </div>
        <div class="modal-footer" style="display:flex;justify-content:flex-end;gap:10px">
          <button type="button" class="btn-kb btn-outline-kb" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn-kb btn-danger-kb"><i class="bi bi-check-lg"></i> Guardar Cambios</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ═══ MODAL ELIMINAR SALIDA ═══ -->
<div class="modal fade" id="modalEliminar" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form action="acciones/eliminar_salida_action.php" method="POST">
        <input type="hidden" name="id_salida" id="del_id_salida">
        <div class="modal-header">
          <div class="modal-title"><i class="bi bi-trash3 text-danger"></i> Eliminar Salida</div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <div class="delete-warning-box">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div>
              <strong>Esta acción no se puede deshacer.</strong>
              Se eliminará la salida de <span id="del_cantidad">—</span> und. de
              "<span id="del_producto">—</span>" entregada a <strong id="del_guia">—</strong>,
              y el stock será restaurado automáticamente.
            </div>
          </div>
        </div>
        <div class="modal-footer" style="display:flex;justify-content:flex-end;gap:10px">
          <button type="button" class="btn-kb btn-outline-kb" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn-kb btn-danger-kb"><i class="bi bi-trash3"></i> Sí, Eliminar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── Preview producto + garantía (form nueva salida) ──
document.getElementById('selProducto').addEventListener('change', function () {
    const opt      = this.options[this.selectedIndex];
    const preview  = document.getElementById('previewProducto');
    const grupoG   = document.getElementById('grupoGarantia');
    const inputG   = document.getElementById('inputGarantia');
    const maxDisp  = document.getElementById('maxDisp');
    const maxVal   = document.getElementById('maxDispVal');
    const inputC   = document.getElementById('inputCantidad');

    if (!this.value) {
        preview.classList.remove('visible');
        grupoG.style.display = 'none';
        inputG.required = false;
        maxDisp.hidden = true;
        return;
    }

    const tipo  = opt.dataset.tipo;
    const disp  = parseInt(opt.dataset.disponible) || 0;
    const nombre = opt.dataset.nombre;
    const talla  = opt.dataset.talla;

    document.getElementById('pv-nombre').textContent = nombre + (talla ? ' · ' + talla : '');
    document.getElementById('pv-disp').textContent   = disp + ' unidades';
    document.getElementById('pv-tipo').textContent   = tipo;

    preview.classList.remove('warning');
    if (disp <= 3) preview.classList.add('warning');
    preview.classList.add('visible');

    inputC.max = disp;
    maxVal.textContent = disp;
    maxDisp.hidden = false;

    if (tipo === 'Garantia') {
        grupoG.style.display = 'block';
        inputG.required = true;
        inputG.focus();
    } else {
        grupoG.style.display = 'none';
        inputG.required = false;
        inputG.value = '';
    }
});

document.getElementById('inputCantidad').addEventListener('input', function () {
    const max = parseInt(this.max) || 0;
    const val = parseInt(this.value) || 0;
    if (max > 0 && val > max) {
        this.classList.add('error');
        this.setCustomValidity('Cantidad supera el stock disponible (' + max + ')');
    } else {
        this.classList.remove('error');
        this.setCustomValidity('');
    }
});

document.getElementById('formSalida').addEventListener('submit', function (e) {
    const guia    = document.getElementById('selGuia').value;
    const prod    = document.getElementById('selProducto').value;
    const cant    = document.getElementById('inputCantidad').value;
    const fecha   = document.querySelector('[name="fecha_salida"]').value;

    let ok = true;
    if (!guia)  { document.getElementById('selGuia').classList.add('error');       ok = false; }
    if (!prod)  { document.getElementById('selProducto').classList.add('error');   ok = false; }
    if (!cant || parseInt(cant) < 1) {
        document.getElementById('inputCantidad').classList.add('error'); ok = false;
    }
    if (!fecha) ok = false;

    if (!ok) { e.preventDefault(); return; }

    const btn = document.getElementById('btnSubmit');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Registrando…';
});

document.querySelectorAll('.kb-input, .kb-select').forEach(el => {
    el.addEventListener('change', () => el.classList.remove('error'));
    el.addEventListener('input',  () => el.classList.remove('error'));
});

const toast = document.getElementById('toastMsg');
if (toast) setTimeout(() => { toast.style.opacity = '0'; }, 3500);

// ── MODAL EDITAR: poblar datos ──
const modalEditarEl = document.getElementById('modalEditar');
const bsModalEditar  = new bootstrap.Modal(modalEditarEl);

function abrirModalEditar(btn) {
    const d = btn.dataset;

    document.getElementById('edit_id_salida').value   = d.id;
    document.getElementById('edit_producto_label').textContent = d.producto;
    document.getElementById('edit_nombre_guia').value = d.guia;
    document.getElementById('edit_cantidad').value    = d.cantidad;
    document.getElementById('edit_fecha').value       = d.fecha;
    document.getElementById('edit_observacion').value = d.observacion;

    const grupoG = document.getElementById('edit_grupoGarantia');
    const inputG = document.getElementById('edit_garantia');
    if (d.tipo === 'Garantia') {
        grupoG.style.display = 'block';
        inputG.required = true;
        inputG.value = parseFloat(d.garantia).toFixed(2);
    } else {
        grupoG.style.display = 'none';
        inputG.required = false;
        inputG.value = '';
    }

    const devuelto = parseInt(d.devuelto) || 0;
    const hint = document.getElementById('edit_devuelto_hint');
    const cantidadInput = document.getElementById('edit_cantidad');
    if (devuelto > 0) {
        hint.hidden = false;
        document.getElementById('edit_devuelto_val').textContent = devuelto;
        cantidadInput.min = devuelto;
    } else {
        hint.hidden = true;
        cantidadInput.min = 1;
    }

    bsModalEditar.show();
}

// ── MODAL ELIMINAR: poblar datos ──
const modalEliminarEl = document.getElementById('modalEliminar');
const bsModalEliminar  = new bootstrap.Modal(modalEliminarEl);

function abrirModalEliminar(btn) {
    const d = btn.dataset;
    document.getElementById('del_id_salida').value = d.id;
    document.getElementById('del_producto').textContent = d.producto;
    document.getElementById('del_guia').textContent = d.guia;
    document.getElementById('del_cantidad').textContent = d.cantidad;
    bsModalEliminar.show();
}
</script>

</body>
</html>