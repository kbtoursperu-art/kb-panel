<?php
include('../../conexion.php'); // Asegúrate de que la ruta de conexión sea correcta.

// Procesar el formulario de edición
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit'])) {
  $id_reserva = $_POST['id_reserva'];
  $fecha_reserva = $_POST['fecha_reserva'];
  $cantidad_pasajeros = $_POST['cantidad_pasajeros'];
  $nro_voucher = $_POST['nro_voucher'];
  $precio_pagado_cuenta = $_POST['precio_pagado_cuenta'];
  $saldo_pendiente = $_POST['saldo_pendiente'];
  $total_pago = $_POST['total_pago'];
  $fecha_pago_saldo = $_POST['fecha_pago_saldo'];

  // Actualizar los datos de la reserva en la base de datos
  $sql = "UPDATE reservas SET 
              fecha_reserva = :fecha_reserva, 
              cantidad_pasajeros = :cantidad_pasajeros, 
              nro_voucher = :nro_voucher, 
              precio_pagado_cuenta = :precio_pagado_cuenta, 
              saldo_pendiente = :saldo_pendiente, 
              total_pago = :total_pago, 
              fecha_pago_saldo = :fecha_pago_saldo 
          WHERE id_reserva = :id_reserva";
  
  $stmt = $conn->prepare($sql);
  $stmt->bindParam(':fecha_reserva', $fecha_reserva);
  $stmt->bindParam(':cantidad_pasajeros', $cantidad_pasajeros);
  $stmt->bindParam(':nro_voucher', $nro_voucher);
  $stmt->bindParam(':precio_pagado_cuenta', $precio_pagado_cuenta);
  $stmt->bindParam(':saldo_pendiente', $saldo_pendiente);
  $stmt->bindParam(':total_pago', $total_pago);
  $stmt->bindParam(':fecha_pago_saldo', $fecha_pago_saldo);
  $stmt->bindParam(':id_reserva', $id_reserva);

  if ($stmt->execute()) {
      echo "Reserva actualizada exitosamente.";
  } else {
      echo "Error al actualizar la reserva.";
  }
}

// Procesar el formulario de agregar datos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
  $id_cliente = $_POST['id_cliente'];
  $id_tour = $_POST['id_tour'];
  $fecha_reserva = $_POST['fecha_reserva'];
  $cantidad_pasajeros = $_POST['cantidad_pasajeros'];
  $nro_voucher = $_POST['nro_voucher'];
  $precio_pagado_cuenta = $_POST['precio_pagado_cuenta'];
  $saldo_pendiente = $_POST['saldo_pendiente'];
  $total_pago = $_POST['total_pago'];
  $fecha_pago_saldo = $_POST['fecha_pago_saldo'];

  // Insertar datos en la tabla reservas
  $sql = "INSERT INTO reservas (id_cliente, id_tour, fecha_reserva, cantidad_pasajeros, nro_voucher, precio_pagado_cuenta, saldo_pendiente, total_pago, fecha_pago_saldo) 
          VALUES (:id_cliente, :id_tour, :fecha_reserva, :cantidad_pasajeros, :nro_voucher, :precio_pagado_cuenta, :saldo_pendiente, :total_pago, :fecha_pago_saldo)";
  
  $stmt = $conn->prepare($sql);
  $stmt->bindParam(':id_cliente', $id_cliente);
  $stmt->bindParam(':id_tour', $id_tour);
  $stmt->bindParam(':fecha_reserva', $fecha_reserva);
  $stmt->bindParam(':cantidad_pasajeros', $cantidad_pasajeros);
  $stmt->bindParam(':nro_voucher', $nro_voucher);
  $stmt->bindParam(':precio_pagado_cuenta', $precio_pagado_cuenta);
  $stmt->bindParam(':saldo_pendiente', $saldo_pendiente);
  $stmt->bindParam(':total_pago', $total_pago);
  $stmt->bindParam(':fecha_pago_saldo', $fecha_pago_saldo);

  if ($stmt->execute()) {
      echo "Reserva agregada exitosamente.";
  } else {
      echo "Error al agregar reserva.";
  }
}

// Variables para las fechas, si no están definidas, se inicializan
$fecha_inicio = isset($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : '';
$fecha_final = isset($_POST['fecha_final']) ? $_POST['fecha_final'] : '';
// Consulta SQL para obtener los datos de reservas y tours
$sql = "SELECT r.id_reserva, r.fecha_reserva, r.cantidad_pasajeros, r.nro_voucher, 
               r.precio_pagado_cuenta, r.saldo_pendiente, r.total_pago, r.fecha_pago_saldo, 
               t.nombre_servicio, t.empresa, t.fecha_inicio, t.fecha_final, t.modalidad_retorno, 
               t.guia, t.cocinero, t.responsable_oficina, t.precio_total, t.modalidad_pago, 
               t.valor_moneda 
        FROM reservas r
        JOIN tours t ON r.id_tour = t.id_tour";

// Filtrar por fecha si ambas fechas están presentes
if ($fecha_inicio && $fecha_final) {
  $sql .= " WHERE r.fecha_reserva BETWEEN :fecha_inicio AND :fecha_final";
}

$stmt = $conn->prepare($sql);

// Si se aplica filtro de fechas, agregar los parámetros a la consulta
if ($fecha_inicio && $fecha_final) {
  $stmt->bindParam(':fecha_inicio', $fecha_inicio);
  $stmt->bindParam(':fecha_final', $fecha_final);
}

// Ejecutar la consulta
$stmt->execute();

// Verificar si hay resultados
if ($stmt->rowCount() > 0) {
    // Crear una tabla HTML para mostrar los datos
    echo "<table border='1'>
            <tr>
                <th>ID Reserva</th>
                <th>Fecha Reserva</th>
                <th>Cantidad Pasajeros</th>
                <th>Nro Voucher</th>
                <th>Precio Pagado</th>
                <th>Saldo Pendiente</th>
                <th>Total Pago</th>
                <th>Fecha Pago Saldo</th>
                <th>Nombre Servicio</th>
                <th>Empresa</th>
                <th>Fecha Inicio</th>
                <th>Fecha Final</th>
                <th>Modalidad Retorno</th>
                <th>Guía</th>
                <th>Cocinero</th>
                <th>Responsable Oficina</th>
                <th>Precio Total</th>
                <th>Modalidad Pago</th>
                <th>Valor Moneda</th>
                <th>Acción</th>
            </tr>";

    // Mostrar los datos en filas de la tabla
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>
                <td>" . $row["id_reserva"] . "</td>
                <td>" . $row["fecha_reserva"] . "</td>
                <td>" . $row["cantidad_pasajeros"] . "</td>
                <td>" . $row["nro_voucher"] . "</td>
                <td>" . $row["precio_pagado_cuenta"] . "</td>
                <td>" . $row["saldo_pendiente"] . "</td>
                <td>" . $row["total_pago"] . "</td>
                <td>" . $row["fecha_pago_saldo"] . "</td>
                <td>" . $row["nombre_servicio"] . "</td>
                <td>" . $row["empresa"] . "</td>
                <td>" . $row["fecha_inicio"] . "</td>
                <td>" . $row["fecha_final"] . "</td>
                <td>" . $row["modalidad_retorno"] . "</td>
                <td>" . $row["guia"] . "</td>
                <td>" . $row["cocinero"] . "</td>
                <td>" . $row["responsable_oficina"] . "</td>
                <td>" . $row["precio_total"] . "</td>
                <td>" . $row["modalidad_pago"] . "</td>
                <td>" . $row["valor_moneda"] . "</td>
                <td><button class='editBtn' data-id='" . $row["id_reserva"] . "' data-fecha_reserva='" . $row["fecha_reserva"] . "' data-cantidad_pasajeros='" . $row["cantidad_pasajeros"] . "' 
                        data-nro_voucher='" . $row["nro_voucher"] . "' data-precio_pagado_cuenta='" . $row["precio_pagado_cuenta"] . "' data-saldo_pendiente='" . $row["saldo_pendiente"] . "' 
                        data-total_pago='" . $row["total_pago"] . "' data-fecha_pago_saldo='" . $row["fecha_pago_saldo"] . "'>Editar</button></td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "No se encontraron resultados.";
}

// Cerrar la conexión
$conn = null;
?>

<!-- Formulario HTML para buscar por fechas -->
<form method="POST">
    <label for="fecha_inicio">Fecha de Inicio:</label>
    <input type="date" name="fecha_inicio" value="<?php echo $fecha_inicio; ?>">
    <br>
    <label for="fecha_final">Fecha Final:</label>
    <input type="date" name="fecha_final" value="<?php echo $fecha_final; ?>">
    <br>
    <button type="submit">Buscar</button>
</form>
<!-- Botón para abrir el modal -->
<button id="openModalBtn">Agregar más datos</button>

<!-- Modal -->
<div id="myModal" style="display:none;">
    <div>
        <span id="closeModalBtn" style="cursor:pointer;">&times;</span>
        <h2>Agregar Nueva Reserva</h2>
        <form method="POST">
            <label for="fecha_reserva">Fecha Reserva:</label>
            <input type="date" name="fecha_reserva" required><br>
            <label for="cantidad_pasajeros">Cantidad Pasajeros:</label>
            <input type="number" name="cantidad_pasajeros" required><br>
            <label for="nro_voucher">Nro Voucher:</label>
            <input type="text" name="nro_voucher" required><br>
            <label for="precio_pagado_cuenta">Precio Pagado Cuenta:</label>
            <input type="number" step="0.01" name="precio_pagado_cuenta" required><br>
            <label for="saldo_pendiente">Saldo Pendiente:</label>
            <input type="number" step="0.01" name="saldo_pendiente" required><br>
            <label for="total_pago">Total Pago:</label>
            <input type="number" step="0.01" name="total_pago" required><br>
            <label for="fecha_pago_saldo">Fecha Pago Saldo:</label>
            <input type="date" name="fecha_pago_saldo" required><br>
            <button type="submit" name="submit">Agregar Reserva</button>
        </form>
    </div>
</div>

<!-- JavaScript para abrir y cerrar el modal -->
<script>
    // Obtener los elementos del modal
    var modal = document.getElementById("myModal");
    var openModalBtn = document.getElementById("openModalBtn");
    var closeModalBtn = document.getElementById("closeModalBtn");

    // Abrir el modal cuando el botón es presionado
    openModalBtn.onclick = function() {
        modal.style.display = "block";
    }

    // Cerrar el modal cuando se presiona la "X"
    closeModalBtn.onclick = function() {
        modal.style.display = "none";
    }

    // Cerrar el modal si se hace clic fuera del modal
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
</script>

<!-- Modal para editar datos -->
<div id="editModal" style="display:none;">
    <div>
        <span id="closeEditModal" style="cursor:pointer;">&times;</span>
        <h2>Editar Reserva</h2>
        <form method="POST">
            <input type="hidden" name="id_reserva" id="id_reserva">
            <label for="fecha_reserva">Fecha Reserva:</label>
            <input type="date" name="fecha_reserva" id="fecha_reserva" required><br>
            <label for="cantidad_pasajeros">Cantidad Pasajeros:</label>
            <input type="number" name="cantidad_pasajeros" id="cantidad_pasajeros" required><br>
            <label for="nro_voucher">Nro Voucher:</label>
            <input type="text" name="nro_voucher" id="nro_voucher" required><br>
            <label for="precio_pagado_cuenta">Precio Pagado Cuenta:</label>
            <input type="number" step="0.01" name="precio_pagado_cuenta" id="precio_pagado_cuenta" required><br>
            <label for="saldo_pendiente">Saldo Pendiente:</label>
            <input type="number" step="0.01" name="saldo_pendiente" id="saldo_pendiente" required><br>
            <label for="total_pago">Total Pago:</label>
            <input type="number" step="0.01" name="total_pago" id="total_pago" required><br>
            <label for="fecha_pago_saldo">Fecha Pago Saldo:</label>
            <input type="date" name="fecha_pago_saldo" id="fecha_pago_saldo" required><br>
            <button type="submit" name="edit">Actualizar Reserva</button>
        </form>
    </div>
</div>

<!-- JavaScript para abrir, cerrar y llenar el modal con datos -->
<script>
    // Obtener los elementos del modal
    var modal = document.getElementById("editModal");
    var closeModalBtn = document.getElementById("closeEditModal");
    var editBtns = document.querySelectorAll(".editBtn");

    // Abrir el modal cuando el botón de edición es presionado
    editBtns.forEach(function(button) {
        button.addEventListener('click', function() {
            // Llenar el modal con los datos de la fila seleccionada
            document.getElementById("id_reserva").value = this.getAttribute("data-id");
            document.getElementById("fecha_reserva").value = this.getAttribute("data-fecha_reserva");
            document.getElementById("cantidad_pasajeros").value = this.getAttribute("data-cantidad_pasajeros");
            document.getElementById("nro_voucher").value = this.getAttribute("data-nro_voucher");
            document.getElementById("precio_pagado_cuenta").value = this.getAttribute("data-precio_pagado_cuenta");
            document.getElementById("saldo_pendiente").value = this.getAttribute("data-saldo_pendiente");
            document.getElementById("total_pago").value = this.getAttribute("data-total_pago");
            document.getElementById("fecha_pago_saldo").value = this.getAttribute("data-fecha_pago_saldo");

            // Mostrar el modal
            modal.style.display = "block";
        });
    });

    // Cerrar el modal cuando se presiona la "X"
    closeModalBtn.onclick = function() {
        modal.style.display = "none";
    }

    // Cerrar el modal si se hace clic fuera del modal
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }
</script>
