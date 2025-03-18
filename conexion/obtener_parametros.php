<?php
// Conexión a la base de datos
$conn = new mysqli("localhost", "root", "", "granja");

// Verificar la conexión
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Verificar si se recibió el procedimiento
if (isset($_POST["procedimiento"])) {
    $procedimiento = $_POST["procedimiento"];

    // Consulta para obtener los parámetros del procedimiento almacenado
    $sql = "
        SELECT PARAMETER_NAME, DATA_TYPE
        FROM INFORMATION_SCHEMA.PARAMETERS
        WHERE SPECIFIC_NAME = ? AND SPECIFIC_SCHEMA = DATABASE()
        ORDER BY ORDINAL_POSITION
    ";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $procedimiento);
        $stmt->execute();
        $result = $stmt->get_result();

        $parametros = [];
        while ($row = $result->fetch_assoc()) {
            $parametros[] = $row;
        }

        echo json_encode($parametros);
        $stmt->close();
    }
}

$conn->close();
?>