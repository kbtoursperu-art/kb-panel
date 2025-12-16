<?php
include('../../conexion.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_cliente = intval($_POST['id_cliente']);

    // Eliminar de Clientes_KB
    $stmt_kb = mysqli_prepare($conexion, "DELETE FROM Clientes_KB WHERE id_cliente = ?");
    mysqli_stmt_bind_param($stmt_kb, "i", $id_cliente);
    mysqli_stmt_execute($stmt_kb);

    // Eliminar de Datos_clientes
    $stmt_datos = mysqli_prepare($conexion, "DELETE FROM Datos_clientes WHERE id_cliente = ?");
    mysqli_stmt_bind_param($stmt_datos, "i", $id_cliente);
    mysqli_stmt_execute($stmt_datos);

    // Redirigir al index
    echo "<script>alert('Cliente eliminado exitosamente'); window.location.href='index.php';</script>";
    exit;
}  else {
    echo "Acceso no permitido. Método: " . $_SERVER['REQUEST_METHOD'];


}
?>
