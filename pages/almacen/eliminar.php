<?php
include('../../conexion.php');

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id'])) {
    $id = $_POST['id'];

    $sql = "DELETE FROM almacen WHERE id = '$id'";

    if (mysqli_query($conexion, $sql)) {
        header("Location: index.php");
        exit;
    } else {
        echo "❌ Error al eliminar: " . mysqli_error($conexion);
    }
}
?>
<p></p>