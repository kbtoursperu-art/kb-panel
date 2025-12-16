<?php
include '../../../conexion.php';

// Verificar si se pasó un ID por la URL
if (isset($_GET['id'])) {
    $id_operacion = intval($_GET['id']);

    // 🧾 Primero verificar si existe la operación
    $verificar = mysqli_query($conexion, "SELECT id_operaciones FROM Operaciones WHERE id_operaciones = $id_operacion");
    if (mysqli_num_rows($verificar) > 0) {

        // 🔹 Eliminar registros relacionados en Contabilidad si existen
        $sqlContabilidad = "DELETE FROM Contabilidad WHERE id_operaciones = ?";
        $stmt1 = mysqli_prepare($conexion, $sqlContabilidad);
        mysqli_stmt_bind_param($stmt1, "i", $id_operacion);
        mysqli_stmt_execute($stmt1);
        mysqli_stmt_close($stmt1);

        // 🔹 Eliminar la operación en sí
        $sqlOperacion = "DELETE FROM Operaciones WHERE id_operaciones = ?";
        $stmt2 = mysqli_prepare($conexion, $sqlOperacion);
        mysqli_stmt_bind_param($stmt2, "i", $id_operacion);
        $resultado = mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);

        // 🔹 Verificar resultado
        if ($resultado) {
            echo "
                <script>
                    alert('✅ La operación se eliminó correctamente.');
                    window.location.href = 'index.php';
                </script>
            ";
        } else {
            echo "
                <script>
                    alert('❌ Error al eliminar la operación.');
                    window.location.href = 'index.php';
                </script>
            ";
        }
    } else {
        echo "
            <script>
                alert('⚠️ No se encontró la operación.');
                window.location.href = 'index.php';
            </script>
        ";
    }
} else {
    echo "
        <script>
            alert('⚠️ Parámetro inválido.');
            window.location.href = 'index.php';
        </script>
    ";
}
?>
