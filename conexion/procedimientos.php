<?php
// Verificar si el formulario fue enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $procedimiento = $_POST['subopcion']; // Obtener el procedimiento seleccionado

    // Conexión a la base de datos
    $conn = new mysqli("localhost", "root", "", "granja");

    if ($conn->connect_error) {
        die("Conexión fallida: " . $conn->connect_error);
    }

    // Obtener parámetros dinámicamente
    $sql = "
        SELECT PARAMETER_NAME
        FROM INFORMATION_SCHEMA.PARAMETERS
        WHERE SPECIFIC_NAME = ? AND SPECIFIC_SCHEMA = DATABASE()
        ORDER BY ORDINAL_POSITION
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $procedimiento);
    $stmt->execute();
    $result = $stmt->get_result();

    $parametros = [];
    while ($row = $result->fetch_assoc()) {
        $parametros[] = $_POST[$row["PARAMETER_NAME"]];
    }
    $stmt->close();

    // Construir la consulta CALL con los parámetros dinámicos
    $placeholders = implode(", ", array_fill(0, count($parametros), "?"));
    $query = "CALL $procedimiento($placeholders)";

    $stmt = $conn->prepare($query);
    $stmt->bind_param(str_repeat("s", count($parametros)), ...$parametros);

    if ($stmt->execute()) {
        echo "El procedimiento '$procedimiento' se ejecutó correctamente.";
    } else {
        echo "Error al ejecutar '$procedimiento': " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>