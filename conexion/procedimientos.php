<?php
header('Content-Type: application/json');
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "granja";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    echo json_encode(["success" => false, "message" => "Error de conexión: " . $conn->connect_error]);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $accion = $_POST['accion'];
    $tabla = $_POST['tabla'];
    unset($_POST['accion'], $_POST['tabla']);

    $params = array_values($_POST);
    $placeholders = implode(", ", array_fill(0, count($params), "?"));
    $param_types = str_repeat("s", count($params));

    switch ($accion) {
        case 'insertar':
            $sql = "CALL insertar_{$tabla}($placeholders)";
            break;
        case 'actualizar':
            $sql = "CALL actualizar_{$tabla}($placeholders)";
            break;
        case 'eliminar':
            $sql = "CALL eliminar_{$tabla}($placeholders)";
            break;
        default:
            echo json_encode(["success" => false, "message" => "Acción no válida"]);
            exit;
    }

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($param_types, ...$params);
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Operación realizada con éxito"]);
        } else {
            echo json_encode(["success" => false, "message" => "Error en la ejecución: " . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(["success" => false, "message" => "Error en la consulta"]);
    }
}

$conn->close();