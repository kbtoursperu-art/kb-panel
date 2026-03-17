<?php
include '../../conexion.php';

// Charset seguro
if (function_exists('mysqli_set_charset')) {
    mysqli_set_charset($conexion, 'utf8mb4');
}

// Contar clientes por grupo (desde operaciones)
$grupoTotales = [];

$resGrupos = mysqli_query($conexion, "
SELECT 
    g.id_grupo,
    g.nombre_grupo,
    (
        SELECT COUNT(*) 
        FROM clientes_kb k 
        WHERE k.id_grupo = g.id_grupo
    ) +
    (
        SELECT COUNT(*) 
        FROM clientes_endosadores e 
        WHERE e.id_grupo = g.id_grupo
    ) AS pasajeros
FROM grupos g
");

while ($g = mysqli_fetch_assoc($resGrupos)) {
    $grupoTotales[$g['id_grupo']] = [
        'nombre' => $g['nombre_grupo'],
        'cantidad' => $g['pasajeros']
    ];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contabilidad - KB Adventures</title>

<!-- Bootstrap -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">

<!-- DataTables -->
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">

</head>
<body>

<!-- SIDEBAR -->
<?php include './../sidebar.php'; ?>

<div class="content p-4">
    <div class="container-fluid">
        <h2 class="mb-4">📊 Área - Contabilidad (Resumen completo)</h2>

        <?php
        // Consulta principal
       $query = "
SELECT 
    g.id_grupo,
    g.nombre_grupo,

    MIN(o.id_operaciones) AS id_operaciones,
    MIN(d.id_cliente) AS id_cliente,
    MIN(CONCAT(d.nombre,' ',d.apellido)) AS cliente,
    MIN(d.nro_pasaporte) AS nro_pasaporte,

    MIN(o.nombre_servicio) AS nombre_servicio,
    MIN(d.tipo_cliente) AS tipo_cliente,

    MIN(c.id_contabilidad) AS id_contabilidad,
    MIN(c.metodo_pago) AS metodo_pago,
    MIN(c.tipo_moneda) AS tipo_moneda,

    MIN(IFNULL(c.precio_servicio,0)) AS precio_servicio,
    MIN(IFNULL(c.pagado_a_cuenta,0)) AS pagado_a_cuenta,
    MIN(IFNULL(c.saldo_pendiente,0)) AS saldo_pendiente,
    MIN(IFNULL(c.comision,0)) AS comision,

    MIN(o.servicio_adicional) AS servicio_adicional,
    MAX(o.fecha_reserva) AS fecha_reserva,
MAX(o.fecha_salida) AS fecha_salida,
MAX(o.fecha_retorno) AS fecha_retorno,
MAX(o.modalidad_retorno) AS modalidad_retorno,
MAX(o.incluye_ingreso) AS incluye_ingreso,
MAX(o.servicio_adicional) AS servicio_adicional,
MAX(o.Encargado) AS Encargado,

    MIN(c.metodo_pago_adicional) AS metodo_pago_adicional,
    MIN(c.tipo_moneda_adicional) AS tipo_moneda_adicional,

    MIN(IFNULL(c.precio_servicio_adicional,0)) AS precio_servicio_adicional,
    MIN(IFNULL(c.pagado_adicional,0)) AS pagado_adicional,
    MIN(IFNULL(c.saldo_adicional,0)) AS saldo_adicional,

    MIN(c.metodo_pago_saldo) AS metodo_pago_saldo,
    MIN(c.tipo_moneda_saldo) AS tipo_moneda_saldo,
    MIN(IFNULL(c.monto_pago_saldo,0)) AS monto_pago_saldo,

    MIN(c.fecha_pago_saldo) AS fecha_pago_saldo,
    MIN(c.Nro_Comprobante_adicional) AS Nro_Comprobante_adicional,
    MIN(c.estado) AS estado,

    MIN(IFNULL(c.modalidad_recibo,'—')) AS tipo_comprobante_pago,
    MIN(c.nro_boleta_cuenta) AS nro_boleta_cuenta,
    MIN(c.nro_boleta_total) AS nro_boleta_total,

    MIN(IFNULL(c.detraccion,0)) AS detraccion,
    MIN(c.NotaCredito) AS NotaCredito,

    MIN(o.observaciones) AS observaciones

FROM operaciones o
INNER JOIN datos_clientes d ON o.id_cliente = d.id_cliente
LEFT JOIN clientes_kb kb ON kb.id_cliente = o.id_cliente
LEFT JOIN clientes_endosadores e ON e.id_cliente = o.id_cliente
LEFT JOIN grupos g ON g.id_grupo = COALESCE(kb.id_grupo, e.id_grupo)
LEFT JOIN contabilidad c ON o.id_operaciones = c.id_operaciones

GROUP BY g.id_grupo
";

        $resultado = mysqli_query($conexion, $query);

        if (!$resultado) {
            echo "<div class='alert alert-danger'>Error en la consulta: " . htmlspecialchars(mysqli_error($conexion)) . "</div>";
            $datos = [];
        } else {
            $datos = mysqli_fetch_all($resultado, MYSQLI_ASSOC);
            $sin_conta = 0;
            foreach ($datos as $row) {
                if (empty($row['id_contabilidad'])) $sin_conta++;
            }
            echo "<div class='alert alert-primary mb-3'>
                📌 Operaciones sin contabilidad: <strong>{$sin_conta}</strong>
            </div>";
        }
        ?>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tablaContabilidad" class="table table-striped table-bordered nowrap" style="width:100%">
                        <thead>

<tr class="text-center">

<th rowspan="2" class="d-none">ID Oper</th>

<th colspan="5" style="background:#d9edf7">CLIENTE</th>

<th colspan="8" style="background:#dff0d8">OPERACIÓN</th>

<th colspan="6" style="background:#fcf8e3">SERVICIO PRINCIPAL</th>

<th colspan="6" style="background:#f5e79e">SERVICIO ADICIONAL</th>

<th colspan="4" style="background:#f2dede">SALDO PAGADO</th>

<th colspan="7" style="background:goldenrod">CONTABILIDAD</th>

<th colspan="1" style="background:#d0d0d0">OTROS</th>

<th rowspan="2">Acciones</th>

</tr>


<tr class="table-dark text-center">

<th>ID Conta</th>
<th>Cliente</th>
<th>Pasaporte</th>
<th>Grupo</th>
<th>Cant</th>

<th>Servicio</th>
<th>Reserva</th>
<th>Salida</th>
<th>Retorno</th>
<th>Modalidad</th>
<th>Ingreso</th>
<th>Encargado</th>
<th>Tipo cliente</th>

<th>Método</th>
<th>Moneda</th>
<th>Precio</th>
<th>Pagado</th>
<th>Saldo</th>
<th>Comisión</th>

<th>Serv.Adic</th>
<th>Mét.Adic</th>
<th>Mon.Adic</th>
<th>Precio</th>
<th>Pagado</th>
<th>Saldo</th>

<th>Mét.Saldo</th>
<th>Mon.Saldo</th>
<th>Monto</th>
<th>Fecha</th>

<th>Estado</th>
<th>Tipo Comp.</th>
<th>Comp Cuenta</th>
<th>Comp Total</th>
<th>Comp Adic</th>
<th>Detracción</th>

<th>Nota Crédito</th>
<th>Observaciones</th>

</tr>

</thead>

                        <tbody>
                        <?php
                        if (!empty($datos)) {
                            foreach ($datos as $row) {
                                $id_conta = $row['id_contabilidad'] ?? '';
                                $id_oper  = $row['id_operaciones'] ?? '';

                                echo "<tr>
                                <td class='d-none'>{$id_oper}</td>
                                <td>" . ($id_conta ?: '—') . "</td>
                                <td>" . htmlspecialchars($row['cliente']) . "</td>
                                <td>" . htmlspecialchars($row['nro_pasaporte']) . "</td>
                                <td>" . htmlspecialchars($grupoTotales[$row['id_grupo']]['nombre'] ?? '—') . "</td>
                                <td class='text-center'>" . ($grupoTotales[$row['id_grupo']]['cantidad'] ?? 0) . "</td>
                                <td>" . htmlspecialchars($row['nombre_servicio']) . "</td>
                                <td>" . $row['fecha_reserva'] . "</td>
                                <td>" . $row['fecha_salida'] . "</td>
                                <td>" . $row['fecha_retorno'] . "</td>
                                <td>" . $row['modalidad_retorno'] . "</td>
                                <td>" . $row['incluye_ingreso'] . "</td>

                                <td>" . htmlspecialchars($row['Encargado']) . "</td>
                                <td>" . htmlspecialchars($row['tipo_cliente']) . "</td>
                                <td>" . htmlspecialchars($row['metodo_pago']) . "</td>
                                <td>" . htmlspecialchars($row['tipo_moneda']) . "</td>
                                <td class='text-end'>" . number_format($row['precio_servicio'],2) . "</td>
                                <td class='text-end'>" . number_format($row['pagado_a_cuenta'],2) . "</td>
                                <td class='text-end'>" . number_format($row['saldo_pendiente'],2) . "</td>
                                <td class='text-end'>" . number_format($row['comision'],2) . "</td>
                                <td>" . htmlspecialchars($row['servicio_adicional']) . "</td>
                                <td>" . htmlspecialchars($row['metodo_pago_adicional']) . "</td>
                                <td>" . htmlspecialchars($row['tipo_moneda_adicional']) . "</td>
                                <td class='text-end'>" . number_format($row['precio_servicio_adicional'],2) . "</td>
                                <td class='text-end'>" . number_format($row['pagado_adicional'],2) . "</td>
                                <td class='text-end'>" . number_format($row['saldo_adicional'],2) . "</td>
                                <td>" . htmlspecialchars($row['metodo_pago_saldo']) . "</td>
                                <td>" . htmlspecialchars($row['tipo_moneda_saldo']) . "</td>
                                <td class='text-end'>" . number_format($row['monto_pago_saldo'],2) . "</td>
                                <td>" . ($row['fecha_pago_saldo'] ?: '-') . "</td>
                                <td class='text-center'>";

                                switch ($row['estado']) {
                                    case 'pagado': echo "<span class='badge bg-success'>Pagado</span>"; break;
                                    case 'pendiente': echo "<span class='badge bg-warning text-dark'>Pendiente</span>"; break;
                                    case 'reembolsado': echo "<span class='badge bg-secondary'>Reembolsado</span>"; break;
                                    default: echo "-";
                                }

                                echo "</td>
                                
                                <td>" . htmlspecialchars($row['tipo_comprobante_pago']) . "</td>
                                <td>" . htmlspecialchars($row['nro_boleta_cuenta']) . "</td>
                                <td>" . htmlspecialchars($row['nro_boleta_total']) . "</td>
                                <td>" . htmlspecialchars($row['Nro_Comprobante_adicional']) . "</td>
                                <td class='text-end'>" . number_format($row['detraccion'],2) . "</td>
                                <td class='text-center'>" . ($row['NotaCredito'] ? 'Sí' : 'No') . "</td>
                                <td>" . htmlspecialchars($row['observaciones']) . "</td>
                                <td class='text-center'>";

                                if ($id_conta) {
                                    echo "<a href='ver.php?id={$id_conta}' class='btn btn-info btn-sm mb-1'>👁</a>
                                          <a href='editar.php?id={$id_conta}' class='btn btn-warning btn-sm mb-1'>✏️</a>
                                          <a href='eliminar.php?id={$id_conta}' class='btn btn-danger btn-sm' onclick='return confirm(\"¿Eliminar este registro contable?\")'>🗑</a>";
                                } else {
                                    echo "<a href='../Area_Operaciones/ope-KB/agregar.php?id_operaciones={$id_oper}' class='btn btn-success btn-sm'>➕</a>";
                                }

                                echo "</td></tr>";
                            }
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    $('#tablaContabilidad').DataTable({
        language: { url: "//cdn.datatables.net/plug-ins/1.13.5/i18n/es-ES.json" },
        pageLength: 10,
        order: [[0, 'desc']],
        responsive: true,
        columnDefs: [
            { targets: 0, visible: false, searchable: false }
        ]
    });
});
</script>
</body>
</html>