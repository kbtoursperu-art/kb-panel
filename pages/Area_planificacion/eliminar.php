<?php
// Incluir la conexión a la base de datos
include '../../conexion.php';

// Verificar si se ha recibido un ID válido por GET
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_planificacion = $_GET['id'];

    // Consulta para eliminar la planificación
    $query = "DELETE FROM Planificacion WHERE id_planificacion = $id_planificacion";

    if (mysqli_query($conexion, $query)) {
        echo "<script>alert('Planificación eliminada con éxito'); window.location.href='index.php';</script>";
    } else {
        echo "<script>alert('Error al eliminar la planificación: " . mysqli_error($conexion) . "');</script>";
    }
} else {
    echo "<script>alert('ID de planificación no válido'); window.location.href='index.php';</script>";
}

// Cerrar conexión
mysqli_close($conexion);
?>
