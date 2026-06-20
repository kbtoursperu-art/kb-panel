<?php
// ── Datos de la operación + cliente ────────────────────────────────────
$qOp = mysqli_query($conexion,"
    SELECT o.*, dc.nombre, dc.apellido, dc.id_cliente,
           g.nombre_grupo, g.id_grupo
    FROM operaciones o
    JOIN datos_clientes dc ON dc.id_cliente = o.id_cliente
    LEFT JOIN grupos g ON g.id_grupo = o.id_grupo
    WHERE o.id_operaciones = $id_operacion
");
$op = mysqli_fetch_assoc($qOp);
if (!$op) die("Operación no encontrada.");

// ── Servicios activos ──────────────────────────────────────────────────
$resServ = mysqli_query($conexion,"SELECT id_servicio, nombre, duracion_dias FROM servicios WHERE activo=1 ORDER BY nombre");
$servicios = [];
while ($s = mysqli_fetch_assoc($resServ)) $servicios[] = $s;

// ── Tours existentes ───────────────────────────────────────────────────
$qTours = mysqli_query($conexion,"
    SELECT od.*, s.nombre AS nombre_servicio, s.duracion_dias
    FROM operaciones_detalle od
    LEFT JOIN servicios s ON s.id_servicio = od.id_servicio
    WHERE od.id_operaciones = $id_operacion ORDER BY od.fecha_salida ASC
");
$tours = [];
while ($t = mysqli_fetch_assoc($qTours)) {
    $qAd = mysqli_query($conexion,"SELECT * FROM adicionales_detalle WHERE id_detalle={$t['id_detalle']}");
    $t['adicionales'] = [];
    while ($a = mysqli_fetch_assoc($qAd)) $t['adicionales'][] = $a;
    $tours[] = $t;
}

// ── Pagos existentes ───────────────────────────────────────────────────
$qPagos = mysqli_query($conexion,"SELECT * FROM pagos WHERE id_operaciones=$id_operacion ORDER BY fecha ASC, id_pago ASC");
$pagos = [];
while ($p = mysqli_fetch_assoc($qPagos)) $pagos[] = $p;

// ── Contabilidad ───────────────────────────────────────────────────────
$cont = mysqli_fetch_assoc(mysqli_query($conexion,"SELECT * FROM contabilidad WHERE id_operaciones=$id_operacion LIMIT 1")) ?: [];

// ── Totales para resumen ─────────────────────────────
$tot_s = 0;
$tot_d = 0;
$pag_s = 0;
$pag_d = 0;

foreach ($tours as $t) {
    if (($t['tipo_moneda'] ?? '') === 'Soles') {
        $tot_s += (float)$t['precio'];
    } else {
        $tot_d += (float)$t['precio'];
    }
}

foreach ($pagos as $p) {
    if (($p['tipo'] ?? '') !== 'reembolso') {
        if (($p['moneda'] ?? '') === 'Soles') {
            $pag_s += (float)$p['monto'];
        } else {
            $pag_d += (float)$p['monto'];
        }
    }
}

$saldo_s = $tot_s - $pag_s;
$saldo_d = $tot_d - $pag_d;

// ── Duraciones para JS ────────────────────────────────────────────────
$duraciones_js = [];
foreach ($servicios as $s) {
    if ($s['duracion_dias']) $duraciones_js[$s['id_servicio']] = (int)$s['duracion_dias'];
}
// ══════════════════════════════════════════════════
// HELPERS — MISMO DISEÑO QUE agregar.php
// ══════════════════════════════════════════════════
$ADICIONALES_OPTS_LIST = [
    'Ninguna','Ingreso a Mollepata','Bolsa de Dormir','Bastones','Hotel',
    'Montaña Huayna Picchu','Montaña Machu Picchu',
    'Trans. Mochilas Playa-Idro','Trans. Mochilas Hidro-Aguas'
];

function buildFilaTour(int $idx, array $servicios, array $t = [], array $AD_OPTS = []): string {
    // opciones servicio
    $opts = '<option value="">— Seleccionar servicio —</option>';
    foreach ($servicios as $s) {
        $sel  = (!empty($t) && $s['id_servicio'] == ($t['id_servicio'] ?? 0)) ? 'selected' : '';
        $opts .= '<option value="'.$s['id_servicio'].'" '.$sel.'>'.htmlspecialchars($s['nombre']).'</option>';
    }

    // opciones adicionales
    $adOpts = '';
    $adSeleccionados = !empty($t['adicionales']) ? array_column($t['adicionales'],'nombre') : [];
    foreach ($AD_OPTS as $a) {
        $sel   = in_array($a, $adSeleccionados) ? 'selected' : '';
        $adOpts .= '<option value="'.$a.'" '.$sel.'>'.$a.'</option>';
    }

    $precio    = htmlspecialchars($t['precio']            ?? '');
    $monedaS   = ($t['tipo_moneda'] ?? 'Soles') === 'Soles'   ? 'selected' : '';
    $monedaD   = ($t['tipo_moneda'] ?? 'Soles') === 'Dólares' ? 'selected' : '';
    $fsalida   = $t['fecha_salida']      ?? '';
    $fretorno  = $t['fecha_retorno']     ?? '';
    $modal     = $t['modalidad_retorno'] ?? '';
    $ingreso   = ($t['incluye_ingreso']  ?? 'NO') === 'SI' ? 'checked' : '';

    $mTren  = $modal==='Tren'      ? 'selected' : '';
    $mCarro = $modal==='Carro'     ? 'selected' : '';
    $mCam   = $modal==='Caminata'  ? 'selected' : '';
    $mSin   = $modal==='Sin retorno'?'selected' : '';
     return <<<HTML
<tr>
    <td><select name="id_servicio[]" class="kb-input kb-select serv-select">$opts</select></td>
    <td><input type="number" step="0.01" name="precio_tour[]" class="kb-input precio_tour" placeholder="0.00" value="$precio"></td>
    <td>
        <select name="moneda_tour[]" class="kb-input kb-select moneda-tour-sel">
            <option value="Soles" $monedaS>S/</option>
            <option value="Dólares" $monedaD>$</option>
        </select>
    </td>
    <td><input type="date" name="fecha_salida[]"  class="kb-input fecha-salida" value="$fsalida"></td>
    <td><input type="date" name="fecha_retorno[]" class="kb-input" value="$fretorno"></td>
    <td>
        <select name="modalidad_retorno[]" class="kb-input kb-select">
            <option value="">—</option>
            <option value="Tren" $mTren>Tren</option>
            <option value="Carro" $mCarro>Carro</option>
            <option value="Caminata" $mCam>Caminata</option>
            <option value="Sin retorno" $mSin>Sin retorno</option>
        </select>
    </td>
    <td>
        <div class="check-wrap">
            <input type="hidden"   name="incluye_ingreso[$idx]" value="NO">
            <input type="checkbox" name="incluye_ingreso[$idx]" value="SI" class="kb-checkbox" $ingreso title="Incluye ingreso">
        </div>
    </td>
    <td>
        <select name="servicio_adicional[$idx][]" multiple class="adicionales-select">$adOpts</select>
    </td>
    <td>
        <button type="button" class="kb-btn kb-btn-danger kb-btn-xs" onclick="eliminarFila(this)" title="Eliminar">
            <i class="fas fa-times"></i>
        </button>
    </td>
</tr>
HTML;
}

function buildFilaPago(array $p = []): string {
    $hoy    = date('Y-m-d');
    $tipo   = $p['tipo']        ?? 'tour';
    $metodo = $p['metodo_pago'] ?? 'Efectivo';
    $moneda = $p['moneda']      ?? 'Soles';
    $monto  = $p['monto']       ?? '';
    $fecha  = $p['fecha']       ?? $hoy;

    $tipos = ['tour'=>'Tour','adicional'=>'Adicional','cuenta'=>'A Cuenta','saldo'=>'Saldo','reembolso'=>'Reembolso'];
    $opts_tipo = '';
    foreach ($tipos as $v=>$l) $opts_tipo .= '<option value="'.$v.'" '.($tipo===$v?'selected':'').'>'.$l.'</option>';

    $metodos = ['Efectivo','We travel','CULQI','Izipay','PAYPAL','BCP','YAPE','Transferencia'];
    $opts_met = '';
    foreach ($metodos as $m) $opts_met .= '<option value="'.$m.'" '.($metodo===$m?'selected':'').'>'.$m.'</option>';

    $selS = $moneda==='Soles'   ? 'selected' : '';
    $selD = $moneda==='Dólares' ? 'selected' : '';

    return <<<HTML
<tr>
    <td>
        <select name="tipo_pago[]" class="kb-input kb-select tipo-pago-select">$opts_tipo</select>
    </td>
    <td>
        <select name="metodo_pago_multi[]" class="kb-input kb-select">$opts_met</select>
    </td>
    <td>
        <select name="moneda_multi[]" class="kb-input kb-select">
            <option value="Soles" $selS>S/</option>
            <option value="Dólares" $selD>$</option>
        </select>
    </td>
    <td><input type="number" step="0.01" name="monto_multi[]" class="kb-input monto-pago" placeholder="0.00" value="$monto"></td>
    <td><input type="date" name="fecha_multi[]" class="kb-input" value="$fecha"></td>
    <td>
        <button type="button" class="kb-btn kb-btn-danger kb-btn-xs" onclick="eliminarPago(this)" title="Eliminar">
            <i class="fas fa-times"></i>
        </button>
    </td>
</tr>
HTML;
}
?>