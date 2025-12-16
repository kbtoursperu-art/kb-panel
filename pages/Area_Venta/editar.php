<?php
include('../../conexion.php');
session_start();

// Verificar el inicio de sesión
if (!isset($_SESSION["id_usuario"])) {
    header("Location: login.php");
    exit();
}

// Procesar la actualización si se ha enviado el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["guardar_cambios"])) {
    actualizarDatos();
}

// Agregar una nueva fila si se ha enviado el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["agregar_fila"])) {
    agregarFila();
}

// Eliminar una fila si se ha enviado el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["eliminar_fila"])) {
    eliminarFila();
}

// Consultar la base de datos para obtener los resultados
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["fecha_inicio"]) && isset($_POST["fecha_final"])) {
    $result = obtenerResultadosPorFechas($_POST["fecha_inicio"], $_POST["fecha_final"]);
} else {
    // Si los valores de fecha_inicio y fecha_final no están definidos en $_POST,
    // puedes establecer valores predeterminados o dejarlos en blanco, dependiendo de tus necesidades.
    // Aquí, por ejemplo, se establecen valores predeterminados para obtener todos los resultados.
    $result = obtenerResultadosPorFechas("", "");
}


// Función para obtener el número total de filas
function obtenerTotalFilas() {
    global $conn;
    $sql = "SELECT COUNT(*) AS total FROM operaciones";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    return $row['total'];
}

// Función para obtener resultados paginados
function obtenerResultadosPaginados($indiceInicial, $resultadosPorPagina) {
    global $conn;
    $sql = "SELECT OperacionesID, FechaSalida,Ruta, DatosPax, Briefing, Guia, Lugar,Hora, Proveedor FROM operaciones LIMIT ?, ?";
    $statement = $conn->prepare($sql);
    $statement->bind_param("ii", $indiceInicial, $resultadosPorPagina);
    $statement->execute();
    return $statement->get_result();
}
// Calcular el número total de páginas
$totalFilas = obtenerTotalFilas(); // Debes implementar la función obtenerTotalFilas()
$resultadosPorPagina = 10; // Define la cantidad de filas que deseas mostrar por página
$totalPaginas = ceil($totalFilas / $resultadosPorPagina);


// Consultar la base de datos para obtener todos los resultados
$result = obtenerTodosResultados();

// Función para obtener todos los resultados
function obtenerTodosResultados() {
    global $conn;
    $sql = "SELECT OperacionesID, FechaSalida, Ruta, DatosPax, Briefing, Guia, Lugar, Hora, Proveedor FROM operaciones";
    $result = $conn->query($sql);
    return $result;
}

?> 

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TABLA BRIEFING</title>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
    <link rel="stylesheet" href="./assets/css/inicio.css">
    <link rel="stylesheet" href="   https://cdnjs.cloudflare.com/ajax/libs/bootstrap-material-design/4.0.0/bootstrap-material-design.iife.min.js">
    <link rel="stylesheet" href="../../assets/css/tabla.css">
    
</head>
<body>
<?php include('../Principal/barra.php'); ?> <!-- Aquí se incluye el menú lateral -->
<br>
<h1 >BRIEFING</h1>
<form class="form-inline" method="post" name="fechas" id="fechas" action="">
    <div class="form-inline col-xs-10 col-xs-offset-3" >
        <div class="form-group" >
            <label for="fecha_inicio">Fecha inicio: </label>
            <input type="date" class="form-control" name="fecha_inicio" required>

        </div>
        <div class="form-group" >
            <label for="fecha_final">Fecha Final: </label>
            <input type="date" class="form-control" name="fecha_final" required>

        </div>
        <div class="form-group" >
            <button type="submit" class="btn btn-primary" >Buscar</button>

        </div>
        
    </div>

</form>
<div style="margin-left: 88%; width:12%;" class="form-group">
    <input type="text" class="form-control" id="busqueda" placeholder="Buscar...">
</div>
<br>
<div style="overflow-x: auto;">
    <table id="editableTable" border='1'>
        <thead>
            <tr>
        <th >COD</th>
        <th >Fecha Salida</th>
        <th >Ruta</th>
        <th >Pax</th>
        <th >Fecha Briefing</th>
        <th >Guía de Ruta</th>
        <th >Lugar</th>
        <th >Hora</th>
        <th >Proveedor</th>
            </tr>
        </thead>
        <tbody>
            <?php imprimirFilasTabla($result); ?>
        </tbody>
    </table>
    <div>
            <?php if ($totalPaginas > 1): ?>
                <ul class="pagination">
                    <li class="page-item <?php echo $paginaActual <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?pagina=<?php echo $paginaActual - 1; ?>">Anterior</a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
                        <li class="page-item <?php echo $paginaActual == $i ? 'active' : ''; ?>">
                            <a class="page-link" href="?pagina=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $paginaActual >= $totalPaginas ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?pagina=<?php echo $paginaActual + 1; ?>">Siguiente</a>
                    </li>
                </ul>
            <?php endif; ?>
        </div>
    <br>
    <button id="agregarFila">Agregar Fila</button>
    <button class="salir" id="salir" onclick="window.location.href='/pages/Principal/operaciones.php'">salir</button>
</div>

<script>
    $(document).ready(function () {
        hacerCeldasEditables();
        agregarManejadoresEventos();

        // Evento para agregar nueva fila
        $('#agregarFila').on('click', function () {
            agregarNuevaFila();
        });
        // Manejar la búsqueda en tiempo real
        $('#busqueda').on('input', function () {
            var valorBusqueda = $(this).val().toLowerCase();
            $('#editableTable tbody tr').filter(function () {
                $(this).toggle($(this).text().toLowerCase().indexOf(valorBusqueda) > -1)
            });
        });
        // Evento para eliminar fila
        $('.eliminar-fila').on('click', function () {
            var fila = $(this).closest('tr');
            var operacionesID = fila.data('id');

            // Enviar solicitud al servidor para eliminar la fila
            $.post('Briefing.php', {
                eliminar_fila: true,
                OperacionesID: operacionesID
            })
            .done(function (response) {
                // Eliminar la fila del DOM si la eliminación fue exitosa
                if (response.includes("Fila eliminada correctamente")) {
                    fila.remove();
                }
                alert(response);
            })
            .fail(function (error) {
                alert("Error al eliminar fila: " + error.statusText);
            });
        });
    });

    function agregarNuevaFila() {
        // Enviar solicitud al servidor para agregar una nueva fila
        $.post('Briefing.php', {
            agregar_fila: true
        })
        .done(function (response) {
            // Agregar la nueva fila al final de la tabla
            $('#editableTable tbody').append(response);

            // Agregar eventos a la nueva fila
            hacerCeldasEditables();
            agregarManejadoresEventos();
        })
        .fail(function (error) {
            alert("Error al agregar fila: " + error.statusText);
        });
    }

    function hacerCeldasEditables() {
        // Agregar evento de doble clic para iniciar la edición
        $('td').on('dblclick', function () {
            var value = $(this).text();
            var input = $('<input type="text">').val(value);
            $(this).html(input);

            // Enfocar automáticamente el nuevo campo de entrada
            input.focus();
        });

        // Agregar evento de desenfoque para guardar los cambios
        $('td').on('blur', function () {
            guardarCambios($(this));
        });
    }

    function agregarManejadoresEventos() {
        $('.guardar-cambios').on('click', function () {
            var fila = $(this).closest('tr');
            guardarCambiosEnFila(fila);
        });

        $('.eliminar-fila').on('click', function () {
            var confirmacion = confirm("¿Estás seguro de que quieres eliminar esta fila?");
            if (confirmacion) {
                var fila = $(this).closest('tr');
                var operacionesID = fila.data('id');

                // Enviar solicitud al servidor para eliminar la fila
                $.post('Briefing.php', {
                    eliminar_fila: true,
                    OperacionesID: operacionesID
                })
                .done(function (response) {
                    // Eliminar la fila del DOM si la eliminación fue exitosa
                    if (response.includes("Fila eliminada correctamente")) {
                        fila.remove();
                    }
                    alert(response);
                })
                .fail(function (error) {
                    alert("Error al eliminar fila: " + error.statusText);
                });
            }
        });
    }

    function guardarCambiosEnFila(fila) {
    var cambiosRealizados = false;  // Variable para rastrear si se han realizado cambios

    // Iterar sobre todas las celdas en la fila
    fila.find('td').each(function () {
        var celda = $(this);
        var nuevoValor = celda.find('input').val();

        // Enviar los datos actualizados al servidor solo si nuevoValor está definido
        if (typeof nuevoValor !== 'undefined') {
            $.post('Briefing.php', {
                guardar_cambios: true,
                OperacionesID: fila.data('id'),
                nombreCampo: celda.index(),
                nuevoValor: limpiarEntrada(nuevoValor)  // Limpiar la entrada antes de enviarla
                // Agrega más campos según tus necesidades
            })
            .done(function (response) { 
                // Puedes manejar la respuesta del servidor aquí

                // Si la respuesta incluye "Actualización exitosa", restaurar la celda a su estado original
                if (response.includes("Actualización exitosa")) {
                    celda.text(nuevoValor);
                    cambiosRealizados = true;
                } else {
                    // Si hay un error o el mensaje no coincide, mostrar un mensaje y restablecer la celda
                    alert("Error al actualizar: " + response);
                    celda.html('<input type="text" value="' + celda.text() + '">');
                }
            })
            .fail(function (error) {
                // Manejar errores en la comunicación con el servidor
                alert("Error de comunicación con el servidor: " + error.statusText);
            });
        }
    });

    // Mostrar el mensaje de éxito solo una vez después de que se hayan procesado todas las celdas
    if (cambiosRealizados) {
        alert("Actualización exitosa");
    }
}

// Función para limpiar la entrada y evitar la inyección de código
function limpiarEntrada(valor) {
    // Puedes agregar más validaciones según tus necesidades
    return $.trim(valor);
}
</script>

</body>

</html>

<?php
$conn->close();

// Funciones adicionales
function obtenerResultadosPorFechas($fechaInicio, $fechaFinal) {
    global $conn;
    $sql = "SELECT OperacionesID, FechaSalida,Ruta, DatosPax, Briefing, Guia, Lugar,Hora, Proveedor FROM operaciones WHERE FechaSalida BETWEEN ? AND ?";
    $statement = $conn->prepare($sql);
    $statement->bind_param("ss", $fechaInicio, $fechaFinal);
    $statement->execute();
    return $statement->get_result();
}

function imprimirFilasTabla($result) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr data-id='" . $row['OperacionesID'] . "'>";
        foreach ($row as $key => $value) {
            echo "<td class='editable' data-field='$key'>" . $value . "</td>";
        }
        echo "<td><button class='guardar-cambios'>Guardar</button></td>";
        echo "<td><button class='eliminar-fila' data-id='" . $row['OperacionesID'] . "'>Eliminar</button></td></tr>";
    }
}

function actualizarDatos() {
    global $conn;

    $operacionesID = $_POST["OperacionesID"];

    if (isset($_POST["nuevoValor"])) {
        $nuevoValor = $_POST["nuevoValor"];
    } else {
        // Manejar el caso en el que $_POST["nuevoValor"] no está definido
        echo "Error: El valor a actualizar no está definido.";
        return;
    }

    // Obtener el nombre del campo para actualizar
    $nombreCampoIndex = $_POST["nombreCampo"];
    $nombreCampo = obtenerNombreCampo($nombreCampoIndex);

    // Utilizar sentencias preparadas para evitar la inyección de SQL
    $updateQuery = $conn->prepare("UPDATE operaciones SET $nombreCampo = ? WHERE OperacionesID = ?");

    
    if (!$updateQuery) {
        echo "Error en la preparación de la consulta: " . $conn->error;
        return;
    }

    $updateQuery->bind_param("si", $nuevoValor, $operacionesID);

    if ($updateQuery->execute()) {
        echo "Actualización exitosa";
    } else {
        // Mostrar un mensaje de error si la actualización falla
        echo "Error al actualizar: " . $updateQuery->error;
    }

    $updateQuery->close();
}

function agregarFila() {
    global $conn;

    // Obtener el último OperacionesID existente y sumarle 1
    $ultimoIDQuery = $conn->query("SELECT MAX(OperacionesID) AS ultimoID FROM operaciones");
    $ultimoIDRow = $ultimoIDQuery->fetch_assoc();
    $nuevoID = $ultimoIDRow['ultimoID'] + 1;

    // Insertar una nueva fila en la base de datos especificando OperacionesID
    $insertQuery = $conn->prepare("INSERT INTO operaciones (OperacionesID, FechaSalida) VALUES (?, ?)");

    if (!$insertQuery) {
        echo "Error en la preparación de la consulta: " . $conn->error;
        return;
    }

    // Asignar valores a las variables antes de ejecutar la consulta
    $fechaSalida = "";

    $insertQuery->bind_param("is", $nuevoID, $fechaSalida);

    // Ejecutar la consulta
    if ($insertQuery->execute()) {
        // Obtener los datos de la nueva fila para imprimirlos
        $nuevaFilaQuery = $conn->query("SELECT * FROM operaciones WHERE OperacionesID = $nuevoID");
        $nuevaFila = $nuevaFilaQuery->fetch_assoc();

        // Imprimir la nueva fila en formato HTML y enviarla de vuelta al cliente
        echo "<tr data-id='" . $nuevoID . "'>";
        foreach ($nuevaFila as $key => $value) {
            echo "<td class='editable' data-field='$key'>" . $value . "</td>";
        }
        echo "<td><button class='guardar-cambios'>Guardar</button></td>";
        echo "<td><button class='eliminar-fila' data-id='" . $nuevoID . "'>Eliminar</button></td></tr>";
    } else {
        echo "Error al agregar una nueva fila: " . $insertQuery->error;
    }

    $insertQuery->close();
}

function eliminarFila() {
    global $conn;

    // Verificar si se proporciona un ID para eliminar
    if (isset($_POST["Operacione
    
    
    sID"])) {
        $operacionesID = $_POST["OperacionesID"];

        // Utilizar sentencia preparada para evitar la inyección de SQL
        $deleteQuery = $conn->prepare("DELETE FROM operaciones WHERE OperacionesID = ?");

        if (!$deleteQuery) {
            echo "Error en la preparación de la consulta: " . $conn->error;
            return;
        }

        // Enlazar parámetros con los valores correspondientes
        $deleteQuery->bind_param("i", $operacionesID);

        // Ejecutar la consulta
        if ($deleteQuery->execute()) {
            echo "Fila eliminada correctamente";
        } else {
            echo "Error al eliminar la fila: " . $deleteQuery->error;
        }

        $deleteQuery->close();
    } else {
        echo "Error: No se proporcionó un ID para eliminar";
    }
}

function obtenerNombreCampo($index) {
    // Cambiar este array según el orden de tus columnas en la base de datos
    $nombresCampos = ['OperacionesID', "FechaSalida","Ruta", "DatosPax", "Briefing", "Guia", "Lugar","Hora", "Proveedor"];
    return $nombresCampos[$index];
}
?>



