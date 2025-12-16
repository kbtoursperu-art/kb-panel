<?php
include('../../conexion.php');
include './../header.php';
include './../sidebar.php';

// estadisticas_reservas.php

// Parámetros de filtro
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? '';
$tour = $_GET['tour'] ?? '';

// Construcción dinámica de la consulta
$query = "
SELECT MONTH(o.fecha_reserva) as mes, COUNT(*) as total
FROM Operaciones o
JOIN Datos_clientes d ON o.id_cliente = d.id_cliente
WHERE YEAR(o.fecha_reserva) = ?";
$params = [$year];
$types = "i";

if ($month !== '') {
    $query .= " AND MONTH(o.fecha_reserva) = ?";
    $params[] = $month;
    $types .= "i";
}
if ($tour !== '') {
    $query .= " AND o.nombre_servicio =zz?";
    $params[] = $tour;
    $types .= "s";
}


$query .= " GROUP BY mes ORDER BY mes";

// Preparar y ejecutar consulta segura con mysqli
$stmt = mysqli_prepare($conexion, $query);

if (!$stmt) {
    die("❌ Error al preparar la consulta: " . $query . " - " . mysqli_error($conexion));
}

if (!mysqli_stmt_bind_param($stmt, $types, ...$params)) {
    die("❌ Error al vincular parámetros: " . mysqli_stmt_error($stmt));
}

if (!mysqli_stmt_execute($stmt)) {
    die("❌ Error al ejecutar la consulta: " . mysqli_stmt_error($stmt));
}

$resultado = mysqli_stmt_get_result($stmt);
if (!$resultado) {
    die("❌ Error al obtener resultados: " . mysqli_stmt_error($stmt));
}

$reservas = mysqli_fetch_all($resultado, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);


// Inicializar datos por mes
$meses = [1=>'Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
$reservas_mensuales = array_fill(1, 12, 0);
foreach ($reservas as $r) {
    $reservas_mensuales[$r['mes']] = $r['total'];
}
?>

<div class="container mt-4">
  <h2 class="mb-4">Estadísticas de Reservas - <?php echo $year; ?></h2>
  <form class="row g-2 mb-4" method="GET">
    <div class="col-md-2">
      <label class="form-label">Año</label>
      <input type="number" name="year" value="<?php echo $year; ?>" class="form-control">
    </div>
    <div class="col-md-2">
      <label class="form-label">Mes</label>
      <select name="month" class="form-select">
        <option value="">Todos</option>
        <?php foreach ($meses as $num => $nombre): ?>
          <option value="<?php echo $num; ?>" <?php if ($month == $num) echo 'selected'; ?>><?php echo $nombre; ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
  <label class="form-label">Paquete (Tour)</label>
  <select name="tour" class="form-select">
    <option value="">Todos</option>
    <?php
    $resultTours = mysqli_query($conexion, "SELECT DISTINCT nombre_servicio FROM Operaciones ORDER BY nombre_servicio");
    while ($row = mysqli_fetch_assoc($resultTours)):
    ?>
      <option value="<?php echo $row['nombre_servicio']; ?>" <?php if ($row['nombre_servicio'] == $tour) echo 'selected'; ?>>
        <?php echo $row['nombre_servicio']; ?>
      </option>
    <?php endwhile; ?>
  </select>
</div>
 <div class="col-md-2 align-self-end">
      <button class="btn btn-primary w-100">Filtrar</button>
    </div>

  </form>

  <canvas id="graficoReservas" height="100"></canvas>

  <div class="mt-4">
    <table id="tablaReservas" class="table table-striped table-bordered">
      <thead>
        <tr>
          <th>Mes</th>
          <th>Total Reservas</th>
          <th>Notificación</th>
          <th>Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($reservas_mensuales as $mes => $total): ?>
          <tr>
            <td><?php echo $meses[$mes]; ?></td>
            <td><?php echo $total; ?></td>
            <td>
              <?php if ($total > 100): ?>
                <span class="badge bg-success">Alta demanda</span>
              <?php elseif ($total < 10): ?>
                <span class="badge bg-danger">Baja demanda</span>
              <?php else: ?>
                <span class="badge bg-secondary">Normal</span>
              <?php endif; ?>
            </td>
            <td><a href="ver_detalles_mes.php?mes=<?php echo $mes; ?>&year=<?php echo $year; ?>" class="btn btn-sm btn-info">Ver detalles</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <button onclick="exportTable('excel')" class="btn btn-success mt-2">Exportar Excel</button>
    <button onclick="exportTable('pdf')" class="btn btn-danger mt-2">Exportar PDF</button>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.29/jspdf.plugin.autotable.min.js"></script>

<script>
  const ctx = document.getElementById('graficoReservas').getContext('2d');
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: <?php echo json_encode(array_values($meses)); ?>,
      datasets: [{
        label: 'Reservas por mes',
        data: <?php echo json_encode(array_values($reservas_mensuales)); ?>,
        backgroundColor: 'rgba(54, 162, 235, 0.6)',
        borderColor: 'rgba(54, 162, 235, 1)',
        borderWidth: 1
      }]
    },
    options: {
      responsive: true,
      scales: {
        y: {
          beginAtZero: true
        }
      }
    }
  });

  $(document).ready(function() {
    $('#tablaReservas').DataTable();
  });

  function exportTable(type) {
    if (type === 'excel') {
      let wb = XLSX.utils.table_to_book(document.getElementById('tablaReservas'), {sheet:"Sheet JS"});
      XLSX.writeFile(wb, 'reservas.xlsx');
    } else if (type === 'pdf') {
      const { jsPDF } = window.jspdf;
      const doc = new jsPDF();
      doc.text("Reporte de Reservas", 14, 10);
      doc.autoTable({ html: '#tablaReservas', startY: 20 });
      doc.save('reservas.pdf');
    }
  }
</script>

<?php include('./../footer.php'); ?>
