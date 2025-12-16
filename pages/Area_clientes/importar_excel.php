<?php
require '../../vendor/autoload.php';
include('../../conexion.php');

use PhpOffice\PhpSpreadsheet\IOFactory;

if (isset($_FILES['archivo_excel']['tmp_name'])) {
    $archivo = $_FILES['archivo_excel']['tmp_name'];
    $documento = IOFactory::load($archivo);
    $hoja = $documento->getActiveSheet();

    foreach ($hoja->getRowIterator(2) as $fila) {
        $celdas = $fila->getCellIterator();
        $celdas->setIterateOnlyExistingCells(false);

        $datos = [];
        foreach ($celdas as $celda) {
            $datos[] = $celda->getValue();
        }

        // Asegúrate de que el orden de columnas coincida
        $stmt = $conexion->prepare("INSERT INTO Datos_clientes (id_cliente, nombre, apellido, edad, genero, nro_pasaporte, nro_whatsapp, nacionalidad, grupo, hotel) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ississssss", $datos[0], $datos[1], $datos[2], $datos[3], $datos[4], $datos[5], $datos[6], $datos[7], $datos[8], $datos[9]);
        $stmt->execute();
    }

    echo "Importación completada.";
} else {
    echo "No se subió ningún archivo.";
}
?>
