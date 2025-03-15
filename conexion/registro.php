<?php
// Configuración de la base de datos
$servidor = "localhost";
$usuario = "root";
$password = "";
$base_datos = "Restaurante";

// Crear conexión
$conn = new mysqli($servidor, $usuario, $password, $base_datos);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// Verificar si el formulario ha sido enviado
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nombre = trim($_POST["nombre"]);
    $correo = trim($_POST["correo"]);
    $telefono = trim($_POST["telefono"]);
    $tipo_usuario = trim($_POST["tipo_usuario"]);
    $clave = trim($_POST["clave"]);

    if (empty($nombre) || empty($correo) || empty($telefono)|| empty($tipo_usuario)|| empty($clave)) {
        die("Error: Todos los campos son obligatorios.");
    }

    // Encriptar la contraseña
   // $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO usuarios (nombre, correo, telefono, tipo_usuario, clave) VALUES (?, ?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sssss", $nombre, $correo, $telefono, $tipo_usuario, $clave);
        if ($stmt->execute()) {

            header("Location: ../index.html");
            exit();

        } else {
            echo "Error al registrar usuario.";
        }
        $stmt->close();
    } else {
        echo "Error en la preparación de la consulta.";
    }

    // Cerrar conexión
    $conn->close();
}
?>