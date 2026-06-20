<?php

header('Content-Type: application/json');
include('../../conexion.php');

// Validar conexión
if (!$conexion) {
    echo json_encode([
        'success' => false,
        'msg' => 'Error de conexión'
    ]);
    exit;
}

// Recibir datos
$id_cliente = isset($_POST['id_cliente']) 
    ? intval($_POST['id_cliente']) 
    : 0;

$id_grupo = isset($_POST['id_grupo']) 
    ? intval($_POST['id_grupo']) 
    : 0;


// Validar datos
if ($id_cliente <= 0 || $id_grupo <= 0) {

    echo json_encode([
        'success' => false,
        'msg' => 'Datos incompletos'
    ]);

    exit;
}


$conexion->begin_transaction();

try {

    // Verificar existencia
    $stmtCheck = $conexion->prepare("
        SELECT id
        FROM clientes_grupo
        WHERE id_cliente=?
        AND id_grupo=?
        AND tipo_cliente='ENDOSADOR'
    ");

    if (!$stmtCheck) {
        throw new Exception(
            "Error preparando verificación: " . $conexion->error
        );
    }

    $stmtCheck->bind_param(
        "ii",
        $id_cliente,
        $id_grupo
    );

    $stmtCheck->execute();

    $resultado = $stmtCheck->get_result();

    if ($resultado->num_rows == 0) {
        throw new Exception(
            "No existe el ENDOSADOR en este grupo"
        );
    }

    $stmtCheck->close();


    // Eliminar
    $stmtDelete = $conexion->prepare("
        DELETE FROM clientes_grupo
        WHERE id_cliente=?
        AND id_grupo=?
        AND tipo_cliente='ENDOSADOR'
    ");

    if (!$stmtDelete) {
        throw new Exception(
            "Error preparando DELETE: " . $conexion->error
        );
    }

    $stmtDelete->bind_param(
        "ii",
        $id_cliente,
        $id_grupo
    );

    $stmtDelete->execute();

    if ($stmtDelete->errno) {
        throw new Exception(
            "Error DELETE: " . $stmtDelete->error
        );
    }

    if ($stmtDelete->affected_rows <= 0) {
        throw new Exception(
            "No se eliminó ningún registro"
        );
    }

    $stmtDelete->close();


    // Contar ENDOSADORES restantes
    $stmtCount = $conexion->prepare("
        SELECT COUNT(*) total
        FROM clientes_grupo
        WHERE id_grupo=?
        AND tipo_cliente='ENDOSADOR'
    ");

    if (!$stmtCount) {
        throw new Exception(
            "Error preparando COUNT: " . $conexion->error
        );
    }

    $stmtCount->bind_param(
        "i",
        $id_grupo
    );

    $stmtCount->execute();

    $resultado = $stmtCount->get_result();

    $fila = $resultado->fetch_assoc();

    $cantidad = intval($fila['total']);

    $stmtCount->close();


    // Actualizar tabla grupos
    // OJO: en tu BD es cantidad y NO registrados
    $stmtUpdate = $conexion->prepare("
        UPDATE grupos
        SET cantidad=?
        WHERE id_grupo=?
    ");

    if (!$stmtUpdate) {
        throw new Exception(
            "Error preparando UPDATE: " . $conexion->error
        );
    }

    $stmtUpdate->bind_param(
        "ii",
        $cantidad,
        $id_grupo
    );

    $stmtUpdate->execute();

    if ($stmtUpdate->errno) {
        throw new Exception(
            "Error UPDATE: " . $stmtUpdate->error
        );
    }

    $stmtUpdate->close();


    // Confirmar transacción
    $conexion->commit();


    echo json_encode([
        'success' => true,
        'msg' => 'ENDOSADOR eliminado correctamente',
        'cantidad' => $cantidad
    ]);

} catch (Exception $e) {

    $conexion->rollback();

    echo json_encode([
        'success' => false,
        'msg' => $e->getMessage()
    ]);
}

$conexion->close();

?>