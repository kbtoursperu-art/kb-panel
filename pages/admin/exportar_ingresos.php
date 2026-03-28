<?php
include('../../conexion.php');

header("Content-Type: application/vnd.ms-excel");
header("Content-Disposition: attachment; filename=ingresos.xls");

$year = $_GET['year'] ?? date('Y');

$query = "
SELECT 
    MONTH(fecha_pago) as mes,
    SUM(monto) as total
FROM pagos_operacion
WHERE YEAR(fecha_pago) = $year
GROUP BY mes
";

$res = mysqli_query($conexion,$query);

echo "Mes\tTotal\n";

while($row = mysqli_fetch_assoc($res)){
    echo $row['mes']."\t".$row['total']."\n";
}