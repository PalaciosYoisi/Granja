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

    case "InsertarAlimentacion":

        $id_especie = $_POST['param1'] ?? null;  
        $tipo_alimento = $_POST['param2'] ?? null;           
        $comidas = $_POST['param3'] ?? null;         
        $cantidad_g = $_POST['param4'] ?? null;     
        $fecha = $_POST['param5'] ?? null; 
        
        if (!$id_especie || !$tipo_alimento || !$comidas || !$cantidad_g || !$fecha) {
            $mensaje_alerta = "Error: Datos incompletos para actualizar_alimentacion.";
        } 
        else {
            $stmt = $conexion->prepare("CALL InsertarAlimentacion(?, ?, ?, ?, ?)");
            $stmt->bind_param("isiis", $id_especie, $tipo_alimento, $comidas, $cantidad_g, $fecha);
            if ($stmt->execute()) {
                $mensaje_alerta = "Alimentación insertada correctamente.";
            } else {
                $mensaje_alerta = "Error al insertar alimentación: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

        case "InsertarAnimal":
            
            $p_id_especie = $_POST['param1'] ?? null;
            $p_nombre_cientifico = $_POST['param2'] ?? null;
            $p_nombre_comun = $_POST['param3'] ?? null;
            $p_edad = $_POST['param4'] ?? null;
            $p_ubicacion = $_POST['param5'] ?? null;
            $p_estado = $_POST['param6'] ?? null;
            $p_descripcion = $_POST['param7'] ?? null;
            
            if (!$p_id_especie || !$p_nombre_cientifico || !$p_nombre_comun || !$p_edad || !$p_ubicacion || !$p_estado || !$p_descripcion) {
                $mensaje_alerta = "Error: Datos incompletos para InsertarAnimal.";
            } else {
                    $stmt = $conexion->prepare("CALL InsertarAnimal(?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ississs",$p_id_especie,$p_nombre_cientifico, $p_nombre_comun, $p_edad, $p_ubicacion, $p_estado, $p_descripcion);
                    
                    if ($stmt->execute()) {
                        $mensaje_alerta = "Animal insertado correctamente.";
                    } else {
                        $mensaje_alerta = "Error al insertar animal: " . $stmt->error;
                    }
                    $stmt->close();
            }
            break;

    case "InsertarEspecie":
        $param1 = $_POST['param1'] ?? null;
        $param2 = $_POST['param2'] ?? null;
        
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

    case "InsertarCosto":
        $tipo_costo = $_POST['param1'] ?? null;
        $descripcion = $_POST['param2'] ?? null;
        $monto = $_POST['param3'] ?? null;
        $fecha = $_POST['param4'] ?? null;
        $id_empleado = !empty($_POST['param5']) ? (int)$_POST['param5'] : null;
        $id_animal = !empty($_POST['param6']) ? (int)$_POST['param6'] : null;
        $id_especie = !empty($_POST['param7']) ? (int)$_POST['param7'] : null;
    
        if (empty($tipo_costo) || empty($descripcion) || empty($monto) || empty($fecha)) {
            $mensaje_alerta = "Error: Tipo, descripción, monto y fecha son obligatorios.";
        } else {
            try {
                $stmt = $conexion->prepare("CALL InsertarCosto(?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->bind_param("ssdsiii", 
                    $tipo_costo,   
                    $descripcion,  
                    $monto,        
                    $fecha,         
                    $id_empleado,   
                    $id_animal,     
                    $id_especie      
                );
                
                if ($stmt->execute()) {
                    $mensaje_alerta = "Costo insertado correctamente.";
                } else {
                    $mensaje_alerta = "Error al insertar costo: " . $stmt->error;
                }
            } catch (mysqli_sql_exception $e) {
                $mensaje_alerta = "Error de base de datos: " . $e->getMessage();
            } finally {
                if (isset($stmt)) {
                    $stmt->close();
                }
            }
        }
        break;

        case "InsertarEmpleado":
        
            $param1 = $_POST['param1'] ?? null;
            $param2 = $_POST['param2'] ?? null;
            $param3 = $_POST['param3'] ?? null;
            $param4 = $_POST['param4'] ?? null;
            $param5 = $_POST['param5'] ?? null;
            
            if (!$param1 || !$param2 || !$param3 || !$param4 || !$param5) {
                $mensaje_alerta = "Error: Datos incompletos para InsertarEmpleado.";
            } else {
                $stmt = $conexion->prepare("CALL InsertarEmpleado(?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssd", $param1, $param2, $param3, $param4, $param5);
                if ($stmt->execute()) {
                    $mensaje_alerta = "Empleado insertado correctamente.";
                } else {
                    $mensaje_alerta = "Error al insertar empleado: " . $stmt->error;
                }
                $stmt->close();
            }
            break;

    case "InsertarHistorialSalud":
  
        $param1 = !empty($_POST['param1']) ? (int)$_POST['param1'] : null;
        $param2 = !empty($_POST['param2']) ? (int)$_POST['param2'] : null;
        $param3 = $_POST['param3'] ?? null;
        $param4 = $_POST['param4'] ?? null;
        // Agrega todos los parámetros necesarios
        
        if (!$param3 || !$param4) {
            $mensaje_alerta = "Error: Datos incompletos para InsertarHistorialSalud.";
        } else {
            $stmt = $conexion->prepare("CALL InsertarHistorialSalud(?, ?, ?, ?)");
            $stmt->bind_param("iiss", $param1, $param2, $param3, $param4);
            if ($stmt->execute()) {
                $mensaje_alerta = "Historial de salud insertado correctamente.";
            } else {
                $mensaje_alerta = "Error al insertar historial de salud: " . $stmt->error;
            }
        
            $stmt->close();
        }
        break;

    case "InsertarInventario":
        
        $param1 = $_POST['param1'] ?? null;
        $param2 = $_POST['param2'] ?? null;
        $param3 = $_POST['param3'] ?? null;
        $param4 = $_POST['param4'] ?? null;
        $param5 = $_POST['param5'] ?? null;
        
        if (!$param1 || !$param2 || !$param3 || !$param4 || !$param5) {
            $mensaje_alerta = "Error: Datos incompletos para InsertarInventario.";
        } else {
            $stmt = $conexion->prepare("CALL InsertarInventario(?, ?, ?, ?, ?)");
            $stmt->bind_param("sissi", $param1, $param2, $param3, $param4, $param5);
            if ($stmt->execute()) {
                $mensaje_alerta = "Inventario insertado correctamente.";
            } else {
                $mensaje_alerta = "Error al insertar inventario: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    case "InsertarPlanta":
        $param1 = $_POST['param1'] ?? null;
        $param2 = $_POST['param2'] ?? null;
        $param3 = $_POST['param3'] ?? null;
        $param4 = $_POST['param4'] ?? null;
        $param5 = $_POST['param5'] ?? null;

        if (!$param1 || !$param2 || !$param3 || !$param4 || !$param5) {
            $mensaje_alerta = "Error: Datos incompletos para InsertarPlanta.";
        } else {
            $stmt = $conexion->prepare("CALL InsertarPlanta(?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $param1, $param2, $param3, $param4, $param5);
            if ($stmt->execute()) {
                $mensaje_alerta = "Planta insertada correctamente.";
            } else {
                $mensaje_alerta = "Error al insertar planta: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    case "InsertarProduccion":
        
        $param1 = $_POST['param1'] ?? null;
        $param2 = $_POST['param2'] ?? null;
        $param3 = $_POST['param3'] ?? null;
        $param4 = $_POST['param4'] ?? null;

        
        if (!$param1 || !$param2 || !$param3 || !$param4) {
            $mensaje_alerta = "Error: Datos incompletos para InsertarProduccion.";
        } else {
            $stmt = $conexion->prepare("CALL InsertarProduccion(?, ?, ?, ?)");
            $stmt->bind_param("isds", $param1, $param2, $param3, $param4);
            if ($stmt->execute()) {
                $mensaje_alerta = "Producción insertada correctamente.";
            } else {
                $mensaje_alerta = "Error al insertar producción: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    case "InsertarProveedor":
    
        $param1 = $_POST['param1'] ?? null;
        $param2 = $_POST['param2'] ?? null;
        $param3 = $_POST['param3'] ?? null;
        $param4 = $_POST['param4'] ?? null;
        
        if (!$param1 || !$param2 || $param3 || $param4) {
            $mensaje_alerta = "Error: Datos incompletos para InsertarProveedor.";
        } else {
            $stmt = $conexion->prepare("CALL InsertarProveedor(?, ?, ?, ?)");
            $stmt->bind_param("ssss", $param1, $param2, $param3, $param4);
            if ($stmt->execute()) {
                $mensaje_alerta = "Proveedor insertado correctamente.";
            } else {
                $mensaje_alerta = "Error al insertar proveedor: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    case "InsertarReporte":

        $param1 = $_POST['param1'] ?? null;
        $param2 = !empty($_POST['param2']) ? (int)$_POST['param2'] : null;
        $param3 = !empty($_POST['param3']) ? (int)$_POST['param3'] : null;
        $param4 = $_POST['param4'] ?? null;
        $param5 = $_POST['param5'] ?? null;

        if (!$param1 || !$param4 || !$param5) {
            $mensaje_alerta = "Error: Datos incompletos para InsertarReporte.";
        } else {
            $stmt = $conexion->prepare("CALL InsertarReporte(?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiss", $param1, $param2, $param3, $param4, $param5);
            if ($stmt->execute()) {
                $mensaje_alerta = "Reporte insertado correctamente.";
            } else {
                $mensaje_alerta = "Error al insertar reporte: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    case "InsertarTratamiento":

        $param1 = $_POST['param1'] ?? null;
        $param2 = $_POST['param2'] ?? null;
        $param3 = $_POST['param3'] ?? null;
        $param4 = $_POST['param4'] ?? null;
        $param5 = $_POST['param5'] ?? null;
        
        if (!$param1 || !$param2 || !$param3 || !$param4 || !$param5) {
            $mensaje_alerta = "Error: Datos incompletos para InsertarTratamiento.";
        } else {
            $stmt = $conexion->prepare("CALL InsertarTratamiento(?, ?, ?, ?, ?)");
            $stmt->bind_param("issss", $param1, $param2, $param3, $param4, $param5);
            if ($stmt->execute()) {
                $mensaje_alerta = "Tratamiento insertado correctamente.";
            } else {
                $mensaje_alerta = "Error al insertar tratamiento: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    case "InsertarVacunacion":

        $param1 = $_POST['param1'] ?? null;
        $param2 = $_POST['param2'] ?? null;
        $param3 = $_POST['param3'] ?? null;
        $param4 = $_POST['param4'] ?? null;
        $param5 = $_POST['param5'] ?? null;
        $param6 = $_POST['param6'] ?? null;
        $param7 = $_POST['param7'] ?? null;
        
        if (!$param1 || !$param2 || !$param3 || !$param4 || !$param5 || !$param6 || !$param7) {
            $mensaje_alerta = "Error: Datos incompletos para InsertarVacunacion.";
        } else {
            $stmt = $conexion->prepare("CALL InsertarVacunacion(?, ?, ?, ?, ?. ?, ?)");
            $stmt->bind_param("iisssis", $param1, $param2, $param3, $param4, $param5, $param6, $param7);
            if ($stmt->execute()) {
                $mensaje_alerta = "Vacunacion insertada correctamente.";
            } else {
                $mensaje_alerta = "Error al insertar vacunacion: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    case "InsertarVacuna":

        $param1 = $_POST['param1'] ?? null;
        $param2 = $_POST['param2'] ?? null;
        $param3 = $_POST['param3'] ?? null;
        $param4 = $_POST['param4'] ?? null;
        $param5 = $_POST['param5'] ?? null;
        $param6 = $_POST['param6'] ?? null;
        
        if (!$param1 || !$param2 || !$param3 || !$param4 || !$param5 || !$param6) {
            $mensaje_alerta = "Error: Datos incompletos para InsertarVacuna.";
        } else {
            $stmt = $conexion->prepare("CALL InsertarVacuna(?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssi", $param1, $param2, $param3, $param4, $param5, $param6);
            if ($stmt->execute()) {
                $mensaje_alerta = "Vacuna insertada correctamente.";
            } else {
                $mensaje_alerta = "Error al insertar vacuna: " . $stmt->error;
            }
            $stmt->close();
        }
        break;

    case "InsertarVenta":

        $param1 = $_POST['param1'] ?? null;
        $param2 = $_POST['param2'] ?? null;
        $param3 = $_POST['param3'] ?? null;
        $param4 = $_POST['param4'] ?? null;
        $param5 = $_POST['param5'] ?? null;
        $param6 = $_POST['param6'] ?? null;
        
        if (!$param1 || !$param2 || !$param3 || !$param4 || !$param5 || !$param6) {
            $mensaje_alerta = "Error: Datos incompletos para InsertarVenta.";
        } else {
            $stmt = $conexion->prepare("CALL InsertarVenta(?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiddss", $param1, $param2, $param3, $param4, $param5, $param6);
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