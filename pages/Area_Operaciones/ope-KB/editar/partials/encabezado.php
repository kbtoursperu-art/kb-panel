<!-- ── Header ──────────────────────────────────── -->
<div class="page-header">
    <div>
        <div class="page-title">
            <i class="fas fa-pencil-alt" style="color:var(--accent);margin-right:8px;font-size:18px"></i>
            Editar operación
            <span style="font-size:14px;color:var(--muted);font-weight:400"> #<?= $id_operacion ?></span>
        </div>
        <div class="page-sub">Modifica tours, pagos y comisión</div>
    </div>
    <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
        <span class="eb eb-<?= $op['estado'] ?>"><?= ucfirst($op['estado']) ?></span>
        <?php if (!empty($cont['estado'])): ?>
        <span class="eb eb-<?= $cont['estado'] ?>"><i class="fas fa-file-invoice-dollar" style="font-size:9px;margin-right:3px"></i><?= ucfirst($cont['estado']) ?></span>
        <?php endif; ?>
        <a href="index.php" class="page-back"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>
</div>

<!-- CHIP CLIENTE -->
<div class="client-chip">
    <i class="fas fa-user"></i>
    <?= htmlspecialchars($op['nombre'].' '.$op['apellido']) ?>
    <?php if ($op['nombre_grupo']): ?>
        &nbsp;·&nbsp; <i class="fas fa-users" style="font-size:12px"></i>
        <?= htmlspecialchars($op['nombre_grupo']) ?> &nbsp;·&nbsp; Grupo #<?= $op['id_grupo'] ?>
    <?php endif; ?>
</div>

<!-- SALDO BANNER -->
<?php
$tot_s=0; $tot_d=0; $pag_s=0; $pag_d=0;
foreach ($tours as $t){ $t['tipo_moneda']==='Soles'?$tot_s+=$t['precio']:$tot_d+=$t['precio']; }
foreach ($pagos as $p){ if($p['tipo']!=='reembolso'){ $p['moneda']==='Soles'?$pag_s+=$p['monto']:$pag_d+=$p['monto']; } }
$saldo_s=max(0,$tot_s-$pag_s); $saldo_d=max(0,$tot_d-$pag_d);
?>
<?php if ($saldo_s > 0 || $saldo_d > 0): ?>
<div class="saldo-banner saldo-pend">
    <i class="fas fa-exclamation-triangle"></i>
    <span>Saldo pendiente:</span>
    <?php if($saldo_s>0): ?><strong>S/ <?= number_format($saldo_s,2) ?></strong><?php endif; ?>
    <?php if($saldo_d>0): ?><strong>$ <?= number_format($saldo_d,2) ?></strong><?php endif; ?>
    <button type="button" class="btn-completar" id="btnCompletarSaldo">
        <i class="fas fa-plus-circle"></i> Registrar pago de saldo
    </button>
</div>
<?php else: ?>
<div class="saldo-banner saldo-ok"><i class="fas fa-check-circle"></i> Operación completamente pagada</div>
<?php endif; ?>

<?php if (!empty($error_msg)): ?>
<div class="kb-alert error"><i class="fas fa-exclamation-circle"></i><div><?= htmlspecialchars($error_msg) ?></div></div>
<?php endif; ?>

<form method="POST" id="formOp" novalidate>
<input type="hidden" name="id_operacion" value="<?= $id_operacion ?>">