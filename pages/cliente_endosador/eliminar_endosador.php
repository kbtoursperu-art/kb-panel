<?php
include '../../conexion.php';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_cliente = $_GET['id'];

    // Obtener la foto (si existe) para eliminarla del servidor
    $query_foto = mysqli_query($conexion, "SELECT foto_documento FROM Clientes_Endosadores WHERE id_cliente = $id_cliente");
    $row = mysqli_fetch_assoc($query_foto);
    if ($row && !empty($row['foto_documento'])) {
        $ruta_foto = 'uploads/' . $row['foto_documento'];
        if (file_exists($ruta_foto)) {
            unlink($ruta_foto);
        }
    }

    // Eliminar el cliente desde la tabla principal, esto eliminará también de Clientes_Endosadores por ON DELETE CASCADE
    $delete = mysqli_query($conexion, "DELETE FROM Datos_clientes WHERE id_cliente = $id_cliente");

    if ($delete) {
        echo "<script>alert('Cliente Endosador eliminado correctamente'); window.location='endosadores.php';</script>";
    } else {
        echo "<script>alert('Error al eliminar el cliente'); window.location='endosadores.php';</script>";
    }
} else {
    echo "<script>alert('ID inválido'); window.location='endosadores.php';</script>";
}
?>
