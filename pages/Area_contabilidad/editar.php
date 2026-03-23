<?php
include '../../conexion.php';
ob_start();   // Activa output buffering
include '../sidebar.php';

// Mostrar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ======================================================
// 1. GUARDAR CAMBIOS (POST)
// ======================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = intval($_POST['id_contabilidad']);

    $Nro_Comprobante_adicional = $_POST['Nro_Comprobante_adicional'] ?? '';
    $estado = $_POST['estado'] ?? '';
    $modalidad_recibo = $_POST['modalidad_recibo'] ?? '';
    $nro_boleta_cuenta = $_POST['nro_boleta_cuenta'] ?? '';
    $nro_boleta_total = $_POST['nro_boleta_total'] ?? '';
    $detraccion = $_POST['detraccion'] ?? 0;
    $NotaCredito = $_POST['NotaCredito'] ?? 0;
    $observaciones = $_POST['observaciones'] ?? '';

    // Actualizamos contabilidad
    $sql_update = "
        UPDATE contabilidad 
        SET 
            Nro_Comprobante_adicional = '$Nro_Comprobante_adicional',
            estado = '$estado',
            modalidad_recibo = '$modalidad_recibo',
            nro_boleta_cuenta = '$nro_boleta_cuenta',
            nro_boleta_total = '$nro_boleta_total',
            detraccion = '$detraccion',
            NotaCredito = '$NotaCredito'
        WHERE id_contabilidad = $id
    ";
    mysqli_query($conexion, $sql_update);

    // Actualizamos observaciones en operaciones
    $sql_obs = "
        UPDATE operaciones 
        SET observaciones = '$observaciones'
        WHERE id_operaciones = (SELECT id_operaciones FROM contabilidad WHERE id_contabilidad = $id)
    ";
    mysqli_query($conexion, $sql_obs);

    header("Location: index.php?update=1");
    exit;
}

// ======================================================
// 2. MOSTRAR DATOS (GET)
// ======================================================
if (!isset($_GET['id'])) {
    die("❌ Error: Falta el ID de contabilidad.");
}
$id = intval($_GET['id']);

// Traemos datos de contabilidad + operaciones
$sql = "
SELECT 
    c.*,
    o.observaciones,
    o.id_grupo
FROM contabilidad c
LEFT JOIN operaciones o ON o.id_operaciones = c.id_operaciones
WHERE c.id_contabilidad = $id
LIMIT 1
";
$res = mysqli_query($conexion, $sql);
if (!$res) die("❌ Error SQL: " . mysqli_error($conexion));
$row = mysqli_fetch_assoc($res);
if (!$row) die("❌ Error: No existe este registro.");

// Traemos cliente y grupo
$grupo_id = $row['id_grupo'];
$cliente_sql = "
SELECT 
    d.nombre,
    d.apellido,
    g.nombre_grupo
FROM grupos g
LEFT JOIN clientes_kb kb ON kb.id_grupo = g.id_grupo
LEFT JOIN datos_clientes d ON d.id_cliente = kb.id_cliente
WHERE g.id_grupo = $grupo_id
LIMIT 1
";
$cliente_res = mysqli_query($conexion, $cliente_sql);
$cliente_row = mysqli_fetch_assoc($cliente_res);

$row['nombre'] = $cliente_row['nombre'] ?? '';
$row['apellido'] = $cliente_row['apellido'] ?? '';
$row['nombre_grupo'] = $cliente_row['nombre_grupo'] ?? '';
?>

<div class="content p-4">
    <div class="container">
        <h3 class="mb-4">✏️ Editar Registro Contable</h3>

        <form method="POST">
            <input type="hidden" name="id_contabilidad" value="<?= $row['id_contabilidad'] ?>">

            <div class="row">
                <!-- Cliente y Grupo -->
                <div class="col-md-4 mb-3">
                    <label class="form-label">Cliente</label>
                    <input type="text" class="form-control" value="<?= $row['nombre'].' '.$row['apellido'] ?>" disabled>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Grupo</label>
                    <input type="text" class="form-control" value="<?= $row['nombre_grupo'] ?>" disabled>
                </div>

                <!-- Método de Pago -->
                <div class="col-md-4 mb-3">
                    <label class="form-label">Método de Pago</label>
                    <input type="text" class="form-control" value="<?= $row['metodo_pago'] ?>" disabled>
                </div>

                <!-- Moneda -->
                <div class="col-md-4 mb-3">
                    <label class="form-label">Moneda</label>
                    <input type="text" class="form-control" value="<?= $row['tipo_moneda'] ?>" disabled>
                </div>

                <!-- Comisión -->
                <div class="col-md-4 mb-3">
                    <label class="form-label">Comisión</label>
                    <input type="number" class="form-control" step="0.01" value="<?= $row['comision'] ?>" disabled>
                </div>

                <!-- Precio del Servicio -->
                <div class="col-md-4 mb-3">
                    <label class="form-label">Precio del Servicio</label>
                    <input type="number" class="form-control" step="0.01" value="<?= $row['precio_servicio'] ?>" disabled>
                </div>

                <!-- Pagado a Cuenta -->
                <div class="col-md-4 mb-3">
                    <label class="form-label">Pagado a Cuenta</label>
                    <input type="number" class="form-control" step="0.01" value="<?= $row['pagado_a_cuenta'] ?>" disabled>
                </div>

                <!-- Saldo Pendiente -->
                <div class="col-md-4 mb-3">
                    <label class="form-label">Saldo Pendiente</label>
                    <input type="number" class="form-control" step="0.01" value="<?= $row['saldo_pendiente'] ?>" disabled>
                </div>

                <!-- Fecha Pago Saldo -->
                <div class="col-md-4 mb-3">
                    <label class="form-label">Fecha Pago Saldo</label>
                    <input type="date" class="form-control" value="<?= $row['fecha_pago_saldo'] ?>" disabled>
                </div>

                <!-- Nro Comprobante Adicional -->
                <div class="col-md-4 mb-3">
                    <label class="form-label">N° Comprobante Adicional</label>
                    <input type="text" class="form-control" name="Nro_Comprobante_adicional" value="<?= $row['Nro_Comprobante_adicional'] ?>">
                </div>

                <!-- Estado -->
                <div class="col-md-4 mb-3">
                    <label class="form-label">Estado</label>
                    <select class="form-control" name="estado">
                        <option value="pagado" <?= ($row['estado'] == 'pagado') ? 'selected' : '' ?>>Pagado</option>
                        <option value="pendiente" <?= ($row['estado'] == 'pendiente') ? 'selected' : '' ?>>Pendiente</option>
                        <option value="reembolsado" <?= ($row['estado'] == 'reembolsado') ? 'selected' : '' ?>>Reembolsado</option>
                    </select>
                </div>

                <!-- Modalidad Recibo -->
                <div class="col-md-4 mb-3">
                    <label class="form-label">Tipo de Comprobante</label>
                    <select class="form-control" name="modalidad_recibo" required>
                        <option value="FACTURA" <?= ($row['modalidad_recibo'] == 'FACTURA') ? 'selected' : '' ?>>FACTURA</option>
                        <option value="FAC_EXPORTACION" <?= ($row['modalidad_recibo'] == 'FAC_EXPORTACION') ? 'selected' : '' ?>>FAC-EXPORTACIÓN</option>
                        <option value="BV_INTANGIBLE" <?= ($row['modalidad_recibo'] == 'BV_INTANGIBLE') ? 'selected' : '' ?>>B/V INTANGIBLE</option>
                        <option value="BV_IGV" <?= ($row['modalidad_recibo'] == 'BV_IGV') ? 'selected' : '' ?>>B/V IGV</option>
                    </select>
                </div>

                <!-- Nro Boletas -->
                <div class="col-md-4 mb-3">
                    <label class="form-label">N° Comprobante Cuenta</label>
                    <input type="text" class="form-control" name="nro_boleta_cuenta" value="<?= $row['nro_boleta_cuenta'] ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">N° Comprobante Total</label>
                    <input type="text" class="form-control" name="nro_boleta_total" value="<?= $row['nro_boleta_total'] ?>">
                </div>

                <!-- Detracción -->
                <div class="col-md-4 mb-3">
                    <label class="form-label">Detracción</label>
                    <input type="number" class="form-control" step="0.01" name="detraccion" value="<?= $row['detraccion'] ?>">
                </div>

                <!-- Nota Crédito -->
                <div class="col-md-4 mb-3">
                    <label class="form-label">Nota Crédito</label>
                    <select class="form-control" name="NotaCredito">
                        <option value="0" <?= ($row['NotaCredito'] == 0) ? 'selected' : '' ?>>No</option>
                        <option value="1" <?= ($row['NotaCredito'] == 1) ? 'selected' : '' ?>>Aplicar NC</option>
                    </select>
                </div>

                <!-- Observaciones -->
                <div class="col-md-12 mb-3">
                    <label class="form-label">Observaciones</label>
                    <textarea class="form-control" name="observaciones" rows="3"><?= htmlspecialchars($row['observaciones'] ?? '') ?></textarea>
                </div>

            </div>

            <button type="submit" class="btn btn-primary px-4">💾 Guardar Cambios</button>
            <a href="index.php" class="btn btn-secondary">Cancelar</a>

        </form>
    </div>
</div>