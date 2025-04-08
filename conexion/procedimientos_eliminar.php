<?php
session_start();

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "granja");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Verificar que la acción fue seleccionada
if (empty($_POST['accion'])) {
    echo "<script>alert('Error: No se seleccionó ninguna acción.');</script>";
    exit();
}

$accion = $_POST['accion'];
$mensaje_alerta = "";

switch ($accion) {
    case "EliminarAnimal":
        $param1 = $_POST['param1'] ?? null;
        
        if (!$param1) {
            $mensaje_alerta = "Error: Falta el ID del animal a eliminar.";
        } else {
            $stmt = $conexion->prepare("CALL EliminarAnimal(?)");
            $stmt->bind_param("i", $param1);
            if ($stmt->execute()) {
                $mensaje_alerta = "Animal eliminado correctamente.";
            } else {
                $mensaje_alerta = "Error al eliminar animal: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    case "EliminarEmpleado":
        $param1 = $_POST['param1'] ?? null;
        
        if (!$param1) {
            $mensaje_alerta = "Error: Falta el ID del empleado a eliminar.";
        } else {
            $stmt = $conexion->prepare("CALL EliminarEmpleado(?)");
            $stmt->bind_param("i", $param1);
            if ($stmt->execute()) {
                $mensaje_alerta = "Empleado eliminado correctamente.";
            } else {
                $mensaje_alerta = "Error al eliminar empleado: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    case "EliminarEspecie":
        $param1 = $_POST['param1'] ?? null;
        
        if (!$param1) {
            $mensaje_alerta = "Error: Falta el ID de la especie a eliminar.";
        } else {
            $stmt = $conexion->prepare("CALL EliminarEspecie(?)");
            $stmt->bind_param("i", $param1);
            if ($stmt->execute()) {
                $mensaje_alerta = "Especie eliminada correctamente.";
            } else {
                $mensaje_alerta = "Error al eliminar especie: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    case "EliminarPlanta":
        $param1 = $_POST['param1'] ?? null;
        
        if (!$param1) {
            $mensaje_alerta = "Error: Falta el ID de la planta a eliminar.";
        } else {
            $stmt = $conexion->prepare("CALL EliminarPlanta(?)");
            $stmt->bind_param("i", $param1);
            if ($stmt->execute()) {
                $mensaje_alerta = "Planta eliminada correctamente.";
            } else {
                $mensaje_alerta = "Error al eliminar planta: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    case "EliminarProveedor":
        $param1 = $_POST['param1'] ?? null;
        
        if (!$param1) {
            $mensaje_alerta = "Error: Falta el ID del proveedor a eliminar.";
        } else {
            $stmt = $conexion->prepare("CALL EliminarProveedor(?)");
            $stmt->bind_param("i", $param1);
            if ($stmt->execute()) {
                $mensaje_alerta = "Proveedor eliminado correctamente.";
            } else {
                $mensaje_alerta = "Error al eliminar proveedor: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    case "EliminarReporte":
        $param1 = $_POST['param1'] ?? null;
        
        if (!$param1) {
            $mensaje_alerta = "Error: Falta el ID del reporte a eliminar.";
        } else {
            $stmt = $conexion->prepare("CALL EliminarReporte(?)");
            $stmt->bind_param("i", $param1);
            if ($stmt->execute()) {
                $mensaje_alerta = "Reporte eliminado correctamente.";
            } else {
                $mensaje_alerta = "Error al eliminar reporte: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    case "EliminarVacuna":
        $param1 = $_POST['param1'] ?? null;
        
        if (!$param1) {
            $mensaje_alerta = "Error: Falta el ID de la vacuna a eliminar.";
        } else {
            $stmt = $conexion->prepare("CALL EliminarVacuna(?)");
            $stmt->bind_param("i", $param1);
            if ($stmt->execute()) {
                $mensaje_alerta = "Vacuna eliminada correctamente.";
            } else {
                $mensaje_alerta = "Error al eliminar vacuna: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    default:
        $mensaje_alerta = "Error: Procedimiento de eliminación no reconocido.";
        break;
}

$conexion->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado de la Operación</title>
    <link rel="stylesheet" href="../style/style.css">
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const mensaje = "<?php echo htmlspecialchars($mensaje_alerta); ?>";
            if (mensaje) {
                alert(mensaje);
                // Redirigir después de mostrar el mensaje
                window.location.href = "../inicio.html";
            }
        });
    </script>
</head>
<body>
    <div class="container">
        <h2>Procesando operación...</h2>
        <p>Por favor espere mientras se completa la operación.</p>
    </div>
</body>
</html>