<?php
include '../../conexion.php';

if (!isset($_GET['id_cliente']) || !is_numeric($_GET['id_cliente'])) {
    echo "<script>
        alert('ID inválido');
        window.location='endosadores.php';
    </script>";
    exit;
}

$id_cliente = (int) $_GET['id_cliente'];

$delete = mysqli_query(
    $conexion,
    "DELETE FROM Datos_clientes WHERE id_cliente = $id_cliente"
);

if ($delete) {
    echo "<script>
        alert('Cliente Endosador eliminado correctamente');
        window.location='endosadores.php';
    </script>";
} else {
    echo "<script>
        alert('Error al eliminar el cliente');
        window.location='endosadores.php';
    </script>";
}
?>
