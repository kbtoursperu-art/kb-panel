<div class="kb-card">

    <div class="kb-card-header d-flex justify-content-between align-items-center">
                                                                                   
        <div>
            <h5 class="mb-0 fw-semibold">
                📋 Operaciones Registradas
            </h5>

            <small class="text-muted">
                                                                                                                    
                Lista general de operaciones Endosadores 

            </small>            
        </div>
    </div>
    <div class="kb-card-body">
        <div class="table-responsive">
            <table
                id="tablaOperaciones"
                class="table kb-table align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Grupo</th>
                        <th>Pax</th>
                        <th>Cliente</th>
                        <th>Tours Programados</th>
                        <th>Observaciones</th>
                        <th>Total</th>
                        <th>Pagado</th>
                        <th>Saldo</th>
                        <th>Estado</th>
                        <th>Financiero</th>
                        <th>Acciones</th>

                    </tr>
                </thead>
                <tbody>
                <?php
                $i = 1;

                $coloresTours = [
                    'tour-chip-blue',
                    'tour-chip-green',
                    'tour-chip-red',
                    'tour-chip-yellow',
                    'tour-chip-purple',
                    'tour-chip-cyan'
                ];
                while ($row = mysqli_fetch_assoc($resultado)):

                    $total_soles   = floatval($row['total_soles'] ?? 0);
                    $total_dolares = floatval($row['total_dolares'] ?? 0);

                    $pagado_soles   = floatval($row['pagado_soles'] ?? 0);
                    $pagado_dolares = floatval($row['pagado_dolares'] ?? 0);

                    $saldo_soles   = $total_soles - $pagado_soles;
                    $saldo_dolares = $total_dolares - $pagado_dolares;

                    $servicios = explode("<br>", $row['nombre_servicio'] ?? '');
                    $fechas    = explode("<br>", $row['fecha_salida'] ?? '');

                ?>

                <tr>

                    <td><?= $i++ ?></td>

                    <td>

                        <span class="grupo-badge">
                            <?= htmlspecialchars($row['nombre_grupo']) ?>
                        </span>

                    </td>

                    <td class="text-center">

                        <span class="pax-badge">
                            <?= $row['pasajeros'] ?>
                        </span>

                    </td>

                    <td>
                        <strong>
                            <?= htmlspecialchars($row['primer_cliente'] ?? '-') ?>
                        </strong>
                    </td>
                    <!-- TOURS -->

                    <td>
                        <?php foreach ($servicios as $k => $serv): ?>

                            <?php if (empty(trim($serv))) continue; ?>

                            <?php
                            $colorClass = $coloresTours[$k % count($coloresTours)];
                            ?>

                            <div class="tour-chip <?= $colorClass ?>">

                                <div class="tour-name">
                                    <?= htmlspecialchars($serv) ?>
                                </div>

                                <div class="tour-date">

                                    📅
                                    <?= !empty($fechas[$k])
                                        ? date('d/m/Y', strtotime($fechas[$k]))
                                        : '-' ?>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    </td>

                    <td class="text-muted">

                        <?= !empty($row['observaciones'])
                            ? htmlspecialchars($row['observaciones'])
                            : '-' ?>

                    </td>

                    <!-- TOTAL -->

                    <td>

                        <div class="money-soles">
                            S/ <?= number_format($total_soles,2) ?>
                        </div>

                        <div class="money-usd">
                            $ <?= number_format($total_dolares,2) ?>
                        </div>

                    </td>

                    <!-- PAGADO -->

                    <td>

                        <div class="money-soles">
                            S/ <?= number_format($pagado_soles,2) ?>
                        </div>

                        <div class="money-usd">
                            $ <?= number_format($pagado_dolares,2) ?>
                        </div>

                    </td>

                    <!-- SALDO -->

                    <td>

                        <div class="<?= $saldo_soles <= 0 ? 'saldo-ok' : 'saldo-pendiente' ?>">

                            S/ <?= number_format($saldo_soles,2) ?>

                        </div>

                        <div class="<?= $saldo_dolares <= 0 ? 'saldo-ok' : 'saldo-pendiente' ?>">

                            $ <?= number_format($saldo_dolares,2) ?>

                        </div>

                    </td>

                    <!-- ESTADO -->

                    <td>

                        <?php

                        switch($row['estado'] ?? 'pendiente') {

                            case 'confirmado':
                                echo '<span class="estado-confirmado">Confirmado</span>';
                                break;

                            case 'cancelado':
                                echo '<span class="estado-cancelado">Cancelado</span>';
                                break;

                            default:
                                echo '<span class="estado-pendiente">Pendiente</span>';

                        }

                        ?>
                                 
                    </td>

                    <!-- FINANCIERO -->

                    <td>

                        <?php

                        if ($saldo_soles <= 0 && $saldo_dolares <= 0) {

                            echo '<span class="estado-confirmado">Pagado</span>';

                        } elseif ($pagado_soles > 0 || $pagado_dolares > 0) {

                            echo '<span class="estado-parcial">Parcial</span>';

                        } else {

                            echo '<span class="estado-pendiente">Pendiente</span>';

                        }

                        ?>

                    </td>

                    <!-- ACCIONES -->

                    <td>

                        <div class="acciones">

                            <?php if (!empty($row['id_operaciones'])): ?>

                                <a href="ver/index.php?id_grupo=<?= $row['id_grupo'] ?>"
                                   class="btn-action btn-view">👁</a>

                                <a href="editar/index.php?id=<?= $row['id_operaciones'] ?>"
                                   class="btn-action btn-edit">
                                    ✏
                                </a>

                                <a href="eliminar.php?id=<?= $row['id_operaciones'] ?>"
                                   class="btn-action btn-delete">
                                    🗑
                                      </a>

                            <?php else: ?>

                                <a href="agregar/index.php?id_cliente=<?= $row['primer_cliente_id'] ?>"
                                   class="btn-action btn-add">
                                    ➕
                                </a>

                            <?php endif; ?>

                        </div>

                    </td>

                </tr>

                <?php endwhile; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>