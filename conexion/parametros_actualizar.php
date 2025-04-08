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
    // Casos existentes de inserción...
    // (Mantén los casos de inserción que ya tienes)
    
    // Nuevos casos para procedimientos de actualización
    case "ActualizarVacuna":
        $param1 = $_POST['param1'] ?? null;
        $param2 = $_POST['param2'] ?? null;
        
        if (!$param1 || !$param2) {
            $mensaje_alerta = "Error: Datos incompletos para ActualizarVacuna.";
        } else {
            $stmt = $conexion->prepare("CALL ActualizarVacuna(?, ?)");
            $stmt->bind_param("ss", $param1, $param2);
            if ($stmt->execute()) {
                $mensaje_alerta = "Vacuna actualizada correctamente.";
            } else {
                $mensaje_alerta = "Error al actualizar vacuna: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    case "actualizar_alimentacion":
        $param1 = $_POST['param1'] ?? null;
        $param2 = $_POST['param2'] ?? null;
        
        if (!$param1 || !$param2) {
            $mensaje_alerta = "Error: Datos incompletos para actualizar_alimentacion.";
        } else {
            $stmt = $conexion->prepare("CALL actualizar_alimentacion(?, ?)");
            $stmt->bind_param("ss", $param1, $param2);
            if ($stmt->execute()) {
                $mensaje_alerta = "Alimentación actualizada correctamente.";
            } else {
                $mensaje_alerta = "Error al actualizar alimentación: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    case "actualizar_animal":
        $param1 = $_POST['param1'] ?? null;
        $param2 = $_POST['param2'] ?? null;
        
        if (!$param1 || !$param2) {
            $mensaje_alerta = "Error: Datos incompletos para actualizar_animal.";
        } else {
            $stmt = $conexion->prepare("CALL actualizar_animal(?, ?)");
            $stmt->bind_param("ss", $param1, $param2);
            if ($stmt->execute()) {
                $mensaje_alerta = "Animal actualizado correctamente.";
            } else {
                $mensaje_alerta = "Error al actualizar animal: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    case "actualizar_especie":
        $param1 = $_POST['param1'] ?? null;
        $param2 = $_POST['param2'] ?? null;
        
        if (!$param1 || !$param2) {
            $mensaje_alerta = "Error: Datos incompletos para actualizar_especie.";
        } else {
            $stmt = $conexion->prepare("CALL actualizar_especie(?, ?)");
            $stmt->bind_param("ss", $param1, $param2);
            if ($stmt->execute()) {
                $mensaje_alerta = "Especie actualizada correctamente.";
            } else {
                $mensaje_alerta = "Error al actualizar especie: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    case "actualizar_inventario":
        $param1 = $_POST['param1'] ?? null;
        $param2 = $_POST['param2'] ?? null;
        
        if (!$param1 || !$param2) {
            $mensaje_alerta = "Error: Datos incompletos para actualizar_inventario.";
        } else {
            $stmt = $conexion->prepare("CALL actualizar_inventario(?, ?)");
            $stmt->bind_param("ss", $param1, $param2);
            if ($stmt->execute()) {
                $mensaje_alerta = "Inventario actualizado correctamente.";
            } else {
                $mensaje_alerta = "Error al actualizar inventario: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    case "actualizar_planta":
        $param1 = $_POST['param1'] ?? null;
        $param2 = $_POST['param2'] ?? null;
        
        if (!$param1 || !$param2) {
            $mensaje_alerta = "Error: Datos incompletos para actualizar_planta.";
        } else {
            $stmt = $conexion->prepare("CALL actualizar_planta(?, ?)");
            $stmt->bind_param("ss", $param1, $param2);
            if ($stmt->execute()) {
                $mensaje_alerta = "Planta actualizada correctamente.";
            } else {
                $mensaje_alerta = "Error al actualizar planta: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    case "actualizar_estado_salud":
        $param1 = $_POST['param1'] ?? null;
        $param2 = $_POST['param2'] ?? null;
        
        if (!$param1 || !$param2) {
            $mensaje_alerta = "Error: Datos incompletos para actualizar_estado_salud.";
        } else {
            $stmt = $conexion->prepare("CALL actualizar_estado_salud(?, ?)");
            $stmt->bind_param("ss", $param1, $param2);
            if ($stmt->execute()) {
                $mensaje_alerta = "Estado de salud actualizado correctamente.";
            } else {
                $mensaje_alerta = "Error al actualizar estado de salud: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    case "actualizar_produccion":
        $param1 = $_POST['param1'] ?? null;
        $param2 = $_POST['param2'] ?? null;
        
        if (!$param1 || !$param2) {
            $mensaje_alerta = "Error: Datos incompletos para actualizar_produccion.";
        } else {
            $stmt = $conexion->prepare("CALL actualizar_produccion(?, ?)");
            $stmt->bind_param("ss", $param1, $param2);
            if ($stmt->execute()) {
                $mensaje_alerta = "Producción actualizada correctamente.";
            } else {
                $mensaje_alerta = "Error al actualizar producción: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    case "actualizar_proveedor":
        $param1 = $_POST['param1'] ?? null;
        $param2 = $_POST['param2'] ?? null;
        
        if (!$param1 || !$param2) {
            $mensaje_alerta = "Error: Datos incompletos para actualizar_proveedor.";
        } else {
            $stmt = $conexion->prepare("CALL actualizar_proveedor(?, ?)");
            $stmt->bind_param("ss", $param1, $param2);
            if ($stmt->execute()) {
                $mensaje_alerta = "Proveedor actualizado correctamente.";
            } else {
                $mensaje_alerta = "Error al actualizar proveedor: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    default:
        $mensaje_alerta = "Error: Procedimiento no reconocido.";
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