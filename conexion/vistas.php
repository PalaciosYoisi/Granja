<?php
$host = "localhost";  // Servidor
$usuario = "root";    // Usuario de MySQL
$password = "";       // Contraseña de MySQL
$base_datos = "granja"; // Nombre de la base de datos

$conn = new mysqli($host, $usuario, $password, $base_datos);

// Verificar la conexión
if ($conn->connect_error) {
    die("Error en la conexión: " . $conn->connect_error);
}

$tabla = ""; // Variable para almacenar la tabla

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['vista']) && !empty($_POST['vista'])) {
        $vista_seleccionada = $_POST['vista'];

        // Consultar la vista seleccionada
        $sql = "SELECT * FROM $vista_seleccionada";
        $resultado = $conn->query($sql);

        if ($resultado) {
            if ($resultado->num_rows > 0) {
                // Crear la tabla con los datos obtenidos
                $tabla .= "<h3>Mostrando: $vista_seleccionada</h3>";
                $tabla .= "<table border='1'><tr>";

                // Obtener los nombres de las columnas
                while ($campo = $resultado->fetch_field()) {
                    $tabla .= "<th>" . htmlspecialchars($campo->name) . "</th>";
                }
                $tabla .= "</tr>";

                // Agregar las filas con los datos
                while ($fila = $resultado->fetch_assoc()) {
                    $tabla .= "<tr>";
                    foreach ($fila as $valor) {
                        $tabla .= "<td>" . htmlspecialchars($valor) . "</td>";
                    }
                    $tabla .= "</tr>";
                }
                $tabla .= "</table>";
            } else {
                $tabla = "<p>No hay datos disponibles para esta vista.</p>";
            }
        } else {
            $tabla = "<p>Error en la consulta: " . $conn->error . "</p>";
        }
    } else {
        $tabla = "<p>Error: No se ha seleccionado ninguna vista.</p>";
    }
}

$conn->close();

// Mostrar la tabla en el mismo HTML
echo $tabla;
?>