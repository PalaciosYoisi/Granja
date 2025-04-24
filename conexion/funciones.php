<?php
// Configuración de la conexión a la base de datos
$servername = "localhost";
$username = "root";
$password = ""; // Añade tu contraseña si es necesaria
$dbname = "granja"; // Reemplaza con el nombre de tu base de datos

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Procesar la consulta según el tipo recibido
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $tipo_consulta = $_POST["consulta"];
    
    switch ($tipo_consulta) {
        case "estado_salud":
            $id_animal = $_POST["id_animal"];
            $sql = "SELECT EstadoSaludAnimal($id_animal) AS resultado";
            $titulo = "Estado de Salud del Animal ID: $id_animal";
            break;
            
        case "ultima_vacuna":
            $id_animal = $_POST["id_animal"];
            $sql = "SELECT UltimaVacunaAnimal($id_animal) AS resultado";
            $titulo = "Última Vacuna del Animal ID: $id_animal";
            break;
            
        case "cantidad_producto":
            $nombre_producto = $_POST["nombre_producto"];
            $sql = "SELECT mostrar_cantidad_producto('$nombre_producto') AS resultado";
            $titulo = "Información del Producto: $nombre_producto";
            break;
            
        default:
            die("Tipo de consulta no válido");
    }
    
    // Ejecutar la consulta
    $result = $conn->query($sql);
    
    if ($result === FALSE) {
        $mensaje = "Error en la consulta: " . $conn->error;
    } else {
        $row = $result->fetch_assoc();
        $mensaje = $row['resultado'];
        
        // Formatear especialmente para la fecha de vacuna
        if ($tipo_consulta == "ultima_vacuna" && $mensaje) {
            $fecha = new DateTime($mensaje);
            $mensaje = $fecha->format('d/m/Y');
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado de Consulta</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        .result-section {
            margin: 20px 0;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .result-title {
            margin-top: 0;
            color: #444;
        }
        .result-content {
            font-size: 18px;
            padding: 10px;
            background-color: #e9e9e9;
            border-radius: 4px;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .back-link:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Resultado de la Consulta</h1>
        
        <div class="result-section">
            <h2 class="result-title"><?php echo $titulo; ?></h2>
            <div class="result-content">
                <?php 
                if (empty($mensaje)) {
                    echo "No se encontraron resultados para esta consulta.";
                } else {
                    echo htmlspecialchars($mensaje);
                }
                ?>
            </div>
        </div>
        
        <a href="../funciones.html" class="back-link">Volver</a>
    </div>
</body>
</html>