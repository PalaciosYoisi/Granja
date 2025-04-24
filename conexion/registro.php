<?php
$servidor = "localhost";
$usuario = "root";
$password = "";
$base_datos = "granja";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

try {
    $conn = new mysqli($servidor, $usuario, $password, $base_datos);
    
    if ($conn->connect_error) {
        throw new Exception("Error de conexión: " . $conn->connect_error);
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Obtener y limpiar datos del formulario
        $nombre = $conn->real_escape_string(trim($_POST["nombre"]));
        $correo = $conn->real_escape_string(trim($_POST["correo"]));
        $telefono = $conn->real_escape_string(trim($_POST["telefono"]));
        $tipo_usuario = $conn->real_escape_string(trim($_POST["tipo_usuario"]));
        $clave = trim($_POST["clave"]);
        $confirmar_clave = trim($_POST["confirmar_clave"]);

        if (empty($nombre) || empty($correo) || empty($telefono) || empty($tipo_usuario) || empty($clave)) {
            throw new Exception("Todos los campos son obligatorios");
        }

        if ($clave !== $confirmar_clave) {
            throw new Exception("Las contraseñas no coinciden");
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Correo electrónico inválido");
        }

        if (strlen($clave) < 8) {
            throw new Exception("La contraseña debe tener al menos 8 caracteres");
        }

        // Verificar si el correo ya existe
        $stmt_check = $conn->prepare("SELECT id_usuario FROM usuarios WHERE correo = ?");
        if (!$stmt_check) {
            throw new Exception("Error al preparar la consulta: " . $conn->error);
        }
        
        $stmt_check->bind_param("s", $correo);
        $stmt_check->execute();
        $stmt_check->store_result();
        
        if ($stmt_check->num_rows > 0) {
            throw new Exception("El correo electrónico ya está registrado");
        }
        $stmt_check->close();

        // Hash de la contraseña
        $clave_hash = password_hash($clave, PASSWORD_BCRYPT);

        // Ajustar tipo_usuario para coincidir con ENUM
        $tipo_usuario_ajustado = match(strtolower($tipo_usuario)) {
            'administrador' => 'Administrador',
            'veterinario' => 'Veterinario',
            'botanico' => 'Botánico',
            'investigador' => 'Investigador',
            default => throw new Exception("Tipo de usuario no válido")
        };

        $stmt = $conn->prepare("INSERT INTO usuarios (nombre, correo, telefono, tipo_usuario, clave) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . $conn->error);
        }

        $stmt->bind_param("sssss", $nombre, $correo, $telefono, $tipo_usuario_ajustado, $clave_hash);
        
        if ($stmt->execute()) {
            // Registro exitoso
            $_SESSION['registro_exitoso'] = true;
            header("Location: ../index.php?registro=exito");
            exit();
        } else {
            if (strpos($stmt->error, "Table 'granja.alertas' doesn't exist") !== false) {
                throw new Exception("Error en el sistema de notificaciones. Por favor contacte al administrador.");
            } else {
                throw new Exception("Error al registrar el usuario: " . $stmt->error);
            }
        }
    }
} catch (Exception $e) {
    // Registrar error en log
    error_log("Error en registro: " . $e->getMessage());
    
    // Redirigir con error
    $_SESSION['error_registro'] = $e->getMessage();
    header("Location: ../registro.html?error=" . urlencode($e->getMessage()));
    exit();
} finally {
    // Cerrar conexiones
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($stmt_check)) {
        $stmt_check->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?>