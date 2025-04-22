<?php
$servidor = "localhost";
$usuario = "root";
$password = "";
$base_datos = "granja"; 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    $conn = new mysqli($servidor, $usuario, $password, $base_datos);
    
    if ($conn->connect_error) {
        throw new Exception("Conexión fallida: " . $conn->connect_error);
    }

    // Forzar uso de la base de datos correcta
    $conn->select_db($base_datos);

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $nombre = $conn->real_escape_string(trim($_POST["nombre"]));
        $correo = $conn->real_escape_string(trim($_POST["correo"]));
        $telefono = $conn->real_escape_string(trim($_POST["telefono"]));
        $tipo_usuario = $conn->real_escape_string(trim($_POST["tipo_usuario"]));
        $clave = trim($_POST["clave"]);
        $confirmar_clave = trim($_POST["confirmar_clave"]);

        // Validaciones
        if (empty($nombre) || empty($correo) || empty($telefono) || empty($tipo_usuario) || empty($clave)) {
            throw new Exception("Todos los campos son obligatorios");
        }

        if ($clave !== $confirmar_clave) {
            throw new Exception("Las contraseñas no coinciden");
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Correo electrónico inválido");
        }

        // Verificar si el correo existe
        $check = $conn->prepare("SELECT id_usuario FROM usuarios WHERE correo = ?");
        $check->bind_param("s", $correo);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows > 0) {
            throw new Exception("El correo ya está registrado");
        }
        $check->close();

        // Hash de contraseña
        $clave_hash = password_hash($clave, PASSWORD_BCRYPT);

        // Insertar usando consulta preparada
        $stmt = $conn->prepare("INSERT INTO usuarios (nombre, correo, telefono, tipo_usuario, clave) VALUES (?, ?, ?, ?, ?)");
        
        if (!$stmt) {
            throw new Exception("Error preparando la consulta: " . $conn->error);
        }

        $stmt->bind_param("sssss", $nombre, $correo, $telefono, $tipo_usuario, $clave_hash);
        
        if (!$stmt->execute()) {
            // Solución alternativa si persiste el error
            $sql_alternativo = "INSERT IGNORE INTO usuarios (nombre, correo, telefono, tipo_usuario, clave) 
                              VALUES ('$nombre', '$correo', '$telefono', '$tipo_usuario', '$clave_hash')";
            
            if (!$conn->query($sql_alternativo)) {
                throw new Exception("Error al registrar: " . $conn->error);
            }
        }

        header("Location: ../index.php?registro=exito");
        exit();
    }
} catch (Exception $e) {
    // Página de error profesional
    echo '<!DOCTYPE html>
    <html>
    <head>
        <title>Error de Registro</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="bg-light">
        <div class="container mt-5">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h4><i class="fas fa-exclamation-triangle"></i> Error en el Registro</h4>
                </div>
                <div class="card-body">
                    <p class="card-text">'.htmlspecialchars($e->getMessage()).'</p>
                    <a href="../registro.html" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Volver al formulario
                    </a>
                </div>
            </div>
        </div>
    </body>
    </html>';
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>