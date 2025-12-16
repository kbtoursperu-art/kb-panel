<?php
require '../../vendor/autoload.php';
include('../../conexion.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$query = "SELECT * FROM Vista_DatosClientes";
$result = mysqli_query($conexion, $query);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Encabezados
$encabezados = ['ID', 'Nombre', 'Apellido', 'Edad', 'Género', 'Pasaporte', 'WhatsApp', 'Nacionalidad', 'Grupo', 'Hotel'];
$sheet->fromArray($encabezados, NULL, 'A1');

// Cuerpo de la tabla
$fila = 2;
while ($row = mysqli_fetch_assoc($result)) {
    $sheet->fromArray([
        $row['id_cliente'],
        $row['nombre'],
        $row['apellido'],
        $row['edad'],
        $row['genero'],
        $row['nro_pasaporte'],
        $row['nro_whatsapp'],
        $row['nacionalidad'],
        $row['grupo'],
        $row['hotel'],
    ], NULL, 'A' . $fila);
    $fila++;
}
// Descargar archivo
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="clientes.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
