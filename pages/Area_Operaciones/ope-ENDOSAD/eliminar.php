<?php
include '../../../conexion.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // Primero elimina registros relacionados en Contabilidad (si existen)
    $deleteCont = "DELETE FROM contabilidad WHERE id_operaciones = '$id'";
    mysqli_query($conexion, $deleteCont);

    // Luego elimina la operación
    $deleteOpe = "DELETE FROM operaciones WHERE id_operaciones = '$id'";
    if (mysqli_query($conexion, $deleteOpe)) {
        echo "<script>
                alert('✅ Operación eliminada correctamente.');
                window.location.href = 'index.php';
              </script>";
    } else {
        echo "<script>
                alert('❌ Error al eliminar la operación.');
                window.location.href = 'index.php';
              </script>";
    }
} else {
    header('Location: index.php');
}
?>
