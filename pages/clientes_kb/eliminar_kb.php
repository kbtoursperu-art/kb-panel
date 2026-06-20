<?php
// eliminar_kb.php

include('../../conexion.php');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Acceso no permitido");
}

header('Content-Type: text/html; charset=utf-8');

$id_cliente = isset($_POST['id_cliente'])
    ? intval($_POST['id_cliente'])
    : 0;


// Validar
if ($id_cliente <= 0) {

    header("Location:index.php?error=cliente_invalido");
    exit;
}


// Validar conexión
if (!$conexion) {

    header("Location:index.php?error=conexion");
    exit;
}


$conexion->begin_transaction();

try {

    // Verificar existencia
    $stmtCheck = mysqli_prepare(
        $conexion,
        "SELECT nombre,apellido
        FROM datos_clientes
        WHERE id_cliente=?"
    );

    if (!$stmtCheck) {
        throw new Exception(mysqli_error($conexion));
    }

    mysqli_stmt_bind_param(
        $stmtCheck,
        "i",
        $id_cliente
    );

    mysqli_stmt_execute($stmtCheck);

    $resultado = mysqli_stmt_get_result($stmtCheck);

    if (mysqli_num_rows($resultado) == 0) {
        throw new Exception(
            "Cliente no encontrado"
        );
    }

    $cliente = mysqli_fetch_assoc($resultado);

    mysqli_stmt_close($stmtCheck);


    // Eliminar cliente
    // CASCADE eliminará hijos automáticamente
    $stmtDelete = mysqli_prepare(
        $conexion,
        "DELETE FROM datos_clientes
        WHERE id_cliente=?"
    );

    if (!$stmtDelete) {
        throw new Exception(mysqli_error($conexion));
    }

    mysqli_stmt_bind_param(
        $stmtDelete,
        "i",
        $id_cliente
    );

    mysqli_stmt_execute($stmtDelete);

    if (mysqli_stmt_affected_rows($stmtDelete) <= 0) {
        throw new Exception(
            "No se pudo eliminar"
        );
    }

    mysqli_stmt_close($stmtDelete);


    // Confirmar cambios
    mysqli_commit($conexion);


    header(
        "Location:index.php?mensaje=eliminado"
    );

    exit;

} catch (Exception $e) {

    mysqli_rollback($conexion);

    header(
        "Location:index.php?error=" .
        urlencode($e->getMessage())
    );

    exit;
}

mysqli_close($conexion);

?>