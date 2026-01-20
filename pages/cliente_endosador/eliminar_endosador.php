<?php
include '../../conexion.php';

if (!isset($_POST['id_cliente']) || !is_numeric($_POST['id_cliente'])) {
    echo "<script>
        alert('ID inválido');
        window.location='clientes_endosadores.php';
    </script>";
    exit;
}

$id_cliente = (int) $_POST['id_cliente'];

/* 
   IMPORTANTE:
   Primero eliminamos de Clientes_Endosadores
   luego de Datos_clientes (por FK o lógica)
*/

// 1️⃣ Eliminar relación endosador
mysqli_query($conexion, "DELETE FROM Clientes_Endosadores WHERE id_cliente = $id_cliente");

// 2️⃣ Eliminar cliente
$delete = mysqli_query($conexion, "DELETE FROM Datos_clientes WHERE id_cliente = $id_cliente");

if ($delete) {
    echo "<script>
        alert('✅ Cliente Endosador eliminado correctamente');
        window.location='index.php';
    </script>";
} else {
    echo "<script>
        alert('❌ Error al eliminar el cliente');
        window.location='index.php';
    </script>";
}
?>
