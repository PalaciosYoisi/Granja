<?php
session_start();

// Conexión a la base de datos
$conexion = new mysqli("localhost", "root", "", "granja");

if ($conexion->connect_error) {
    die("Error de conexión: " . $conexion->connect_error);
}

// Verificar que la acción fue seleccionada
if (empty($_POST['subopcion'])) {
    echo "<script>alert('Error: No se seleccionó ninguna acción.');</script>";
    exit();
}

$accion = $_POST['subopcion'];  
$mensaje_alerta = "";

switch ($accion) {
    case "ActualizarVacuna":
        $id_vacuna = $_POST['param1'] ?? null;    
        $nombre_vacuna = $_POST['param2'] ?? null; 
        $descripcion = $_POST['param3'] ?? null;   
        $fabricante = $_POST['param4'] ?? null;   
        $temperatura = $_POST['param5'] ?? null;  
        $vida_util = $_POST['param6'] ?? null;     
        $cantidad = $_POST['param7'] ?? null; 
        
        if (!$id_vacuna || !$nombre_vacuna || !$descripcion || !$fabricante || !$temperatura || !$vida_util || !$cantidad) {
            $mensaje_alerta = "Error: Datos incompletos para ActualizarVacuna.";
        } else {
            $stmt = $conexion->prepare("CALL ActualizarVacuna(?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssi", $id_vacuna, $nombre_vacuna, $descripcion, $fabricante, $temperatura, $vida_util, $cantidad);
            if ($stmt->execute()) {
                $mensaje_alerta = "Vacuna actualizada correctamente.";
            } else {
                $mensaje_alerta = "Error al actualizar vacuna: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    case "actualizar_alimentacion":

        $id_alimentacion = $_POST['param1'] ?? null;  
        $id_especie = $_POST['param2'] ?? null;
        $tipo_alimento = $_POST['param3'] ?? null;   
        $comidas = $_POST['param4'] ?? null;         
        $cantidad_g = $_POST['param5'] ?? null;     
        $ultima_comida = $_POST['param6'] ?? null;
        
        if (!$id_alimentacion || !$id_especie || !$tipo_alimento || !$comidas || !$cantidad_g || !$ultima_comida) {
            $mensaje_alerta = "Error: Datos incompletos para actualizar_alimentacion.";
        } 
        else {
            $stmt = $conexion->prepare("CALL actualizar_alimentacion(?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisiis", $id_alimentacion, $id_especie, $tipo_alimento, $comidas, $cantidad_g, $ultima_comida);
            if ($stmt->execute()) {
                $mensaje_alerta = "Alimentación actualizada correctamente.";
            } else {
                $mensaje_alerta = "Error al actualizar alimentación: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    case "actualizar_animal":

        $id_animal = $_POST['param1'] ?? null;  
        $nombre_cientifico = $_POST['param2'] ?? null;
        $nombre_comun = $_POST['param3'] ?? null;   
        $id_especie = $_POST['param4'] ?? null;         
        $edad = $_POST['param5'] ?? null;     
        $ubicacion = $_POST['param6'] ?? null;
        $estado = $_POST['param7'] ?? null;
        $descripcion = $_POST['param8'] ?? null;

        if (!$id_animal || !$nombre_cientifico || !$nombre_comun || !$id_especie || !$edad || !$ubicacion || !$estado || !$descripcion) {
            $mensaje_alerta = "Error: Datos incompletos para actualizar_animal.";
        } else {
            $stmt = $conexion->prepare("CALL actualizar_animal(?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issiisss", $id_animal, $nombre_cientifico, $nombre_comun, $id_especie, $edad, $ubicacion, $estado, $descripcion);
            if ($stmt->execute()) {
                $mensaje_alerta = "Animal actualizado correctamente.";
            } else {
                $mensaje_alerta = "Error al actualizar animal: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    case "actualizar_especie":
        $id_especie = $_POST['param1'] ?? null;
        $nombre_especie = $_POST['param2'] ?? null;
        $descripcion = $_POST['param3'] ?? null;
        
        if (!$id_especie || !$nombre_especie || !$descripcion) {
            $mensaje_alerta = "Error: Datos incompletos para actualizar_especie.";
        } else {
            $stmt = $conexion->prepare("CALL actualizar_especie(?, ?, ?)");
            $stmt->bind_param("iss", $id_especie, $nombre_especie, $descripcion);
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
        $param3 = $_POST['param3'] ?? null;
        $param4 = $_POST['param4'] ?? null;
        
        if (!$param1 || !$param2 || !$param3 || !$param4) {
            $mensaje_alerta = "Error: Datos incompletos para actualizar_inventario.";
        } else {
            $stmt = $conexion->prepare("CALL actualizar_inventario(?, ?, ?, ?)");
            $stmt->bind_param("isis", $param1, $param2, $param3, $param4);
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
        $param3 = $_POST['param3'] ?? null;
        $param4 = $_POST['param4'] ?? null;
        $param5 = $_POST['param5'] ?? null;
        $param6 = $_POST['param6'] ?? null;
        
        if (!$param1 || !$param2 || !$param3 ||!$param4 || !$param5 || !$param6) {
            $mensaje_alerta = "Error: Datos incompletos para actualizar_planta.";
        } else {
            $stmt = $conexion->prepare("CALL actualizar_planta(?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssss", $param1, $param2, $param3, $param4, $param5, $param6);
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
        $param3 = $_POST['param3'] ?? null;
        
        if (!$param1 || !$param2) {
            $mensaje_alerta = "Error: Datos incompletos para actualizar_estado_salud.";
        } else {
            $stmt = $conexion->prepare("CALL actualizar_estado_salud(?, ?, ?)");
            $stmt->bind_param("iss", $param1, $param2, $param3);
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
        $param3 = $_POST['param3'] ?? null;
        $param4 = $_POST['param4'] ?? null;
        $param5 = $_POST['param5'] ?? null;

        if (!$param1 || !$param2 || !$param3 || !$param4 || !$param5) {
            $mensaje_alerta = "Error: Datos incompletos para actualizar_produccion.";
        } else {
            $stmt = $conexion->prepare("CALL actualizar_produccion(?, ?, ?, ?, ?)");
            $stmt->bind_param("iisis", $param1, $param2, $param3, $param4, $param5);
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
        $param3 = $_POST['param3'] ?? null;
        $param4 = $_POST['param4'] ?? null;
        if (!$param1 || !$param2 || !$param3 || !$param4 ) {
            $mensaje_alerta = "Error: Datos incompletos para actualizar_proveedor.";
        } else {
            $stmt = $conexion->prepare("CALL actualizar_proveedor(?, ?, ?, ?)");
            $stmt->bind_param("isss", $param1, $param2, $param3, $param4);
            if ($stmt->execute()) {
                $mensaje_alerta = "Proveedor actualizado correctamente.";
            } else {
                $mensaje_alerta = "Error al actualizar proveedor: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    
        case "actualizar_empleado":
            $param1 = $_POST['param1'] ?? null;
            $param2 = $_POST['param2'] ?? null;
            $param3 = $_POST['param3'] ?? null;
            $param4 = $_POST['param4'] ?? null;
            $param5 = $_POST['param5'] ?? null;
            
            if (!$param1 || !$param2 || !$param3 || !$param4 || !$param5) {
                $mensaje_alerta = "Error: Datos incompletos para actualizar_proveedor.";
            } else {
                $stmt = $conexion->prepare("CALL actualizar_proveedor(?, ?, ?, ?, ?)");
                $stmt->bind_param("issss", $param1, $param2);
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
    <link rel="stylesheet" href="../style/estilo.css">
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const mensaje = "<?php echo $mensaje_alerta; ?>";
            if (mensaje) {
                alert(mensaje);
                // Redirigir después de mostrar el mensaje
                window.location.href = "../procedimientos.html";
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