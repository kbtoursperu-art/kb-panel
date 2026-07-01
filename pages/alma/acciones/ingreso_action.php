<?php
session_start();
if (!isset($_SESSION["usuario"])) {
    header("Location: ../../../index.php");
    exit();
}

include '../../../conexion.php';
mysqli_set_charset($conexion, 'utf8mb4');

$modo        = $_POST['modo']        ?? 'existente';
$cantidad    = intval($_POST['cantidad'] ?? 0);
$observacion = mysqli_real_escape_string($conexion, trim($_POST['observacion'] ?? ''));

if ($cantidad < 1) {
    header("Location: ../ingreso.php?error=" . urlencode("La cantidad debe ser mayor a 0"));
    exit();
}

// ══════════════════════════════════
// MODO 1: AGREGAR A STOCK EXISTENTE
// ══════════════════════════════════
if ($modo === 'existente') {

    $id_stock = intval($_POST['id_stock'] ?? 0);

    if ($id_stock < 1) {
        header("Location: ../ingreso.php?error=" . urlencode("Seleccione un producto válido"));
        exit();
    }

    // Verificar que el stock existe
    $check = mysqli_query($conexion,
        "SELECT id_stock FROM almacen_stock WHERE id_stock = $id_stock");
    if (!mysqli_num_rows($check)) {
        header("Location: ../ingreso.php?error=" . urlencode("Producto no encontrado"));
        exit();
    }

    $sql = "UPDATE almacen_stock
            SET cantidad_total      = cantidad_total      + $cantidad,
                cantidad_disponible = cantidad_disponible + $cantidad
            WHERE id_stock = $id_stock";

    if (!mysqli_query($conexion, $sql)) {
        header("Location: ../ingreso.php?error=" . urlencode("Error al actualizar: " . mysqli_error($conexion)));
        exit();
    }

    header("Location: ../ingreso.php?ok=" . urlencode("Ingreso registrado correctamente ($cantidad unidades)"));
    exit();
}

// ══════════════════════════════════
// MODO 2: CREAR PRODUCTO NUEVO
// ══════════════════════════════════
if ($modo === 'nuevo') {

    $nombre_item = mysqli_real_escape_string($conexion, trim($_POST['nombre_item'] ?? ''));
    $tipo_item   = mysqli_real_escape_string($conexion, trim($_POST['tipo_item']   ?? ''));
    $talla       = mysqli_real_escape_string($conexion, trim($_POST['talla']       ?? ''));

    if (empty($nombre_item) || empty($tipo_item)) {
        header("Location: ../ingreso.php?error=" . urlencode("Nombre y tipo son obligatorios"));
        exit();
    }

    // Validar tipo
    $tipos_validos = ['Consumible', 'Retornable', 'Garantia'];
    if (!in_array($tipo_item, $tipos_validos)) {
        header("Location: ../ingreso.php?error=" . urlencode("Tipo de producto inválido"));
        exit();
    }

    mysqli_begin_transaction($conexion);

    try {
        // 1) Insertar ítem
        $sql_item = "INSERT INTO almacen_items (nombre, tipo) VALUES ('$nombre_item', '$tipo_item')";
        if (!mysqli_query($conexion, $sql_item)) throw new Exception(mysqli_error($conexion));

        $id_item = mysqli_insert_id($conexion);

        // 2) Insertar stock
        $talla_val = $talla ? "'$talla'" : "NULL";
        $sql_stock = "INSERT INTO almacen_stock (id_item, talla, cantidad_total, cantidad_disponible)
                      VALUES ($id_item, $talla_val, $cantidad, $cantidad)";
        if (!mysqli_query($conexion, $sql_stock)) throw new Exception(mysqli_error($conexion));

        mysqli_commit($conexion);
        header("Location: ../ingreso.php?ok=" . urlencode("Producto '$nombre_item' creado con $cantidad unidades"));
        exit();

    } catch (Exception $e) {
        mysqli_rollback($conexion);
        header("Location: ../ingreso.php?error=" . urlencode("Error: " . $e->getMessage()));
        exit();
    }
}

// Fallback
header("Location: ../ingreso.php?error=" . urlencode("Solicitud no válida"));
exit();