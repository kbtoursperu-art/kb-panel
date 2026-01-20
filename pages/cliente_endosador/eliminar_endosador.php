<?php
include '../../conexion.php';

if (isset($_POST['id_cliente']) && is_numeric($_POST['id_cliente'])) {

    $id_cliente = (int) $_POST['id_cliente'];

    // Obtener foto si existe
    $query_foto = mysqli_query($conexion, "
        SELECT foto_documento 
        FROM Clientes_Endosadores 
        WHERE id_cliente = $id_cliente
    ");
    $row = mysqli_fetch_assoc($query_foto);

    if ($row && !empty($row['foto_documento'])) {
        $ruta_foto = 'uploads/' . $row['foto_documento'];
        if (file_exists($ruta_foto)) {
            unlink($ruta_foto);
        }
    }

    // Eliminar cliente (CASCADE elimina en Clientes_Endosadores)
    $delete = mysqli_query(
        $conexion,
        "DELETE FROM Datos_clientes WHERE id_cliente = $id_cliente"
    );

    if ($delete) {
        echo "<script>
            alert('Cliente Endosador eliminado correctamente');
            window.location='clientes_endosadores.php';
        </script>";
    } else {
        echo "<script>
            alert('Error al eliminar el cliente');
            window.location='clientes_endosadores.php';
        </script>";
    }

} else {
    echo "<script>
        alert('ID inválido');
        window.location='clientes_endosadores.php';
    </script>";
}
