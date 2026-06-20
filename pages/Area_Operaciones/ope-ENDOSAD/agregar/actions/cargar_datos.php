<?php
// ── Grupo del cliente ──────────────────────────────────────────────────
$qGrupo   = mysqli_query($conexion, "SELECT id_grupo FROM clientes_grupo WHERE id_cliente = $id_cliente LIMIT 1");
$rowGrupo = mysqli_fetch_assoc($qGrupo);
$id_grupo = $rowGrupo['id_grupo'] ?? null;

// ── Datos del cliente ──────────────────────────────────────────────────
$res     = mysqli_query($conexion, "SELECT CONCAT(nombre,' ',apellido) nombre_completo FROM datos_clientes WHERE id_cliente = $id_cliente");
$cliente = mysqli_fetch_assoc($res);
if (!$cliente) die("Cliente no encontrado.");

// ── Servicios desde BD ─────────────────────────────────────────────────
$resServ  = mysqli_query($conexion, "SELECT id_servicio, nombre, duracion_dias FROM servicios WHERE activo = 1 ORDER BY nombre");
$servicios = [];
while ($s = mysqli_fetch_assoc($resServ)) $servicios[] = $s;

// Mapeo duraciones para JS
$duraciones_js = [];
foreach ($servicios as $s) {
    if ($s['duracion_dias']) $duraciones_js[$s['id_servicio']] = (int)$s['duracion_dias'];
}
?>