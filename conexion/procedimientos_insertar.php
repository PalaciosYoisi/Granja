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
    case "InsertarAlimentacion":
        // Parámetros para InsertarAlimentacion
        $param1 = $_POST['param1'] ?? null;
        $param2 = $_POST['param2'] ?? null;
        // Agrega todos los parámetros necesarios
        
        if (!$param1 || !$param2) {
            $mensaje_alerta = "Error: Datos incompletos para InsertarAlimentacion.";
        } else {
            $stmt = $conexion->prepare("CALL InsertarAlimentacion(?, ?)");
            $stmt->bind_param("ss", $param1, $param2);
            if ($stmt->execute()) {
                $mensaje_alerta = "Alimentación insertada correctamente.";
            } else {
                $mensaje_alerta = "Error al insertar alimentación: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    case "InsertarAnimal":
        // Parámetros para InsertarAnimal
        $param1 = $_POST['param1'] ?? null;
        $param2 = $_POST['param2'] ?? null;
        // Agrega todos los parámetros necesarios
        
        if (!$param1 || !$param2) {
            $mensaje_alerta = "Error: Datos incompletos para InsertarAnimal.";
        } else {
            $stmt = $conexion->prepare("CALL InsertarAnimal(?, ?)");
            $stmt->bind_param("ss", $param1, $param2);
            if ($stmt->execute()) {
                $mensaje_alerta = "Animal insertado correctamente.";
            } else {
                $mensaje_alerta = "Error al insertar animal: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    case "InsertarCosto":
        // Parámetros para InsertarCosto
        $param1 = $_POST['param1'] ?? null;
        $param2 = $_POST['param2'] ?? null;
        // Agrega todos los parámetros necesarios
        
        if (!$param1 || !$param2) {
            $mensaje_alerta = "Error: Datos incompletos para InsertarCosto.";
        } else {
            $stmt = $conexion->prepare("CALL InsertarCosto(?, ?)");
            $stmt->bind_param("ss", $param1, $param2);
            if ($stmt->execute()) {
                $mensaje_alerta = "Costo insertado correctamente.";
            } else {
                $mensaje_alerta = "Error al insertar costo: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    case "InsertarEspecie":
        // Parámetros para InsertarEspecie
        $param1 = $_POST['param1'] ?? null;
        $param2 = $_POST['param2'] ?? null;
        // Agrega todos los parámetros necesarios
        
        if (!$param1 || !$param2) {
            $mensaje_alerta = "Error: Datos incompletos para InsertarEspecie.";
        } else {
            $stmt = $conexion->prepare("CALL InsertarEspecie(?, ?)");
            $stmt->bind_param("ss", $param1, $param2);
            if ($stmt->execute()) {
                $mensaje_alerta = "Especie insertada correctamente.";
            } else {
                $mensaje_alerta = "Error al insertar especie: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    case "InsertarHistorialSalud":
        // Parámetros para InsertarHistorialSalud
        $param1 = $_POST['param1'] ?? null;
        $param2 = $_POST['param2'] ?? null;
        // Agrega todos los parámetros necesarios
        
        if (!$param1 || !$param2) {
            $mensaje_alerta = "Error: Datos incompletos para InsertarHistorialSalud.";
        } else {
            $stmt = $conexion->prepare("CALL InsertarHistorialSalud(?, ?)");
            $stmt->bind_param("ss", $param1, $param2);
            if ($stmt->execute()) {
                $mensaje_alerta = "Historial de salud insertado correctamente.";
            } else {
                $mensaje_alerta = "Error al insertar historial de salud: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    case "InsertarInventario":
        // Parámetros para InsertarInventario
        $param1 = $_POST['param1'] ?? null;
        $param2 = $_POST['param2'] ?? null;
        // Agrega todos los parámetros necesarios
        
        if (!$param1 || !$param2) {
            $mensaje_alerta = "Error: Datos incompletos para InsertarInventario.";
        } else {
            $stmt = $conexion->prepare("CALL InsertarInventario(?, ?)");
            $stmt->bind_param("ss", $param1, $param2);
            if ($stmt->execute()) {
                $mensaje_alerta = "Inventario insertado correctamente.";
            } else {
                $mensaje_alerta = "Error al insertar inventario: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    case "InsertarPlanta":
        // Parámetros para InsertarPlanta
        $param1 = $_POST['param1'] ?? null;
        $param2 = $_POST['param2'] ?? null;
        // Agrega todos los parámetros necesarios
        
        if (!$param1 || !$param2) {
            $mensaje_alerta = "Error: Datos incompletos para InsertarPlanta.";
        } else {
            $stmt = $conexion->prepare("CALL InsertarPlanta(?, ?)");
            $stmt->bind_param("ss", $param1, $param2);
            if ($stmt->execute()) {
                $mensaje_alerta = "Planta insertada correctamente.";
            } else {
                $mensaje_alerta = "Error al insertar planta: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    case "InsertarProduccion":
        // Parámetros para InsertarProduccion
        $param1 = $_POST['param1'] ?? null;
        $param2 = $_POST['param2'] ?? null;
        // Agrega todos los parámetros necesarios
        
        if (!$param1 || !$param2) {
            $mensaje_alerta = "Error: Datos incompletos para InsertarProduccion.";
        } else {
            $stmt = $conexion->prepare("CALL InsertarProduccion(?, ?)");
            $stmt->bind_param("ss", $param1, $param2);
            if ($stmt->execute()) {
                $mensaje_alerta = "Producción insertada correctamente.";
            } else {
                $mensaje_alerta = "Error al insertar producción: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    case "InsertarProveedor":
        // Parámetros para InsertarProveedor
        $param1 = $_POST['param1'] ?? null;
        $param2 = $_POST['param2'] ?? null;
        // Agrega todos los parámetros necesarios
        
        if (!$param1 || !$param2) {
            $mensaje_alerta = "Error: Datos incompletos para InsertarProveedor.";
        } else {
            $stmt = $conexion->prepare("CALL InsertarProveedor(?, ?)");
            $stmt->bind_param("ss", $param1, $param2);
            if ($stmt->execute()) {
                $mensaje_alerta = "Proveedor insertado correctamente.";
            } else {
                $mensaje_alerta = "Error al insertar proveedor: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    case "InsertarReporte":
        // Parámetros para InsertarReporte
        $param1 = $_POST['param1'] ?? null;
        $param2 = $_POST['param2'] ?? null;
        // Agrega todos los parámetros necesarios
        
        if (!$param1 || !$param2) {
            $mensaje_alerta = "Error: Datos incompletos para InsertarReporte.";
        } else {
            $stmt = $conexion->prepare("CALL InsertarReporte(?, ?)");
            $stmt->bind_param("ss", $param1, $param2);
            if ($stmt->execute()) {
                $mensaje_alerta = "Reporte insertado correctamente.";
            } else {
                $mensaje_alerta = "Error al insertar reporte: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    case "InsertarTratamiento":
        // Parámetros para InsertarTratamiento
        $param1 = $_POST['param1'] ?? null;
        $param2 = $_POST['param2'] ?? null;
        // Agrega todos los parámetros necesarios
        
        if (!$param1 || !$param2) {
            $mensaje_alerta = "Error: Datos incompletos para InsertarTratamiento.";
        } else {
            $stmt = $conexion->prepare("CALL InsertarTratamiento(?, ?)");
            $stmt->bind_param("ss", $param1, $param2);
            if ($stmt->execute()) {
                $mensaje_alerta = "Tratamiento insertado correctamente.";
            } else {
                $mensaje_alerta = "Error al insertar tratamiento: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    case "InsertarVacuna":
        // Parámetros para InsertarVacuna
        $param1 = $_POST['param1'] ?? null;
        $param2 = $_POST['param2'] ?? null;
        // Agrega todos los parámetros necesarios
        
        if (!$param1 || !$param2) {
            $mensaje_alerta = "Error: Datos incompletos para InsertarVacuna.";
        } else {
            $stmt = $conexion->prepare("CALL InsertarVacuna(?, ?)");
            $stmt->bind_param("ss", $param1, $param2);
            if ($stmt->execute()) {
                $mensaje_alerta = "Vacuna insertada correctamente.";
            } else {
                $mensaje_alerta = "Error al insertar vacuna: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    case "InsertarVacunacion":
        // Parámetros para InsertarVacunacion
        $param1 = $_POST['param1'] ?? null;
        $param2 = $_POST['param2'] ?? null;
        // Agrega todos los parámetros necesarios
        
        if (!$param1 || !$param2) {
            $mensaje_alerta = "Error: Datos incompletos para InsertarVacunacion.";
        } else {
            $stmt = $conexion->prepare("CALL InsertarVacunacion(?, ?)");
            $stmt->bind_param("ss", $param1, $param2);
            if ($stmt->execute()) {
                $mensaje_alerta = "Vacunación insertada correctamente.";
            } else {
                $mensaje_alerta = "Error al insertar vacunación: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    case "InsertarVenta":
        // Parámetros para InsertarVenta
        $param1 = $_POST['param1'] ?? null;
        $param2 = $_POST['param2'] ?? null;
        // Agrega todos los parámetros necesarios
        
        if (!$param1 || !$param2) {
            $mensaje_alerta = "Error: Datos incompletos para InsertarVenta.";
        } else {
            $stmt = $conexion->prepare("CALL InsertarVenta(?, ?)");
            $stmt->bind_param("ss", $param1, $param2);
            if ($stmt->execute()) {
                $mensaje_alerta = "Venta insertada correctamente.";
            } else {
                $mensaje_alerta = "Error al insertar venta: " . $stmt->error;
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
    <style>
        .result-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .success-message {
            color: #28a745;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
        }
        .error-message {
            color: #dc3545;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
        }
        .btn-back {
            display: inline-block;
            padding: 0.5rem 1.5rem;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s;
        }
        .btn-back:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="result-container">
            <h2>Resultado de la Operación</h2>
            
            <?php if (!empty($mensaje_alerta)): ?>
                <?php if (strpos($mensaje_alerta, 'Error:') === 0): ?>
                    <div class="error-message">
                        <strong>Error:</strong> <?php echo htmlspecialchars(substr($mensaje_alerta, 7)); ?>
                    </div>
                <?php else: ?>
                    <div class="success-message">
                        <strong>Éxito:</strong> <?php echo htmlspecialchars($mensaje_alerta); ?>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="error-message">
                    <strong>Error:</strong> No se recibió respuesta del servidor.
                </div>
            <?php endif; ?>
            
            <a href="javascript:history.back()" class="btn-back">Volver Atrás</a>
            <a href="../inicio.html" class="btn-back" style="margin-left: 10px;">Ir a Inicio</a>
        </div>
    </div>
</body>
</html>