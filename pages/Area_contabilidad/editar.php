<?php
include '../../conexion.php';
ob_start();   // <-- ACTIVA EL OUTPUT BUFFERING
include '../sidebar.php';

// ======================================================
// 1. SI SE ENVÍA EL FORMULARIO → HACER UPDATE DIRECTO
// ======================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id = intval($_POST['id_contabilidad']);

    $Nro_Comprobante_adicional = $_POST['Nro_Comprobante_adicional'];
    $estado = $_POST['estado'];
    $modalidad_recibo = $_POST['modalidad_recibo'];
    $nro_boleta_cuenta = $_POST['nro_boleta_cuenta'];
    $nro_boleta_total = $_POST['nro_boleta_total'];
    $detraccion = $_POST['detraccion'];
    $NotaCredito = $_POST['NotaCredito'];
    $observaciones = $_POST['observaciones'];

    $sql_update = "
        UPDATE Contabilidad 
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

    // También actualizamos observaciones en Operaciones
    $sql_obs = "
        UPDATE Operaciones 
        SET observaciones = '$observaciones'
        WHERE id_operaciones = (SELECT id_operaciones FROM Contabilidad WHERE id_contabilidad = $id)
    ";

    mysqli_query($conexion, $sql_obs);

    // Redirige sin recargar formulario
    header("Location: index.php?update=1");
    exit;
}


// ======================================================
// 2. MOSTRAR DATOS (MÉTODO GET)
// ======================================================
if (!isset($_GET['id'])) {
    die("❌ Error: Falta el ID de contabilidad.");
}

$id = intval($_GET['id']);

$sql = "

SELECT 

c.id_contabilidad,
c.id_operaciones,

c.metodo_pago,
c.tipo_moneda,
c.comision,
c.precio_servicio,
c.pagado_a_cuenta,
c.saldo_pendiente,
c.fecha_pago_saldo,

c.Nro_Comprobante_adicional,
c.estado,
c.modalidad_recibo,
c.nro_boleta_cuenta,
c.nro_boleta_total,
c.detraccion,
c.NotaCredito,

o.observaciones,

d.nombre,
d.apellido,
g.nombre_grupo

FROM Contabilidad c

INNER JOIN Operaciones o 
    ON o.id_operaciones = c.id_operaciones

LEFT JOIN datos_clientes d 
    ON d.id_cliente = o.id_cliente

LEFT JOIN clientes_kb kb 
    ON kb.id_cliente = d.id_cliente

LEFT JOIN grupos g 
    ON g.id_grupo = kb.id_grupo

WHERE c.id_contabilidad = $id

";

$res = mysqli_query($conexion, $sql);

if (!$res) {
    die("❌ Error SQL: " . mysqli_error($conexion));
}

$row = mysqli_fetch_assoc($res);

if (!$row) {
    die("❌ Error: No existe este registro.");
}
?>

<div class="content p-4">
    <div class="container">
        <h3 class="mb-4">✏️ Editar Registro Contable</h3>

        <!-- IMPORTANTE: action vacío = guardar en este mismo archivo -->
        <form method="POST">

            <input type="hidden" name="id_contabilidad" value="<?= $row['id_contabilidad'] ?>">

            <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Cliente</label>
                        <input type="text" class="form-control"
                        value="<?= $row['nombre'].' '.$row['apellido'] ?>"
                        disabled>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Grupo</label>
                        <input type="text" class="form-control"
                        value="<?= $row['nombre_grupo'] ?>"
                        disabled>
                    </div>
                <!-- MÉTODO PAGO -->
                <div class="col-md-4 mb-3">
                    <label class="form-label">Método de Pago</label>
                    <select class="form-control" name="metodo_pago" disabled>

                        <option <?= ($row['metodo_pago'] == 'Efectivo') ? 'selected' : '' ?>>Efectivo</option>

                        <option <?= ($row['metodo_pago'] == 'We travel') ? 'selected' : '' ?>>We travel</option>

                        <option <?= ($row['metodo_pago'] == 'Izipay') ? 'selected' : '' ?>>
                        Izipay
                        </option>

1                        <option <?= ($row['metodo_pago'] == 'PAYPAL') ? 'selected' : '' ?>>
                        PAYPAL
                        </option>

                        <option <?= ($row['metodo_pago'] == 'Bcp') ? 'selected' : '' ?>>
                        Bcp
                        </option>

                        <option <?= ($row['metodo_pago'] == 'CULQI') ? 'selected' : '' ?>>
                        CULQI
                        </option>

                        <option <?= ($row['metodo_pago'] == 'YAPE') ? 'selected' : '' ?>>
                        YAPE
                        </option>

</select>
                    </select>
                </div>

                <!-- MONEDA -->
                <div class="col-md-4 mb-3">
                    <label class="form-label">Moneda</label>
                    <select class="form-control" name="tipo_moneda" disabled>

                        <option <?= ($row['tipo_moneda'] == 'Soles') ? 'selected' : '' ?>>
                            Soles (PEN)
                        </option>

                        <option <?= ($row['tipo_moneda'] == 'Dólares') ? 'selected' : '' ?>>
                            Dólares (USD)
                        </option>

                    </select>
                </div>

                <!-- COMISIÓN -->
                <div class="col-md-4 mb-3">
                    <label class="form-label">Comisión</label>
                    <input type="number" class="form-control" step="0.01" value="<?= $row['comision'] ?>" disabled>
                </div>

                <!-- PRECIO SERVICIO -->
                <div class="col-md-4 mb-3">
                    <label class="form-label">Precio del Servicio</label>
                    <input type="number" class="form-control" step="0.01" value="<?= $row['precio_servicio'] ?>" disabled>
                </div>

                <!-- PAGADO A CUENTA -->
                <div class="col-md-4 mb-3">
                    <label class="form-label">Pagado a Cuenta</label>
                    <input type="number" class="form-control" step="0.01" value="<?= $row['pagado_a_cuenta'] ?>" disabled>
                </div>

                <!-- SALDO PENDIENTE -->
                <div class="col-md-4 mb-3">
                    <label class="form-label">Saldo Pendiente</label>
                    <input type="number" class="form-control" step="0.01" value="<?= $row['saldo_pendiente'] ?>" disabled>
                </div>

                <!-- FECHA SALDO -->
                <div class="col-md-4 mb-3">
                    <label class="form-label">Fecha Pago Saldo</label>
                    <input type="date" class="form-control" value="<?= $row['fecha_pago_saldo'] ?>" disabled>
                </div>

                <!-- N° COMPROBANTE ADICIONAL -->
                <div class="col-md-4 mb-3">
                    <label class="form-label">N° Comprobante Adicional</label>
                    <input type="text" class="form-control" name="Nro_Comprobante_adicional" value="<?= $row['Nro_Comprobante_adicional'] ?>">
                </div>

                <!-- ESTADO -->
                <div class="col-md-4 mb-3">
                    <label class="form-label">Estado</label>
                    <select class="form-control" name="estado">
                        <option value="pagado" <?= ($row['estado'] == 'pagado') ? 'selected' : '' ?>>Pagado</option>
                        <option value="pendiente" <?= ($row['estado'] == 'pendiente') ? 'selected' : '' ?>>Pendiente</option>
                        <option value="reembolsado" <?= ($row['estado'] == 'reembolsado') ? 'selected' : '' ?>>Reembolsado</option>
                    </select>
                </div>

                <!-- TIPO COMPROBANTE -->
              <div class="col-md-4 mb-3">
    <label class="form-label">Tipo de Comprobante</label>
    <select class="form-control" name="modalidad_recibo" required>
        <option value="FACTURA" <?= ($row['modalidad_recibo'] == 'FACTURA') ? 'selected' : '' ?>>FACTURA</option>
        <option value="FAC_EXPORTACION" <?= ($row['modalidad_recibo'] == 'FAC_EXPORTACION') ? 'selected' : '' ?>>FAC-EXPORTACIÓN</option>
        <option value="BV_INTANGIBLE" <?= ($row['modalidad_recibo'] == 'BV_INTANGIBLE') ? 'selected' : '' ?>>B/V INTANGIBLE</option>
        <option value="BV_IGV" <?= ($row['modalidad_recibo'] == 'BV_IGV') ? 'selected' : '' ?>>B/V IGV</option>
    </select>
</div>

                <!-- Nro boleta cuenta -->
                <div class="col-md-4 mb-3">
                    <label class="form-label">N° Comprobante Cuenta</label>
                    <input type="text" class="form-control" name="nro_boleta_cuenta" value="<?= $row['nro_boleta_cuenta'] ?>">
                </div>

                <!-- Nro boleta total -->
                <div class="col-md-4 mb-3">
                    <label class="form-label">N° Comprobante Total</label>
                    <input type="text" class="form-control" name="nro_boleta_total" value="<?= $row['nro_boleta_total'] ?>">
                </div>

                <!-- DETRACCION -->
                <div class="col-md-4 mb-3">
                    <label class="form-label">Detracción</label>
                    <input type="number" class="form-control" step="0.01" name="detraccion" value="<?= $row['detraccion'] ?>">
                </div>

                <!-- NOTA CREDITO -->
                <div class="col-md-4 mb-3">
                    <label class="form-label">Nota Crédito</label>
                    <select class="form-control" name="NotaCredito">
                        <option value="0" <?= ($row['NotaCredito'] == 0) ? 'selected' : '' ?>>No</option>
                        <option value="1" <?= ($row['NotaCredito'] == 1) ? 'selected' : '' ?>>Aplicar NC</option>
                    </select>
                </div>

                <!-- OBSERVACIONES -->
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
