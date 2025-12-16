<?php
include '../../conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener los valores del formulario y sanitizarlos
    $nombre_servicio = mysqli_real_escape_string($conexion, $_POST['nombre_servicio']);
    $fecha_reserva = mysqli_real_escape_string($conexion, $_POST['fecha_reserva']);
    $fecha_salida = mysqli_real_escape_string($conexion, $_POST['fecha_salida']);
    $fecha_retorno = mysqli_real_escape_string($conexion, $_POST['fecha_retorno']);
    $grupo = mysqli_real_escape_string($conexion, $_POST['grupo']);
    $metodo_pago = mysqli_real_escape_string($conexion, $_POST['metodo_pago']);
    $precio_servicio = mysqli_real_escape_string($conexion, $_POST['precio_servicio']);
    $pagado_a_cuenta = mysqli_real_escape_string($conexion, $_POST['pagado_a_cuenta']);
    $saldo_pendiente = mysqli_real_escape_string($conexion, $_POST['saldo_pendiente']);
    $fecha_pago_saldo = mysqli_real_escape_string($conexion, $_POST['fecha_pago_saldo']);
    $nro_voucher = mysqli_real_escape_string($conexion, $_POST['nro_voucher']);
    $modalidad_pago = mysqli_real_escape_string($conexion, $_POST['modalidad_pago']);

    // Insertar datos en la base de datos
    $query = "INSERT INTO Venta (nombre_servicio, fecha_reserva, fecha_salida, fecha_retorno, grupo, 
                                metodo_pago, precio_servicio, pagado_a_cuenta, saldo_pendiente, 
                                fecha_pago_saldo, nro_voucher, modalidad_pago) 
              VALUES ('$nombre_servicio', '$fecha_reserva', '$fecha_salida', '$fecha_retorno', '$grupo', 
                      '$metodo_pago', '$precio_servicio', '$pagado_a_cuenta', '$saldo_pendiente', 
                      '$fecha_pago_saldo', '$nro_voucher', '$modalidad_pago')";

    if (mysqli_query($conexion, $query)) {
        echo "<script>alert('Venta agregada exitosamente'); window.location.href='index.php';</script>";
        exit();
    } else {
        echo "<script>alert('Error al agregar la venta: ". mysqli_error($conexion) . "');</script>";
    }
}

// Cerrar conexión
mysqli_close($conexion);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Venta</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<script>
    function calcularSaldo() {
        let precio = parseFloat(document.getElementById("precio_servicio").value) || 0;
        let pagado = parseFloat(document.getElementById("pagado_a_cuenta").value) || 0;
        let saldo = Math.max(precio - pagado, 0); // Asegura que el saldo no sea negativo
        document.getElementById("saldo_pendiente").value = saldo.toFixed(2);
    }
</script>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">Agregar Nueva Venta</h2>
        <form action="agregar.php" method="POST">
            <div class="row">
                <!-- Primera columna -->
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Nombre del Servicio:</label>
                    <select name="nombre_servicio" class="form-select" required>
                        <option value="">-- Seleccione una opción --</option>
                        <option value="SALKANTAY A MACHU PICCHU 5 DÍAS (PRIVADO)">SALKANTAY A MACHU PICCHU 5 DÍAS (PRIVADO)</option>
                        <option value="SALKANTAY A MACHU PICCHU 5 DÍAS">SALKANTAY A MACHU PICCHU 5 DÍAS</option>
                        <option value="SALKANTAY A MACHU PICCHU 4 DÍAS">SALKANTAY A MACHU PICCHU 4 DÍAS</option>
                        <option value="SALKANTAY A MACHU PICCHU 3 DÍAS">SALKANTAY A MACHU PICCHU 3 DÍAS</option>
                        <option value="SALKANTAY Y LAGUNA HUMANTAY 2 DÍAS">SALKANTAY Y LAGUNA HUMANTAY 2 DÍAS</option>
                        <option value="SALKANTAY Y CAMINO INCA 7 DÍAS (PRIVADO)">SALKANTAY Y CAMINO INCA 7 DÍAS (PRIVADO)</option>
                        <option value="CAMINO INCA 4 DÍAS">CAMINO INCA 4 DÍAS</option>
                        <option value="CAMINO INCA 4 DÍAS (PRIVADO)">CAMINO INCA 4 DÍAS (PRIVADO)</option>
                        <option value="CAMINO INCA 2 DÍAS">CAMINO INCA 2 DÍAS</option>
                        <option value="MACHU PICCHU DE UN DÍA">MACHU PICCHU DE UN DÍA</option>
                        <option value="MACHU PICCHU EN TREN 2 DÍAS">MACHU PICCHU EN TREN 2 DÍAS</option>
                        <option value="VALLE SAGRADO A MACHU PICCHU 2 DÍAS">VALLE SAGRADO A MACHU PICCHU 2 DÍAS</option>
                        <option value="CHOQUEQUIRAO 5 DÍAS (PRIVADO)">CHOQUEQUIRAO 5 DÍAS (PRIVADO)</option>
                        <option value="CHOQUEQUIRAO 4 DÍAS">CHOQUEQUIRAO 4 DÍAS</option>
                        <option value="CHOQUEQUIRAO 4 DÍAS (PRIVADO)">CHOQUEQUIRAO 4 DÍAS (PRIVADO)</option>
                        <option value="LARES A MACHU PICCHU 4 DÍAS (PRIVADO)">LARES A MACHU PICCHU 4 DÍAS (PRIVADO)</option>
                        <option value="AUSANGATE Y MONTAÑA DE COLORES 4 DÍAS">AUSANGATE Y MONTAÑA DE COLORES 4 DÍAS</option>
                        <option value="HUCHUY QOSQO 3 DÍAS (PRIVADO)">HUCHUY QOSQO 3 DÍAS (PRIVADO)</option>
                        <option value="INCA JUNGLE TRAIL 4 DAYS">INCA JUNGLE TRAIL 4 DAYS</option>
                        <option value="LAGUNA HUMANTAY DE UN DIA">LAGUNA HUMANTAY DE UN DÍA</option>
                        <option value="MONTAÑA DE COLORES DE UN DIA">MONTAÑA DE COLORES DE UN DÍA</option>
                        <option value="PALCOYO DE UN DIA">PALCOYO DE UN DÍA</option>
                        <option value="VALLE SAGRADO VIP DE UN DIA">VALLE SAGRADO VIP DE UN DÍA</option>
                        <option value="VALLE TRADICIONAL">VALLE TRADICIONAL</option>
                        <option value="7 LAGUNAS DE AUSANGATE DE UN DIA">7 LAGUNAS DE AUSANGATE DE UN DÍA</option>
                        <option value="MARAS MORAY DE UN DIA">MARAS MORAY DE UN DÍA</option>                             
                        <option value="Q’ESHUACHAKA Y 4 LAGUNAS DE UN DÍA">Q’ESHUACHAKA Y 4 LAGUNAS DE UN DÍA</option>
                        <option value="WAQRAPUKARA DE UN DIA">WAQRAPUKARA DE UN DÍA</option>
                        <option value="CITY TOUR CUSCO MEDIO DIA">CITY TOUR CUSCO MEDIO DÍA</option>
                        <option value="CUATRIMOTOS">CUATRIMOTOS</option>
                        <option value="ICA – PARACAS DE UN DIA">ICA – PARACAS DE UN DÍA</option>
                        <option value="PUNO DE UN DÍA">PUNO DE UN DÍA</option>
                        <option value="MANU 4 DÍAS Y 3 NOCHES">MANU 4 DÍAS Y 3 NOCHES</option>
                </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fecha de Reserva:</label>
                        <input type="date" name="fecha_reserva" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fecha de Salida:</label>
                        <input type="date" name="fecha_salida" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fecha de Retorno:</label>
                        <input type="date" name="fecha_retorno" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Grupo:</label>
                        <input type="text" name="grupo" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Método de Pago:</label>
                        <select name="metodo_pago" class="form-select">
                            <option value="Efectivo">Efectivo</option>
                            <option value="We travel">We travel</option>
                            <option value="Izipay">Izipay</option>
                            <option value="PAYPAL">PAYPAL</option>
                        </select>
                    </div>
                </div>

                <!-- Segunda columna -->
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Precio del Servicio:</label>
                        <input type="number" id="precio_servicio" name="precio_servicio" class="form-control" required oninput="calcularSaldo()">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Pagado a Cuenta:</label>
                        <input type="number" id="pagado_a_cuenta" name="pagado_a_cuenta" class="form-control" oninput="calcularSaldo()">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Saldo Pendiente:</label>
                        <input type="number" id="saldo_pendiente" name="saldo_pendiente" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fecha de Pago del Saldo:</label>
                        <input type="date" name="fecha_pago_saldo" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nro de Voucher:</label>
                        <input type="text" name="nro_voucher" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Modalidad de Pago:</label>
                        <select name="modalidad_pago" class="form-select">
                            <option value="Dolares">Dólares</option>
                            <option value="Soles">Soles</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-between mt-4">
                <button type="submit" class="btn btn-primary">Guardar Venta</button>
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</body>
</html>
