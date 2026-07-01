<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: ../../dashboard_almacen.php");
    exit();
}
include '../../conexion.php';
mysqli_set_charset($conexion, 'utf8mb4');

// ── Stock actual ──
$stock_res = mysqli_query($conexion,"
    SELECT st.id_stock, i.nombre, i.tipo, st.talla,
           st.cantidad_total, st.cantidad_disponible
    FROM almacen_stock st
    JOIN almacen_items i ON i.id_item = st.id_item
    ORDER BY i.nombre, st.talla
");
$stocks = mysqli_fetch_all($stock_res, MYSQLI_ASSOC);
$hay_stock = count($stocks) > 0;

// Si no hay stock → abrir tab "nuevo" por defecto
$modo_inicial = $hay_stock ? 'existente' : 'nuevo';

$mensaje = $_GET['ok']    ?? '';
$error   = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ingreso de Stock — KB Tours</title>
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

.main-content{max-width:920px;margin:0 auto;padding:28px 24px 80px}

.kb-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);
    box-shadow:var(--shadow);overflow:hidden;margin-bottom:24px}
.kb-card-header{padding:14px 20px;border-bottom:1px solid var(--border);background:var(--surface-2);
    display:flex;align-items:center;justify-content:space-between}
.section-title{display:flex;align-items:center;gap:8px;font-family:'Outfit',sans-serif;
    font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.7px}
.section-icon{width:28px;height:28px;border-radius:7px;display:flex;align-items:center;
    justify-content:center;font-size:13px;color:#fff}

.form-body{padding:24px}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.form-row-3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:20px}

.field-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;
    color:var(--text-muted);margin-bottom:6px;display:block}
.field-required::after{content:' *';color:var(--danger)}

.kb-input,.kb-select{background:var(--surface-2);border:1.5px solid var(--border);border-radius:9px;
    padding:9px 13px;font-size:13px;font-family:'DM Sans',sans-serif;color:var(--text);
    width:100%;outline:none;transition:border-color .15s,box-shadow .15s,background .15s;appearance:none}
.kb-input:focus,.kb-select:focus{border-color:var(--brand);background:#fff;box-shadow:0 0 0 3px rgba(26,86,219,.1)}
.kb-input.error,.kb-select.error{border-color:var(--danger)}

.select-wrap{position:relative}
.select-wrap i{position:absolute;right:12px;top:50%;transform:translateY(-50%);
    pointer-events:none;color:var(--text-muted);font-size:14px}
.select-wrap .kb-select{padding-right:36px}

.modo-tabs{display:flex;gap:0;background:var(--surface-3);border-radius:10px;padding:4px;margin-bottom:24px}
.modo-tab{flex:1;padding:8px 0;text-align:center;font-size:13px;font-weight:600;
    border-radius:7px;cursor:pointer;border:none;background:transparent;
    color:var(--text-muted);transition:all .15s}
.modo-tab.active{background:var(--surface);color:var(--brand);box-shadow:0 1px 4px rgba(0,0,0,.08)}

.preview-card{background:linear-gradient(135deg,var(--brand-light),#eff6ff);border:1.5px solid #bfdbfe;
    border-radius:10px;padding:14px 16px;margin-top:12px;display:none}
.preview-card.visible{display:block}
.preview-row{display:flex;justify-content:space-between;margin-bottom:6px}
.preview-row:last-child{margin-bottom:0}
.preview-label{font-size:11px;color:var(--text-muted);font-weight:600;text-transform:uppercase;letter-spacing:.5px}
.preview-val{font-family:'DM Mono',monospace;font-size:12px;font-weight:600;color:var(--brand-dark)}

.form-divider{border:none;border-top:1px solid var(--border);margin:22px 0}

.btn-kb{padding:10px 22px;border-radius:9px;font-size:13px;font-weight:600;
    font-family:'DM Sans',sans-serif;cursor:pointer;border:none;transition:all .15s;
    display:inline-flex;align-items:center;gap:7px;text-decoration:none}
.btn-success-kb{background:var(--success);color:#fff}
.btn-success-kb:hover{background:#15803d;color:#fff}
.btn-outline-kb{background:transparent;color:var(--text-muted);border:1.5px solid var(--border)}
.btn-outline-kb:hover{background:var(--surface-2);color:var(--text)}
.btn-lg-kb{padding:12px 28px;font-size:14px;border-radius:10px}

.toast-kb{position:fixed;top:20px;right:20px;padding:14px 20px;border-radius:10px;
    font-size:13px;font-weight:600;display:flex;align-items:center;gap:10px;
    box-shadow:var(--shadow-md);z-index:9999;animation:slideIn .25s ease;max-width:360px;
    transition:opacity .4s}
.toast-success{background:#dcfce7;color:#15803d;border:1px solid #bbf7d0}
.toast-error{background:#fee2e2;color:#b91c1c;border:1px solid #fca5a5}
@keyframes slideIn{from{opacity:0;transform:translateX(20px)}to{opacity:1;transform:translateX(0)}}

/* aviso sin productos */
.empty-notice{background:linear-gradient(135deg,#fef3c7,#fef9c3);border:1.5px solid #fde68a;
    border-radius:10px;padding:16px 20px;margin-bottom:20px;display:flex;align-items:flex-start;gap:12px}
.empty-notice i{font-size:20px;color:#d97706;flex-shrink:0;margin-top:2px}

/* stock table */
.kb-table thead th{background:var(--surface-2);font-size:10px;font-weight:700;
    text-transform:uppercase;letter-spacing:.7px;color:var(--text-muted);
    border-bottom:2px solid var(--border);padding:10px 14px;white-space:nowrap}
.kb-table tbody td{padding:11px 14px;vertical-align:middle;border-bottom:1px solid var(--border);font-size:13px}
.kb-table tbody tr:hover{background:var(--surface-2)}

.badge-tipo{display:inline-block;padding:2px 10px;border-radius:20px;
    font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px}
.badge-consumible{background:#dbeafe;color:#1e40af}
.badge-retornable{background:#dcfce7;color:#166534}
.badge-garantia{background:#fef3c7;color:#92400e}

.stock-bar-wrap{display:flex;align-items:center;gap:8px}
.stock-bar{flex:1;height:6px;background:var(--surface-3);border-radius:99px;overflow:hidden;min-width:60px}
.stock-bar-fill{height:100%;border-radius:99px}
.stock-pct{font-family:'DM Mono',monospace;font-size:11px;color:var(--text-muted);min-width:32px}

@media(max-width:640px){
    .form-row,.form-row-3{grid-template-columns:1fr}
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
        <h1><i class="bi bi-plus-circle text-success me-2"></i>Ingreso de Stock</h1>
        <p class="subtitle">Registrar entrada de productos al almacén · KB Tours</p>
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

<!-- AVISO SI NO HAY PRODUCTOS -->
<?php if (!$hay_stock): ?>
<div class="empty-notice">
    <i class="bi bi-info-circle-fill"></i>
    <div>
        <div style="font-weight:700;font-size:13px;color:#92400e;margin-bottom:4px">
            No hay productos registrados en el sistema
        </div>
        <div style="font-size:12px;color:#a16207">
            Usa el formulario de abajo para crear tu primer producto e ingresar su stock inicial.
            Una vez creado podrás agregar más unidades desde el tab "Agregar a existente".
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ═══ FORMULARIO ═══ -->
<div class="kb-card">
    <div class="kb-card-header">
        <div class="section-title">
            <span class="section-icon" style="background:#16a34a">
                <i class="bi bi-box-seam-fill"></i>
            </span>
            Registrar Ingreso
        </div>
        <span style="font-size:11px;color:var(--text-muted)">
            <i class="bi bi-info-circle me-1"></i>Los campos con * son obligatorios
        </span>
    </div>

    <div class="form-body">

        <!-- TABS -->
        <div class="modo-tabs">
            <button class="modo-tab <?= $modo_inicial==='existente' ? 'active' : '' ?>"
                    id="tabExistente" onclick="setModo('existente')">
                <i class="bi bi-archive me-1"></i> Agregar a existente
                <?php if (!$hay_stock): ?>
                <span style="background:#fde68a;color:#92400e;border-radius:20px;
                             padding:1px 7px;font-size:10px;margin-left:4px">Sin datos</span>
                <?php endif; ?>
            </button>
            <button class="modo-tab <?= $modo_inicial==='nuevo' ? 'active' : '' ?>"
                    id="tabNuevo" onclick="setModo('nuevo')">
                <i class="bi bi-plus-square me-1"></i> Producto nuevo
            </button>
        </div>

        <!-- ── EXISTENTE ── -->
        <div id="seccion-existente"
             style="display:<?= $modo_inicial==='existente' ? 'block' : 'none' ?>">

            <?php if (!$hay_stock): ?>
            <div style="text-align:center;padding:40px 20px;color:var(--text-muted)">
                <i class="bi bi-inbox" style="font-size:36px;display:block;margin-bottom:10px;color:#cbd5e1"></i>
                <div style="font-weight:600;font-size:14px;margin-bottom:6px">No hay productos aún</div>
                <div style="font-size:12px;margin-bottom:16px">
                    Primero registra un producto nuevo usando el tab de la derecha.
                </div>
                <button onclick="setModo('nuevo')" class="btn-kb btn-success-kb">
                    <i class="bi bi-plus-square"></i> Crear primer producto
                </button>
            </div>
            <?php else: ?>

            <form action="acciones/ingreso_action.php" method="POST" id="formExistente" novalidate>
                <input type="hidden" name="modo" value="existente">

                <div class="mb-4">
                    <label class="field-label field-required">Producto</label>
                    <div class="select-wrap">
                        <select name="id_stock" class="kb-select" id="selectStock" required>
                            <option value="">— Seleccione un producto —</option>
                            <?php foreach ($stocks as $s): ?>
                            <option value="<?= $s['id_stock'] ?>"
                                    data-total="<?= $s['cantidad_total'] ?>"
                                    data-disponible="<?= $s['cantidad_disponible'] ?>"
                                    data-tipo="<?= htmlspecialchars($s['tipo']) ?>">
                                <?= htmlspecialchars($s['nombre']) ?>
                                <?= $s['talla'] ? ' · Talla '.$s['talla'] : '' ?>
                                — (<?= $s['cantidad_disponible'] ?>/<?= $s['cantidad_total'] ?> disp.)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <i class="bi bi-chevron-down"></i>
                    </div>

                    <div class="preview-card" id="previewStock">
                        <div class="preview-row">
                            <span class="preview-label">Stock total</span>
                            <span class="preview-val" id="pv-total">—</span>
                        </div>
                        <div class="preview-row">
                            <span class="preview-label">Disponible</span>
                            <span class="preview-val" id="pv-disponible">—</span>
                        </div>
                        <div class="preview-row">
                            <span class="preview-label">En uso</span>
                            <span class="preview-val" id="pv-enuso">—</span>
                        </div>
                        <div class="preview-row">
                            <span class="preview-label">Tipo</span>
                            <span class="preview-val" id="pv-tipo">—</span>
                        </div>
                    </div>
                </div>

                <div class="form-row">
                    <div>
                        <label class="field-label field-required">Cantidad a ingresar</label>
                        <input type="number" name="cantidad" id="cantExistente"
                               class="kb-input" min="1" max="9999"
                               placeholder="Ej: 10" required>
                        <div style="font-size:11px;color:var(--text-muted);margin-top:5px">
                            <i class="bi bi-info-circle me-1"></i>Se suma al total y disponible
                        </div>
                    </div>
                    <div>
                        <label class="field-label">Observación (opcional)</label>
                        <input type="text" name="observacion" class="kb-input"
                               placeholder="Ej: Reposición mensual…">
                    </div>
                </div>

                <hr class="form-divider">
                <div style="display:flex;gap:10px;justify-content:flex-end">
                    <a href="index.php" class="btn-kb btn-outline-kb">
                        <i class="bi bi-x"></i> Cancelar
                    </a>
                    <button type="submit" class="btn-kb btn-success-kb btn-lg-kb" id="btnSubmit">
                        <i class="bi bi-plus-circle-fill"></i> Registrar Ingreso
                    </button>
                </div>
            </form>
            <?php endif; ?>
        </div>

        <!-- ── NUEVO PRODUCTO ── -->
        <div id="seccion-nuevo"
             style="display:<?= $modo_inicial==='nuevo' ? 'block' : 'none' ?>">

            <div style="background:var(--brand-light);border:1px solid #bfdbfe;border-radius:10px;
                        padding:12px 16px;margin-bottom:22px;font-size:12px;color:var(--brand-dark);
                        display:flex;align-items:center;gap:8px">
                <i class="bi bi-lightbulb-fill"></i>
                Crea un <strong>nuevo producto</strong> y define su stock inicial.
                Luego podrás agregar más unidades desde "Agregar a existente".
            </div>

            <form action="acciones/ingreso_action.php" method="POST" id="formNuevo" novalidate>
                <input type="hidden" name="modo" value="nuevo">

                <div class="form-row" style="margin-bottom:20px">
                    <div>
                        <label class="field-label field-required">Nombre del producto</label>
                        <input type="text" name="nombre_item" class="kb-input"
                               placeholder="Ej: Bastón de trekking, Poncho de lluvia…" required>
                    </div>
                    <div>
                        <label class="field-label field-required">Tipo</label>
                        <div class="select-wrap">
                            <select name="tipo_item" class="kb-select" required>
                                <option value="">— Seleccione —</option>
                                <option value="Consumible">🔵 Consumible</option>
                                <option value="Retornable">🟢 Retornable</option>
                                <option value="Garantia">🟡 Garantía</option>
                            </select>
                            <i class="bi bi-chevron-down"></i>
                        </div>
                        <div style="font-size:11px;color:var(--text-muted);margin-top:5px">
                            Consumible: se gasta · Retornable: se devuelve · Garantía: con depósito
                        </div>
                    </div>
                </div>

                <div class="form-row-3" style="margin-bottom:20px">
                    <div>
                        <label class="field-label">Talla / Variante</label>
                        <input type="text" name="talla" class="kb-input"
                               placeholder="Ej: S, M, L, XL, Única…">
                        <div style="font-size:11px;color:var(--text-muted);margin-top:5px">
                            Deja vacío si no aplica
                        </div>
                    </div>
                    <div>
                        <label class="field-label field-required">Stock inicial</label>
                        <input type="number" name="cantidad" class="kb-input"
                               min="1" max="9999" placeholder="Ej: 20" required>
                    </div>
                    <div>
                        <label class="field-label">Observación</label>
                        <input type="text" name="observacion" class="kb-input"
                               placeholder="Notas de ingreso…">
                    </div>
                </div>

                <hr class="form-divider">
                <div style="display:flex;gap:10px;justify-content:flex-end">
                    <a href="index.php" class="btn-kb btn-outline-kb">
                        <i class="bi bi-x"></i> Cancelar
                    </a>
                    <button type="submit" class="btn-kb btn-success-kb btn-lg-kb">
                        <i class="bi bi-box-seam-fill"></i> Crear Producto e Ingresar
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>

<!-- ═══ STOCK ACTUAL ═══ -->
<div class="kb-card">
    <div class="kb-card-header">
        <div class="section-title">
            <span class="section-icon" style="background:#1a56db">
                <i class="bi bi-boxes"></i>
            </span>
            Stock Actual
        </div>
        <span style="font-size:11px;color:var(--text-muted)">
            <?= count($stocks) ?> producto(s) registrado(s)
        </span>
    </div>
    <div style="overflow-x:auto">
        <table class="table kb-table align-middle" style="width:100%;margin:0">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Tipo</th>
                    <th>Talla</th>
                    <th style="text-align:center">Total</th>
                    <th style="text-align:center">Disponible</th>
                    <th style="text-align:center">En Uso</th>
                    <th>Disponibilidad</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($stocks)): ?>
            <tr>
                <td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted)">
                    <i class="bi bi-inbox" style="font-size:32px;display:block;margin-bottom:8px;color:#cbd5e1"></i>
                    <div style="font-weight:600;margin-bottom:4px">Sin productos registrados</div>
                    <div style="font-size:12px">Crea el primer producto usando el formulario de arriba.</div>
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($stocks as $s):
                $pct = $s['cantidad_total'] > 0
                    ? round(($s['cantidad_disponible'] / $s['cantidad_total']) * 100) : 0;
                $bar_color = $pct >= 60 ? '#16a34a' : ($pct >= 30 ? '#d97706' : '#dc2626');
                $en_uso = $s['cantidad_total'] - $s['cantidad_disponible'];
                $tipo_class = match(strtolower($s['tipo'] ?? '')) {
                    'consumible' => 'badge-consumible',
                    'retornable' => 'badge-retornable',
                    'garantia'   => 'badge-garantia',
                    default      => 'badge-consumible',
                };
            ?>
            <tr>
                <td style="font-weight:600"><?= htmlspecialchars($s['nombre']) ?></td>
                <td><span class="badge-tipo <?= $tipo_class ?>"><?= htmlspecialchars($s['tipo'] ?? '—') ?></span></td>
                <td style="font-family:'DM Mono',monospace;font-size:12px">
                    <?= $s['talla'] ? htmlspecialchars($s['talla']) : '—' ?>
                </td>
                <td style="text-align:center;font-family:'DM Mono',monospace;font-weight:600">
                    <?= $s['cantidad_total'] ?>
                </td>
                <td style="text-align:center">
                    <span style="font-family:'DM Mono',monospace;font-weight:600;
                                 color:<?= $s['cantidad_disponible'] > 0 ? '#16a34a' : '#dc2626' ?>">
                        <?= $s['cantidad_disponible'] ?>
                    </span>
                </td>
                <td style="text-align:center">
                    <span style="font-family:'DM Mono',monospace;font-size:12px;
                                 color:<?= $en_uso > 0 ? '#d97706' : 'var(--text-muted)' ?>">
                        <?= $en_uso ?>
                    </span>
                </td>
                <td style="min-width:130px">
                    <div class="stock-bar-wrap">
                        <div class="stock-bar">
                            <div class="stock-bar-fill"
                                 style="width:<?= $pct ?>%;background:<?= $bar_color ?>"></div>
                        </div>
                        <span class="stock-pct"><?= $pct ?>%</span>
                    </div>
                    <?php if ($pct < 20): ?>
                    <div style="font-size:10px;color:#dc2626;font-weight:600;margin-top:2px">⚠ Stock crítico</div>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</div><!-- /.main-content -->
</div><!-- /.kb-content -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function setModo(modo) {
    document.getElementById('tabExistente').classList.toggle('active', modo === 'existente');
    document.getElementById('tabNuevo').classList.toggle('active', modo === 'nuevo');
    document.getElementById('seccion-existente').style.display = modo === 'existente' ? 'block' : 'none';
    document.getElementById('seccion-nuevo').style.display     = modo === 'nuevo'     ? 'block' : 'none';
}

const sel = document.getElementById('selectStock');
if (sel) {
    sel.addEventListener('change', function () {
        const opt     = this.options[this.selectedIndex];
        const preview = document.getElementById('previewStock');
        if (!this.value) { preview.classList.remove('visible'); return; }
        const total = parseInt(opt.dataset.total) || 0;
        const disp  = parseInt(opt.dataset.disponible) || 0;
        document.getElementById('pv-total').textContent      = total + ' uds.';
        document.getElementById('pv-disponible').textContent = disp  + ' uds.';
        document.getElementById('pv-enuso').textContent      = (total - disp) + ' uds.';
        document.getElementById('pv-tipo').textContent       = opt.dataset.tipo || '—';
        preview.classList.add('visible');
    });
}

const formE = document.getElementById('formExistente');
if (formE) {
    formE.addEventListener('submit', function (e) {
        const stock = document.getElementById('selectStock').value;
        const cant  = document.getElementById('cantExistente').value;
        if (!stock || !cant || parseInt(cant) < 1) {
            e.preventDefault();
            if (!stock) document.getElementById('selectStock').classList.add('error');
            if (!cant || parseInt(cant) < 1) document.getElementById('cantExistente').classList.add('error');
            return;
        }
        const btn = document.getElementById('btnSubmit');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Registrando…';
    });
}

const toast = document.getElementById('toastMsg');
if (toast) setTimeout(() => { toast.style.opacity = '0'; }, 3500);
</script>
</body>
</html>