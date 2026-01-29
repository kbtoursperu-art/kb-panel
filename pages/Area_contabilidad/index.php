<?php
include '../../conexion.php';

// Charset seguro
if (function_exists('mysqli_set_charset')) {
    mysqli_set_charset($conexion, 'utf8mb4');
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

    <style>
        body {
            background-color: #f1f3f5;
        }
        h2 {
            font-weight: 700;
            color: #2d3436;
        }
        .card {
            border-radius: 12px;
            border: none;
            box-shadow: 0px 2px 10px rgba(0,0,0,0.08);
        }
        table thead {
            font-size: 14px;
        }
        .btn-sm {
            border-radius: 6px;
        }
        .dataTables_filter input {
            border-radius: 6px;
            padding: 6px 10px;
        }
    </style>
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
    o.id_operaciones,
    d.id_cliente,
    CONCAT(d.nombre, ' ', d.apellido) AS cliente,
    d.nro_pasaporte,
    o.nombre_servicio,
    d.tipo_cliente,

    c.id_contabilidad,

    -- PRECIO GENERAL
    c.metodo_pago,
    c.tipo_moneda,
    IFNULL(c.precio_servicio,0)   AS precio_servicio,
    IFNULL(c.pagado_a_cuenta,0)   AS pagado_a_cuenta,
    IFNULL(c.saldo_pendiente,0)   AS saldo_pendiente,
    IFNULL(c.comision,0)          AS comision,

    -- SERVICIO ADICIONAL
    o.servicio_adicional,
    c.metodo_pago_adicional,
    c.tipo_moneda_adicional,
    IFNULL(c.precio_servicio_adicional,0) AS precio_servicio_adicional,
    IFNULL(c.pagado_adicional,0)           AS pagado_adicional,
    IFNULL(c.saldo_adicional,0)            AS saldo_adicional,

    -- SALDO FINAL
    c.metodo_pago_saldo,
    c.tipo_moneda_saldo,
    IFNULL(c.monto_pago_saldo,0) AS monto_pago_saldo,
    c.fecha_pago_saldo,

    -- OTROS
    c.Nro_Comprobante_adicional,
    c.estado,
    IFNULL(c.modalidad_recibo,'—') AS tipo_comprobante_pago,
    c.nro_boleta_cuenta,
    c.nro_boleta_total,
    IFNULL(c.detraccion,0) AS detraccion,
    c.NotaCredito,
    o.observaciones

FROM Operaciones o
INNER JOIN Datos_clientes d ON o.id_cliente = d.id_cliente
LEFT JOIN Contabilidad c ON o.id_operaciones = c.id_operaciones
ORDER BY o.id_operaciones DESC
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
                        <thead class="table-dark text-center">
                            <tr>
                                <th class="d-none">ID Oper</th>
                                <th>ID Contabilidad</th>
                                <th>Cliente</th>
                                <th>Pasaporte</th>
                                <th>Servicio</th>
                                <th>Tipo cliente</th>
                               <!-- PRECIO GENERAL -->
                                <th>Método Pago</th>
                                <th>Moneda</th>
                                <th>Precio</th>
                                <th>Pagado</th>
                                <th>Saldo</th>
                                <th>Comisión</th>     

                                <!-- SERVICIO ADICIONAL -->
                                <th>Servicio Adicional</th>
                                <th>Mét. Adic.</th>
                                <th>Mon. Adic.</th>
                                <th>Precio Adic.</th>
                                <th>Pagado Adic.</th>
                                <th>Saldo Adic.</th>

                                <!-- SALDO FINAL -->
                                <th>Mét. Saldo</th>
                                <th>Mon. Saldo</th>
                                <th>Monto Saldo</th>
                                <th>Fecha Saldo</th>
                                
                            
                                <th>N° Comp. Adic.</th>
                                <th>Estado</th>
                                <th>Tipo Comprobante</th>
                                <th>N° Comprobante Cuenta</th>
                                <th>N° Comprobante Total</th>
                                <th>Detracción</th>
                                <th>Nota Crédito</th>
                                <th>Observaciones</th>
                                <th>Acciones</th>
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
                                <td>" . htmlspecialchars($row['nombre_servicio']) . "</td>
                                <td>" . htmlspecialchars($row['tipo_cliente']) . "</td>

                                <!-- PRECIO GENERAL -->
                                <td>" . htmlspecialchars($row['metodo_pago']) . "</td>
                                <td>" . htmlspecialchars($row['tipo_moneda']) . "</td>
                                <td class='text-end'>" . number_format($row['precio_servicio'] ?? 0, 2) . "</td>
                                <td class='text-end'>" . number_format($row['pagado_a_cuenta'] ?? 0, 2) . "</td>
                                <td class='text-end'>" . number_format($row['saldo_pendiente'] ?? 0, 2) . "</td>
                                <td class='text-end'>" . number_format($row['comision'] ?? 0, 2) . "</td>

                                <!-- SERVICIO ADICIONAL -->
                                <td>" . htmlspecialchars($row['servicio_adicional']) . "</td>
                                <td>" . htmlspecialchars($row['metodo_pago_adicional']) . "</td>
                                <td>" . htmlspecialchars($row['tipo_moneda_adicional']) . "</td>
                                <td class='text-end'>" . number_format($row['precio_servicio_adicional'] ?? 0, 2) . "</td>
                                <td class='text-end'>" . number_format($row['pagado_adicional'] ?? 0, 2) . "</td>
                                <td class='text-end'>" . number_format($row['saldo_adicional'] ?? 0, 2) . "</td>

                                <!-- SALDO FINAL -->
                                <td>" . htmlspecialchars($row['metodo_pago_saldo']) . "</td>
                                <td>" . htmlspecialchars($row['tipo_moneda_saldo']) . "</td>
                                <td class='text-end'>" . number_format($row['monto_pago_saldo'] ?? 0, 2) . "</td>
                                <td>" . ($row['fecha_pago_saldo'] ?: '-') . "</td>

                                <td>" . htmlspecialchars($row['Nro_Comprobante_adicional']) . "</td>

                                <td class='text-center'>";

                                // Badge estado
                                switch ($row['estado']) {
                                        case 'pagado':
                                            echo "<span class='badge bg-success'>Pagado</span>";
                                            break;
                                        case 'pendiente':
                                            echo "<span class='badge bg-warning text-dark'>Pendiente</span>";
                                            break;
                                        case 'reembolsado':
                                            echo "<span class='badge bg-secondary'>Reembolsado</span>";
                                            break;
                                        default:
                                            echo "-";
                                    }


                                echo "</td>
                                        <td>" . htmlspecialchars($row['tipo_comprobante_pago']) . "</td>
                                        <td>" . htmlspecialchars($row['nro_boleta_cuenta']) . "</td>
                                        <td>" . htmlspecialchars($row['nro_boleta_total']) . "</td>
                                        <td class='text-end'>" . number_format($row['detraccion'] ?? 0, 2) . "</td>
                                        <td class='text-center'>" . ($row['NotaCredito'] ? 'Sí' : 'No') . "</td>
                                        <td>" . htmlspecialchars($row['observaciones']) . "</td>

                                        <td class='text-center'>";


                                // Acciones
                               if ($id_conta) {
                                echo "
                                    <a href='ver.php?id={$id_conta}' class='btn btn-info btn-sm mb-1'>👁</a>
                                    <a href='editar.php?id={$id_conta}' class='btn btn-warning btn-sm mb-1'>✏️</a>
                                    <a href='eliminar.php?id={$id_conta}' class='btn btn-danger btn-sm'
                                    onclick='return confirm(\"¿Eliminar este registro contable?\")'>🗑</a>
                                ";
                            } else {
                                echo "
                                    <a href='../Area_Operaciones/ope-KB/agregar.php?id_operaciones={$id_oper}'
                                    class='btn btn-success btn-sm'>➕</a>
                                ";
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
