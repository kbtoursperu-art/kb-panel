<?php
// eliminar_kb.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include('../../conexion.php');

    $id_cliente = intval($_POST['id_cliente']);
    mysqli_query($conexion, "DELETE FROM Clientes_KB WHERE id_cliente = $id_cliente");
    mysqli_query($conexion, "DELETE FROM Datos_clientes WHERE id_cliente = $id_cliente");

    header("Location: index.php?mensaje=eliminado");
} else {
    die("Acceso no permitido. Método: " . $_SERVER['REQUEST_METHOD']);
}
?>
