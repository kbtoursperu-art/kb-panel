<?php
// ✅ Archivo: pages/contabilidad/index.php
include '../../conexion.php';
include './../sidebar.php';

// Asegurar charset correcto
if (function_exists('mysqli_set_charset')) {
    mysqli_set_charset($conexion, 'utf8mb4');
}
?>

<div class="content p-4">
    <div class="container-fluid">
        <h2 class="mb-4">📊 Área - Contabilidad (Resumen completo)</h2>

        <?php
        // 🟢 Consulta principal
        $query = "
            SELECT 
                o.id_operaciones,
                d.id_cliente,
                CONCAT(d.nombre, ' ', d.apellido) AS cliente,
                d.nro_pasaporte,
                o.nombre_servicio,
                d.tipo_cliente,
                c.id_contabilidad,
                c.metodo_pago,
                c.tipo_moneda,
                c.comision,
                o.servicio_adicional,
                c.precio_servicio,
                IFNULL(am_sum.total_adicionales, 0) AS precio_articulo_almacen,
                c.pagado_a_cuenta,
                c.saldo_pendiente,
                c.fecha_pago_saldo,
                c.Nro_Comprobante_adicional,
                c.estado,
                c.modalidad_recibo AS tipo_comprobante_pago,
                c.nro_boleta_cuenta,
                c.nro_boleta_total,
                c.detraccion,
                c.NotaCredito,
                o.observaciones
            FROM Operaciones o
            INNER JOIN Datos_clientes d ON o.id_cliente = d.id_cliente
            LEFT JOIN Contabilidad c ON o.id_operaciones = c.id_operaciones
            LEFT JOIN (
                SELECT ap.id_servicio AS id_servicio, SUM(am.monto) AS total_adicionales
                FROM almacen_pasajeros ap
                LEFT JOIN almacen_movimientos am ON am.id_stock = ap.id_stock
                GROUP BY ap.id_servicio
            ) am_sum ON am_sum.id_servicio = o.id_operaciones
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

            echo "<div class='alert alert-info mb-3'>📌 Operaciones sin contabilidad: <strong>{$sin_conta}</strong></div>";
        }
        ?>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tablaContabilidad" class="table table-striped table-bordered nowrap">
                        <thead class="table-dark text-center">
                            <tr>
                                <th class="d-none">ID Oper</th>
                                <th>ID Contabilidad</th>
                                <th>Cliente</th>
                                <th>Pasaporte</th>
                                <th>Servicio</th>
                                <th>tipo cliente</th>
                                <th>Método Pago</th>
                                <th>Moneda</th>
                                <th>Comisión</th>
                                <th>Servicio Adicional</th>
                                <th>Precio Servicio</th>
                                <th>Adicional Almacén</th>
                                <th>Pagado a Cuenta</th>
                                <th>Saldo Pendiente</th>
                                <th>Fecha Pago Saldo</th>
                                <th>N° Comprobante Adic.</th>
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
                                $id_oper = $row['id_operaciones'] ?? '';

                                // Formateo seguro
                                $cliente = htmlspecialchars($row['cliente'] ?? '-');
                                $nro_pasaporte = htmlspecialchars($row['nro_pasaporte'] ?? '-');
                                $servicio = htmlspecialchars($row['nombre_servicio'] ?? '-');
                                $tipo_cliente = htmlspecialchars($row['tipo_cliente'] ?? '-');
                                $metodo_pago = htmlspecialchars($row['metodo_pago'] ?? '-');
                                $tipo_moneda = htmlspecialchars($row['tipo_moneda'] ?? '-');
                                $comision = number_format(floatval($row['comision'] ?? 0), 2);
                                $serv_adicional = htmlspecialchars($row['servicio_adicional'] ?? '-');
                                $precio_servicio = number_format(floatval($row['precio_servicio'] ?? 0), 2);
                                $precio_art_alm = number_format(floatval($row['precio_articulo_almacen'] ?? 0), 2);
                                $pagado = number_format(floatval($row['pagado_a_cuenta'] ?? 0), 2);
                                $saldo = number_format(floatval($row['saldo_pendiente'] ?? 0), 2);
                                $fecha_pago_saldo = !empty($row['fecha_pago_saldo']) ? htmlspecialchars($row['fecha_pago_saldo']) : '-';
                                $nro_comp_adic = htmlspecialchars($row['Nro_Comprobante_adicional'] ?? '-');
                                $estado = $row['estado'] ?? null;
                                $tipo_comp = htmlspecialchars($row['tipo_comprobante_pago'] ?? '-');
                                $nro_boleta_cuenta = htmlspecialchars($row['nro_boleta_cuenta'] ?? '-');
                                $nro_boleta_total = htmlspecialchars($row['nro_boleta_total'] ?? '-');
                                $detraccion = number_format(floatval($row['detraccion'] ?? 0), 2);
                                $nota_credito 
                                = (!empty($row['NotaCredito']) && ($row['NotaCredito'] == 1 || $row['NotaCredito'] === '1')) ? 'Sí' : 'No';
                                $observaciones = htmlspecialchars($row['observaciones'] ?? '-');


                                
                                echo "<tr>
                                    <td class='d-none'>" . htmlspecialchars($id_oper) . "</td>
                                    <td>" . ($id_conta ?: '—') . "</td>
                                    <td>{$cliente}</td>
                                    <td>{$nro_pasaporte}</td>
                                    <td>{$servicio}</td>
                                    <td>{$tipo_cliente}</td>
                                    <td>{$metodo_pago}</td>
                                    <td>{$tipo_moneda}</td>
                                    <td class='text-end'>{$comision}</td>
                                    <td>{$serv_adicional}</td>
                                    <td class='text-end'>{$precio_servicio}</td>
                                    <td class='text-end'>{$precio_art_alm}</td>
                                    <td class='text-end'>{$pagado}</td>
                                    <td class='text-end'>{$saldo}</td>
                                    <td>{$fecha_pago_saldo}</td>
                                    <td>{$nro_comp_adic}</td>
                                    <td class='text-center'>";

                                // 🟡 Badges por estado
                                switch ($estado) {
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
                                        echo htmlspecialchars($estado ?? '-');
                                }

                                echo "</td>
                                    <td>{$tipo_comp}</td>
                                    <td>{$nro_boleta_cuenta}</td>
                                    <td>{$nro_boleta_total}</td>
                                    <td class='text-end'>{$detraccion}</td>
                                    <td>{$nota_credito}</td>
                                    <td>{$observaciones}</td>
                                    <td class='text-center'>";

                                // 🟢 Botones de acción
                                if (!empty($id_conta) && $id_conta !== '—' && $id_conta !== '-' && $id_conta !== '0') {
                                    $id_conta_esc = urlencode($id_conta);
                                    echo "
                                        <a href='ver.php?id={$id_conta_esc}' class='btn btn-info btn-sm mb-1'>👁 Ver</a>
                                        <a href='editar.php?id={$id_conta_esc}' class='btn btn-warning btn-sm mb-1'>✏️ Editar</a>
                                        <a href='eliminar.php?id={$id_conta_esc}' class='btn btn-danger btn-sm' onclick='return confirm(\"¿Eliminar este registro contable?\")'>🗑 Eliminar</a>
                                    ";
                                } else {
                                    // 🔗 Enlace corregido ➕ Registrar
                                    $id_oper_esc = urlencode($id_oper);
                                    echo "<a href='../Area_Operaciones/ope-KB/agregar.php?id_operaciones={$id_oper_esc}' class='btn btn-success btn-sm'>➕ Registrar</a>";
                                }

                                echo "</td></tr>";
                            }
                        } else {
                            echo "<tr><td colspan='23' class='text-center text-muted'>No hay registros disponibles</td></tr>";
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../../footer.php'; ?>
<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css">

<script>
$(document).ready(function() {
    $('#tablaContabilidad').DataTable({
        language: { url: "//cdn.datatables.net/plug-ins/1.13.5/i18n/es-ES.json" },
        pageLength: 10,
        order: [[0, 'desc']],
        columnDefs: [
            { targets: 0, visible: false, searchable: false }
        ]
    });
});
</script>

